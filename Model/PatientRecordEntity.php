<?php

declare(strict_types=1);

require_once __DIR__ . '/../Shared/SensitiveDataCipher.php';

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientRecordEntity extends Model
{
    protected $table = 'patient_records';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'patient_id',
        'condition_id',
        'doctor_id',
        'severity',
        'remark',
        'record_date',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(PatientEntity::class, 'patient_id');
    }

    public function condition(): BelongsTo
    {
        return $this->belongsTo(ConditionEntity::class, 'condition_id');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(PatientRecordPrescriptionEntity::class, 'patient_record_id');
    }

    protected function remark(): Attribute
    {
        return Attribute::make(
            get: static fn (?string $value): string =>
                SensitiveDataCipher::instance()->decrypt($value ?? ''),
            set: static fn (?string $value): string =>
                SensitiveDataCipher::instance()->encrypt($value ?? '')
        );
    }
}
