<?php

declare(strict_types=1);

/** XML consumer using Pharmacy's namespaced getPrescription contract. */
final class PharmacyServiceClient
{
    private const NAMESPACE = 'urn:medicare:patient-record';
    private const MAX_RESPONSE_BYTES = 262144;

    private ?Closure $transport;

    /** @param null|Closure(string,string,string,int):array{body:string,httpStatus:int} $transport */
    public function __construct(
        private readonly string $serviceUrl,
        private readonly string $apiKey,
        private readonly int $timeoutSeconds = 5,
        ?Closure $transport = null
    ) {
        $this->transport = $transport;
    }

    /** @return array<string, mixed> */
    public function getPrescription(string $prescriptionReference): array
    {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{2,49}$/', $prescriptionReference) !== 1) {
            throw new InvalidArgumentException('Invalid prescription reference.');
        }
        $requestId = $this->newRequestId();
        $response = $this->sendAndParse($this->buildRequest('getPrescription', [
            'requestID' => $requestId, 'timestamp' => $this->timestamp(), 'prescriptionReference' => $prescriptionReference,
        ]), 'getPrescriptionResponse');
        $this->requireSuccessAndMatchingRequest($response, $requestId);
        if (($response['prescriptionReference'] ?? '') !== $prescriptionReference) {
            throw new RuntimeException('Pharmacy response does not match the prescription reference.');
        }
        return $response;
    }

    /** @return array<string, mixed> */
    public function getMedicineAvailability(string $sku, int $quantity): array
    {
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{2,49}$/', $sku) !== 1 || $quantity < 1 || $quantity > 9999) {
            throw new InvalidArgumentException('Invalid medicine SKU or quantity.');
        }
        $requestId = $this->newRequestId();
        $response = $this->sendAndParse($this->buildRequest('getMedicineAvailability', [
            'requestID' => $requestId, 'timestamp' => $this->timestamp(), 'sku' => $sku, 'quantity' => (string) $quantity,
        ]), 'getMedicineAvailabilityResponse');
        $this->requireSuccessAndMatchingRequest($response, $requestId);
        if (($response['sku'] ?? '') !== $sku || (int) ($response['requestedQuantity'] ?? 0) !== $quantity) {
            throw new RuntimeException('Pharmacy availability response does not match the requested medicine.');
        }
        if (!in_array($response['available'] ?? '', ['true', 'false'], true)) {
            throw new RuntimeException('Pharmacy response contains an invalid availability value.');
        }
        return $response;
    }

    /** @return list<array{sku:string,name:string,strength:string,dosageForm:string,availableQuantity:int}> */
    public function getMedicineCatalog(): array
    {
        $requestId = $this->newRequestId();
        $response = $this->sendAndParse($this->buildRequest('getMedicineCatalog', [
            'requestID' => $requestId, 'timestamp' => $this->timestamp(),
        ]), 'getMedicineCatalogResponse');
        $this->requireSuccessAndMatchingRequest($response, $requestId);

        return $response['items'];
    }

    /**
     * @param list<array{sku:string,quantity:int,instructions:string}> $items
     * @return array<string, mixed>
     */
    public function createApprovedDispensingRequest(
        string $patientId,
        string $patientName,
        string $doctorId,
        string $doctorName,
        array $items,
        ?string $prescriptionReference = null,
        ?string $requestId = null
    ): array {
        $requestId ??= $this->newRequestId();
        $reference = $prescriptionReference ?? ('RX-' . strtoupper(bin2hex(random_bytes(8))));
        if (preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{2,49}$/', $reference) !== 1
            || preg_match('/^[A-Za-z0-9_-]{8,64}$/', $requestId) !== 1
            || $items === []) {
            throw new InvalidArgumentException('Invalid dispensing request identifiers or medicine items.');
        }
        $document = new DOMDocument('1.0', 'UTF-8');
        $root = $document->appendChild($document->createElementNS(self::NAMESPACE, 'm:createApprovedDispensingRequest'));
        foreach ([
            'requestID' => $requestId,
            'timestamp' => $this->timestamp(),
            'prescriptionReference' => $reference,
            'patientExternalId' => $patientId,
            'patientName' => $patientName,
            'prescriberExternalId' => $doctorId,
            'prescriberName' => $doctorName,
        ] as $name => $value) {
            $this->appendText($document, $root, $name, $value);
        }
        $itemsNode = $root->appendChild($document->createElementNS(self::NAMESPACE, 'm:items'));
        foreach ($items as $item) {
            $itemNode = $itemsNode->appendChild($document->createElementNS(self::NAMESPACE, 'm:item'));
            $this->appendText($document, $itemNode, 'sku', $item['sku']);
            $this->appendText($document, $itemNode, 'quantity', (string) $item['quantity']);
            $this->appendText($document, $itemNode, 'instructions', $item['instructions']);
        }
        $response = $this->sendAndParse((string) $document->saveXML(), 'createApprovedDispensingRequestResponse');
        $this->requireSuccessAndMatchingRequest($response, $requestId);
        if (($response['prescriptionReference'] ?? '') !== $reference || ($response['dispensingStatus'] ?? '') !== 'approved') {
            throw new RuntimeException('Pharmacy did not create an approved dispensing request.');
        }

        return $response;
    }

    /** @param array<string, string> $fields */
    private function buildRequest(string $operation, array $fields): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $root = $document->appendChild($document->createElementNS(self::NAMESPACE, 'm:' . $operation));
        foreach ($fields as $name => $value) $this->appendText($document, $root, $name, $value);
        return $document->saveXML() ?: '';
    }

    /** @return array<string, mixed> */
    private function sendAndParse(string $requestXml, string $expectedRoot): array
    {
        $this->validateAgainstSchema($requestXml, __DIR__ . '/xsd/pharmacy_request.xsd', 'request');
        if ($this->transport !== null) {
            $result = ($this->transport)($this->serviceUrl, $requestXml, $this->apiKey, $this->timeoutSeconds);
            $body = $result['body'] ?? '';
            $httpStatus = (int) ($result['httpStatus'] ?? 0);
            if (!is_string($body)) {
                throw new RuntimeException('Pharmacy transport returned an invalid body.');
            }
        } else {
        $client = curl_init($this->serviceUrl);
        curl_setopt_array($client, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $requestXml, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds, CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => ['Content-Type: application/xml', 'Accept: application/xml', 'X-API-Key: ' . $this->apiKey],
        ]);
        $body = curl_exec($client); $httpStatus = (int) curl_getinfo($client, CURLINFO_RESPONSE_CODE); $error = curl_error($client);
        curl_close($client);
        if ($body === false || $error !== '') throw new RuntimeException('Pharmacy service is unavailable.');
        }
        if (strlen((string) $body) > self::MAX_RESPONSE_BYTES) throw new RuntimeException('Pharmacy response is too large.');
        $this->validateAgainstSchema((string) $body, __DIR__ . '/xsd/pharmacy_response.xsd', 'response');
        $response = $this->parseResponse((string) $body, $expectedRoot);
        $response['httpStatus'] = $httpStatus;
        return $response;
    }

    /** @return array<string, mixed> */
    private function parseResponse(string $xml, string $expectedRoot): array
    {
        if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) throw new RuntimeException('Unsafe Pharmacy XML response.');
        $document = new DOMDocument(); libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT); libxml_clear_errors();
        if (!$loaded || $document->documentElement?->localName !== $expectedRoot) throw new RuntimeException('Pharmacy service returned malformed or unexpected XML.');
        $result = [];
        foreach (['status', 'timestamp', 'message', 'requestID'] as $field) $result[$field] = $this->requiredValue($document, $field);
        foreach (['prescriptionReference', 'patientExternalId', 'patientName', 'prescriberName', 'sku', 'requestedQuantity', 'available', 'availableQuantity', 'dispensingRequestId', 'dispensingStatus'] as $field) {
            $value = $this->optionalValue($document, $field); if ($value !== null) $result[$field] = $value;
        }
        $result['items'] = [];
        foreach ($this->nodes($document, 'item') as $item) {
            $parsed = ['sku' => $this->requiredValue($item, 'sku')];
            foreach (['quantity', 'instructions', 'name', 'strength', 'dosageForm', 'availableQuantity'] as $field) {
                $value = $this->optionalValue($item, $field);
                if ($value !== null) $parsed[$field] = $field === 'availableQuantity' ? (int) $value : $value;
            }
            $result['items'][] = $parsed;
        }
        return $result;
    }

    private function validateAgainstSchema(string $xml, string $schemaPath, string $direction): void
    {
        if (!is_file($schemaPath) || !is_readable($schemaPath)) {
            throw new RuntimeException("Pharmacy {$direction} XSD is unavailable.");
        }
        if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) {
            throw new RuntimeException("Unsafe Pharmacy XML {$direction}.");
        }

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT);
        $valid = $loaded && $document->schemaValidate($schemaPath);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$valid) {
            throw new RuntimeException("Pharmacy XML {$direction} does not match the agreed XSD.");
        }
    }

    /** @param array<string, mixed> $response */
    private function requireSuccessAndMatchingRequest(array $response, string $requestId): void
    {
        if (($response['httpStatus'] ?? 0) !== 200 || ($response['status'] ?? '') !== 'S') throw new RuntimeException('Pharmacy service did not return a successful result.');
        if (($response['requestID'] ?? '') !== $requestId) throw new RuntimeException('Pharmacy response requestID does not match the request.');
    }

    private function requiredValue(DOMNode $parent, string $field): string
    {
        $value = $this->optionalValue($parent, $field);
        if ($value === null || $value === '') throw new RuntimeException("Pharmacy response is missing {$field}.");
        return $value;
    }

    private function optionalValue(DOMNode $parent, string $field): ?string
    {
        $nodes = $this->nodes($parent, $field);
        return count($nodes) === 1 ? trim($nodes[0]->textContent) : null;
    }

    /** @return list<DOMElement> */
    private function nodes(DOMNode $parent, string $name): array
    {
        $xpath = new DOMXPath($parent instanceof DOMDocument ? $parent : $parent->ownerDocument);
        $nodeList = $xpath->query('.//*[local-name()="' . $name . '"]', $parent); $result = [];
        foreach ($nodeList ?: [] as $node) if ($node instanceof DOMElement) $result[] = $node;
        return $result;
    }

    private function appendText(DOMDocument $document, DOMNode $parent, string $name, string $value): void
    {
        $node = $document->createElementNS(self::NAMESPACE, 'm:' . $name);
        $node->appendChild($document->createTextNode($value)); $parent->appendChild($node);
    }

    private function newRequestId(): string { return 'REQ-' . bin2hex(random_bytes(16)); }
    private function timestamp(): string { return (new DateTimeImmutable('now', new DateTimeZone('Asia/Kuala_Lumpur')))->format('Y-m-d H:i:s'); }
}
