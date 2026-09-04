<?php

declare(strict_types=1);

/** Produces one consistent, escaped error page for browser requests. */
final class ErrorRenderer
{
    public static function render(int $status, string $title, string $message): void
    {
        http_response_code($status);
        require __DIR__ . '/../View/errors/status.php';
    }
}
