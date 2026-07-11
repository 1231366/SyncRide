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

        // Shared hosts GC /tmp sessions after ~24 min regardless of the cookie
        // lifetime, which silently kills long-lived app (WebView) sessions.
        // Keep session files under our own storage/ so gc_maxlifetime applies.
        $savePath = dirname(__DIR__, 2) . '/storage/sessions';
        if (!is_dir($savePath)) {
            @mkdir($savePath, 0700, true);
        }
        if (is_dir($savePath) && is_writable($savePath)) {
            session_save_path($savePath);
            @ini_set('session.gc_maxlifetime', (string) $lifetime);
        }

        session_start();
    }

    /**
     * Populate the session from a Users row. Single source of truth used by
     * the login controller, the front controller and the remember-me restore
     * in AuthMiddleware — so every path sets the exact same keys.
     *
     * @param array<string,mixed> $user
     */
    public static function hydrateFromUser(array $user): void
    {
        $_SESSION['user_id']            = (int) $user['id'];
        $_SESSION['email']              = $user['email'];
        $_SESSION['role']               = (int) $user['role'];
        $_SESSION['name']               = $user['name'];
        $_SESSION['profile_photo_path'] = $user['profile_photo_path'] ?? null;
        // Super-admin (role=0) has no company — null means "see everything".
        // CRITICAL: without company_id the whole tenant scope breaks.
        $_SESSION['company_id']         = isset($user['company_id']) && $user['company_id'] !== null
            ? (int) $user['company_id']
            : null;
        $_SESSION['admin_lang']         = in_array($user['lang_pref'] ?? '', ['en', 'pt'], true)
            ? $user['lang_pref']
            : 'en';
    }

    public static function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function role(): ?int
    {
        return isset($_SESSION['role']) ? (int) $_SESSION['role'] : null;
    }

    public static function name(): ?string
    {
        return $_SESSION['name'] ?? null;
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
