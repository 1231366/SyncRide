<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
use App\Http\AuthMiddleware;
use App\Http\Controllers\Api\AiSyncController;
AuthMiddleware::handle(0, 1); // super-admin or company admin
(new AiSyncController())->index();
