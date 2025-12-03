<?php
// Includes/dist/pages/api_stop_tracking.php
header('Content-Type: application/json');
// Desativa display de erros (para evitar que HTML se misture com JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 1. Tentar iniciar a sessão (é o ponto de falha mais comum)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. CARREGAR DB (Usando o caminho que confirmaste)
require __DIR__ . '/../../../auth/dbconfig.php'; 

// 3. VERIFICAR AUTENTICAÇÃO (Crucial para o driver_id)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado: Sessão expirada ou falhou no carregamento.']);
    exit();
}

// 4. RECEBER DADOS
$input = file_get_contents('php://input');
$data = json_decode($input, true);

$rideId = $data['ride_id'] ?? null;
$driverId = $_SESSION['user_id'];

if ($rideId) {
    try {
        // 5. EXECUTAR LIMPEZA (Garantimos que só apaga a dele)
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
        echo json_encode(['success' => false, 'error' => 'Erro SQL: Falha na eliminação. ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID da viagem em falta.']);
}
?>