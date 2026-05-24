<?php

declare(strict_types=1);

require __DIR__ . '/../../auth/dbconfig.php';

if (!isset($_SESSION['user_id'], $_SESSION['role']) || (int) $_SESSION['role'] !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

header('Content-Type: application/json');

try {
    $pdo->beginTransaction();
    $pdo->exec('DELETE FROM Logs');
    $pdo->prepare("INSERT INTO Logs (Action, date) VALUES ('Action history cleared', NOW())")->execute();
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('clear-logs failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to clear logs.']);
}
