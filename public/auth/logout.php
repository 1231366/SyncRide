<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\Auth\AuthController;
use App\Support\Session;

Session::start();

(new AuthController())->logout();
