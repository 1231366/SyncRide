<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\Support\Session;

Session::start();

// Already logged in → go to their dashboard
if (Session::isAuthenticated()) {
    $routes = [0 => '/SRMT/public/superadmin/', 1 => '/SRMT/public/admin/', 2 => '/SRMT/public/driver/', 3 => '/SRMT/public/partner/'];
    header('Location: ' . ($routes[Session::role()] ?? '/SRMT/public/'));
    exit;
}

(new App\Http\Controllers\Auth\RegisterController())->handle();
