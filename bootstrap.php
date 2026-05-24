<?php

declare(strict_types=1);

/**
 * Application bootstrap — entry point for every front-controller and CLI runner.
 *
 *   1. Loads the Composer autoloader (PSR-4 under App\ and Cron\).
 *   2. Loads environment variables from `.env`.
 *   3. Configures error reporting based on APP_DEBUG.
 *   4. Sets the default timezone.
 *
 * Every public endpoint (public/index.php, public/api/*.php) and every CLI
 * cron runner should include this file before doing anything else.
 */

use App\Support\Env;

// --- Composer autoloader (PSR-4 + dev dependencies) -------------------------
$autoload = __DIR__ . '/vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    // Minimal in-tree autoloader so the app still boots before `composer install`.
    spl_autoload_register(static function (string $class): void {
        $prefixes = [
            'App\\'  => __DIR__ . '/app/',
            'Cron\\' => __DIR__ . '/cron/',
        ];
        foreach ($prefixes as $prefix => $baseDir) {
            if (str_starts_with($class, $prefix)) {
                $relative = substr($class, strlen($prefix));
                $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
                if (is_file($file)) {
                    require $file;
                    return;
                }
            }
        }
    });
}

// --- Environment ------------------------------------------------------------
Env::load(__DIR__ . '/.env');

// --- Error reporting --------------------------------------------------------
// In dev we want warnings/fatals on screen, but the legacy pages emit a lot
// of PHP 8 `Deprecated` notices we cannot fix today — mute those in both
// environments so they do not pollute the browser output.
$debug = (bool) Env::get('APP_DEBUG', false);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('display_startup_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE & ~E_STRICT);

// --- Timezone ---------------------------------------------------------------
date_default_timezone_set((string) Env::get('APP_TIMEZONE', 'UTC'));
