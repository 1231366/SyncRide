<?php

declare(strict_types=1);

namespace App\Auth;

use App\Support\Env;
use PDO;

/**
 * Persistent login ("remember me") via signed cookie + hashed token in DB.
 *
 * The hashed token is stored in `Users.remember_token` (column name preserved
 * from the legacy schema). The plaintext token never touches the database.
 */
final class RememberMeService
{
    private const COOKIE_NAME = 'remember_me';

    public function __construct(private readonly PDO $db)
    {
    }

    public function issue(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $hash  = hash('sha256', $token);

        $stmt = $this->db->prepare('UPDATE Users SET remember_token = ? WHERE id = ?');
        $stmt->execute([$hash, $userId]);

        $this->setCookie($token);
    }

    /**
     * If a valid remember-me cookie is present, return the corresponding user
     * row (associative array) and refresh the cookie. Returns null otherwise.
     *
     * @return array<string,mixed>|null
     */
    public function consume(): ?array
    {
        if (empty($_COOKIE[self::COOKIE_NAME])) {
            return null;
        }

        $token = (string) $_COOKIE[self::COOKIE_NAME];
        $hash  = hash('sha256', $token);

        $stmt = $this->db->prepare('SELECT * FROM Users WHERE remember_token = ?');
        $stmt->execute([$hash]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        $this->setCookie($token); // sliding renewal
        return $user;
    }

    public function clear(int $userId): void
    {
        $stmt = $this->db->prepare('UPDATE Users SET remember_token = NULL WHERE id = ?');
        $stmt->execute([$userId]);

        setcookie(self::COOKIE_NAME, '', time() - 3600, '/', '', false, true);
    }

    private function setCookie(string $token): void
    {
        $lifetime = (int) Env::get('REMEMBER_ME_LIFETIME', 2592000);
        $secure   = Env::get('APP_ENV') === 'production';
        setcookie(self::COOKIE_NAME, $token, time() + $lifetime, '/', '', $secure, true);
    }
}
