<?php

declare(strict_types=1);

/**
 * Returns the live GPS position of every active driver, used by the
 * admin tracking map. Polled by the front-end every ~5 seconds.
 */

require __DIR__ . '/../../auth/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'], $_SESSION['role']) || (int) $_SESSION['role'] !== 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

try {
    $sql = <<<SQL
        SELECT l.driver_id,
               l.trip_id,
               l.latitude,
               l.longitude,
               l.speed,
               l.heading,
               l.last_update,
               u.name
        FROM DriverLiveLocation l
        JOIN Users u ON l.driver_id = u.id
        WHERE u.role = 2
    SQL;

    $drivers = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'drivers' => $drivers]);
} catch (Throwable $e) {
    error_log('live-locations failed: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to fetch driver locations.']);
}
