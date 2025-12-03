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
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_PERSISTENT => true
        ]
    );
} catch (PDOException $e) {
    // Não mostrar erros na tela em produção, usar log
    error_log("Erro na conexão: " . $e->getMessage());
    die("Erro de conexão. Tente mais tarde.");
}

// --- SISTEMA DE AUTO-LOGIN (REMEMBER ME) ---
// Inicia sessão APENAS se ainda não estiver ativa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Se não estiver logado, mas tiver o cookie, tenta logar
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'])) {
    $cookieToken = $_COOKIE['remember_me'];
    $tokenHash = hash('sha256', $cookieToken);

    try {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE remember_token = ?");
        $stmt->execute([$tokenHash]);
        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            
            // Renovar o cookie
            setcookie('remember_me', $cookieToken, time() + (86400 * 30), "/", "", false, true);
        }
    } catch (Exception $e) {
        // Ignorar erro silenciosamente
    }
}
?>