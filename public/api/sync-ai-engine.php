<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';

use App\Http\AuthMiddleware;
use App\Http\Controllers\Api\AiSyncController;

AuthMiddleware::handle(0, 1); // super-admin or company admin

$controller = new AiSyncController();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    match ($_GET['action'] ?? 'send') {
        'create_conversation' => $controller->createConversation(),
        'delete_conversation'  => $controller->deleteConversation(),
        default                => $controller->send(),
    };
} elseif (isset($_GET['conversations'])) {
    $controller->conversationsList();
} elseif (isset($_GET['history'])) {
    $controller->history();
} else {
    $controller->send();
}
