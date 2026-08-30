<?php

declare(strict_types=1);

final class PharmacyServiceClient
{
    private const MAX_RESPONSE_BYTES = 262144;

    public function __construct(
        private readonly string $serviceUrl,
        private readonly string $apiKey,
        private readonly int $timeoutSeconds = 5
    ) {
    }

    public function getPrescription(string $patientId, string $appointmentId): array
    {
        if (preg_match('/^PA[0-9]{3,8}$/', $patientId) !== 1) {
            throw new InvalidArgumentException('Invalid patient ID.');
        }
        if (preg_match('/^APT[0-9]{4,17}$/', $appointmentId) !== 1) {
            throw new InvalidArgumentException('Invalid appointment ID.');
        }

        $requestId = 'REQ-' . bin2hex(random_bytes(8));
        $document = new DOMDocument('1.0', 'UTF-8');
        $root = $document->appendChild($document->createElement('pharmacyPrescriptionRequest'));
        $this->appendText($document, $root, 'requestID', $requestId);
        $this->appendText($document, $root, 'timeStamp', date('Y-m-d H:i:s'));
        $this->appendText($document, $root, 'patientID', $patientId);
        $this->appendText($document, $root, 'appointmentID', $appointmentId);

        $client = curl_init($this->serviceUrl);
        curl_setopt_array($client, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $document->saveXML(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/xml',
                'Accept: application/xml',
                'X-API-Key: ' . $this->apiKey,
            ],
        ]);

        $responseBody = curl_exec($client);
        $httpStatus = (int) curl_getinfo($client, CURLINFO_RESPONSE_CODE);
        $error = curl_error($client);
        curl_close($client);

        if ($responseBody === false || $error !== '') {
            throw new RuntimeException('Pharmacy service is unavailable.');
        }
        if (strlen((string) $responseBody) > self::MAX_RESPONSE_BYTES) {
            throw new RuntimeException('Pharmacy response is too large.');
        }

        $response = $this->parseResponse((string) $responseBody);
        if ($response['requestID'] !== $requestId) {
            throw new RuntimeException('Pharmacy response requestID does not match the request.');
        }
        if ($httpStatus !== 200 || $response['status'] !== 'S') {
            throw new RuntimeException('Pharmacy service did not return a successful result.');
        }
        if ($response['patientID'] !== $patientId || $response['appointmentID'] !== $appointmentId) {
            throw new RuntimeException('Pharmacy response does not match the requested patient and appointment.');
        }

        return $response;
    }

    private function parseResponse(string $xml): array
    {
        if (stripos($xml, '<!DOCTYPE') !== false || stripos($xml, '<!ENTITY') !== false) {
            throw new RuntimeException('Unsafe Pharmacy XML response.');
        }

        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT);
        libxml_clear_errors();
        if (!$loaded) {
            throw new RuntimeException('Pharmacy service returned malformed XML.');
        }

        $result = [];
        foreach (['requestID', 'status', 'timeStamp', 'message', 'patientID', 'appointmentID', 'prescriptionID', 'doctorID', 'finalPrice'] as $field) {
            $result[$field] = $this->requiredValue($document, $field);
        }

        if (!in_array($result['status'], ['S', 'F', 'E'], true)) {
            throw new RuntimeException('Pharmacy response contains an invalid status.');
        }
        if (!is_numeric($result['finalPrice']) || (float) $result['finalPrice'] < 0) {
            throw new RuntimeException('Pharmacy response contains an invalid final price.');
        }

        $result['medicines'] = [];
        foreach ($document->getElementsByTagName('medicine') as $medicineNode) {
            $medicine = [];
            foreach (['medicineID', 'medicineName', 'quantity', 'totalPrice'] as $field) {
                $nodes = $medicineNode->getElementsByTagName($field);
                if ($nodes->length !== 1 || trim($nodes->item(0)->textContent) === '') {
                    throw new RuntimeException("Pharmacy medicine is missing {$field}.");
                }
                $medicine[$field] = trim($nodes->item(0)->textContent);
            }
            if (filter_var($medicine['quantity'], FILTER_VALIDATE_INT) === false || (int) $medicine['quantity'] < 1) {
                throw new RuntimeException('Pharmacy medicine contains an invalid quantity.');
            }
            if (!is_numeric($medicine['totalPrice']) || (float) $medicine['totalPrice'] < 0) {
                throw new RuntimeException('Pharmacy medicine contains an invalid total price.');
            }
            $result['medicines'][] = $medicine;
        }

        if ($result['medicines'] === []) {
            throw new RuntimeException('Pharmacy response contains no medicines.');
        }

        return $result;
    }

    private function requiredValue(DOMDocument $document, string $field): string
    {
        $nodes = $document->getElementsByTagName($field);
        if ($nodes->length !== 1 || trim($nodes->item(0)->textContent) === '') {
            throw new RuntimeException("Pharmacy response is missing {$field}.");
        }

        return trim($nodes->item(0)->textContent);
    }

    private function appendText(DOMDocument $document, DOMNode $parent, string $name, string $value): void
    {
        $node = $document->createElement($name);
        $node->appendChild($document->createTextNode($value));
        $parent->appendChild($node);
    }
}
