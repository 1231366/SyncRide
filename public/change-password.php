<?php
declare(strict_types=1);
require __DIR__ . '/../bootstrap.php';
use App\Http\AuthMiddleware;
use App\Http\Controllers\ProfileController;
AuthMiddleware::handle(0, 1, 2, 3);
(new ProfileController())->changePassword();
