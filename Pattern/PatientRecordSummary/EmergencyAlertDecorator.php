<?php

declare(strict_types=1);

require_once __DIR__ . '/PatientRecordDecorator.php';

final class EmergencyAlertDecorator extends PatientRecordDecorator
{
    public function __construct(
        PatientRecordInterface $patientRecord,
        private readonly string $alertMessage
    ) {
        parent::__construct($patientRecord);
    }

    public function getDetails(): string
    {
        return '[EMERGENCY ALERT: ' . $this->alertMessage . ']' . PHP_EOL
            . parent::getDetails();
    }
}
