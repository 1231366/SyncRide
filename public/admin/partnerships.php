<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
use App\Http\AuthMiddleware;
use App\Http\Controllers\Admin\PartnershipsController;
AuthMiddleware::handle(1);
(new PartnershipsController())->index();
