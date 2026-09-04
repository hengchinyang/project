<?php

declare(strict_types=1);

final class AppointmentServiceConfig
{
    public static function url(): string
    {
        $value = getenv('MEDICARE_APPOINTMENT_ASSIGNMENTS_URL');
        return is_string($value) && $value !== ''
            ? $value
            : 'http://localhost/UserManagement/?action=api-doctor-patient-assignments';
    }

    public static function apiKey(): string
    {
        $configured = getenv('MEDICARE_APPOINTMENT_API_KEY_FILE');
        $path = is_string($configured) && $configured !== ''
            ? $configured
            : dirname(__DIR__, 3) . '/medicare-connect-secrets/appointment-service.key';
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Appointment service API key is not configured.');
        }
        $key = trim((string) file_get_contents($path));
        if ($key === '') throw new RuntimeException('Appointment service API key is empty.');
        return $key;
    }
}
