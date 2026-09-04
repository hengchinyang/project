<?php

declare(strict_types=1);

/** Reads database settings from the environment with local XAMPP defaults. */
final class DatabaseConfig
{
    /** @return array<string, string> */
    public static function connection(): array
    {
        $secrets = self::secretFile();
        return [
            'driver' => 'mysql',
            'host' => self::value('MEDICARE_DB_HOST', (string) ($secrets['host'] ?? '127.0.0.1')),
            'port' => self::value('MEDICARE_DB_PORT', (string) ($secrets['port'] ?? '3306')),
            'database' => self::value('MEDICARE_DB_DATABASE', (string) ($secrets['database'] ?? 'medicare_connect2')),
            'username' => self::value('MEDICARE_DB_USERNAME', (string) ($secrets['username'] ?? 'patient_record_app')),
            'password' => self::value('MEDICARE_DB_PASSWORD', (string) ($secrets['password'] ?? '')),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_general_ci',
            'prefix' => '',
        ];
    }

    /** @return array<string, scalar> */
    private static function secretFile(): array
    {
        $configured = getenv('MEDICARE_DB_CONFIG_FILE');
        $path = is_string($configured) && $configured !== ''
            ? $configured
            : dirname(__DIR__, 3) . '/medicare-connect-secrets/patient-record-db.json';
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Patient Record database credentials are not configured.');
        }
        $decoded = json_decode((string) file_get_contents($path), true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || trim((string) ($decoded['password'] ?? '')) === '') {
            throw new RuntimeException('Patient Record database credential file is invalid.');
        }
        return $decoded;
    }

    private static function value(string $name, string $default): string
    {
        $value = getenv($name);

        return is_string($value) ? $value : $default;
    }
}
