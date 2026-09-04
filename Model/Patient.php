<?php

declare(strict_types=1);

require_once __DIR__ . '/PatientEntity.php';

class Patient
{
    /** Stores the minimal local patient reference received from an authorised REST response. */
    public function ensureExists(string $patientId, string $patientName): void
    {
        if (preg_match('/^PA[0-9]{3,8}$/', $patientId) !== 1 || trim($patientName) === '') {
            throw new InvalidArgumentException('Invalid external patient identity.');
        }

        PatientEntity::query()->updateOrCreate(
            ['id' => $patientId],
            ['name' => trim($patientName)]
        );
    }

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
