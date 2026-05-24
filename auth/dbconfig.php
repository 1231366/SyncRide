<?php

declare(strict_types=1);

/**
 * Legacy compatibility shim.
 *
 * Kept on disk only so the pages that have not yet been migrated to the
 * new App\ namespace (everything under /Includes/dist/pages) continue to
 * work without edit. New code MUST NOT require this file — use
 * App\Support\Database::connection() instead.
 */

require_once __DIR__ . '/../bootstrap.php';

use App\Support\Database;
use App\Support\Session;

Session::start();

/** @var PDO $pdo retained for backward compatibility with legacy pages */
$pdo = Database::connection();

// --- Legacy remember-me hook (preserves prior behaviour exactly) ------------
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    try {
        $service = new App\Auth\RememberMeService($pdo);
        $user = $service->consume();
        if ($user) {
            $_SESSION['user_id']            = (int) $user['id'];
            $_SESSION['email']              = $user['email'];
            $_SESSION['role']               = (int) $user['role'];
            $_SESSION['name']               = $user['name'];
            $_SESSION['profile_photo_path'] = $user['profile_photo_path'] ?? null;
        }
    } catch (Throwable $e) {
        error_log('remember_me bridge failure: ' . $e->getMessage());
    }
}
