<?php

declare(strict_types=1);

/** Starts a hardened application session without inventing a logged-in user. */
final class SessionSecurity
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        if (!session_start()) {
            throw new RuntimeException('The secure application session could not be started.');
        }

        if (!isset($_SESSION['_session_initialized'])) {
            session_regenerate_id(true);
            $_SESSION['_session_initialized'] = time();
        }

        self::normaliseAuthenticationContract();
    }

    /**
     * The Authentication/Appointment module currently publishes `userId`.
     * Adapt that legacy name at this module boundary so application code only
     * depends on the agreed snake_case session contract.
     */
    private static function normaliseAuthenticationContract(): void
    {
        if (($_SESSION['loggedIn'] ?? false) === true
            && is_string($_SESSION['userId'] ?? null)
            && trim($_SESSION['userId']) !== '') {
            // Always synchronise, because the same browser may log out and
            // then log in as a different User Management account.
            $_SESSION['user_id'] = trim($_SESSION['userId']);
        }

        if (strtolower((string) ($_SESSION['role'] ?? '')) === 'patient'
            && preg_match('/^PA[0-9]{3,8}$/', (string) ($_SESSION['user_id'] ?? '')) === 1) {
            $_SESSION['patient_id'] = $_SESSION['user_id'];
        } elseif (($_SESSION['loggedIn'] ?? false) === true) {
            unset($_SESSION['patient_id']);
        }
    }

    public static function isAuthenticated(): bool
    {
        $role = strtolower(trim((string) ($_SESSION['role'] ?? '')));
        $userId = trim((string) ($_SESSION['user_id'] ?? ''));
        $username = trim((string) ($_SESSION['username'] ?? ''));

        if (!in_array($role, ['patient', 'doctor', 'admin'], true) || $userId === '' || $username === '') {
            return false;
        }

        return $role !== 'patient'
            || preg_match('/^PA[0-9]{3,8}$/', (string) ($_SESSION['patient_id'] ?? '')) === 1;
    }

    private static function isHttps(): bool
    {
        return isset($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    }
}
