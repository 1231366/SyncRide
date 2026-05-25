<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';

use App\Http\AuthMiddleware;
use App\Http\Controllers\SuperAdmin\DashboardController;

AuthMiddleware::handle(0);
(new DashboardController())->index();
