<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
use App\Http\AuthMiddleware;
use App\Http\Controllers\Api\TripReportController;
AuthMiddleware::handle(1, 2); // admin or driver
(new TripReportController())->send();
