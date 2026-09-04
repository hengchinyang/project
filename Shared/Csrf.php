<?php

declare(strict_types=1);

/** CSRF protection for browser forms that change Patient Record data. */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        if (!isset($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    public static function input(): string
    {
        return '<input type="hidden" name="_csrf" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '">';
    }

    public static function verify(?string $providedToken): bool
    {
        $expectedToken = $_SESSION[self::SESSION_KEY] ?? null;

        return is_string($expectedToken)
            && is_string($providedToken)
            && $providedToken !== ''
            && hash_equals($expectedToken, $providedToken);
    }
}
