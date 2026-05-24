<?php
// Includes/dist/pages/tracking-stop.php

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

// Ativa a exibição de erros (para debug)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Tentar iniciar a sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. CARREGAR DB (Usando o caminho que confirmaste)
require __DIR__ . '/../../auth/dbconfig.php'; 

// 3. RECEBER DADOS
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$rideId = $data['ride_id'] ?? null;

// 4. RESOLVER driver_id: sessão (web) OU payload (Capacitor sem cookie)
$driverId = $_SESSION['user_id'] ?? ($data['driver_id'] ?? null);
if (!$driverId) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado: driver_id em falta.']);
    exit();
}

if ($rideId) {
    try {
        // 5. EXECUTAR LIMPEZA NA TABELA CORRETA (RideTracking)
        $stmt = $pdo->prepare("DELETE FROM RideTracking WHERE ride_id = ? AND driver_id = ?");
        $stmt->execute([$rideId, $driverId]);
        
        $deleted = $stmt->rowCount();
        
        // 6. Resposta de Sucesso
        echo json_encode([
            'success' => true, 
            'message' => "Tracking parado. Registos apagados: $deleted",
            'ride_id' => $rideId
        ]);

    } catch (PDOException $e) {
        // Devolve erro SQL em JSON
        echo json_encode([
            'success' => false, 
            'error' => 'Erro SQL: Falha na eliminação.', 
            'details' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID da viagem em falta.']);
}
?>