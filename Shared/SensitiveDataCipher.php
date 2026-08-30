<?php

declare(strict_types=1);

final class SensitiveDataCipher
{
    private const CIPHER = 'aes-256-gcm';
    private const PREFIX = 'enc:v1:';
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;
    private const AAD = 'medicare-connect:patient-record-remark:v1';

    private static ?self $instance = null;

    private function __construct(private readonly string $key)
    {
    }

    public static function instance(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $configuredPath = getenv('MEDICARE_ENCRYPTION_KEY_FILE');
        $keyPath = is_string($configuredPath) && $configuredPath !== ''
            ? $configuredPath
            : dirname(__DIR__, 3) . '/medicare-connect-secrets/patient-record.key';

        if (!is_file($keyPath) || !is_readable($keyPath)) {
            throw new RuntimeException(
                'Patient Record encryption key is missing or unreadable. Configure MEDICARE_ENCRYPTION_KEY_FILE.'
            );
        }

        $encodedKey = trim((string) file_get_contents($keyPath));
        $key = base64_decode($encodedKey, true);

        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('Patient Record encryption key must be a base64-encoded 32-byte key.');
        }

        self::$instance = new self($key);

        return self::$instance;
    }

    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    public function encrypt(string $plaintext): string
    {
        if (self::isEncrypted($plaintext)) {
            return $plaintext;
        }

        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            self::AAD,
            self::TAG_LENGTH
        );

        if ($ciphertext === false || strlen($tag) !== self::TAG_LENGTH) {
            throw new RuntimeException('Sensitive data encryption failed.');
        }

        return self::PREFIX . base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $storedValue): string
    {
        // Allows a controlled one-time migration of existing plaintext rows.
        if (!self::isEncrypted($storedValue)) {
            return $storedValue;
        }

        $payload = base64_decode(substr($storedValue, strlen(self::PREFIX)), true);
        if ($payload === false || strlen($payload) <= self::IV_LENGTH + self::TAG_LENGTH) {
            throw new RuntimeException('Encrypted Patient Record data is malformed.');
        }

        $iv = substr($payload, 0, self::IV_LENGTH);
        $tag = substr($payload, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($payload, self::IV_LENGTH + self::TAG_LENGTH);
        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            self::AAD
        );

        if ($plaintext === false) {
            throw new RuntimeException('Encrypted Patient Record data failed authentication.');
        }

        return $plaintext;
    }
}
