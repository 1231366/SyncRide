<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';

use App\Http\AuthMiddleware;
use App\Http\Controllers\SuperAdmin\CompaniesController;

AuthMiddleware::handle(0);

$action = $_GET['action'] ?? 'index';
$ctrl   = new CompaniesController();

match ($action) {
    'store'      => $ctrl->store(),
    'update'     => $ctrl->update(),
    'destroy'    => $ctrl->destroy(),
    'store_user' => $ctrl->storeUser(),
    default      => $ctrl->index(),
};
