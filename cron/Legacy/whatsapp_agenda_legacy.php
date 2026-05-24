<?php
/**
 * Ficheiro: cron_whatsapp_agenda.php
 * Identidade: SyncRide WhatsApp Automation - VERSÃO FINAL VIP
 */

error_reporting(0); 
ini_set('display_errors', 0);
date_default_timezone_set('Europe/Lisbon');
set_time_limit(120);

require __DIR__ . '/../../../auth/dbconfig.php';
$pdo->exec("SET NAMES utf8mb4");

$apiToken = (string) (getenv('WHATSAPP_API_TOKEN') ?: '');
$apiUrl   = (string) (getenv('WHATSAPP_API_URL') ?: 'https://gate.whapi.cloud/messages/text');
if ($apiToken === '') {
    error_log('whatsapp-agenda: WHATSAPP_API_TOKEN missing — aborting.');
    return;
}

$amanha = new DateTime('tomorrow');
$dataSql = $amanha->format('Y-m-d');
$dataPt  = $amanha->format('d/m/Y');

try {
    $query = "
        SELECT 
            u.id AS UserID, u.phone, u.name AS NomeCondutor,
            s.ID AS ServiceID, s.serviceStartTime, s.serviceStartPoint, s.serviceTargetPoint, s.NomeCliente, s.FlightNumber
        FROM Services s
        JOIN Services_Rides sr ON s.ID = sr.RideID
        JOIN Users u ON sr.UserID = u.id
        WHERE s.serviceDate = ? 
          AND u.role = 2 
          AND u.phone IS NOT NULL 
        ORDER BY s.serviceStartTime ASC
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$dataSql]);
    $servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($servicos)) exit("Sem serviços.");

    $agendasPorTelefone = [];
    foreach ($servicos as $s) {
        $numLimpo = preg_replace('/[^0-9]/', '', (string)$s['phone']);
        if (strlen($numLimpo) > 9 && substr($numLimpo, 0, 3) === '351') {
            $numLimpo = substr($numLimpo, 3);
        }
        $agendasPorTelefone[$numLimpo][] = $s;
    }

    // LISTA DE IDs VIP ATUALIZADA
    // 28: Camilo | 37: Jorge | 49: João | 39: Toze (António) | 42: Paulo Ferreira
    $vips = [28, 37, 49, 39, 42]; 

    uasort($agendasPorTelefone, function($a, $b) use ($vips) {
        $idsA = array_column($a, 'UserID');
        $idsB = array_column($b, 'UserID');
        
        $isAVip = !empty(array_intersect($idsA, $vips));
        $isBVip = !empty(array_intersect($idsB, $vips));

        if ($isAVip && !$isBVip) return -1;
        if (!$isAVip && $isBVip) return 1;

        return count($b) <=> count($a);
    });

    foreach ($agendasPorTelefone as $telemovel => $viagens) {
        $primeiroNome = explode(' ', $viagens[0]['NomeCondutor'])[0];

        $msg = "Olá, *{$primeiroNome}*! 👋\n";
        $msg .= "Aqui tens a tua agenda de serviços para amanhã, *{$dataPt}*:\n";
        $msg .= "------------------------------------------\n\n";

        foreach ($viagens as $v) {
            $hora = substr($v['serviceStartTime'], 0, 5);
            $voo = !empty($v['FlightNumber']) ? " (✈️ {$v['FlightNumber']})" : "";
            
            $msg .= "⏰ *{$hora}* | Viagem #{$v['ServiceID']}\n";
            $msg .= "👤 " . mb_strtoupper($v['NomeCliente']) . "{$voo}\n";
            $msg .= "📍 *De:* {$v['serviceStartPoint']}\n";
            $msg .= "🏁 *Para:* {$v['serviceTargetPoint']}\n";
            $msg .= "------------------------------------------\n\n";
        }

        $msg .= "Bom trabalho e conduz com cuidado! 🚀";

        $postData = [
            "typing_time" => 0,
            "to" => "351" . $telemovel . "@s.whatsapp.net",
            "body" => $msg
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
        
        usleep(1500000); 
    }

    echo "✅ Mensagens enviadas (Prioridade VIP: Camilo, Jorge, João, Toze e Paulo Ferreira).";

} catch (Exception $e) {
    echo "❌ Erro.";
}