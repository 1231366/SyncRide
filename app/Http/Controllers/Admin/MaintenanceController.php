<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\LogRepository;
use App\Repositories\ServiceRepository;
use PDO;

/**
 * Destructive admin operations grouped together because they share the
 * same shape: POST → action → JSON response.
 *
 *   - backup()    streams a full SQL dump of the database.
 *   - clearLogs() empties the audit log.
 *   - wipeRides() removes all Services + Services_Rides.
 */
final class MaintenanceController extends BaseController
{
    private LogRepository $logs;

    public function __construct()
    {
        $this->logs = LogRepository::default();
    }

    /** GET /admin/backup.php — stream a `.sql` dump. */
    public function backup(): never
    {
        $dump = $this->exportDatabase($this->db());
        $this->logs->record('Database backup generated');

        $filename = 'syncride_backup_' . date('Y-m-d_H-i-s') . '.sql';
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $dump;
        exit;
    }

    /** POST /admin/clear-logs.php — wipe the audit log. */
    public function clearLogs(): never
    {
        $this->requirePost();
        $this->logs->clear();
        $this->json(['success' => true, 'message' => 'Audit log cleared.']);
    }

    /** POST /admin/ride-delete.php — wipe every ride (kept for legacy bookmarks). */
    public function wipeRides(): never
    {
        $this->requirePost();
        try {
            ServiceRepository::default()->deleteAll();
            $this->logs->record('All rides deleted');
            $this->json(['success' => true, 'message' => 'All rides removed and logged.']);
        } catch (\Throwable $e) {
            error_log('wipeRides failed: ' . $e->getMessage());
            $this->json(['success' => false, 'message' => 'Failed to delete rides.'], 500);
        }
    }

    private function exportDatabase(PDO $db): string
    {
        $sql = "-- SyncRide backup generated " . date('c') . "\nSET NAMES utf8mb4;\n";

        $tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $createRow = $db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
            $sql .= "\n\n-- " . str_repeat('-', 60) . "\n-- Table: {$table}\n-- " . str_repeat('-', 60) . "\n\n";
            $sql .= $createRow[1] . ";\n\n";

            $rows = $db->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $cols   = implode(', ', array_map(static fn(string $c) => "`{$c}`", array_keys($row)));
                $values = implode(', ', array_map(
                    static fn($v) => $v === null ? 'NULL' : $db->quote((string) $v),
                    array_values($row),
                ));
                $sql .= "INSERT INTO `{$table}` ({$cols}) VALUES ({$values});\n";
            }
        }

        return $sql;
    }
}
