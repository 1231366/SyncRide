<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
use App\Http\AuthMiddleware;
use App\Http\Controllers\Admin\RidesController;
AuthMiddleware::handle(1);
(new RidesController())->recall();
