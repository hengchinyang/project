<?php

declare(strict_types=1);

final class ServiceApiKey
{
    public static function isValid(string $providedKey): bool
    {
        if ($providedKey === '') {
            return false;
        }

        $configuredPath = getenv('MEDICARE_SERVICE_API_KEY_FILE');
        $keyPath = is_string($configuredPath) && $configuredPath !== ''
            ? $configuredPath
            : dirname(__DIR__, 3) . '/medicare-connect-secrets/patient-record-service.key';

        if (!is_file($keyPath) || !is_readable($keyPath)) {
            throw new RuntimeException('Patient Record service API key is not configured.');
        }

        $expectedKey = trim((string) file_get_contents($keyPath));

        return $expectedKey !== '' && hash_equals($expectedKey, $providedKey);
    }
}
