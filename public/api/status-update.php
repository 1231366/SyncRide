<?php
// status-update.php

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

date_default_timezone_set('Europe/Lisbon');
require __DIR__ . '/../../auth/dbconfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Suporta tanto JSON (Capacitor) como FormData (web)
        $rawInput = file_get_contents('php://input');
        $jsonData = json_decode($rawInput, true);
        $ride_id = $jsonData['ride_id'] ?? $_POST['ride_id'] ?? null;
        $new_status = isset($jsonData['status']) ? (int)$jsonData['status'] : (isset($_POST['status']) ? (int)$_POST['status'] : null);

        if (!$ride_id || $new_status === null) {
            throw new Exception("Dados incompletos.");
        }
        $current_time = date('Y-m-d H:i:s');
        
        $sql_part = "";
        $status_label = "";
        
        switch ($new_status) {
            case 1: 
                $sql_part = ", ts_start_pickup = :time"; 
                $status_label = "A Caminho";
                break;
            case 2: 
                $sql_part = ", ts_arrived_pickup = :time"; 
                $status_label = "Chegou ao local";
                break;
            case 5: 
                // AGORA GUARDA NA COLUNA REAL
                $sql_part = ", ts_with_client = :time"; 
                $status_label = "Com o cliente";
                break;
            case 3: 
                $sql_part = ", ts_start_trip = :time"; 
                $status_label = "Viagem Iniciada";
                break;
            case 4: 
                $sql_part = ", ts_completed = :time"; 
                $status_label = "Serviço Terminado";
                break;
        }

        $sql = "UPDATE Services SET status_id = :status $sql_part WHERE ID = :id";
        $stmt = $pdo->prepare($sql);
        
        $params = [':status' => $new_status, ':id' => $ride_id];
        if ($sql_part !== "") { $params[':time'] = $current_time; }

        if ($stmt->execute($params)) {
            // Log de auditoria
            $logMsg = "Serviço ID #$ride_id: Estado alterado para $status_label";
            $logStmt = $pdo->prepare("INSERT INTO Logs (Action, date) VALUES (?, ?)");
            $logStmt->execute([$logMsg, $current_time]);

            echo json_encode(['success' => true, 'status' => $new_status, 'message' => 'Sucesso']);
        } else {
            throw new Exception("Erro no update.");
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}