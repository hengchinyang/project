<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientEntity extends Model
{
    protected $table = 'patients';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'name'];

    public function records(): HasMany
    {
        return $this->hasMany(PatientRecordEntity::class, 'patient_id');
    }
}
