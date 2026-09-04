<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Kuala_Lumpur');

/**
 * Local smoke tests for the Patient Record XML service.
 * Run with: C:\xampp\php\php.exe tests\xml_service_smoke.php
 */

$endpoint = 'http://127.0.0.1/project/api/patient-record-summary.php';
$keyPath = 'C:/xampp/medicare-connect-secrets/patient-record-service.key';
$apiKey = trim((string) file_get_contents($keyPath));
$validRequest = (string) file_get_contents(__DIR__ . '/../WebService/examples/patient_record_request.xml');
$validRequest = preg_replace(
    '/<timeStamp>[^<]+<\/timeStamp>/',
    '<timeStamp>' . date('Y-m-d H:i:s') . '</timeStamp>',
    $validRequest
) ?: $validRequest;

function callService(string $endpoint, string $body, ?string $apiKey, string $method = 'POST'): array
{
    $handle = curl_init($endpoint);
    $headers = ['Content-Type: application/xml', 'Accept: application/xml'];

    if ($apiKey !== null) {
        $headers[] = 'X-API-Key: ' . $apiKey;
    }

    curl_setopt_array($handle, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $method === 'POST' ? $body : null,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($handle);
    if ($response === false) {
        throw new RuntimeException(curl_error($handle));
    }

    return [(int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), $response];
}

function assertService(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }

    echo '[PASS] ' . $message . PHP_EOL;
}

[$code, $body] = callService($endpoint, $validRequest, $apiKey);
assertService($code === 200, 'valid request returns HTTP 200');
assertService(str_contains($body, '<status>S</status>'), 'valid response has success status');
assertService(!str_contains($body, '<remark>'), 'response does not expose encrypted clinical remarks');

[$code, $body] = callService($endpoint, '<broken>', $apiKey);
assertService($code === 400 && str_contains($body, '<status>E</status>'), 'invalid XML returns HTTP 400/E');

[$code] = callService($endpoint, $validRequest, null);
assertService($code === 401, 'missing API key returns HTTP 401');

[$code] = callService($endpoint, '', $apiKey, 'GET');
assertService($code === 405, 'unsupported method returns HTTP 405');

$staleRequest = preg_replace(
    '/<timeStamp>[^<]+<\/timeStamp>/',
    '<timeStamp>2000-01-01 00:00:00</timeStamp>',
    $validRequest
) ?: $validRequest;
[$code, $body] = callService($endpoint, $staleRequest, $apiKey);
assertService($code === 400 && str_contains($body, '<status>E</status>'), 'stale timestamp is rejected');

$missingRequest = str_replace('<patientID>PA001</patientID>', '<patientID>PA999</patientID>', $validRequest);
[$code, $body] = callService($endpoint, $missingRequest, $apiKey);
assertService($code === 404 && str_contains($body, '<status>F</status>'), 'unknown patient returns HTTP 404/F');
