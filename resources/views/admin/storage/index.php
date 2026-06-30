<?php
use App\Http\View;

View::layout('layouts.admin', [
    'title'  => 'Storage — SyncRide OS',
    'active' => 'storage',
    'extraHead' => '
        <style>
            .action-tile {
                display: flex; flex-direction: column; align-items: flex-start;
                justify-content: space-between; padding: 22px; border-radius: 22px;
                cursor: pointer; transition: all .2s;
                min-height: 140px; width: 100%;
                backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
                text-align: left;
            }
            [data-theme="dark"]  .action-tile { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); color: #f1f5f9; }
            [data-theme="light"] .action-tile { background: rgba(255,255,255,0.65); border: 1px solid rgba(0,0,0,0.08); color: #0f172a; }
            [data-theme="dark"]  .action-tile:hover { background: rgba(255,255,255,0.08); transform: translateY(-2px); }
            [data-theme="light"] .action-tile:hover { background: rgba(255,255,255,0.88); transform: translateY(-2px); }
            .action-tile:active { transform: scale(0.98); }
            .action-tile .ico {
                width: 44px; height: 44px; border-radius: 14px;
                display: inline-flex; align-items: center; justify-content: center;
                margin-bottom: 12px;
            }
            .action-tile .title { font-size: 14px; font-weight: 800; letter-spacing: -0.01em; }
            .action-tile .sub   { font-size: 11px; color: #71717a; font-weight: 600; margin-top: 4px; }
            .tile-backup .ico { background: rgba(59,130,246,0.15); color: #3b82f6; }
            .tile-delete .ico { background: rgba(239,68,68,0.15);  color: #ef4444; }
            .tile-clear  .ico { background: rgba(251,191,36,0.15); color: #f59e0b; }

            .health-card {
                border-radius: 22px; padding: 28px; text-align: center;
                backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
                display: flex; flex-direction: column; align-items: center; gap: 16px;
            }
            [data-theme="dark"]  .health-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); }
            [data-theme="light"] .health-card { background: rgba(255,255,255,0.65); border: 1px solid rgba(0,0,0,0.08); }
            .health-ring {
                width: 96px; height: 96px; border-radius: 999px;
                display: flex; align-items: center; justify-content: center;
                position: relative;
            }
            [data-theme="dark"]  .health-ring { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08); }
            [data-theme="light"] .health-ring { background: rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.08); }
            .health-ring .dot {
                position: absolute; top: 6px; right: 6px;
                width: 14px; height: 14px; border-radius: 999px;
            }
            [data-theme="dark"]  .health-ring .dot { border: 3px solid #020617; }
            [data-theme="light"] .health-ring .dot { border: 3px solid #f1f5f9; }
            .health-title { font-size: 20px; font-weight: 800; }
            .health-msg   { font-size: 12px; color: #71717a; font-weight: 600; }
            .progress-rail {
                width: 100%; height: 8px; border-radius: 999px; overflow: hidden;
            }
            [data-theme="dark"]  .progress-rail { background: rgba(255,255,255,0.06); }
            [data-theme="light"] .progress-rail { background: rgba(0,0,0,0.08); }
            .progress-fill { height: 100%; border-radius: 999px; transition: width .4s; }

            .log-card {
                border-radius: 22px; padding: 4px;
                backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); height: 100%;
            }
            [data-theme="dark"]  .log-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); }
            [data-theme="light"] .log-card { background: rgba(255,255,255,0.65); border: 1px solid rgba(0,0,0,0.08); }
            .log-head { display: flex; align-items: center; gap: 10px; padding: 18px 20px 12px 20px; }
            .log-head h3 { font-size: 13px; font-weight: 800; letter-spacing: -0.01em; }
            .log-item {
                display: flex; justify-content: space-between; align-items: center;
                padding: 12px 20px; font-size: 13px;
            }
            [data-theme="dark"]  .log-item { border-top: 1px solid rgba(255,255,255,0.05); }
            [data-theme="light"] .log-item { border-top: 1px solid rgba(0,0,0,0.06); }
            .log-item .action {
                font-weight: 600; max-width: 70%;
                overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
            }
            .log-item .when {
                font-family: monospace; font-size: 10px; color: #71717a;
                padding: 4px 8px; border-radius: 999px;
            }
            [data-theme="dark"]  .log-item .when { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); }
            [data-theme="light"] .log-item .when { background: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.08); }
            .log-empty { padding: 40px 20px; text-align: center; color: #71717a; font-size: 12px; font-weight: 600; }
        </style>
    ',
    'extraScripts' => '
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
        <script>
            var SR_STORAGE = {
                backupOk: "' . addslashes(t('storage.backup_ok')) . '",
                done: "' . addslashes(t('storage.done')) . '",
                error: "' . addslashes(t('storage.error')) . '",
                logCleared: "' . addslashes(t('storage.log_cleared')) . '"
            };
            toastr.options = { closeButton: true, progressBar: true, positionClass: "toast-top-right", timeOut: 3000 };

            document.getElementById("backup-btn").addEventListener("click", function () {
                fetch("backup.php")
                    .then(r => { if (!r.ok) throw new Error("Backup error"); return r.blob(); })
                    .then(blob => {
                        const url = URL.createObjectURL(blob);
                        const a = document.createElement("a");
                        a.href = url; a.download = "backup.sql";
                        document.body.appendChild(a); a.click(); a.remove();
                        toastr.success(SR_STORAGE.backupOk, SR_STORAGE.done);
                        setTimeout(() => location.reload(), 1500);
                    })
                    .catch(e => toastr.error(e.message, SR_STORAGE.error));
            });

            document.getElementById("delete-rides-btn").addEventListener("click", function () {
                if (!confirm("WARNING: This will delete ALL rides from the system. This cannot be undone.\n\nAre you sure?")) return;
                fetch("ride-delete.php", { method: "POST" })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) { toastr.success("All rides deleted.", SR_STORAGE.done); setTimeout(() => location.reload(), 1500); }
                        else toastr.error(data.message, SR_STORAGE.error);
                    });
            });

            document.getElementById("clear-logs-btn").addEventListener("click", function () {
                if (!confirm("Clear the entire audit log history?")) return;
                fetch("clear-logs.php", { method: "POST" })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) { toastr.success(SR_STORAGE.logCleared, SR_STORAGE.done); setTimeout(() => location.reload(), 1500); }
                        else toastr.error(data.message, SR_STORAGE.error);
                    });
            });
        </script>
    ',
]);
?>

<main class="px-6 mt-8">
    <div class="mb-6">
        <h1 class="text-[24px] font-extrabold tracking-tight"><?= t('storage.title') ?></h1>
        <p class="text-[11px] text-zinc-500 font-semibold mt-1"><?= t('storage.subtitle') ?></p>
    </div>

    <!-- Action tiles -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
        <button class="action-tile tile-backup" id="backup-btn">
            <div class="ico"><i data-lucide="cloud-download" class="w-5 h-5"></i></div>
            <div>
                <div class="title"><?= t('storage.backup_db') ?></div>
                <div class="sub"><?= t('storage.backup_desc') ?></div>
            </div>
        </button>
        <button class="action-tile tile-delete" id="delete-rides-btn">
            <div class="ico"><i data-lucide="trash-2" class="w-5 h-5"></i></div>
            <div>
                <div class="title"><?= t('storage.delete_rides') ?></div>
                <div class="sub"><?= t('storage.delete_desc') ?></div>
            </div>
        </button>
        <button class="action-tile tile-clear" id="clear-logs-btn">
            <div class="ico"><i data-lucide="eraser" class="w-5 h-5"></i></div>
            <div>
                <div class="title"><?= t('storage.clear_log') ?></div>
                <div class="sub"><?= t('storage.clear_desc') ?></div>
            </div>
        </button>
    </div>

    <!-- Health + History -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <div class="health-card">
            <div class="health-ring">
                <i data-lucide="database" class="w-9 h-9" style="color:<?= htmlspecialchars($statusColor) ?>"></i>
                <span class="dot" style="background:<?= htmlspecialchars($statusColor) ?>"></span>
            </div>
            <div>
                <div class="health-title"><?= htmlspecialchars($status) ?></div>
                <div class="health-msg mt-1"><?= htmlspecialchars($msg) ?></div>
            </div>
            <div class="progress-rail">
                <div class="progress-fill" style="width:<?= (int) $progress ?>%;background:<?= htmlspecialchars($statusColor) ?>"></div>
            </div>
        </div>

        <div class="log-card">
            <div class="log-head">
                <i data-lucide="scroll-text" class="w-4 h-4 text-zinc-400"></i>
                <h3><?= t('storage.recent') ?></h3>
            </div>
            <?php if ($recent): ?>
                <?php foreach ($recent as $row): ?>
                    <div class="log-item">
                        <span class="action"><?= htmlspecialchars((string) $row['Action']) ?></span>
                        <span class="when"><?= htmlspecialchars(date('d/m H:i', strtotime((string) $row['date']))) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="log-empty"><?= t('storage.no_records') ?></div>
            <?php endif; ?>
        </div>

    </div>
</main>
