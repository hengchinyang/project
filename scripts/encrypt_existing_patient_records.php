<?php

declare(strict_types=1);

require __DIR__ . '/../Shared/orm.php';
require_once __DIR__ . '/../Model/PatientEntity.php';
require_once __DIR__ . '/../Model/ConditionEntity.php';
require_once __DIR__ . '/../Model/PatientRecordEntity.php';

$updated = PatientRecordEntity::getConnectionResolver()
    ->connection()
    ->transaction(function (): int {
        $count = 0;

        foreach (PatientRecordEntity::query()->lockForUpdate()->get() as $record) {
            $storedRemark = (string) $record->getRawOriginal('remark');
            if (SensitiveDataCipher::isEncrypted($storedRemark)) {
                continue;
            }

            $record->remark = $storedRemark;
            $record->save();
            $count++;
        }

        return $count;
    });

echo "Encrypted {$updated} existing Patient Record remark(s)." . PHP_EOL;
