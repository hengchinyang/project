<?php

declare(strict_types=1);

require_once __DIR__ . '/../Service/PharmacyServiceClient.php';
require_once __DIR__ . '/../Service/PatientRecordCreationService.php';

function checkIntegration(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
    echo '[PASS] ' . $message . PHP_EOL;
}

function expectFailure(Closure $operation, string $message): void
{
    try {
        $operation();
    } catch (Throwable) {
        checkIntegration(true, $message);
        return;
    }
    throw new RuntimeException($message . ' (no failure was raised)');
}

/** @param array<string,string> $fields */
function pharmacyResponse(string $root, array $fields, string $items = ''): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?><m:' . $root . ' xmlns:m="urn:medicare:patient-record">';
    foreach ($fields as $name => $value) {
        $xml .= '<m:' . $name . '>' . htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</m:' . $name . '>';
    }
    return $xml . $items . '</m:' . $root . '>';
}

$createdByReference = [];
$lastCreatedItemCount = 0;
$transport = static function (string $url, string $requestXml, string $apiKey, int $timeout) use (&$createdByReference, &$lastCreatedItemCount): array {
    if ($apiKey !== 'correct-test-key') {
        return ['httpStatus' => 401, 'body' => pharmacyResponse('errorResponse', [
            'status' => 'F', 'timestamp' => '2026-09-04 12:00:00', 'message' => 'Incorrect API key', 'requestID' => 'UNKNOWN01',
        ])];
    }
    $request = new DOMDocument();
    if (!$request->loadXML($requestXml, LIBXML_NONET)) throw new RuntimeException('Malformed outgoing XML.');
    $root = $request->documentElement?->localName ?? '';
    $xpath = new DOMXPath($request);
    $value = static fn (string $name): string => trim((string) $xpath->evaluate('string(/*/*[local-name()="' . $name . '"])'));
    $requestId = $value('requestID');
    $common = ['status' => 'S', 'timestamp' => '2026-09-04 12:00:01', 'message' => 'Success', 'requestID' => $requestId];

    if ($root === 'getMedicineCatalog') {
        $items = '<m:items><m:item><m:sku>MED-PARA-500</m:sku><m:name>Paracetamol</m:name><m:strength>500 mg</m:strength><m:dosageForm>tablet</m:dosageForm><m:availableQuantity>50</m:availableQuantity></m:item></m:items>';
        return ['httpStatus' => 200, 'body' => pharmacyResponse('getMedicineCatalogResponse', $common, $items)];
    }
    if ($root === 'getMedicineAvailability') {
        $sku = $value('sku');
        $quantity = $value('quantity');
        $available = $sku === 'MED-PARA-500' && (int) $quantity <= 50 ? 'true' : 'false';
        return ['httpStatus' => 200, 'body' => pharmacyResponse('getMedicineAvailabilityResponse', $common + [
            'sku' => $sku, 'requestedQuantity' => $quantity, 'available' => $available, 'availableQuantity' => $sku === 'MED-PARA-500' ? '50' : '0',
        ])];
    }
    if ($root === 'createApprovedDispensingRequest') {
        $reference = $value('prescriptionReference');
        $lastCreatedItemCount = (int) $xpath->evaluate('count(/*/*[local-name()="items"]/*[local-name()="item"])');
        $createdByReference[$reference] ??= 'DR-TEST-001';
        return ['httpStatus' => 200, 'body' => pharmacyResponse('createApprovedDispensingRequestResponse', $common + [
            'dispensingRequestId' => $createdByReference[$reference], 'prescriptionReference' => $reference, 'dispensingStatus' => 'approved',
        ])];
    }
    throw new RuntimeException('Unexpected test operation: ' . $root);
};

$client = new PharmacyServiceClient('http://pharmacy.test/api', 'correct-test-key', 1, $transport);
$catalog = $client->getMedicineCatalog();
checkIntegration(count($catalog) === 1 && $catalog[0]['sku'] === 'MED-PARA-500', 'medicine catalogue retrieval');
checkIntegration($client->getMedicineAvailability('MED-PARA-500', 2)['available'] === 'true', 'available medicine');
checkIntegration($client->getMedicineAvailability('MED-PARA-500', 999)['available'] === 'false', 'unavailable medicine');
expectFailure(fn () => $client->getMedicineAvailability('!', 1), 'invalid medicine SKU');

$items = [
    ['sku' => 'MED-PARA-500', 'quantity' => 2, 'instructions' => 'After food'],
    ['sku' => 'MED-VITA-100', 'quantity' => 1, 'instructions' => 'Once daily'],
];
$first = $client->createApprovedDispensingRequest('PA001', 'Patient Test', 'DC001', 'Doctor Test', $items, 'RX-INTEGRATION-001', 'REQ-INTEGRATION-0001');
checkIntegration($lastCreatedItemCount === 2, 'multiple medicines are sent in one XML request');
checkIntegration($first['dispensingStatus'] === 'approved', 'approved dispensing-request creation');
$duplicate = $client->createApprovedDispensingRequest('PA001', 'Patient Test', 'DC001', 'Doctor Test', $items, 'RX-INTEGRATION-001', 'REQ-INTEGRATION-0002');
checkIntegration($duplicate['dispensingRequestId'] === $first['dispensingRequestId'], 'duplicate prescription reference is idempotent');

$offline = new PharmacyServiceClient('http://offline.test', 'correct-test-key', 1, static fn (): array => throw new RuntimeException('Connection refused'));
expectFailure(fn () => $offline->getMedicineCatalog(), 'Pharmacy unavailable');
$malformed = new PharmacyServiceClient('http://bad.test', 'correct-test-key', 1, static fn (): array => ['httpStatus' => 200, 'body' => '<not-xml']);
expectFailure(fn () => $malformed->getMedicineCatalog(), 'malformed Pharmacy XML');
$wrongKey = new PharmacyServiceClient('http://pharmacy.test', 'wrong-key', 1, $transport);
expectFailure(fn () => $wrongKey->getMedicineCatalog(), 'incorrect Pharmacy API key');

$reconciliationReference = '';
$workflow = new PatientRecordCreationService(
    $client,
    static fn (array $data, string $reference): string => throw new RuntimeException('Simulated local database failure'),
    static function (string $reference) use (&$reconciliationReference): void { $reconciliationReference = $reference; }
);
try {
    $workflow->create(
        ['doctor_id' => 'DC001'],
        ['id' => 'PA001', 'name' => 'Patient Test'],
        'Doctor Test',
        [['sku' => 'MED-PARA-500', 'quantity' => 1, 'instructions' => 'After food']]
    );
    throw new RuntimeException('Expected local database failure was not raised.');
} catch (IntegrationConsistencyException $exception) {
    checkIntegration($exception->prescriptionReference !== '' && $reconciliationReference === $exception->prescriptionReference, 'Pharmacy success followed by local database failure is flagged for reconciliation');
}

echo 'All Pharmacy REST/XML integration tests passed.' . PHP_EOL;
