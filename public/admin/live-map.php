<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
use App\Http\AuthMiddleware;
use App\Http\Controllers\Admin\TrackingController;
AuthMiddleware::handle(1);
(new TrackingController())->liveMap();
