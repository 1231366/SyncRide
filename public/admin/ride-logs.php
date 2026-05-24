<?php
// ride-logs.php
header('Content-Type: application/json');
require __DIR__ . '/../../auth/dbconfig.php';

if (!isset($_GET['id'])) { exit; }
$id = $_GET['id'];

try {
    // Adicionada a coluna ts_with_client na query
    $stmt = $pdo->prepare("SELECT ts_start_pickup, ts_arrived_pickup, ts_with_client, ts_start_trip, ts_completed FROM Services WHERE ID = ?");
    $stmt->execute([$id]);
    $serviceData = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $serviceData
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}