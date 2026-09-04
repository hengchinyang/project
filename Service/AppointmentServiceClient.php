<?php

declare(strict_types=1);

/** Consumes doctor-patient assignments without reading the Appointment database. */
final class AppointmentServiceClient
{
    private ?Closure $transport;

    /** @param null|Closure(string,string,string,int):array{body:string,httpStatus:int} $transport */
    public function __construct(
        private readonly string $url,
        private readonly string $apiKey,
        private readonly int $timeoutSeconds = 5,
        ?Closure $transport = null
    ) { $this->transport = $transport; }

    /** @return list<array{patient_id:string,patient_name:string,appointment_id:string,status:string}> */
    public function getDoctorPatientAssignments(string $doctorId): array
    {
        if (preg_match('/^DC[0-9]{3,18}$/', $doctorId) !== 1) throw new InvalidArgumentException('Invalid doctor ID.');
        $requestId = 'REQ-' . bin2hex(random_bytes(16));
        $xml = '<?xml version="1.0" encoding="UTF-8"?><m:getDoctorPatientAssignments xmlns:m="urn:medicare:appointment">'
            . '<m:requestID>' . $requestId . '</m:requestID><m:timestamp>' . date('Y-m-d H:i:s') . '</m:timestamp>'
            . '<m:doctorId>' . htmlspecialchars($doctorId, ENT_XML1, 'UTF-8') . '</m:doctorId></m:getDoctorPatientAssignments>';
        $result = $this->send($xml);
        if ($result['httpStatus'] !== 200) throw new RuntimeException('Appointment service rejected the assignment request.');
        $document = new DOMDocument();
        if (stripos($result['body'], '<!DOCTYPE') !== false || !$document->loadXML($result['body'], LIBXML_NONET)) {
            throw new RuntimeException('Appointment service returned malformed XML.');
        }
        if ($document->documentElement?->localName !== 'getDoctorPatientAssignmentsResponse') throw new RuntimeException('Unexpected Appointment response.');
        $xpath = new DOMXPath($document);
        $value = static fn (DOMNode $node, string $name): string => trim((string) $xpath->evaluate('string(./*[local-name()="' . $name . '"])', $node));
        if ($value($document->documentElement, 'status') !== 'S' || $value($document->documentElement, 'requestID') !== $requestId) {
            throw new RuntimeException('Appointment assignment request failed or did not correlate.');
        }
        $assignments = [];
        foreach ($xpath->query('/*/*[local-name()="assignments"]/*[local-name()="assignment"]') ?: [] as $node) {
            $patientId = $value($node, 'patientId');
            $appointmentId = $value($node, 'appointmentId');
            $status = strtolower($value($node, 'status'));
            if (preg_match('/^PA[0-9]{3,8}$/', $patientId) !== 1 || preg_match('/^APT[0-9]{4,8}$/', $appointmentId) !== 1) continue;
            $assignments[] = ['patient_id' => $patientId, 'patient_name' => $value($node, 'patientName'), 'appointment_id' => $appointmentId, 'status' => $status];
        }
        return $assignments;
    }

    /** @return array{body:string,httpStatus:int} */
    private function send(string $xml): array
    {
        if ($this->transport !== null) return ($this->transport)($this->url, $xml, $this->apiKey, $this->timeoutSeconds);
        $curl = curl_init($this->url);
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $xml, CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds, CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => ['Content-Type: application/xml', 'Accept: application/xml', 'X-API-Key: ' . $this->apiKey]]);
        $body = curl_exec($curl); $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); $error = curl_error($curl); curl_close($curl);
        if ($body === false || $error !== '') throw new RuntimeException('Appointment service is unavailable.');
        return ['body' => (string) $body, 'httpStatus' => $status];
    }
}
