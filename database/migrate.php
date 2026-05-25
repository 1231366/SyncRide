<?php
/**
 * One-time migration runner.
 *
 * Run from CLI:  php database/migrate.php
 * Run from web:  http://localhost/SRMT/database/migrate.php
 *                (DELETE this file from the server after running!)
 */
declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use App\Support\Database;

$pdo = Database::connection();
$raw = file_get_contents(__DIR__ . '/migrations/001_multi_tenant.sql');

// Strip every comment line before splitting on ";"
// This prevents semicolons inside comment text from creating broken fragments.
$stripped = implode("\n", array_filter(
    explode("\n", $raw),
    fn(string $line) => !str_starts_with(ltrim($line), '--')
));

$statements = array_filter(
    array_map('trim', explode(';', $stripped)),
    fn(string $s) => $s !== ''
);

$ok     = 0;
$errors = [];

foreach ($statements as $statement) {
    try {
        $pdo->exec($statement);
        $ok++;
    } catch (\PDOException $e) {
        $code = (int) $e->getCode();
        // 1060 = Duplicate column name  (column already exists — safe to ignore)
        // 1061 = Duplicate key name      (index already exists — safe to ignore)
        // 1146 = Table doesn't exist     (optional table — safe to ignore)
        if (in_array($code, [1060, 1061, 1146], true)) {
            $ok++; // treat as OK — migration is idempotent
        } else {
            $errors[] = [
                'sql'   => substr($statement, 0, 200) . (strlen($statement) > 200 ? '…' : ''),
                'error' => $e->getMessage(),
            ];
        }
    }
}

$isCli = PHP_SAPI === 'cli';
$nl    = $isCli ? "\n" : "<br>\n";

echo $isCli ? '' : '<!DOCTYPE html><meta charset="utf-8"><pre>';
echo "Migration 001_multi_tenant{$nl}";
echo "Statements OK: {$ok}{$nl}";

if ($errors !== []) {
    echo "ERRORS:" . $nl;
    foreach ($errors as $e) {
        echo "  SQL: {$e['sql']}{$nl}";
        echo "  ERR: {$e['error']}{$nl}{$nl}";
    }
} else {
    echo "All statements ran without errors. Safe to delete this file.{$nl}";
}

echo $isCli ? '' : '</pre>';
