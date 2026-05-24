<?php

declare(strict_types=1);

/**
 * Backward-compat shim — the mobile client (mobile_auth.js) and any
 * deployed bookmarks still POST to /SRMT/auth/auth.php. Forward those
 * requests to the new App\Auth\AuthController.
 *
 * Once every client has been updated to hit /SRMT/public/auth/login.php
 * directly, this file can be deleted.
 */

require __DIR__ . '/../bootstrap.php';

use App\Auth\AuthController;
use App\Support\Session;

Session::start();

(new AuthController())->login();
