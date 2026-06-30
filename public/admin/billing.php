<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

App\Http\AuthMiddleware::handle(App\Models\User::ROLE_ADMIN);

$action = $_GET['action'] ?? 'index';
$ctrl   = new App\Http\Controllers\Admin\BillingController();

match ($action) {
    'checkout'    => $ctrl->checkout(),
    'cancel'      => $ctrl->cancel(),
    'reactivate'  => $ctrl->reactivate(),
    'change_plan' => $ctrl->changePlan(),
    default       => $ctrl->index(),
};
