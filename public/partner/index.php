<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
use App\Http\AuthMiddleware;
use App\Http\Controllers\Partner\DashboardController;
AuthMiddleware::handle(3);
(new DashboardController())->index();
