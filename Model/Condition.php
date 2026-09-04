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

    public function findById(string $conditionId): ?array
    {
        $condition = ConditionEntity::query()->find($conditionId);
        return $condition === null ? null : $condition->only(['id', 'name', 'description']);
    }

    public function create(string $name, string $description): string
    {
        return ConditionEntity::getConnectionResolver()->connection()->transaction(function () use ($name, $description): string {
            $lastId = ConditionEntity::query()->lockForUpdate()->orderByDesc('id')->value('id');
            $nextNumber = $lastId === null ? 1 : ((int) substr((string) $lastId, 1)) + 1;
            $id = 'C' . str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT);
            ConditionEntity::query()->create(['id' => $id, 'name' => $name, 'description' => $description ?: null]);
            return $id;
        });
    }

    public function update(string $conditionId, string $name, string $description): bool
    {
        $condition = ConditionEntity::query()->find($conditionId);
        if ($condition === null) return false;
        $condition->name = $name;
        $condition->description = $description ?: null;
        return $condition->save();
    }
}
