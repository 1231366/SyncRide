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
    $pdo->exec('DELETE FROM Services_Rides');
    $pdo->exec('DELETE FROM Services');
    $pdo->prepare("INSERT INTO Logs (Action) VALUES ('All rides deleted')")->execute();
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'All rides removed and logged.',
    ]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('ride-delete failed: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to delete rides.',
    ]);
}
