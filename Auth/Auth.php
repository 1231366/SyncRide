<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir o arquivo de configuração da base de dados
require_once 'dbconfig.php';  // Ajuste o caminho conforme a localização do seu dbconfig.php

// Começar a sessão para armazenar as variáveis de sessão
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obter email e senha do formulário
    $email = trim($_POST['email']);
    $password = trim($_POST['pass']);

    // Verificar se os campos não estão vazios
    if (empty($email) || empty($password)) {
        die("Email ou senha não podem estar vazios.");
    }

    // Buscar o utilizador na base de dados
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verificar a password com hashing
            if (password_verify($password, $user['password'])) {
                // Autenticação bem-sucedida, guardar os dados do utilizador na sessão
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];  // Armazenando o nome do utilizador

                // Redirecionar para o painel de controlo do utilizador (conforme o tipo)
                if ($user['role'] == 1) {
                    header("Location: /SyncRide/Includes/dist/pages/admin.php");
                } else {
                    header("Location: /SyncRide/Includes/dist/pages/driver.php");
                }
                exit();
            } else {
                header("Location: ../index.php?error=senha_incorreta");
                exit();
            }
        } else {
            header("Location: ../index.php?error=utilizador_nao_encontrado");
            exit();
        }
    } catch (PDOException $e) {
        echo "Erro ao consultar o banco de dados: " . $e->getMessage();
    }
}
?>
