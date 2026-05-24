<?php
/**
 * Ficheiro: upload-no-show.php
 * Design: Boarding Pass Style - Ultra UI (Original Full Width)
 * Status: PRODUÇÃO (Destinatários Oficiais)
 */

// 1. Configurações Iniciais
error_reporting(0);
ini_set('display_errors', 0);
date_default_timezone_set('Europe/Lisbon');

session_start();
header('Content-Type: application/json');

// 2. Carregar PHPMailer
require __DIR__ . '/../../vendor/phpmailer/PHPMailer/Exception.php';
require __DIR__ . '/../../vendor/phpmailer/PHPMailer/PHPMailer.php';
require __DIR__ . '/../../vendor/phpmailer/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// 3. Ligação à Base de Dados
require __DIR__ . '/../../auth/dbconfig.php';

// 4. Segurança
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

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

// 6. Pastas e Processamento de Imagem
$upload_dir_name = __DIR__ . '/../uploads/no_shows/';
$upload_dir_path = __DIR__ . '/' . $upload_dir_name; 
if (!is_dir($upload_dir_path)) { mkdir($upload_dir_path, 0755, true); }

$image_parts = explode(";base64,", $image_data_url);
$image_base64 = base64_decode($image_parts[1]);
$file_name = 'noshow_' . $trip_id . '_' . time() . '.jpg';
$full_server_path = $upload_dir_path . $file_name;
$file_path_relative = $upload_dir_name . $file_name;

if (file_put_contents($full_server_path, $image_base64)) {
    
    try {
        // A. Atualizar BD
        $sql = "UPDATE Services SET noShowStatus = 1, noShowPhotoPath = :photo_path, noShowLat = :lat, noShowLng = :lng WHERE ID = :trip_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':photo_path' => $file_path_relative, ':lat' => $lat, ':lng' => $lng, ':trip_id' => $trip_id]);

        // B. Obter dados da viagem para o Design
        $stmtCheck = $pdo->prepare("
            SELECT s.NomeCliente, s.serviceStartPoint, s.serviceTargetPoint, s.serviceDate, 
                   s.partner_id, u.email as partner_email, u.name as partner_name 
            FROM Services s
            LEFT JOIN Users u ON s.partner_id = u.id
            WHERE s.ID = ?
        ");
        $stmtCheck->execute([$trip_id]);
        $tripData = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        $dataHora = date('d/m/Y H:i'); 

        // C. UI Localização GPS do Report
        $locationHtml = "";
        if ($lat && $lng) {
            $mapLink = "http://googleusercontent.com/maps.google.com/maps?q={$lat},{$lng}";
            $locationHtml = "
                <div style='margin-top: 20px; padding: 15px; background: #fff5f5; border-radius: 10px; border: 1px solid #feb2b2;'>
                    <div style='font-size: 10px; color: #c53030; text-transform: uppercase; font-weight: bold;'>Localização GPS do Report</div>
                    <a href='$mapLink' target='_blank' style='color: #2d3748; text-decoration: none; font-size: 13px; font-weight: bold;'>📍 Abrir no Google Maps ($lat, $lng)</a>
                </div>";
        }

        // D. Configurar PHPMailer
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'cloud865.thundercloud.uk'; 
        $mail->SMTPAuth   = true;
        $mail->Username   = 'no-reply@syncride.wmservers.pt'; 
        $mail->Password   = (string) (getenv('MAIL_PASSWORD') ?: ''); 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('no-reply@syncride.wmservers.pt', 'SyncRide Alertas');
        
        // --- LÓGICA DE DESTINATÁRIOS FINAL (PRODUÇÃO) ---
        if (!empty($tripData['partner_id']) && !empty($tripData['partner_email'])) {
            // Caso seja um Parceiro Externo
            $mail->addAddress($tripData['partner_email'], $tripData['partner_name']);
            $mail->addAddress('flexewar@gmail.com');
        } else {
            // Caso seja MTS Globe (Agência)
            $mail->addAddress('transfers.pt@mtsglobe.com');
            $mail->addAddress('joao.teixeira@mtsglobe.com');
            $mail->addAddress('complaints.pt@mtsglobe.com');
            $mail->addAddress('flexewar@gmail.com');
        }

        // E. Corpo do Email (Ultra UI)
        $mail->addEmbeddedImage($full_server_path, 'foto_noshow');
        $mail->isHTML(true);
        $mail->Subject = "No-Show Reportado: Viagem #$trip_id";
        
        $mail->Body = "
        <div style='background-color: #f0f4f8; padding: 50px 20px; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;'>
                
                <div style='background-color: #e53e3e; padding: 30px; color: #ffffff; display: flex; justify-content: space-between; align-items: center;'>
                    <div style='font-size: 24px; font-weight: 800; letter-spacing: -1px;'>SyncRide<span style='color: #ffffff;'>.</span></div>
                    <div style='text-align: right;'>
                        <div style='font-size: 10px; text-transform: uppercase; opacity: 0.9;'>Status</div>
                        <div style='font-size: 14px; font-weight: bold;'>NO-SHOW REPORTED</div>
                    </div>
                </div>

                <div style='padding: 40px;'>
                    <table style='width: 100%; margin-bottom: 40px;'>
                        <tr>
                            <td style='width: 50%; vertical-align: top;'>
                                <div style='font-size: 10px; color: #a0aec0; text-transform: uppercase; margin-bottom: 5px; font-weight: 700;'>Passageiro</div>
                                <div style='font-size: 18px; font-weight: bold; color: #2d3748;'>".mb_strtoupper($tripData['NomeCliente'])."</div>
                            </td>
                            <td style='width: 50%; vertical-align: top; text-align: right;'>
                                <div style='font-size: 10px; color: #a0aec0; text-transform: uppercase; margin-bottom: 5px; font-weight: 700;'>Registo do Incidente</div>
                                <div style='font-size: 18px; font-weight: bold; color: #2d3748;'>$dataHora</div>
                            </td>
                        </tr>
                    </table>

                    <div style='background-color: #f7fafc; border-radius: 15px; padding: 25px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; border: 1px dashed #cbd5e0;'>
                        <div style='text-align: left;'>
                            <div style='font-size: 24px; font-weight: 900; color: #1a202c;'>PICK</div>
                            <div style='font-size: 12px; color: #718096; max-width: 150px;'>{$tripData['serviceStartPoint']}</div>
                        </div>
                        <div style='font-size: 24px; color: #e53e3e;'>✕</div>
                        <div style='text-align: right;'>
                            <div style='font-size: 24px; font-weight: 900; color: #1a202c;'>DROP</div>
                            <div style='font-size: 12px; color: #718096; max-width: 150px;'>{$tripData['serviceTargetPoint']}</div>
                        </div>
                    </div>

                    <div style='text-align: center; margin-bottom: 25px;'>
                        <div style='font-size: 10px; color: #a0aec0; text-transform: uppercase; margin-bottom: 10px; font-weight: 700;'>Prova Fotográfica</div>
                        <img src='cid:foto_noshow' style='width: 100%; max-width: 520px; border-radius: 15px; border: 4px solid #ffffff; box-shadow: 0 4px 12px rgba(0,0,0,0.1);'>
                    </div>

                    $locationHtml

                </div>

                <div style='background-color: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #edf2f7;'>
                    <div style='font-size: 24px; color: #1a202c; margin-bottom: 10px; font-weight: bold;'>Viagem ID #$trip_id</div>
                    <div style='font-size: 10px; color: #a0aec0; letter-spacing: 2px;'>SYNCRIDE PORTUGAL ECOSYSTEM</div>
                </div>
            </div>
            <div style='text-align: center; margin-top: 20px; color: #a0aec0; font-size: 11px;'>
                Enviado automaticamente pelo sistema de segurança SyncRide.
            </div>
        </div>";

        // Enviar Email
        $mail->send();
        echo json_encode(['success' => true, 'message' => 'No Show reportado com sucesso!']);

    } catch (Exception $e) {
        echo json_encode(['success' => true, 'message' => 'Guardado, mas erro no envio do email.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao gravar imagem.']);
}
?>