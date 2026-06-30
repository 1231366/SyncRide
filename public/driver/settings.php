<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
use App\Http\AuthMiddleware;
use App\Http\Controllers\Driver\SettingsController;
AuthMiddleware::handle(2);
(new SettingsController())->index();
