<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
use App\Http\AuthMiddleware;
use App\Http\Controllers\Api\FlightStatusController;
AuthMiddleware::handle(1, 2, 3); // admin, driver or partner
(new FlightStatusController())->show();
