<?php

declare(strict_types=1);

require_once __DIR__ . '/PatientRecordDecorator.php';

final class ConfidentialityDecorator extends PatientRecordDecorator
{
    public function __construct(
        PatientRecordInterface $patientRecord,
        private readonly string $securityLabel
    ) {
        parent::__construct($patientRecord);
    }

    public function getDetails(): string
    {
        return '[' . $this->securityLabel . ']' . PHP_EOL
            . parent::getDetails();
    }
}
