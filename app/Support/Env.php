<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Lightweight .env loader. Reads key=value pairs from a file into
 * getenv()/$_ENV/$_SERVER so the rest of the application can pull
 * configuration through {@see Env::get()} without hardcoded secrets.
 */
final class Env
{
    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        if (!is_readable($path)) {
            throw new RuntimeException("Cannot read env file at: {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $value = self::stripQuotes($value);
            $value = self::expandReferences($value);

            if (getenv($key) === false) {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);
        if ($value === false || $value === null) {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }

    public static function require(string $key): string
    {
        $value = self::get($key);
        if ($value === null || $value === '') {
            throw new RuntimeException("Missing required env variable: {$key}");
        }

        return (string) $value;
    }

    private static function stripQuotes(string $value): string
    {
        if (strlen($value) >= 2) {
            $first = $value[0];
            $last  = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }

    private static function expandReferences(string $value): string
    {
        return preg_replace_callback(
            '/\$\{([A-Z0-9_]+)\}/i',
            static fn(array $m): string => (string) (self::get($m[1]) ?? ''),
            $value
        );
    }
}
