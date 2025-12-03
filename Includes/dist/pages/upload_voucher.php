<?php
// 1. Configurações Iniciais
error_reporting(0);
ini_set('display_errors', 0);
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
    file_put_contents(__DIR__ . '/debug_voucher.txt', date('d/m/Y H:i:s') . " -> " . $texto . "\n", FILE_APPEND);
}

// 4. Segurança
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

$driverName = $_SESSION['name'] ?? 'Condutor Desconhecido';

// 5. Receber Dados
$input = json_decode(file_get_contents('php://input'), true);
$trip_id = $input['trip_id'] ?? null;
$image_data_url = $input['image_data'] ?? null;
$lat = $input['lat'] ?? null;
$lng = $input['lng'] ?? null;

if (!$trip_id || !$image_data_url) {
    echo json_encode(['success' => false, 'message' => 'Dados em falta.']);
    exit();
}

// 6. Pastas (Cria uma pasta específica para vouchers)
$upload_dir_name = 'uploads/vouchers/';
$upload_dir_path = __DIR__ . '/' . $upload_dir_name; 
if (!is_dir($upload_dir_path)) { mkdir($upload_dir_path, 0755, true); }

// 7. Processar Imagem
$image_parts = explode(";base64,", $image_data_url);
$image_base64 = base64_decode($image_parts[1]);
$file_name = 'voucher_' . $trip_id . '_' . time() . '.jpg';
$full_server_path = $upload_dir_path . $file_name;

// 8. Obter Dados da Viagem
$tripInfo = [];
try {
    $stmt = $pdo->prepare("SELECT serviceDate, serviceStartTime, NomeCliente, ClientNumber, serviceStartPoint, serviceTargetPoint FROM Services WHERE ID = ?");
    $stmt->execute([$trip_id]);
    $tripInfo = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Continua mesmo se falhar a busca, mas com dados vazios
}

$clienteNome = $tripInfo['NomeCliente'] ?? 'N/A';
$clienteNumero = $tripInfo['ClientNumber'] ?? 'N/A';
$dataServico = $tripInfo['serviceDate'] ?? 'N/A';
$horaServico = $tripInfo['serviceStartTime'] ?? 'N/A';
$origem = $tripInfo['serviceStartPoint'] ?? 'N/A';
$destino = $tripInfo['serviceTargetPoint'] ?? 'N/A';

// 9. Guardar e Processar
if (file_put_contents($full_server_path, $image_base64)) {
    
    try {
        // Nota: Não atualizamos o 'noShowStatus' na BD, pois é um voucher.
        // Se quiseres guardar o caminho do voucher na BD, terias de criar uma coluna nova (ex: voucherPhotoPath).
        
        // B. Preparar Dados para o Email
        $dataHoraRegisto = date('d/m/Y H:i'); 

        // Link do Mapa
        $mapLink = "Localização indisponível";
        if ($lat && $lng) {
            $mapLink = "http://maps.google.com/maps?q={$lat},{$lng}";
            $locationHtml = "<p>📍 <b>Local do Registo:</b> <a href='$mapLink' target='_blank'>Ver no Google Maps</a> <small>($lat, $lng)</small></p>";
        } else {
            $locationHtml = "<p>⚠️ Localização não capturada no momento da foto.</p>";
        }

        // C. Enviar Email
        escreverLog("A enviar voucher ID: $trip_id");
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

            $mail->setFrom('no-reply@syncride.webminds.pt', 'SyncRide Vouchers');
            $mail->addAddress('tiagofsilva04@gmail.com'); 

            $mail->isHTML(true);
            $mail->Subject = 'Voucher - Viagem #' . $trip_id . ' - ' . $clienteNome;
            
            // CORPO DO EMAIL DETALHADO
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2 style='color: #28a745;'>Comprovativo de Voucher</h2>
                    <hr>
                    <p><b>Condutor:</b> $driverName</p>
                    <p><b>Data/Hora Registo:</b> $dataHoraRegisto</p>
                    
                    <h3>Dados da Viagem #$trip_id</h3>
                    <ul>
                        <li><b>Cliente:</b> $clienteNome</li>
                        <li><b>Contacto:</b> $clienteNumero</li>
                        <li><b>Data Serviço:</b> $dataServico às $horaServico</li>
                        <li><b>Rota:</b> $origem → $destino</li>
                    </ul>
                    
                    $locationHtml
                    
                    <p>A fotografia do voucher segue em anexo.</p>
                    <br>
                    <small style='color: #777;'>Sistema SyncRide - Módulo de Condutor</small>
                </div>
            ";
            
            $mail->AltBody = "Voucher Viagem $trip_id. Cliente: $clienteNome. Condutor: $driverName.";

            $mail->addAttachment($full_server_path, $file_name);
            $mail->send();
            
            escreverLog("SUCESSO: Voucher enviado!");
            echo json_encode(['success' => true, 'message' => 'Voucher enviado com sucesso!']);

        } catch (Exception $e) {
            escreverLog("ERRO SMTP: " . $mail->ErrorInfo);
            echo json_encode(['success' => true, 'message' => 'Guardado, mas erro no envio de email.']);
        }

    } catch (Exception $e) {
        unlink($full_server_path);
        echo json_encode(['success' => false, 'message' => 'Erro Interno: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao gravar imagem.']);
}
?>