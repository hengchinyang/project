<?php

declare(strict_types=1);

require_once __DIR__ . '/PatientEntity.php';

class Patient
{
    public function findById(string $patientId): ?array
    {
        $patient = PatientEntity::query()->find($patientId);

        if ($patient === null) {
            return null;
        }

        return [
            'id' => (string) $patient->id,
            'name' => (string) $patient->name,
        ];
    }
}
