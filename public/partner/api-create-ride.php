<?php
session_start();
require_once __DIR__ . '/../../auth/dbconfig.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 3) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

try {
    // Validação básica
    if(empty($_POST['date']) || empty($_POST['time']) || empty($_POST['client_name'])) {
        throw new Exception("Preencha os campos obrigatórios.");
    }

    $sql = "INSERT INTO Services (
                serviceDate, 
                serviceStartTime, 
                serviceStartPoint, 
                serviceTargetPoint, 
                paxADT, 
                paxCHD, 
                NomeCliente, 
                FlightNumber, 
                partner_id, 
                status_pedido, 
                serviceType, 
                ClientNumber, 
                total_price, 
                has_key
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pendente', 1, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    
    $result = $stmt->execute([
        $_POST['date'],
        $_POST['time'],
        $_POST['pickup'],
        $_POST['dropoff'],
        $_POST['pax_adt'],
        $_POST['pax_chd'] ?? 0,
        $_POST['client_name'],
        $_POST['flight'] ?? '',
        $_SESSION['user_id'],
        $_POST['client_phone'] ?? '',
        $_POST['price'] ?? null,
        $_POST['has_key'] ?? 0 // Captura 1 para Sim e 0 para Não
    ]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Erro ao inserir na base de dados.");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}