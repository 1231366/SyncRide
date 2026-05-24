<?php
// Includes/dist/pages/location-update.php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

$rawInput = file_get_contents('php://input');
require __DIR__ . '/../../auth/dbconfig.php';
$data = json_decode($rawInput, true);

file_put_contents('debug_gps.txt', date('H:i:s') . " - RAW: " . $rawInput . " | PARSED: " . json_encode($data) . PHP_EOL, FILE_APPEND);

if (isset($data['ride_id'], $data['lat'], $data['lng'])) {
    $driver_id = $data['driver_id'] ?? ($_SESSION['user_id'] ?? 0);
    try {
        $checkStmt = $pdo->prepare("SELECT status_id FROM Services WHERE ID = ?");
        $checkStmt->execute([$data['ride_id']]);
        $rideStatus = $checkStmt->fetchColumn();
        if ($rideStatus !== false && (int)$rideStatus === 4) {
            echo json_encode(['success' => true, 'skipped' => 'ride_completed']);
            exit;
        }

        $sql = "INSERT INTO RideTracking (ride_id, driver_id, latitude, longitude, speed, heading)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                latitude = VALUES(latitude),
                longitude = VALUES(longitude),
                speed = VALUES(speed),
                heading = VALUES(heading),
                last_update = NOW()";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $data['ride_id'],
            $driver_id,
            $data['lat'],
            $data['lng'],
            $data['speed'] ?? 0,
            $data['heading'] ?? 0
        ]);

        file_put_contents('debug_gps.txt', date('H:i:s') . " - INSERT OK | ride=" . $data['ride_id'] . " driver=" . $driver_id . " lat=" . $data['lat'] . " lng=" . $data['lng'] . PHP_EOL, FILE_APPEND);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        file_put_contents('debug_gps.txt', date('H:i:s') . " - ERRO SQL: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    file_put_contents('debug_gps.txt', date('H:i:s') . " - DADOS INCOMPLETOS | data=" . json_encode($data) . PHP_EOL, FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
}
?>