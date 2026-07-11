<?php

declare(strict_types=1);

/**
 * Front controller — SyncRide.
 *
 * Single web entry point. Boots the application, starts the session,
 * applies remember-me if a cookie is present, and either redirects an
 * authenticated user to their role dashboard or renders the login view.
 */

require __DIR__ . '/../bootstrap.php';

use App\Auth\RememberMeService;
use App\Support\Database;
use App\Support\Session;

Session::start();

// Auto sign-in via persistent cookie -----------------------------------------
if (!Session::isAuthenticated()) {
    $service = new RememberMeService(Database::connection());
    if (($user = $service->consume()) !== null) {
        Session::hydrateFromUser($user);
    }
}

// Role routing for authenticated users ---------------------------------------
if (Session::isAuthenticated()) {
    $role = Session::role();
    $dashboards = [
        1 => '/SRMT/public/admin/',
        2 => '/SRMT/public/driver/',
        3 => '/SRMT/public/partner/',
    ];
    if (isset($dashboards[$role])) {
        header('Location: ' . $dashboards[$role]);
        exit;
    }
}

// Otherwise render the login view --------------------------------------------
$errorCode = $_GET['error'] ?? null;
require __DIR__ . '/../resources/views/auth/login.php';
