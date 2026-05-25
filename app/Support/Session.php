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
