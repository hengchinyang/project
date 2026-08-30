<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientRecordAccessLogEntity extends Model
{
    protected $table = 'patient_record_access_logs';
    public $timestamps = false;
    protected $fillable = [
        'patient_record_id',
        'accessor_id',
        'accessed_by',
        'accessor_role',
        'access_type',
        'accessed_at',
    ];
    protected $casts = [
        'accessed_at' => 'immutable_datetime',
    ];

    public function patientRecord(): BelongsTo
    {
        return $this->belongsTo(PatientRecordEntity::class, 'patient_record_id');
    }
}
