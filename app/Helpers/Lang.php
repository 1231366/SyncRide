<?php
declare(strict_types=1);

function t(string $key): string
{
    static $strings = null;
    if ($strings === null) {
        $lang  = $_SESSION['admin_lang'] ?? 'en';
        $file  = dirname(__DIR__, 2) . '/resources/lang/' . $lang . '.php';
        $strings = is_file($file) ? (require $file) : [];
    }
    return (string) ($strings[$key] ?? $key);
}
