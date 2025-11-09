<?php
session_start();
require __DIR__ . '/../../../Auth/dbconfig.php';

// Prepara a resposta JSON
header('Content-Type: application/json');

// Apenas utilizadores logados (condutores ou admin) podem fazer isto
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

// Receber os dados do JavaScript (fetch)
$input = json_decode(file_get_contents('php://input'), true);
$trip_id = $input['trip_id'] ?? null;
$image_data_url = $input['image_data'] ?? null;

if (!$trip_id || !$image_data_url) {
    echo json_encode(['success' => false, 'message' => 'Dados em falta.']);
    exit();
}

// --- VERIFICAÇÃO DE PASTA (DEBUG MELHORADO) ---

$upload_dir_name = 'uploads/no_shows/';
$upload_dir_path = __DIR__ . '/' . $upload_dir_name; // Caminho absoluto no servidor

// 1. A pasta existe?
if (!is_dir($upload_dir_path)) {
    // Tenta criar a pasta
    if (!mkdir($upload_dir_path, 0775, true)) {
        echo json_encode(['success' => false, 'message' => "Erro Crítico: A pasta '{$upload_dir_name}' não existe e não consegui criá-la. (Verifica permissões na pasta 'pages')"]);
        exit();
    }
}

// 2. A pasta tem permissões de escrita?
if (!is_writable($upload_dir_path)) {
    echo json_encode(['success' => false, 'message' => "Erro de Permissão: A pasta '{$upload_dir_name}' não tem permissões de escrita. (Tenta CHMOD 775 ou 777)"]);
    exit();
}
// --- FIM DA VERIFICAÇÃO ---


// 1. Processar a Imagem Base64
// Remove o prefixo "data:image/jpeg;base64,"
$image_parts = explode(";base64,", $image_data_url);
if (count($image_parts) < 2) {
    echo json_encode(['success' => false, 'message' => 'Erro: Formato de imagem inválido.']);
    exit();
}
$image_base64 = base64_decode($image_parts[1]);

// 2. Criar um nome de ficheiro e caminho
$file_name = 'noshow_' . $trip_id . '_' . time() . '.jpg';
$file_path_relative = $upload_dir_name . $file_name; // Caminho relativo para a BD
$full_server_path = $upload_dir_path . $file_name; // Caminho absoluto para guardar

// 3. Salvar a imagem no servidor
if (file_put_contents($full_server_path, $image_base64)) {
    
    // 4. Atualizar a Base de Dados
    try {
        $sql = "UPDATE Services 
                SET noShowStatus = 1, 
                    noShowPhotoPath = :photo_path 
                WHERE ID = :trip_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':photo_path' => $file_path_relative, // Salva o caminho relativo
            ':trip_id' => $trip_id
        ]);

        echo json_encode(['success' => true, 'message' => 'No Show reportado com sucesso!']);

    } catch (PDOException $e) {
        // Se falhar a BD, apaga a foto que foi guardada
        unlink($full_server_path);
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar a base de dados: ' . $e->getMessage()]);
    }

} else {
    // Esta é a mensagem de erro que estavas a receber
    echo json_encode(['success' => false, 'message' => 'Erro ao guardar a imagem no servidor. (file_put_contents falhou)']);
}
?>