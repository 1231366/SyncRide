<?php
// Includes/dist/pages/location-upload.php

// HEADERS CORS — necessários quando a app Capacitor não corre na mesma origem do servidor
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json');

// Pre-flight (iOS WKWebView envia OPTIONS antes do POST)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . '/../../auth/dbconfig.php';

$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['ride_id'], $data['lat'], $data['lng'], $data['driver_id'])) {
    try {
        // GUARD: rejeita posições atrasadas de viagens já terminadas (status 4)
        $checkStmt = $pdo->prepare("SELECT status_id FROM Services WHERE ID = ?");
        $checkStmt->execute([$data['ride_id']]);
        $rideStatus = $checkStmt->fetchColumn();
        if ($rideStatus !== false && (int)$rideStatus === 4) {
            echo json_encode(['success' => true, 'skipped' => 'ride_completed']);
            exit;
        }

        // Usa "ON DUPLICATE KEY UPDATE" para manter apenas a ULTIMA localização
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
            $data['driver_id'],
            $data['lat'],
            $data['lng'],
            $data['speed'] ?? 0,
            $data['heading'] ?? 0
        ]);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>