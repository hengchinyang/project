<?php

declare(strict_types=1);

require_once __DIR__ . '/PatientRecordEntity.php';
require_once __DIR__ . '/PatientRecordAccessLogEntity.php';

class PatientRecordAccessLog
{
    private const ACCESS_TYPES = ['VIEW', 'CREATE', 'UPDATE'];
    private const ROLES = ['patient', 'doctor', 'admin'];

    public function record(
        string $patientRecordId,
        string $accessorId,
        string $accessedBy,
        string $accessorRole,
        string $accessType,
        DateTimeImmutable $accessedAt
    ): PatientRecordAccessLogEntity {
        $normalisedRole = strtolower($accessorRole);
        $normalisedType = strtoupper($accessType);

        if (!in_array($normalisedRole, self::ROLES, true)) {
            throw new InvalidArgumentException('Invalid audit accessor role.');
        }
        if (!in_array($normalisedType, self::ACCESS_TYPES, true)) {
            throw new InvalidArgumentException('Invalid audit access type.');
        }

        return PatientRecordAccessLogEntity::query()->create([
            'patient_record_id' => $patientRecordId,
            'accessor_id' => mb_substr($accessorId, 0, 20),
            'accessed_by' => mb_substr($accessedBy, 0, 100),
            'accessor_role' => $normalisedRole,
            'access_type' => $normalisedType,
            'accessed_at' => $accessedAt->format('Y-m-d H:i:s'),
        ]);
    }
}
