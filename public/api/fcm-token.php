<?php

declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\Support\Database;
use App\Support\Session;

// Soft session: we still need the logged-in user's identity to associate the
// token, but we must NOT 302-redirect on failure — the native HTTP client can't
// follow that and the token would be lost silently. Return a clean JSON 401.
Session::start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

$userId = Session::userId() ?? 0;
if ($userId === 0) {
    // The most common real failure: the native POST didn't carry the WebView
    // session cookie. Logged so it shows up in the prod PHP error log.
    error_log('[fcm-token] rejected: no authenticated session on token POST');
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Accept both the base64 WAF-shield (p=...) and a raw JSON body.
$body = [];
if (isset($_POST['p'])) {
    $decoded = base64_decode((string) $_POST['p'], true);
    if ($decoded !== false) {
        $body = json_decode($decoded, true) ?? [];
    }
}
if ($body === []) {
    $body = json_decode((string) file_get_contents('php://input'), true) ?? [];
}

$token = trim((string) ($body['token'] ?? ''));
if ($token === '') {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => 'Token required']);
    exit;
}

$pdo = Database::connection();
$pdo->prepare(
    'INSERT INTO device_tokens (user_id, token)
     VALUES (:uid, :tok)
     ON DUPLICATE KEY UPDATE updated_at = NOW()'
)->execute(['uid' => $userId, 'tok' => $token]);

error_log("[fcm-token] stored token for user {$userId}");
echo json_encode(['success' => true]);
