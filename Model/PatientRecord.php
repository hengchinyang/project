<?php

declare(strict_types=1);

require_once __DIR__ . '/PatientEntity.php';
require_once __DIR__ . '/ConditionEntity.php';
require_once __DIR__ . '/PatientRecordEntity.php';
require_once __DIR__ . '/PatientRecordPrescriptionEntity.php';

class PatientRecord
{
    /** @return array{records:array<int,array<string,mixed>>,total:int,page:int,perPage:int} */
    public function paginateByPatient(string $patientId, int $page, int $perPage, ?string $doctorId = null): array
    {
        $query = PatientRecordEntity::query()
            ->with(['patient', 'condition', 'prescriptions'])
            ->where('patient_id', $patientId);

        if ($doctorId !== null) {
            $query->where('doctor_id', $doctorId);
        }

        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $records = $query
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (PatientRecordEntity $record): array => $this->toViewArray($record))
            ->all();

        return ['records' => $records, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    public function getAllByPatient(string $patientId): array
    {
        // Eloquent sends this string value to PDO as a bound parameter.
        return PatientRecordEntity::query()
            ->with(['patient', 'condition', 'prescriptions'])
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
            ->with(['patient', 'condition', 'prescriptions'])
            ->find($recordId);

        return $record === null ? null : $this->toViewArray($record);
    }

    public function getAllByPatientForDoctor(string $patientId, string $doctorId): array
    {
        return PatientRecordEntity::query()
            ->with(['patient', 'condition', 'prescriptions'])
            ->where('patient_id', $patientId)
            ->where('doctor_id', $doctorId)
            ->orderByDesc('record_date')
            ->get()
            ->map(fn (PatientRecordEntity $record): array => $this->toViewArray($record))
            ->all();
    }

    public function getPatientsForDoctor(string $doctorId): array
    {
        return PatientRecordEntity::query()
            ->with('patient')
            ->where('doctor_id', $doctorId)
            ->orderBy('patient_id')
            ->get()
            ->unique('patient_id')
            ->map(static fn (PatientRecordEntity $record): array => [
                'id' => $record->patient_id,
                'name' => $record->patient?->name ?? '',
            ])
            ->values()
            ->all();
    }

    public function doctorIsResponsibleForPatient(string $doctorId, string $patientId): bool
    {
        return PatientRecordEntity::query()
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId)
            ->exists();
    }

    public function create(array $data, string $prescriptionReference = ''): string
    {
        return PatientRecordEntity::getConnectionResolver()
            ->connection()
            ->transaction(function () use ($data, $prescriptionReference): string {
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

                if ($prescriptionReference !== '') {
                    PatientRecordPrescriptionEntity::query()->create([
                        'patient_record_id' => $recordId,
                        'prescription_reference' => $prescriptionReference,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }

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
            'doctor_id' => $record->doctor_id,
            'condition_id' => $record->condition_id,
            'condition_name' => $record->condition?->name ?? '',
            'condition_description' => $record->condition?->description ?? '',
            'severity' => $record->severity,
            'remark' => $record->remark,
            'record_date' => $record->record_date,
            'prescription_references' => $record->prescriptions
                ->pluck('prescription_reference')
                ->all(),
        ];
    }
}
