<?php
session_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

require __DIR__ . '/../../auth/dbconfig.php';
header('Content-Type: application/json');
$pdo->exec("SET NAMES utf8mb4");

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$userMsg = $data['message'] ?? '';

if (!$userMsg) exit;

try {
    // --- 1. INFO OPERACIONAL: HOJE ---
    $stmtHoje = $pdo->query("SELECT s.serviceStartTime, s.NomeCliente, s.FlightNumber, s.serviceStartPoint, s.serviceTargetPoint, 
                                    (SELECT name FROM Users JOIN Services_Rides ON Users.id = Services_Rides.UserID WHERE Services_Rides.RideID = s.ID LIMIT 1) as condutor
                             FROM Services s WHERE s.serviceDate = CURDATE() ORDER BY s.serviceStartTime ASC");
    $detalheHoje = $stmtHoje->fetchAll(PDO::FETCH_ASSOC);

    // --- 2. PERFORMANCE DETALHADA (TOTAL VS MÊS ATUAL) ---
    // Esta query agora traz o total de sempre E o total específico do mês de Abril
    $mesAtual = date('m');
    $anoAtual = date('Y');
    
    $stmtDrivers = $pdo->query("SELECT u.name, 
                                COUNT(sr.RideID) as total_historico, 
                                SUM(CASE WHEN MONTH(s.serviceDate) = $mesAtual AND YEAR(s.serviceDate) = $anoAtual THEN 1 ELSE 0 END) as total_mes_atual,
                                AVG(s.driver_rating) as rating 
                                FROM Users u 
                                LEFT JOIN Services_Rides sr ON u.id = sr.UserID 
                                LEFT JOIN Services s ON sr.RideID = s.ID
                                WHERE u.role = 2 GROUP BY u.id ORDER BY total_mes_atual DESC");
    $leaderboard = $stmtDrivers->fetchAll(PDO::FETCH_ASSOC);

    // --- 3. LISTA DE EQUIPA ---
    $stmtUsers = $pdo->query("SELECT name, role, phone FROM Users ORDER BY role ASC");
    $equipaCompleta = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

    // --- 4. FINANCEIRO E FUTURO ---
    $gastosMes = $pdo->query("SELECT SUM(amount) FROM Expenses WHERE MONTH(date) = $mesAtual AND YEAR(date) = $anoAtual")->fetchColumn() ?: 0;
    
    $stmtFuturo = $pdo->query("SELECT s.serviceDate, s.serviceStartTime, s.NomeCliente FROM Services s WHERE s.serviceDate > CURDATE() ORDER BY s.serviceDate ASC LIMIT 10");
    $proximas = $stmtFuturo->fetchAll(PDO::FETCH_ASSOC);

    // --- 5. MONTAGEM DO CONTEXTO DE ELITE ---
    $nomeAdmin = $_SESSION['name'] ?? 'Tiago';
    $contextoFinal = json_encode([
        "data_sistema" => date('d/m/Y H:i'),
        "mes_referencia" => "Abril", // O sistema sabe que estamos em Abril
        "interlocutor" => "Sr. $nomeAdmin",
        "agenda_hoje" => $detalheHoje,
        "performance_condutores" => $leaderboard, // Aqui está o segredo para a pergunta do Camilo
        "equipa_ativa" => $equipaCompleta,
        "gastos_abril" => $gastosMes . "€",
        "proximas_reservas" => $proximas
    ], JSON_UNESCAPED_UNICODE);

    // --- 6. GROQ API ---
    $apiKey = (string) (getenv('GROQ_API_KEY') ?: '');
    $apiUrl = "https://api.groq.com/openai/v1/chat/completions";

    $systemPrompt = "Tu és o SyncAI, o assistente pessoal de elite do Sr. $nomeAdmin na SyncRide.
    Postura: Braço direito executivo. Sê preciso, leal e profissional.
    
    TENS DADOS ESPECÍFICOS:
    - Na 'performance_condutores', tens o campo 'total_mes_atual'. Se o Sr. $nomeAdmin perguntar sobre Abril, usa esse valor.
    - Se ele perguntar o total de sempre, usa 'total_historico'.
    - Trata-o sempre por 'Sr. $nomeAdmin'. Nunca uses gíria.
    
    DADOS REAIS: $contextoFinal";

    $postData = [
        "model" => "llama-3.3-70b-versatile",
        "messages" => [
            ["role" => "system", "content" => $systemPrompt],
            ["role" => "user", "content" => $userMsg]
        ],
        "temperature" => 0.1 // Estritamente factual
    ];

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $apiKey", "Content-Type: application/json"]);

    $response = curl_exec($ch);
    $resDecoded = json_decode($response, true);
    $aiText = $resDecoded['choices'][0]['message']['content'] ?? "Sr. $nomeAdmin, ocorreu uma falha na consulta aos dados.";

    echo json_encode(["success" => true, "response" => $aiText]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "response" => "Sr. $nomeAdmin, o sistema encontrou uma dificuldade técnica temporária."]);
}