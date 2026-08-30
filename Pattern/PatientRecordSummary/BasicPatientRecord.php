<?php

declare(strict_types=1);

require_once __DIR__ . '/PatientRecordInterface.php';

final class BasicPatientRecord implements PatientRecordInterface
{
    public function __construct(
        private readonly string $recordId,
        private readonly string $patientName,
        private readonly string $condition,
        private readonly string $remark
    ) {
    }

    public function getDetails(): string
    {
        return implode(PHP_EOL, [
            'Record ID: ' . $this->recordId,
            'Patient Name: ' . $this->patientName,
            'Condition: ' . $this->condition,
            'Remark: ' . $this->remark,
        ]);
    }
}
