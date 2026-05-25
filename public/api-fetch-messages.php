<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

require __DIR__ . '/../bootstrap.php';

use App\Support\Database;
use App\Support\Session;

if (session_status() === PHP_SESSION_NONE) { Session::start(); }

// Must be authenticated — this endpoint is only called by logged-in drivers and admins.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$pdo      = Database::connection();
$rideId   = isset($_GET['ride_id'])   ? (int) $_GET['ride_id']           : 0;
$userType = $_GET['user_type'] ?? null;

if ($rideId <= 0 || !in_array($userType, ['client', 'driver'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing ride_id or user_type']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT sender_type, message, timestamp FROM ChatMessages WHERE ride_id = ? ORDER BY timestamp ASC');
    $stmt->execute([$rideId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $readColumn  = ($userType === 'driver') ? 'is_read_by_driver' : 'is_read_by_client';
    $otherSender = ($userType === 'driver') ? 'client' : 'driver';

    $sql  = "UPDATE ChatMessages SET {$readColumn} = 1 WHERE ride_id = ? AND sender_type = ? AND {$readColumn} = 0";
    $upd  = $pdo->prepare($sql);
    $upd->bindValue(1, $rideId,      PDO::PARAM_INT);
    $upd->bindValue(2, $otherSender, PDO::PARAM_STR);
    $upd->execute();

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (PDOException $e) {
    error_log('Chat Fetch Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
