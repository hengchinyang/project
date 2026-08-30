<?php

declare(strict_types=1);

require_once __DIR__ . '/ConditionEntity.php';
require_once __DIR__ . '/PatientRecordEntity.php';

class Condition
{
    public function getAll(): array
    {
        return ConditionEntity::query()
            ->orderBy('name')
            ->get(['id', 'name', 'description'])
            ->toArray();
    }

    public function exists(string $conditionId): bool
    {
        // whereKey() parameterizes the strongly typed string value via PDO.
        return ConditionEntity::query()->whereKey($conditionId)->exists();
    }
}
