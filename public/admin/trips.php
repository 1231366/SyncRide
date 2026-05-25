<?php
declare(strict_types=1);
require __DIR__ . '/../../bootstrap.php';
use App\Http\AuthMiddleware;
use App\Support\Database;
AuthMiddleware::handle(1);
header('Content-Type: application/json');

$pdo = Database::connection();

$completed   = (int) $pdo->query("SELECT COUNT(*) FROM Services WHERE status_id = 5")->fetchColumn();
$weekCount   = (int) $pdo->query("SELECT COUNT(*) FROM Services WHERE serviceDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)")->fetchColumn();
$todayCount  = (int) $pdo->query("SELECT COUNT(*) FROM Services WHERE serviceDate = CURDATE()")->fetchColumn();
$weekDone    = (int) $pdo->query("SELECT COUNT(*) FROM Services WHERE status_id = 5 AND serviceDate BETWEEN DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND CURDATE()")->fetchColumn();
$pct         = $weekCount > 0 ? round(($weekDone / $weekCount) * 100, 2) : 0.0;

echo json_encode([
    'total_completed'              => $completed,
    'total_scheduled_week'         => $weekCount,
    'weekly_completion_percentage' => $pct,
    'total_scheduled_today'        => $todayCount,
]);
