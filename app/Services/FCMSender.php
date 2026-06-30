<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Database;

final class FCMSender
{
    private const FCM_URL   = 'https://fcm.googleapis.com/v1/projects/syncride-alertas/messages:send';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const SA_PATH   = __DIR__ . '/../../storage/firebase/service-account.json';

    private static ?string $cachedToken  = null;
    private static int     $tokenExpires = 0;

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /** Send a notification to a specific FCM device token. */
    public static function send(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        $accessToken = self::accessToken();
        if ($accessToken === null) {
            return false;
        }

        // Data-only payload: no 'notification' block so Android always routes through
        // onMessageReceived (foreground AND background), giving us full icon control.
        $payload = [
            'message' => [
                'token'   => $fcmToken,
                'data'    => array_merge(
                    ['title' => $title, 'body' => $body],
                    array_map('strval', $data)
                ),
                'android' => ['priority' => 'high'],
            ],
        ];

        $ch = curl_init(self::FCM_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200) {
            error_log("[FCMSender] send failed: HTTP {$code} — " . (string) $response);
        }

        return $code === 200;
    }

    /** Send to every device token registered by a specific user. */
    public static function sendToUser(int $userId, string $title, string $body, array $data = []): void
    {
        foreach (self::tokensForUser($userId) as $token) {
            self::send($token, $title, $body, $data);
        }
    }

    /** Send to every admin of a given company. */
    public static function sendToAdmins(int $companyId, string $title, string $body, array $data = []): void
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT dt.token
               FROM device_tokens dt
               JOIN Users u ON u.ID = dt.user_id
              WHERE u.company_id = :cid AND u.role = 1'
        );
        $stmt->execute(['cid' => $companyId]);
        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $token) {
            self::send($token, $title, $body, $data);
        }
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /** @return string[] */
    private static function tokensForUser(int $userId): array
    {
        $pdo  = Database::connection();
        $stmt = $pdo->prepare('SELECT token FROM device_tokens WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    private static function accessToken(): ?string
    {
        if (self::$cachedToken !== null && time() < self::$tokenExpires) {
            return self::$cachedToken;
        }

        $sa = json_decode((string) file_get_contents(self::SA_PATH), true);
        if (!is_array($sa)) {
            return null;
        }

        $now     = time();
        $header  = self::b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $claims  = self::b64url(json_encode([
            'iss'   => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud'   => self::TOKEN_URL,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ]));

        $sigInput = "$header.$claims";
        openssl_sign($sigInput, $sig, $sa['private_key'], 'SHA256');
        $jwt = "$sigInput." . self::b64url($sig);

        $ch = curl_init(self::TOKEN_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ]),
            CURLOPT_TIMEOUT => 10,
        ]);
        $resp = json_decode((string) curl_exec($ch), true);
        curl_close($ch);

        if (!isset($resp['access_token'])) {
            return null;
        }

        self::$cachedToken  = $resp['access_token'];
        self::$tokenExpires = $now + (int) ($resp['expires_in'] ?? 3600) - 60;

        return self::$cachedToken;
    }

    private static function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
