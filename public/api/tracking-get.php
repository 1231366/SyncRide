<?php
header('Content-Type: application/json');
// Desativa erros de HTML para não estragar o JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

require __DIR__ . '/../../auth/dbconfig.php';

$rideId = $_GET['ride_id'] ?? null;

try {
    if ($rideId) {
        // MODO CLIENTE (Link específico)
        $stmt = $pdo->prepare("
            SELECT t.*, s.serviceTargetPoint 
            FROM RideTracking t 
            LEFT JOIN Services s ON t.ride_id = s.ID 
            WHERE t.ride_id = ?
        ");
        $stmt->execute([$rideId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Se não encontrar, devolve array vazio em vez de erro
        if(!$data) $data = null;
        
        echo json_encode(['success' => true, 'data' => $data]);

    } else {
        // MODO ADMIN (Ver tudo)
        // serviceDate + status_id + license_plate são necessários para o live_map filtrar
        // e mostrar dados completos no driver sheet.
        $sql = "
            SELECT
                t.ride_id, t.driver_id, t.latitude, t.longitude, t.speed, t.heading, t.last_update,
                COALESCE(u.name, CONCAT('Condutor ', t.driver_id)) AS driver_name,
                COALESCE(s.NomeCliente, 'Desconhecido') AS NomeCliente,
                COALESCE(s.serviceStartPoint, '') AS serviceStartPoint,
                COALESCE(s.serviceTargetPoint, 'N/A') AS serviceTargetPoint,
                s.serviceDate,
                s.status_id,
                v.license_plate AS vehicle_plate
            FROM RideTracking t
            LEFT JOIN Users u ON t.driver_id = u.id
            LEFT JOIN Services s ON t.ride_id = s.ID
            LEFT JOIN Vehicles v ON u.assigned_vehicle_id = v.id
        ";

        $stmt = $pdo->query($sql);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $data]);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>