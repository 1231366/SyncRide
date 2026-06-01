<?php

declare(strict_types=1);

namespace App\Support;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // NOTE: we deliberately keep PHP's default cookie name (PHPSESSID).
        // Legacy pages under /Includes/dist/pages call session_start() with
        // no prior session_name(), so they expect PHPSESSID. Overriding the
        // name here would create two parallel sessions and break login.
        $lifetime = (int) Env::get('SESSION_LIFETIME', 86400);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'secure'   => Env::get('APP_ENV') === 'production',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    public static function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function role(): ?int
    {
        return isset($_SESSION['role']) ? (int) $_SESSION['role'] : null;
    }

    /** Returns the company the current user belongs to, or null for super-admin (sees all). */
    public static function companyId(): ?int
    {
        return isset($_SESSION['company_id']) ? (int) $_SESSION['company_id'] : null;
    }

    public static function isAuthenticated(): bool
    {
        return self::userId() !== null;
    }

    /** Returns (and lazily creates) the CSRF token for the current session. */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(): bool
    {
        $token = $_POST['csrf_token']
            ?? $_SERVER['HTTP_X_CSRF_TOKEN']
            ?? '';
        $stored = $_SESSION['csrf_token'] ?? '';
        return $stored !== '' && hash_equals($stored, $token);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }
        session_destroy();
    }
}
