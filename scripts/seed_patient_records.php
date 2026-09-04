<?php

declare(strict_types=1);

require __DIR__ . '/../Shared/orm.php';
require_once __DIR__ . '/../Model/PatientEntity.php';
require_once __DIR__ . '/../Model/ConditionEntity.php';
require_once __DIR__ . '/../Model/PatientRecordEntity.php';

$samples = [
    [
        'id' => 'PR001',
        'patient_id' => 'PA001',
        'condition_id' => 'C001',
        'doctor_id' => 'DC001',
        'severity' => 'Moderate',
        'remark' => 'Patient reported breathing difficulty.',
        'record_date' => '2026-08-01',
    ],
    [
        'id' => 'PR002',
        'patient_id' => 'PA001',
        'condition_id' => 'C002',
        'doctor_id' => 'DC001',
        'severity' => 'Mild',
        'remark' => 'Patient reported persistent headache.',
        'record_date' => '2026-08-05',
    ],
    [
        'id' => 'PR003',
        'patient_id' => 'PA001',
        'condition_id' => 'C003',
        'doctor_id' => 'DC002',
        'severity' => 'Severe',
        'remark' => 'High fever with dehydration symptoms; immediate medical attention advised.',
        'record_date' => '2026-08-10',
    ],
    [
        'id' => 'PR004',
        'patient_id' => 'PA001',
        'condition_id' => 'C004',
        'doctor_id' => 'DC002',
        'severity' => 'Moderate',
        'remark' => 'Blood pressure above target range; continue monitoring and follow-up.',
        'record_date' => '2026-08-14',
    ],
    [
        'id' => 'PR005',
        'patient_id' => 'PA001',
        'condition_id' => 'C006',
        'doctor_id' => 'DC003',
        'severity' => 'Mild',
        'remark' => 'Seasonal nasal congestion and sneezing reported.',
        'record_date' => '2026-08-18',
    ],
    [
        'id' => 'PR006',
        'patient_id' => 'PA001',
        'condition_id' => 'C008',
        'doctor_id' => 'DC003',
        'severity' => 'Severe',
        'remark' => 'Severe migraine with visual disturbance; urgent assessment recommended.',
        'record_date' => '2026-08-22',
    ],
    [
        'id' => 'PR007',
        'patient_id' => 'PA002',
        'condition_id' => 'C005',
        'doctor_id' => 'DC004',
        'severity' => 'Moderate',
        'remark' => 'Glucose readings remain elevated; diet and medication reviewed.',
        'record_date' => '2026-08-03',
    ],
    [
        'id' => 'PR008',
        'patient_id' => 'PA002',
        'condition_id' => 'C007',
        'doctor_id' => 'DC004',
        'severity' => 'Mild',
        'remark' => 'Intermittent stomach discomfort after meals.',
        'record_date' => '2026-08-09',
    ],
    [
        'id' => 'PR009',
        'patient_id' => 'PA003',
        'condition_id' => 'C009',
        'doctor_id' => 'DC005',
        'severity' => 'Moderate',
        'remark' => 'Persistent productive cough; hydration and follow-up advised.',
        'record_date' => '2026-08-12',
    ],
    [
        'id' => 'PR010',
        'patient_id' => 'PA003',
        'condition_id' => 'C002',
        'doctor_id' => 'DC005',
        'severity' => 'Mild',
        'remark' => 'Occasional tension headache associated with prolonged screen use.',
        'record_date' => '2026-08-16',
    ],
    [
        'id' => 'PR011',
        'patient_id' => 'PA004',
        'condition_id' => 'C010',
        'doctor_id' => 'DC006',
        'severity' => 'Moderate',
        'remark' => 'Ankle swelling after sports injury; rest and support advised.',
        'record_date' => '2026-08-19',
    ],
    [
        'id' => 'PR013',
        'patient_id' => 'PA005',
        'condition_id' => 'C001',
        'doctor_id' => 'DC006',
        'severity' => 'Severe',
        'remark' => 'Acute breathing difficulty reported; emergency assessment required.',
        'record_date' => '2026-08-21',
    ],
];

$created = 0;
foreach ($samples as $sample) {
    $record = PatientRecordEntity::query()->firstOrCreate(
        ['id' => $sample['id']],
        $sample
    );
    if ($record->wasRecentlyCreated) {
        $created++;
    }
}

echo "Created {$created} encrypted sample Patient Record(s)." . PHP_EOL;
