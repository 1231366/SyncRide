<?php
// api_update_location.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require __DIR__ . '/../../../Auth/dbconfig.php';

// Receber JSON da App
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['driver_id']) || !isset($input['lat']) || !isset($input['lng'])) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit();
}

$driver_id = $input['driver_id'];
$lat = $input['lat'];
$lng = $input['lng'];

try {
    // Atualiza a posição do condutor (ou cria se não existir)
    $sql = "INSERT INTO DriverLiveLocation (driver_id, latitude, longitude) 
            VALUES (:uid, :lat, :lng)
            ON DUPLICATE KEY UPDATE 
            latitude = :lat_upd, 
            longitude = :lng_upd,
            last_update = NOW()";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':uid' => $driver_id,
        ':lat' => $lat,
        ':lng' => $lng,
        ':lat_upd' => $lat,
        ':lng_upd' => $lng
    ]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    // Silencioso em produção
    echo json_encode(['success' => false]);
}
?>