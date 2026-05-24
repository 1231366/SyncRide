<?php

declare(strict_types=1);

require __DIR__ . '/../../auth/dbconfig.php';

if (!isset($_SESSION['user_id'], $_SESSION['role']) || (int) $_SESSION['role'] !== 1) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Streams a SQL dump (schema + data) of every table in the current database.
 * Reuses the shared PDO connection bootstrapped by `auth/dbconfig.php`.
 */
function exportDatabase(PDO $db): string
{
    $sql = "SET NAMES utf8mb4;\n";

    $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $createRow = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
        $sql .= "\n\n" . $createRow[1] . ";\n\n";

        $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $cols   = implode(', ', array_keys($row));
            $values = implode(', ', array_map(static fn($v) => $v === null ? 'NULL' : $db->quote((string) $v), array_values($row)));
            $sql   .= "INSERT INTO `{$table}` ({$cols}) VALUES ({$values});\n";
        }
        $sql .= "\n";
    }

    return $sql;
}

try {
    $dump = exportDatabase($pdo);
    $pdo->prepare("INSERT INTO Logs (Action, date) VALUES ('Database backup generated', NOW())")->execute();

    $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo $dump;
} catch (Throwable $e) {
    error_log('backup failed: ' . $e->getMessage());
    http_response_code(500);
    echo "Backup failed.";
}
