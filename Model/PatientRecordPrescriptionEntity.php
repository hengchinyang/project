<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientRecordPrescriptionEntity extends Model
{
    protected $table = 'patient_record_prescriptions';
    public $timestamps = false;
    protected $fillable = ['patient_record_id', 'prescription_reference', 'created_at'];

    public function patientRecord(): BelongsTo
    {
        return $this->belongsTo(PatientRecordEntity::class, 'patient_record_id');
    }
}
