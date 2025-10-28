<?php
require __DIR__ . '/../../../Auth/dbconfig.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Receber os dados do formulário
    $serviceDate = $_POST['serviceDate'];
    $serviceStartTime = $_POST['serviceStartTime'];
    $paxADT = $_POST['paxADT'];
    $paxCHD = $_POST['paxCHD'];
    $serviceStartPoint = $_POST['serviceStartPoint'];
    $serviceTargetPoint = $_POST['serviceTargetPoint'];
    $driver = $_POST['driver']; // "later" ou um ID de condutor

    try {
        // Preparar a query para inserir a viagem na tabela "Services"
        $stmt = $pdo->prepare("INSERT INTO Services (serviceDate, serviceStartTime, paxADT, paxCHD, serviceStartPoint, serviceTargetPoint) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$serviceDate, $serviceStartTime, $paxADT, $paxCHD, $serviceStartPoint, $serviceTargetPoint]);

        // Obter o ID da viagem recém-criada
        $rideId = $pdo->lastInsertId();

        // Se um condutor foi selecionado, associamos à viagem
        if ($driver !== "later") {
            $stmtDriver = $pdo->prepare("INSERT INTO Services_Rides (RideID, UserID) VALUES (?, ?)");
            $stmtDriver->execute([$rideId, $driver]);
        }

        // Redirecionar para a página de gestão de viagens com sucesso
        header("Location: ManageRides.php?success=ride_created");
        exit();
    } catch (PDOException $e) {
        die("Erro ao criar viagem: " . $e->getMessage());
    }
}
?>
