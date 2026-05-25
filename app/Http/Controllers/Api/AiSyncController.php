<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Repositories\ExpenseRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\UserRepository;

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

        $month     = (int) date('m');
        $year      = (int) date('Y');
        $yearMonth = sprintf('%04d-%02d', $year, $month);
        $adminName = (string) ($_SESSION['name'] ?? 'Admin');

        $services = ServiceRepository::default();
        $users    = UserRepository::default();
        $expenses = ExpenseRepository::default();

        $today      = $services->todayWithDriver();
        $leaderboard = $services->driverLeaderboardDetailed($month, $year);
        $team        = array_map(
            static fn($u) => ['name' => $u->name, 'role' => $u->role, 'phone' => $u->phone],
            $users->all()
        );
        $expensesMonth = $expenses->totalForMonth($yearMonth);
        $upcoming      = array_map(
            static fn(array $r) => [
                'serviceDate'      => $r['serviceDate'],
                'serviceStartTime' => $r['serviceStartTime'],
                'NomeCliente'      => $r['NomeCliente'],
            ],
            $services->upcoming(10)
        );

        $context = json_encode([
            'system_datetime'    => date('d/m/Y H:i'),
            'current_month'      => date('F'),
            'admin_name'         => $adminName,
            'today_agenda'       => $today,
            'driver_performance' => $leaderboard,
            'team'               => $team,
            'expenses_month'     => $expensesMonth . '€',
            'upcoming_bookings'  => $upcoming,
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
