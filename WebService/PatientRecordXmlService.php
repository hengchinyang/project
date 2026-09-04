<?php

declare(strict_types=1);

require_once __DIR__ . '/../Model/PatientRecord.php';

final class PatientRecordXmlService
{
    private const MAX_REQUEST_BYTES = 65536;
    private const MAX_CLOCK_DIFFERENCE_SECONDS = 300;

    public function __construct(private readonly PatientRecord $patientRecordModel)
    {
    }

    public function handle(string $requestBody): array
    {
        $requestId = $this->extractRequestId($requestBody);

        if ($requestBody === '' || strlen($requestBody) > self::MAX_REQUEST_BYTES) {
            return $this->result(400, $requestId, 'E', 'Request body is empty or too large.');
        }

        if (stripos($requestBody, '<!DOCTYPE') !== false || stripos($requestBody, '<!ENTITY') !== false) {
            return $this->result(400, $requestId, 'E', 'DTD and external entity declarations are not allowed.');
        }

        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $document->loadXML(
            $requestBody,
            LIBXML_NONET | LIBXML_NOBLANKS | LIBXML_COMPACT
        );

        if (!$loaded || !$document->schemaValidate(__DIR__ . '/xsd/patient_record_request.xsd')) {
            libxml_clear_errors();
            return $this->result(400, $requestId, 'E', 'Request XML does not match the Patient Record IFA.');
        }
        libxml_clear_errors();

        $requestId = $this->elementValue($document, 'requestID');
        $timeStamp = $this->elementValue($document, 'timeStamp');
        $patientId = $this->elementValue($document, 'patientID');

        $timeZone = new DateTimeZone('Asia/Kuala_Lumpur');
        $requestDate = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $timeStamp, $timeZone);
        if ($requestDate === false || $requestDate->format('Y-m-d H:i:s') !== $timeStamp) {
            return $this->result(400, $requestId, 'E', 'Invalid request timestamp.');
        }
        $clockDifference = abs((new DateTimeImmutable('now', $timeZone))->getTimestamp() - $requestDate->getTimestamp());
        if ($clockDifference > self::MAX_CLOCK_DIFFERENCE_SECONDS) {
            return $this->result(400, $requestId, 'E', 'Request timestamp is outside the allowed five-minute window.');
        }

        $records = $this->patientRecordModel->getAllByPatient($patientId);
        if ($records === []) {
            return $this->result(404, $requestId, 'F', 'No Patient Record was found.');
        }

        return $this->result(
            200,
            $requestId,
            'S',
            'Patient Record summary retrieved successfully.',
            [
                'id' => $patientId,
                'name' => $records[0]['patient_name'],
            ],
            $records
        );
    }

    public function error(int $httpStatus, string $requestId, string $message): array
    {
        return $this->result($httpStatus, $requestId, 'E', $message);
    }

    private function result(
        int $httpStatus,
        string $requestId,
        string $status,
        string $message,
        ?array $patient = null,
        array $records = []
    ): array {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $root = $document->appendChild($document->createElement('patientRecordResponse'));
        $this->appendText($document, $root, 'requestID', $requestId);
        $this->appendText($document, $root, 'status', $status);
        $this->appendText($document, $root, 'timeStamp', date('Y-m-d H:i:s'));
        $this->appendText($document, $root, 'message', $message);

        if ($patient !== null) {
            $patientNode = $root->appendChild($document->createElement('patient'));
            $this->appendText($document, $patientNode, 'patientID', $patient['id']);
            $this->appendText($document, $patientNode, 'patientName', $patient['name']);
        }

        if ($records !== []) {
            $recordsNode = $root->appendChild($document->createElement('records'));
            foreach ($records as $record) {
                $recordNode = $recordsNode->appendChild($document->createElement('record'));
                $this->appendText($document, $recordNode, 'recordID', $record['id']);
                $this->appendText($document, $recordNode, 'doctorID', $record['doctor_id']);
                $this->appendText($document, $recordNode, 'condition', $record['condition_name']);
                $this->appendText($document, $recordNode, 'severity', $record['severity']);
                $this->appendText($document, $recordNode, 'recordDate', $record['record_date']);
            }
        }

        if (!$document->schemaValidate(__DIR__ . '/xsd/patient_record_response.xsd')) {
            throw new RuntimeException('Generated Patient Record response failed XSD validation.');
        }

        return [
            'httpStatus' => $httpStatus,
            'xml' => (string) $document->saveXML(),
        ];
    }

    private function appendText(DOMDocument $document, DOMNode $parent, string $name, string $value): void
    {
        $node = $document->createElement($name);
        $node->appendChild($document->createTextNode($value));
        $parent->appendChild($node);
    }

    private function elementValue(DOMDocument $document, string $name, bool $required = true): string
    {
        $node = $document->getElementsByTagName($name)->item(0);
        if ($node === null) {
            if ($required) {
                throw new RuntimeException("Required XML element {$name} is missing.");
            }
            return '';
        }

        return trim($node->textContent);
    }

    private function extractRequestId(string $requestBody): string
    {
        if (preg_match('/<requestID>([A-Za-z0-9_-]{1,64})<\/requestID>/', $requestBody, $matches) === 1) {
            return $matches[1];
        }

        return 'UNKNOWN';
    }
}
