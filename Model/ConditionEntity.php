<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConditionEntity extends Model
{
    protected $table = 'conditions';
    protected $primaryKey = 'id';
    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'name', 'description'];

    public function records(): HasMany
    {
        return $this->hasMany(PatientRecordEntity::class, 'condition_id');
    }
}
