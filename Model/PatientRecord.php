<?php

declare(strict_types=1);

require_once __DIR__ . '/PatientEntity.php';
require_once __DIR__ . '/ConditionEntity.php';
require_once __DIR__ . '/PatientRecordEntity.php';

class PatientRecord
{
    public function getAllByPatient(string $patientId): array
    {
        // Eloquent sends this string value to PDO as a bound parameter.
        return PatientRecordEntity::query()
            ->with(['patient', 'condition'])
            ->where('patient_id', $patientId)
            ->orderByDesc('record_date')
            ->get()
            ->map(fn (PatientRecordEntity $record): array => $this->toViewArray($record))
            ->all();
    }

    public function findById(string $recordId): ?array
    {
        // find() parameterizes the strongly typed string primary-key value.
        $record = PatientRecordEntity::query()
            ->with(['patient', 'condition'])
            ->find($recordId);

        return $record === null ? null : $this->toViewArray($record);
    }

    public function create(array $data): string
    {
        return PatientRecordEntity::getConnectionResolver()
            ->connection()
            ->transaction(function () use ($data): string {
                $lastId = PatientRecordEntity::query()
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->value('id');
                $nextNumber = $lastId === null ? 1 : ((int) substr($lastId, 2)) + 1;
                $recordId = 'PR' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);

                PatientRecordEntity::query()->create([
                    'id' => $recordId,
                    ...$data,
                ]);

                return $recordId;
            });
    }

    public function update(string $recordId, array $data): bool
    {
        $record = PatientRecordEntity::query()->find($recordId);
        if ($record === null) {
            return false;
        }

        $record->fill($data);

        return $record->save();
    }

    private function toViewArray(PatientRecordEntity $record): array
    {
        return [
            'id' => $record->id,
            'patient_id' => $record->patient_id,
            'patient_name' => $record->patient?->name ?? '',
            'appointment_id' => $record->appointment_id,
            'doctor_id' => $record->doctor_id,
            'condition_id' => $record->condition_id,
            'condition_name' => $record->condition?->name ?? '',
            'condition_description' => $record->condition?->description ?? '',
            'severity' => $record->severity,
            'remark' => $record->remark,
            'record_date' => $record->record_date,
        ];
    }
}
