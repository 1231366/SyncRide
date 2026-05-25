<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Support\Database;
use PDO;

final class AiSyncController extends BaseController
{
    /** POST /api/sync-ai-engine.php */
    public function index(): never
    {
        header('Content-Type: application/json');
        ini_set('display_errors', '0');

        $payload = $this->jsonBody();
        $userMsg = trim((string) ($payload['message'] ?? ''));

        if ($userMsg === '') { exit; }

        $db       = Database::connection();
        $db->exec('SET NAMES utf8mb4');
        $month    = (int) date('m');
        $year     = (int) date('Y');
        $adminName = (string) ($_SESSION['name'] ?? 'Admin');

        $today        = $db->query("SELECT s.serviceStartTime, s.NomeCliente, s.FlightNumber, s.serviceStartPoint, s.serviceTargetPoint, (SELECT name FROM Users JOIN Services_Rides ON Users.id = Services_Rides.UserID WHERE Services_Rides.RideID = s.ID LIMIT 1) as driver FROM Services s WHERE s.serviceDate = CURDATE() ORDER BY s.serviceStartTime ASC")->fetchAll(PDO::FETCH_ASSOC);
        $leaderboard  = $db->query("SELECT u.name, COUNT(sr.RideID) as total_all_time, SUM(CASE WHEN MONTH(s.serviceDate) = {$month} AND YEAR(s.serviceDate) = {$year} THEN 1 ELSE 0 END) as total_this_month, AVG(s.driver_rating) as rating FROM Users u LEFT JOIN Services_Rides sr ON u.id = sr.UserID LEFT JOIN Services s ON sr.RideID = s.ID WHERE u.role = 2 GROUP BY u.id ORDER BY total_this_month DESC")->fetchAll(PDO::FETCH_ASSOC);
        $team         = $db->query('SELECT name, role, phone FROM Users ORDER BY role ASC')->fetchAll(PDO::FETCH_ASSOC);
        $expenses     = $db->query("SELECT SUM(amount) FROM Expenses WHERE MONTH(date) = {$month} AND YEAR(date) = {$year}")->fetchColumn() ?: 0;
        $upcoming     = $db->query("SELECT serviceDate, serviceStartTime, NomeCliente FROM Services WHERE serviceDate > CURDATE() ORDER BY serviceDate ASC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

        $context = json_encode([
            'system_datetime'  => date('d/m/Y H:i'),
            'current_month'    => date('F'),
            'admin_name'       => $adminName,
            'today_agenda'     => $today,
            'driver_performance' => $leaderboard,
            'team'             => $team,
            'expenses_month'   => $expenses . '€',
            'upcoming_bookings' => $upcoming,
        ], JSON_UNESCAPED_UNICODE);

        $systemPrompt = "You are SyncAI, the personal AI assistant for {$adminName} at SyncRide. "
            . "Be precise, professional, and helpful. Always address them as '{$adminName}'. "
            . "LIVE DATA: {$context}";

        $apiKey = (string) (getenv('GROQ_API_KEY') ?: '');
        $ch     = curl_init('https://api.groq.com/openai/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'       => 'llama-3.3-70b-versatile',
                'messages'    => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user',   'content' => $userMsg],
                ],
                'temperature' => 0.1,
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
        ]);
        $raw      = (string) curl_exec($ch);
        $decoded  = json_decode($raw, true) ?? [];
        $aiText   = $decoded['choices'][0]['message']['content']
            ?? "{$adminName}, the AI service is temporarily unavailable.";

        $this->json(['success' => true, 'response' => $aiText]);
    }
}
