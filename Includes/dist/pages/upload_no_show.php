<?php
// 1. Configurações Iniciais
error_reporting(0);
ini_set('display_errors', 0);

// Define o fuso horário para Portugal
date_default_timezone_set('Europe/Lisbon');

session_start();
header('Content-Type: application/json');

// 2. Carregar PHPMailer
require __DIR__ . '/PHPMailer/Exception.php';
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 3. Ligação à Base de Dados
require __DIR__ . '/../../../auth/dbconfig.php';

// Função Log
function escreverLog($texto) {
    file_put_contents(__DIR__ . '/debug_email.txt', date('d/m/Y H:i:s') . " -> " . $texto . "\n", FILE_APPEND);
}

// 4. Segurança
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

// 5. Receber Dados
$input = json_decode(file_get_contents('php://input'), true);
$trip_id = $input['trip_id'] ?? null;
$image_data_url = $input['image_data'] ?? null;

// Latitude e Longitude
$lat = $input['lat'] ?? null;
$lng = $input['lng'] ?? null;

if (!$trip_id || !$image_data_url) {
    echo json_encode(['success' => false, 'message' => 'Dados em falta.']);
    exit();
}

// 6. Pastas
$upload_dir_name = 'uploads/no_shows/';
$upload_dir_path = __DIR__ . '/' . $upload_dir_name; 
if (!is_dir($upload_dir_path)) { mkdir($upload_dir_path, 0755, true); }

// 7. Processar Imagem
$image_parts = explode(";base64,", $image_data_url);
$image_base64 = base64_decode($image_parts[1]);
$file_name = 'noshow_' . $trip_id . '_' . time() . '.jpg';
$full_server_path = $upload_dir_path . $file_name;
$file_path_relative = $upload_dir_name . $file_name;

// 8. Guardar e Processar
if (file_put_contents($full_server_path, $image_base64)) {
    
    try {
        // A. Atualizar BD
        $sql = "UPDATE Services 
                SET noShowStatus = 1, 
                    noShowPhotoPath = :photo_path,
                    noShowLat = :lat,
                    noShowLng = :lng
                WHERE ID = :trip_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':photo_path' => $file_path_relative,
            ':lat' => $lat,
            ':lng' => $lng,
            ':trip_id' => $trip_id
        ]);

        // B. Preparar Dados para o Email
        $dataHora = date('d/m/Y H:i'); 

        // Link do Mapa
        $mapLink = "Localização indisponível";
        if ($lat && $lng) {
            $mapLink = "http://maps.google.com/maps?q={$lat},{$lng}";
            $locationHtml = "<p>📍 <b>Localização:</b> <a href='$mapLink' target='_blank'>Ver no Google Maps</a> <small>($lat, $lng)</small></p>";
        } else {
            $locationHtml = "<p>⚠️ Localização não capturada (GPS desligado ou permissão negada).</p>";
        }

        // C. Enviar Email
        escreverLog("A iniciar envio para múltiplos destinatários. ID: $trip_id");
        $mail = new PHPMailer(true);

        try {
            // SMTP Webminds
            $mail->isSMTP();
            $mail->Host       = 'mail.syncride.webminds.pt';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'no-reply@syncride.webminds.pt';
            $mail->Password   = 'cyWug-Z@AI,k#bY*';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            // --- MUDANÇA 1: NOME DO REMETENTE ---
            $mail->setFrom('no-reply@syncride.webminds.pt', 'Welcome Agitation Alertas');
            
            // --- MUDANÇA 2: MÚLTIPLOS DESTINATÁRIOS ---
            $mail->addAddress('transfers.pt@mtsglobe.com'); 
            $mail->addAddress('flexewar@gmail.com'); 

            $mail->isHTML(true);
            $mail->Subject = 'NoShow - Viagem #' . $trip_id;
            
            // CORPO DO EMAIL
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h3>No Show Reportado</h3>
                    <p>Foi registado um No Show para a viagem número <b>$trip_id</b>.</p>
                    
                    <p>📅 <b>Data e Hora do Registo:</b> $dataHora</p>
                    
                    $locationHtml
                    
                    <p>A fotografia de prova segue em anexo.</p>
                    <br>
                    <hr>
                    <small style='color: #777;'>Enviado automaticamente pelo sistema SyncRide.</small>
                </div>
            ";
            
            $mail->AltBody = "No Show Viagem $trip_id em $dataHora. Foto em anexo.";

            $mail->addAttachment($full_server_path, $file_name);
            $mail->send();
            
            escreverLog("SUCESSO: Email enviado para ambos os destinatários!");
            echo json_encode(['success' => true, 'message' => 'No Show reportado com sucesso!']);

        } catch (Exception $e) {
            escreverLog("ERRO SMTP: " . $mail->ErrorInfo);
            echo json_encode(['success' => true, 'message' => 'Guardado na BD, erro no email.']);
        }

    } catch (PDOException $e) {
        unlink($full_server_path);
        echo json_encode(['success' => false, 'message' => 'Erro BD: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao gravar imagem.']);
}
?>