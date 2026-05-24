<?php

declare(strict_types=1);

/**
 * Test-suite bootstrap.
 *
 * Loads the Composer autoloader (or the fallback PSR-4 autoloader) so
 * tests can resolve App\* and Cron\* classes without touching .env.
 */

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require $autoload;
} else {
    spl_autoload_register(static function (string $class): void {
        $prefixes = [
            'App\\'   => __DIR__ . '/../app/',
            'Cron\\'  => __DIR__ . '/../cron/',
            'Tests\\' => __DIR__ . '/',
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
