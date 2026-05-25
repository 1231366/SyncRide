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
$sql = file_get_contents(__DIR__ . '/migrations/001_multi_tenant.sql');

// Split on semicolons, skip comments and empty statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    fn(string $s) => $s !== '' && !str_starts_with(ltrim($s), '--')
);

$ok     = 0;
$errors = [];

foreach ($statements as $statement) {
    try {
        $pdo->exec($statement);
        $ok++;
    } catch (\PDOException $e) {
        $errors[] = ['sql' => substr($statement, 0, 120) . '…', 'error' => $e->getMessage()];
    }
}

$isCli = PHP_SAPI === 'cli';
$nl     = $isCli ? "\n" : "<br>\n";

echo $isCli ? '' : '<!DOCTYPE html><meta charset="utf-8"><pre>';
echo "Migration 001_multi_tenant{$nl}";
echo "Statements executed: {$ok}{$nl}";

if ($errors !== []) {
    echo "ERRORS ({$nl}";
    foreach ($errors as $e) {
        echo "  SQL: {$e['sql']}{$nl}";
        echo "  ERR: {$e['error']}{$nl}{$nl}";
    }
} else {
    echo "All statements ran without errors.{$nl}";
}

echo $isCli ? '' : '</pre>';
