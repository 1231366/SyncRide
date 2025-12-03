<?php
// Auth/Auth.php - Versão Híbrida (Web Redirect + App JSON)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'dbconfig.php';
session_start();

// 1. Detetar se a chamada veio da App Móvel (AJAX/Fetch) ou de um formulário Web normal
$is_mobile_app_call = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Se for a App, forçamos a resposta JSON
if ($is_mobile_app_call) {
    header('Content-Type: application/json');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['pass']);

    if (empty($email) || empty($password)) {
        if ($is_mobile_app_call) {
            echo json_encode(['success' => false, 'message' => 'Email ou senha não podem estar vazios.']);
        } else {
            header("Location: ../index.php?error=campos_vazios");
        }
        exit();
    }

    try {
        // Usa 'Users' (Maiúsculo) conforme a estrutura da sua BD
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                
                // Login Sucesso: Inicia a sessão PHP (para compatibilidade Web)
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];

                $redirect_route = ($user['role'] == 1) ? '/SyncRide/Includes/dist/pages/admin.php' : '/SyncRide/Includes/dist/pages/driver.php';

                if ($is_mobile_app_call) {
                    // Cénario 1: App Móvel (Devolve JSON)
                    echo json_encode([
                        'success' => true,
                        'message' => 'Login bem-sucedido.',
                        'user' => [
                            'id' => $user['id'], 
                            'token' => $user['id'],
                            'role' => $user['role'],
                            'name' => $user['name']
                        ],
                        'redirect_route' => $redirect_route
                    ]);
                } else {
                    // Cénario 2: Web Browser (Redireciona)
                    header("Location: " . $redirect_route);
                }
                exit();

            } else {
                // Senha Incorreta
                if ($is_mobile_app_call) {
                    echo json_encode(['success' => false, 'message' => 'Senha incorreta.']);
                } else {
                    header("Location: ../index.php?error=senha_incorreta");
                }
                exit();
            }
        } else {
            // Utilizador não encontrado
            if ($is_mobile_app_call) {
                echo json_encode(['success' => false, 'message' => 'Utilizador não encontrado.']);
            } else {
                header("Location: ../index.php?error=utilizador_nao_encontrado");
            }
            exit();
        }
    } catch (PDOException $e) {
        if ($is_mobile_app_call) {
            echo json_encode(['success' => false, 'message' => 'Erro BD: ' . $e->getMessage()]);
        } else {
            echo "Erro ao consultar o banco de dados: " . $e->getMessage();
        }
        exit();
    }
}

// Se o método não for POST
if ($is_mobile_app_call) {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
} else {
    // Redireciona para o login se for acesso direto via GET
    header("Location: ../index.php"); 
}
?>