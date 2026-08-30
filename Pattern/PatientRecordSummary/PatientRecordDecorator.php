<?php

declare(strict_types=1);

require_once __DIR__ . '/PatientRecordInterface.php';

abstract class PatientRecordDecorator implements PatientRecordInterface
{
    public function __construct(
        protected readonly PatientRecordInterface $patientRecord
    ) {
    }

    public function getDetails(): string
    {
        return $this->patientRecord->getDetails();
    }
}
