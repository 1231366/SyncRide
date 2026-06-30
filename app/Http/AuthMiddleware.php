<?php

declare(strict_types=1);

namespace App\Http;

use App\Support\Session;

/**
 * Role gate at the top of every protected entry-point.
 *
 * Roles:
 *   0 = super-admin (cross-tenant, sees all companies)
 *   1 = admin       (company admin)
 *   2 = driver
 *   3 = partner
 *
 * Usage:
 *
 *   require __DIR__ . '/../../bootstrap.php';
 *   App\Http\AuthMiddleware::handle(0);          // super-admin only
 *   App\Http\AuthMiddleware::handle(1);          // company admin only
 *   App\Http\AuthMiddleware::handle(1, 2);       // admin or driver
 *
 * The middleware starts the session, redirects unauthenticated visitors
 * back to the login page, and 403s authenticated visitors whose role
 * does not match.
 */
final class AuthMiddleware
{
    public static function handle(int ...$allowedRoles): void
    {
        Session::start();

        if (!Session::isAuthenticated()) {
            self::redirectTo('/SRMT/public/');
        }

        $role = Session::role();
        if ($allowedRoles !== [] && !in_array($role, $allowedRoles, true)) {
            self::forbidden();
        }

        // Subscription gate for company admins
        if ($role === 1) {
            BillingGate::handle();
        }
    }

    private static function redirectTo(string $location): never
    {
        header("Location: {$location}");
        exit;
    }

    private static function forbidden(): never
    {
        http_response_code(403);
        if (self::wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
        } else {
            echo '<!DOCTYPE html><meta charset="utf-8"><title>403</title><h1>403 — Forbidden</h1>';
        }
        exit;
    }

    private static function wantsJson(): bool
    {
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $xrw    = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        return str_contains($accept, 'application/json') || $xrw === 'xmlhttprequest';
    }
}
