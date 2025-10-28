<?php
// Configurações de conexão
$host = 'localhost'; // Nome do host
$username = 'root';  // Nome do utilizador
$password = '';      // Palavra-passe
$database = 'SyncRide'; // Nome da base de dados

// Conexão com a base de dados
$conn = new mysqli($host, $username, $password, $database);

// Verificar se a conexão foi bem-sucedida
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com a base de dados.']));
}

// Iniciar transação
$conn->begin_transaction();

try {
    // Apagar da tabela Services_Rides
    $conn->query("DELETE FROM Services_Rides");

    // Apagar da tabela Services
    $conn->query("DELETE FROM Services");

    // Registar a ação na tabela Logs
    $stmt = $conn->prepare("INSERT INTO Logs (Action) VALUES ('Eliminou todas as viagens')");
    $stmt->execute();

    // Confirmar a transação
    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Viagens eliminadas com sucesso e log registado!']);
} catch (Exception $e) {
    // Reverter alterações em caso de erro
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Erro ao eliminar as viagens: ' . $e->getMessage()]);
}
?>
