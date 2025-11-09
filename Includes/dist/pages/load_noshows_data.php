<?php
session_start();
require __DIR__ . '/../../../Auth/dbconfig.php';

// Apenas admins podem aceder
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    echo json_encode(['data' => []]);
    exit();
}

try {
    // ===================================
    // CONSULTA SQL CORRIGIDA
    // ===================================
    // LEFT JOIN assegura que apanhamos todos os No Shows,
    // mesmo sem condutor associado em Services_Rides.
    $sql = "SELECT 
                s.ID, 
                s.serviceDate, 
                s.serviceStartTime, 
                s.serviceStartPoint, 
                s.serviceTargetPoint, 
                s.noShowPhotoPath,
                u.name AS driverName
            FROM Services s
            LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
            LEFT JOIN Users u ON sr.UserID = u.ID
            WHERE CAST(s.noShowStatus AS CHAR) = '1'
            ORDER BY s.serviceDate DESC, s.serviceStartTime DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $viagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];

    foreach ($viagens as $viagem) {
        // === FORMATAÇÃO DE DADOS ===
        $data_hora = htmlspecialchars($viagem['serviceDate'] . ' ' . substr($viagem['serviceStartTime'], 0, 5));

        // Condutor pode ser nulo
        $driver_name_text = $viagem['driverName'] ? htmlspecialchars($viagem['driverName']) : 'N.A.';
        $condutor_html = '<span class="badge text-bg-secondary">' . $driver_name_text . '</span>';

        // Rota formatada
        $rota_html = htmlspecialchars($viagem['serviceStartPoint'] . ' → ' . $viagem['serviceTargetPoint']);

        // === CORREÇÃO DO LINK DA FOTO ===
        // Detecta automaticamente se está em localhost ou produção
        if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
            // Ambiente local — altere o nome da pasta do projeto se necessário
            $base_url = "http://localhost/project"; 
        } else {
            // Ambiente de produção
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            $base_url = $protocol . $_SERVER['HTTP_HOST'];
        }

        // Construir URL completo da foto (garante caminho correto)
        $full_photo_url = $base_url . '/' . ltrim($viagem['noShowPhotoPath'], '/');

        // === PREPARAÇÃO DO LINK DE EMAIL ===
        $email_subject = "NO Show - Viagem ID " . $viagem['ID'];

        $email_body_parts = [
            "Segue o comprovativo do No Show para a Viagem #" . $viagem['ID'] . ".",
            "",
            "Link para a foto: " . $full_photo_url,
            "",
            "--- Detalhes da Viagem ---",
            "ID: " . $viagem['ID'],
            "Condutor: " . $driver_name_text,
            "Data: " . htmlspecialchars($viagem['serviceDate']),
            "Hora: " . htmlspecialchars(substr($viagem['serviceStartTime'], 0, 5)),
            "Recolha: " . htmlspecialchars($viagem['serviceStartPoint']),
            "Entrega: " . htmlspecialchars($viagem['serviceTargetPoint']),
        ];
        $email_body = rawurlencode(implode("\r\n", $email_body_parts));

        $mailto_link = "mailto:tiagofsilva04@gmail.com?subject=" . rawurlencode($email_subject) . "&body=" . $email_body;

        // === BOTÕES DE AÇÃO ===
        $acoes_html = '<div class="btn-group-sm d-flex justify-content-center">';

        // Botão Ver Foto
        $acoes_html .= '<a href="#" class="btn btn-info rounded-circle" 
                           data-bs-toggle="tooltip" title="Ver Foto"
                           onclick="event.preventDefault(); openPhotoModal(' . $viagem['ID'] . ', \'' . htmlspecialchars($viagem['noShowPhotoPath']) . '\');">
                           <i class="bi bi-camera-fill"></i>
                        </a>';

        // Botão Reencaminhar por Email
        $acoes_html .= '<a href="' . $mailto_link . '" class="btn btn-primary rounded-circle" 
                           data-bs-toggle="tooltip" title="Reencaminhar por Email"
                           target="_blank">
                           <i class="bi bi-send-fill"></i>
                        </a>';

        $acoes_html .= '</div>';

        // === ADICIONAR AO ARRAY FINAL ===
        $data[] = [
            'id' => $viagem['ID'],
            'data_hora' => $data_hora,
            'condutor' => $condutor_html,
            'rota' => $rota_html,
            'acoes' => $acoes_html
        ];
    }

    // === OUTPUT JSON ===
    echo json_encode(['data' => $data]);

} catch (PDOException $e) {
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
?>
