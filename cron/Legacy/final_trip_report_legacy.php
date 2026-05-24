<?php
/**
 * Ficheiro: send_finaltrip_report.php
 * Design: Boarding Pass Style - Ultra UI
 * Update: Lógica de emails dinâmica (Parceiro vs Agência) conforme upload_no_show.php
 */

ob_start();
error_reporting(E_ALL); 
ini_set('display_errors', 0); 
date_default_timezone_set('Europe/Lisbon');

header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

try {
    $baseDir = __DIR__ . '/PHPMailer/';
    $dbFile = realpath(__DIR__ . '/../../../auth/dbconfig.php');

    require_once $baseDir . 'Exception.php';
    require_once $baseDir . 'PHPMailer.php';
    require_once $baseDir . 'SMTP.php';
    require_once $dbFile; 

    if (!isset($_GET['ride_id'])) throw new Exception("ID ausente.");
    $ride_id = intval($_GET['ride_id']);

    // 1. Obter Dados da Viagem e do Parceiro (JOIN com a tabela Users)
    $stmt = $pdo->prepare("
        SELECT s.NomeCliente, s.serviceStartPoint, s.serviceTargetPoint, s.serviceDate, s.serviceStartTime, 
               s.partner_id, u.email as partner_email, u.name as partner_name 
        FROM Services s
        LEFT JOIN Users u ON s.partner_id = u.id
        WHERE s.ID = ?
    ");
    $stmt->execute([$ride_id]);
    $ride = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ride) throw new Exception("Viagem não encontrada.");

    // 2. BUSCA FILTRADA: Apenas logs desta viagem
    $search_term = "%ID #".$ride_id."%";
    $stmt_logs = $pdo->prepare("SELECT Action, date FROM Logs WHERE Action LIKE ? ORDER BY date ASC");
    $stmt_logs->execute([$search_term]);
    $logs = $stmt_logs->fetchAll(PDO::FETCH_ASSOC);

    $logs_html = "";
    if ($logs) {
        foreach ($logs as $l) {
            $time = date("H:i:s", strtotime($l['date']));
            $clean_action = str_replace("Serviço ID #$ride_id: ", "", $l['Action']);
            $logs_html .= "
            <div style='display: flex; margin-bottom: 10px; border-left: 2px solid #e0e0e0; padding-left: 15px;'>
                <div style='min-width: 70px; font-weight: bold; color: #1a202c; font-size: 12px;'>$time</div>
                <div style='color: #4a5568; font-size: 12px;'>$clean_action</div>
            </div>";
        }
    } else {
        $logs_html = "<p style='text-align:center; color:#a0aec0; font-size:12px;'>Histórico detalhado indisponível.</p>";
    }

    // 3. Configuração do Email
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

    // --- LÓGICA DE DESTINATÁRIOS IGUAL AO NO-SHOW ---
    if (!empty($ride['partner_id']) && !empty($ride['partner_email'])) {
        // Se tem parceiro -> Email do Parceiro + flexewar (para monitorização)
        $mail->addAddress($ride['partner_email'], $ride['partner_name']);
    } else {
        // Se não tem parceiro -> Agência + flexewar
        $mail->addAddress('transfers.pt@mtsglobe.com');
    }
    // ------------------------------------------------

    $mail->isHTML(true);
    $mail->Subject = "Viagem #$ride_id Finalizada";

    // 4. UI Boarding Ticket
    $mail->Body = "
    <div style='background-color: #f0f4f8; padding: 50px 20px; font-family: \"Helvetica Neue\", Helvetica, Arial, sans-serif;'>
        <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 20px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); border: 1px solid #e2e8f0;'>
            
            <div style='background-color: #000000; padding: 30px; color: #ffffff; display: flex; justify-content: space-between; align-items: center;'>
                <div style='font-size: 24px; font-weight: 800; letter-spacing: -1px;'>SyncRide<span style='color: #48bb78;'>.</span></div>
                <div style='text-align: right;'>
                    <div style='font-size: 10px; text-transform: uppercase; opacity: 0.6;'>Status</div>
                    <div style='font-size: 14px; font-weight: bold; color: #48bb78;'>COMPLETED</div>
                </div>
            </div>

            <div style='padding: 40px;'>
                <table style='width: 100%; margin-bottom: 40px;'>
                    <tr>
                        <td style='width: 50%; vertical-align: top;'>
                            <div style='font-size: 10px; color: #a0aec0; text-transform: uppercase; margin-bottom: 5px; font-weight: 700;'>Passageiro</div>
                            <div style='font-size: 18px; font-weight: bold; color: #2d3748;'>".mb_strtoupper($ride['NomeCliente'])."</div>
                        </td>
                        <td style='width: 50%; vertical-align: top; text-align: right;'>
                            <div style='font-size: 10px; color: #a0aec0; text-transform: uppercase; margin-bottom: 5px; font-weight: 700;'>Data do Serviço</div>
                            <div style='font-size: 18px; font-weight: bold; color: #2d3748;'>".date('d M Y', strtotime($ride['serviceDate']))."</div>
                        </td>
                    </tr>
                </table>

                <div style='background-color: #f7fafc; border-radius: 15px; padding: 25px; display: flex; align-items: center; justify-content: space-between; margin-bottom: 40px; border: 1px dashed #cbd5e0;'>
                    <div style='text-align: left;'>
                        <div style='font-size: 24px; font-weight: 900; color: #1a202c;'>PICK</div>
                        <div style='font-size: 12px; color: #718096; max-width: 150px;'>{$ride['serviceStartPoint']}</div>
                    </div>
                    <div style='font-size: 24px; color: #cbd5e0;'>✈</div>
                    <div style='text-align: right;'>
                        <div style='font-size: 24px; font-weight: 900; color: #1a202c;'>DROP</div>
                        <div style='font-size: 12px; color: #718096; max-width: 150px;'>{$ride['serviceTargetPoint']}</div>
                    </div>
                </div>

                <div style='margin-top: 20px;'>
                    <h3 style='font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color: #a0aec0; margin-bottom: 20px;'>Activity Timeline</h3>
                    $logs_html
                </div>
            </div>

            <div style='background-color: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #edf2f7;'>
                <div style='font-size: 24px; color: #1a202c; margin-bottom: 10px; font-weight: bold;'>#$ride_id</div>
                <div style='font-size: 10px; color: #a0aec0; letter-spacing: 2px;'>SYNCRIDE PORTUGAL ECOSYSTEM</div>
            </div>
        </div>
        <div style='text-align: center; margin-top: 20px; color: #a0aec0; font-size: 11px;'>
            Generated at ".date('H:i:s d/m/Y')."
        </div>
    </div>";

    $mail->send();

    ob_clean();
    echo json_encode(["success" => true, "message" => "Relatório enviado com sucesso."]);

} catch (Exception $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

ob_end_flush();
exit;