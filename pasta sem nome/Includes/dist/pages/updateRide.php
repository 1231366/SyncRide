<?php
require __DIR__ . '/../../../Auth/dbconfig.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $viagemId = $_POST['edit_trip_id'] ?? null;
    $condutorId = $_POST['edit_conductor_id'] ?? null;
    $numPassageiros = $_POST['edit_passengers'] ?? null;
    $localRecolha = $_POST['edit_origin'] ?? null;
    $localEntrega = $_POST['edit_destination'] ?? null;

    if ($viagemId) {
        try {
            // Atualizar os dados da viagem
            $sql = "UPDATE Services 
                    SET paxADT = :paxADT, 
                        serviceStartPoint = :startPoint, 
                        serviceTargetPoint = :targetPoint
                    WHERE ID = :rideID";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':paxADT', $numPassageiros, PDO::PARAM_INT);
            $stmt->bindParam(':startPoint', $localRecolha, PDO::PARAM_STR);
            $stmt->bindParam(':targetPoint', $localEntrega, PDO::PARAM_STR);
            $stmt->bindParam(':rideID', $viagemId, PDO::PARAM_INT);
            $stmt->execute();

            // Se um condutor for escolhido, atualizar a tabela Services_Rides
            if ($condutorId) {
                $sql = "UPDATE Services_Rides SET UserID = :userID WHERE RideID = :rideID";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':userID', $condutorId, PDO::PARAM_INT);
                $stmt->bindParam(':rideID', $viagemId, PDO::PARAM_INT);
                $stmt->execute();
            }

            // Redireciona para ManageRides.php com sucesso
            header('Location: ManageRides.php?success=rideUpdated');
            exit();
        } catch (PDOException $e) {
            // Redireciona com erro
            header('Location: ManageRides.php?success=false&message=' . urlencode('Erro ao atualizar viagem: ' . $e->getMessage()));
            exit();
        }
    } else {
        // Redireciona com erro
        header('Location: ManageRides.php?success=false&message=' . urlencode('Dados inválidos!'));
        exit();
    }
} else {
    // Redireciona com erro
    header('Location: ManageRides.php?success=false&message=' . urlencode('Método não permitido!'));
    exit();
}
?>
