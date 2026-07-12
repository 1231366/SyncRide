<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';

use App\Http\AuthMiddleware;
use App\Http\Controllers\Api\DriverChatController;

AuthMiddleware::handle(1, 2); // admin or driver

$controller = new DriverChatController();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    match ($_GET['action'] ?? 'send') {
        'create_topic'  => $controller->createTopic(),
        'close_topic'   => $controller->closeTopic(),
        'convert_topic' => $controller->convertTopic(),
        'pin_topic'     => $controller->pinTopic(),
        'delete_topic'  => $controller->deleteTopic(),
        default         => $controller->send(),
    };
} elseif (isset($_GET['inbox'])) {
    $controller->inbox();
} elseif (isset($_GET['count'])) {
    $controller->count();
} elseif (isset($_GET['topics'])) {
    $controller->topics();
} elseif (isset($_GET['search'])) {
    $controller->search();
} elseif (isset($_GET['recent_rides'])) {
    $controller->recentRides();
} else {
    $controller->fetch();
}
