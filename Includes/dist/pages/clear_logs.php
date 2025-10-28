<?php
// Conectar à base de dados
$conn = new mysqli('localhost', 'root', '', 'SyncRide');

// Verificar erro na conexão
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Erro na conexão com a base de dados.']));
}

// Apagar todos os registos da tabela Logs
$sql = "DELETE FROM Logs";

if ($conn->query($sql) === TRUE) {
    // Inserir log da ação de limpeza
    $conn->query("INSERT INTO Logs (Action, date) VALUES ('Histórico de ações limpo', NOW())");

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao limpar o histórico: ' . $conn->error]);
}
?>
