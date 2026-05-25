<?php

declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Singleton PDO connection driven by environment variables.
 *
 * Replaces the legacy `auth/dbconfig.php` script. Database schema column
 * and table names are intentionally preserved (Portuguese) — only the
 * application-layer code is translated.
 */
final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $host       = Env::get('DB_HOST', '127.0.0.1');
        $port       = (string) Env::get('DB_PORT', '3306');
        $database   = Env::require('DB_DATABASE');
        $username   = Env::require('DB_USERNAME');
        $password   = (string) Env::get('DB_PASSWORD', '');
        $charset    = (string) Env::get('DB_CHARSET', 'utf8mb4');
        $persistent = (bool) Env::get('DB_PERSISTENT', false);

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset={$charset}";

        try {
            self::$instance = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => true,
                PDO::ATTR_PERSISTENT         => $persistent,
            ]);
        } catch (PDOException $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            throw new RuntimeException('Database is unavailable.', 0, $e);
        }

        return self::$instance;
    }

    /** Test seam — never call outside tests. */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
