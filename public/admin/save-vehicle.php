<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

App\Http\AuthMiddleware::handle(App\Models\User::ROLE_ADMIN);

$controller = new App\Http\Controllers\Admin\FleetController();
if (($_GET['action'] ?? '') === 'delete') {
    $controller->delete();
}
$controller->save();
