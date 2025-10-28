<?php
require __DIR__ . '/../../../Auth/dbconfig.php';

// Verifica se os dados foram recebidos via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Receber os dados enviados pelo frontend
    $viagemId = isset($_POST['viagemId']) ? $_POST['viagemId'] : null;
    $condutorId = isset($_POST['condutorId']) ? $_POST['condutorId'] : null;

    // Verifica se os dados são válidos
    if ($viagemId && $condutorId) {
        try {
            // Preparar a query para inserir a relação entre a viagem e o condutor
            $stmt = $pdo->prepare("INSERT INTO Services_Rides (RideID, UserID) VALUES (?, ?)");
            $stmt->execute([$viagemId, $condutorId]);

            // Redireciona de volta para a página de gerenciamento de viagens
            header('Location: ManageRides.php?success=viagemAtribuida');
            exit; // Sempre chame exit após um redirecionamento
        } catch (PDOException $e) {
            // Em caso de erro, redireciona de volta para a página de gerenciamento de viagens
            header('Location: ManageRides.php');
            exit; // Sempre chame exit após um redirecionamento
        }
    } else {
        // Se faltar algum dado, redireciona de volta para a página de gerenciamento de viagens
        header('Location: ManageRides.php');
        exit; // Sempre chame exit após um redirecionamento
    }
} else {
    // Se a requisição não for POST, redireciona de volta para a página de gerenciamento de viagens
    header('Location: ManageRides.php');
    exit; // Sempre chame exit após um redirecionamento
}
?>

