<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

App\Http\AuthMiddleware::handle(App\Models\User::ROLE_ADMIN);
(new App\Http\Controllers\Admin\MaintenanceController())->wipeRides();
