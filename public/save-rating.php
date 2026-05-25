<?php
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

require __DIR__ . '/../bootstrap.php';

use App\Support\Database;

$data   = json_decode((string) file_get_contents('php://input'), true) ?? [];
$rideId = isset($data['ride_id']) ? (int) $data['ride_id'] : 0;
$rating = isset($data['rating'])  ? (float) $data['rating']  : 0.0;

if ($rideId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing ride_id']);
    exit;
}

// Rating must be between 1 and 5 (inclusive, accepts .5 steps).
if ($rating < 1.0 || $rating > 5.0) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Invalid rating value']);
    exit;
}

try {
    $pdo  = Database::connection();

    // Only allow rating a ride that exists and is completed (status_id = 4).
    // This prevents anonymous users from spamming ratings on arbitrary IDs.
    $check = $pdo->prepare('SELECT 1 FROM Services WHERE ID = :id AND status_id = 4 LIMIT 1');
    $check->execute(['id' => $rideId]);
    if (!$check->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ride not found or not completed']);
        exit;
    }

    $stmt = $pdo->prepare('UPDATE Services SET driver_rating = :r WHERE ID = :id');
    $stmt->execute(['r' => $rating, 'id' => $rideId]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
