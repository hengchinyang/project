<?php

declare(strict_types=1);

final class PharmacyServiceConfig
{
    public static function url(): string
    {
        $configured = getenv('MEDICARE_PHARMACY_API_URL');

        return is_string($configured) && $configured !== ''
            ? $configured
            : 'http://localhost/MediCareConnect-Pharmacy-Lawliet/MediCareConnect-Pharmacy-Lawliet/api/pharmacy';
    }

    public static function apiKey(): string
    {
        $configuredPath = getenv('MEDICARE_PHARMACY_API_KEY_FILE');
        $keyPath = is_string($configuredPath) && $configuredPath !== ''
            ? $configuredPath
            : dirname(__DIR__, 3) . '/medicare-connect-secrets/pharmacy-service.key';

        if (!is_file($keyPath) || !is_readable($keyPath)) {
            throw new RuntimeException('Pharmacy service API key is not configured.');
        }

        $key = trim((string) file_get_contents($keyPath));
        if ($key === '') {
            throw new RuntimeException('Pharmacy service API key is empty.');
        }

        return $key;
    }
}
