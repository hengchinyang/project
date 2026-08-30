<?php

declare(strict_types=1);

require_once __DIR__ . '/PatientRecordDecorator.php';

final class AuditTrailDecorator extends PatientRecordDecorator
{
    public function __construct(
        PatientRecordInterface $patientRecord,
        private readonly string $accessedBy,
        private readonly DateTimeImmutable $accessTime
    ) {
        parent::__construct($patientRecord);
    }

    public function getDetails(): string
    {
        return parent::getDetails() . PHP_EOL
            . 'Accessed By: ' . $this->accessedBy . PHP_EOL
            . 'Access Time: ' . $this->accessTime->format('Y-m-d H:i:s');
    }
}
