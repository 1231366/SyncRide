<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\LogRepository;

final class StorageController extends BaseController
{
    private LogRepository $logs;

    public function __construct()
    {
        $this->logs = LogRepository::default();
    }

    /** GET /admin/storage.php */
    public function index(): void
    {
        $lastBackup = $this->logs->lastBackupDate();
        $recent     = $this->logs->recent(6);

        [$progress, $status, $statusColor, $msg] = $this->healthStatus($lastBackup);

        $this->view('admin.storage.index', compact(
            'lastBackup', 'recent', 'progress', 'status', 'statusColor', 'msg'
        ));
    }

    /** @return array{int,string,string,string} */
    private function healthStatus(?string $lastBackupDate): array
    {
        if ($lastBackupDate === null) {
            return [0, 'No Data', '#71717a', 'No backup record found.'];
        }
        $diff = (time() - strtotime($lastBackupDate)) / (60 * 60 * 24);
        if ($diff < 7) {
            return [100, 'System Healthy', '#34d399',
                'Last backup: ' . date('d/m/Y', strtotime($lastBackupDate))];
        }
        if ($diff < 30) {
            return [60, 'Attention Required', '#fbbf24',
                'Backup is ' . (int) round($diff) . ' days old.'];
        }
        return [30, 'Critical Risk', '#f87171', 'Urgent backup recommended!'];
    }
}
