<?php
// Configurações da base de dados
$host = 'localhost';
$dbname = 'SyncRide';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
        $username, 
        $password, 
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Ativa os erros
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Retorna arrays associativos por padrão
            PDO::ATTR_PERSISTENT => true // Mantém a conexão aberta para melhorar a performance
        ]
    );
} catch (PDOException $e) {
    die("Erro na conexão com a base de dados: " . $e->getMessage());
}
?>
