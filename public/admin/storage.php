<?php
session_start();

// 1. VERIFICAÇÃO DE ADMIN
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    header("refresh: 1; url=/SRMT/public/");
    exit();
}

require __DIR__ . '/../../auth/dbconfig.php';

// 2. Lógica da Foto de Perfil
$defaultPhoto = "../assets/img/user2-160x160.jpg";
$userPhoto = $defaultPhoto;
if (isset($_SESSION['profile_photo_path']) && !empty($_SESSION['profile_photo_path'])) {
    $userPhoto = "../../../" . $_SESSION['profile_photo_path'];
}

// 3. SAÚDE DO SISTEMA
$sql = "SELECT date FROM Logs WHERE Action = 'Backup da base de dados realizado' ORDER BY date DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$lastBackup = $stmt->fetch(PDO::FETCH_ASSOC);

$progress = 0; $status = "Sem Dados"; $badge = "secondary"; $msg = "Nenhum registo encontrado.";
$statusColor = "#71717a";

if ($lastBackup) {
    $diff = (time() - strtotime($lastBackup['date'])) / (60 * 60 * 24);
    if ($diff < 7) {
        $progress = 100; $status = "Sistema Saudável"; $badge = "success"; $statusColor = "#34d399";
        $msg = "Último backup: " . date("d/m/Y", strtotime($lastBackup['date']));
    } elseif ($diff < 30) {
        $progress = 60; $status = "Atenção Necessária"; $badge = "warning"; $statusColor = "#fbbf24";
        $msg = "Backup antigo (" . round($diff) . " dias).";
    } else {
        $progress = 30; $status = "Risco Crítico"; $badge = "danger"; $statusColor = "#f87171";
        $msg = "Recomendado backup urgente!";
    }
}

// 4. LOGS RECENTES
$sql = "SELECT Action, date FROM Logs ORDER BY date DESC LIMIT 6";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Armazenamento | SyncRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no" />
    <meta name="theme-color" content="#000000">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>

    <style>
        :root {
            --safe-top: env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 20px);
        }
        html, body { background-color: #000; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #fff; margin: 0; -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            background: radial-gradient(circle at 50% -10%, #1e40af 0%, #000 75%);
            background-attachment: fixed;
            padding-bottom: calc(110px + var(--safe-bottom));
            padding-top: var(--safe-top);
        }

        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.08); }
        .glass-strong { background: rgba(20, 20, 20, 0.92); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px); border: 1px solid rgba(255, 255, 255, 0.1); }

        .app-top { padding: 28px 24px 0 24px; display: flex; justify-content: space-between; align-items: center; }
        .icon-btn {
            width: 40px; height: 40px; border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            color: #fff; transition: all .2s;
        }
        .icon-btn:hover { background: rgba(255,255,255,0.1); }
        .icon-btn:active { transform: scale(0.92); }

        .page-title { font-size: 24px; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
        .page-subtitle { font-size: 11px; color: #71717a; font-weight: 600; }

        /* ACTION CARDS (big tiles) */
        .action-tile {
            display: flex; flex-direction: column; align-items: flex-start; justify-content: space-between;
            padding: 22px; border-radius: 22px;
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
            color: #fff; cursor: pointer; transition: all .2s;
            min-height: 140px; width: 100%;
            backdrop-filter: blur(20px);
            text-align: left;
        }
        .action-tile:hover { background: rgba(255,255,255,0.08); transform: translateY(-2px); }
        .action-tile:active { transform: scale(0.98); }
        .action-tile .ico {
            width: 44px; height: 44px; border-radius: 14px;
            display: inline-flex; align-items: center; justify-content: center;
            margin-bottom: 12px;
        }
        .action-tile .title { font-size: 14px; font-weight: 800; color: #fff; letter-spacing: -0.01em; }
        .action-tile .sub { font-size: 11px; color: #a1a1aa; font-weight: 600; margin-top: 4px; }

        .tile-backup .ico { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .tile-delete .ico { background: rgba(239, 68, 68, 0.15); color: #f87171; }
        .tile-clear  .ico { background: rgba(251, 191, 36, 0.15); color: #fbbf24; }

        /* HEALTH CARD */
        .health-card {
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 22px; padding: 28px;
            text-align: center; backdrop-filter: blur(20px);
            display: flex; flex-direction: column; align-items: center; gap: 16px;
        }
        .health-ring {
            width: 96px; height: 96px; border-radius: 999px;
            display: flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
            position: relative;
        }
        .health-ring .dot {
            position: absolute; top: 6px; right: 6px;
            width: 14px; height: 14px; border-radius: 999px;
            border: 3px solid #0a0a0a;
        }
        .health-title { font-size: 20px; font-weight: 800; color: #fff; }
        .health-msg { font-size: 12px; color: #a1a1aa; font-weight: 600; }
        .progress-rail {
            width: 100%; height: 8px; border-radius: 999px;
            background: rgba(255,255,255,0.06); overflow: hidden;
        }
        .progress-fill { height: 100%; border-radius: 999px; transition: width .4s; }

        /* HISTORY LIST */
        .log-card {
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 22px; padding: 4px;
            backdrop-filter: blur(20px); height: 100%;
        }
        .log-head {
            display: flex; align-items: center; gap: 10px;
            padding: 18px 20px 12px 20px;
        }
        .log-head h3 { font-size: 13px; font-weight: 800; color: #fff; letter-spacing: -0.01em; }
        .log-item {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 20px; border-top: 1px solid rgba(255,255,255,0.05);
            font-size: 13px;
        }
        .log-item .action { color: #e4e4e7; font-weight: 600; max-width: 70%;
            overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .log-item .when {
            font-family: monospace; font-size: 10px; color: #a1a1aa;
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06);
            padding: 4px 8px; border-radius: 999px;
        }
        .log-empty {
            padding: 40px 20px; text-align: center; color: #71717a;
            font-size: 12px; font-weight: 600;
        }

        /* Floating nav */
        .nav-float {
            position: fixed; bottom: calc(16px + var(--safe-bottom));
            left: 50%; transform: translateX(-50%);
            width: calc(100% - 32px); max-width: 400px; height: 72px;
            background: rgba(18, 18, 18, 0.95);
            backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px);
            border-radius: 26px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex; justify-content: space-around; align-items: center;
            z-index: 1000;
        }
        .nav-float a {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 4px; text-decoration: none; color: #71717a; flex: 1;
            transition: color .15s;
        }
        .nav-float a span { font-size: 7px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em; }
        .nav-float a.active { color: #3b82f6; }
        .nav-float a:hover { color: #fff; }
        .nav-float .nav-extra { display: none; }
        @media (min-width: 992px) {
            .nav-float { max-width: 720px; height: 78px; border-radius: 32px; }
            .nav-float .nav-extra { display: flex; }
            .nav-float a span { font-size: 8px; }
        }

        /* Hamburger menu */
        #fullMenu { position: fixed; inset: 0; z-index: 2000; display: none; }
        #fullMenu.open { display: block; }
        #fullMenu .mask { position: absolute; inset: 0; background: rgba(0,0,0,0.98); backdrop-filter: blur(40px); }
        #fullMenu .panel {
            position: relative; height: 100%; padding: 40px;
            display: flex; flex-direction: column; color: #fff; overflow-y: auto;
        }
        #fullMenu nav a {
            display: flex; align-items: center; gap: 16px;
            color: #fff; text-decoration: none; padding: 12px 0;
            font-size: 18px; font-weight: 700;
        }
        #fullMenu nav a.active-link { color: #60a5fa; }
        #fullMenu hr { border-color: #27272a; margin: 8px 0; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="app-top">
        <div class="flex items-center gap-3">
            <img src="<?php echo $userPhoto; ?>" class="w-10 h-10 rounded-full object-cover" style="border: 2px solid rgba(59,130,246,0.25);">
            <div>
                <h2 class="text-[15px] font-extrabold leading-tight">Olá, <?= explode(' ', $_SESSION['name'])[0] ?></h2>
                <p class="text-[8px] text-zinc-500 font-black tracking-widest uppercase italic">System Admin</p>
            </div>
        </div>
        <button onclick="toggleMenu()" class="icon-btn" aria-label="Menu">
            <i data-lucide="menu" class="w-4 h-4"></i>
        </button>
    </header>

    <main class="px-6 mt-8">

        <div class="mb-6">
            <h1 class="page-title">Armazenamento</h1>
            <p class="page-subtitle mt-1">Backups, limpeza e logs do sistema.</p>
        </div>

        <!-- ACTION TILES -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
            <button class="action-tile tile-backup" id="backup-btn">
                <div class="ico"><i data-lucide="cloud-download" class="w-5 h-5"></i></div>
                <div>
                    <div class="title">Fazer Backup</div>
                    <div class="sub">Descarregar SQL completo</div>
                </div>
            </button>
            <button class="action-tile tile-delete" id="delete-rides-btn">
                <div class="ico"><i data-lucide="trash-2" class="w-5 h-5"></i></div>
                <div>
                    <div class="title">Eliminar Viagens</div>
                    <div class="sub">Remove todas as viagens</div>
                </div>
            </button>
            <button class="action-tile tile-clear" id="clear-data-btn">
                <div class="ico"><i data-lucide="eraser" class="w-5 h-5"></i></div>
                <div>
                    <div class="title">Limpar Logs</div>
                    <div class="sub">Apaga histórico do sistema</div>
                </div>
            </button>
        </div>

        <!-- HEALTH + HISTORY GRID -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

            <!-- Health -->
            <div class="health-card">
                <div class="health-ring">
                    <i data-lucide="database" class="w-9 h-9" style="color: <?= $statusColor ?>"></i>
                    <span class="dot" style="background: <?= $statusColor ?>"></span>
                </div>
                <div>
                    <div class="health-title"><?= htmlspecialchars($status) ?></div>
                    <div class="health-msg mt-1"><?= htmlspecialchars($msg) ?></div>
                </div>
                <div class="progress-rail">
                    <div class="progress-fill" style="width: <?= $progress ?>%; background: <?= $statusColor ?>;"></div>
                </div>
            </div>

            <!-- History -->
            <div class="log-card">
                <div class="log-head">
                    <i data-lucide="scroll-text" class="w-4 h-4" style="color: #a1a1aa"></i>
                    <h3>Histórico Recente</h3>
                </div>
                <?php if (count($logs) > 0): ?>
                    <?php foreach ($logs as $row): ?>
                        <div class="log-item">
                            <span class="action"><?= htmlspecialchars($row['Action']) ?></span>
                            <span class="when"><?= date("d/m H:i", strtotime($row['date'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="log-empty">Sem registos recentes.</div>
                <?php endif; ?>
            </div>

        </div>

    </main>

    <!-- Floating bottom nav -->
    <nav class="nav-float">
        <a href="/SRMT/public/admin/"><i data-lucide="home" class="w-5 h-5"></i><span>Home</span></a>
        <a href="rides.php"><i data-lucide="calendar" class="w-5 h-5"></i><span>Rides</span></a>
        <a href="live-map.php"><i data-lucide="locate-fixed" class="w-5 h-5"></i><span>Live</span></a>
        <a href="financial.php"><i data-lucide="wallet" class="w-5 h-5"></i><span>Cash</span></a>
        <a href="fleet.php" class="nav-extra"><i data-lucide="truck" class="w-5 h-5"></i><span>Frota</span></a>
        <a href="users.php" class="nav-extra"><i data-lucide="users" class="w-5 h-5"></i><span>Equipa</span></a>
        <a href="driver-stats.php" class="nav-extra"><i data-lucide="bar-chart-3" class="w-5 h-5"></i><span>Stats</span></a>
        <a href="no-shows.php" class="nav-extra"><i data-lucide="alert-triangle" class="w-5 h-5"></i><span>No Show</span></a>
        <a href="storage.php" class="nav-extra active"><i data-lucide="database" class="w-5 h-5"></i><span>Storage</span></a>
    </nav>

    <!-- HAMBURGER MENU -->
    <div id="fullMenu">
        <div class="mask" onclick="toggleMenu()"></div>
        <div class="panel no-scrollbar">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-3xl font-black italic tracking-tighter">SyncRide <span style="color:#2563eb">OS</span></h2>
                <button onclick="toggleMenu()" class="icon-btn"><i data-lucide="x"></i></button>
            </div>
            <nav class="flex flex-col gap-2 text-lg font-bold">
                <a href="/SRMT/public/admin/"><i data-lucide="layout-grid"></i> Dashboard</a>
                <a href="rides.php"><i data-lucide="navigation"></i> Viagens</a>
                <a href="live-map.php"><i data-lucide="map"></i> Live Map</a>
                <hr>
                <a href="users.php"><i data-lucide="users"></i> Equipa</a>
                <a href="fleet.php"><i data-lucide="truck"></i> Frota</a>
                <a href="financial.php"><i data-lucide="banknote"></i> Financeiro</a>
                <hr>
                <a href="driver-stats.php"><i data-lucide="bar-chart-3"></i> Estatísticas</a>
                <a href="no-shows.php"><i data-lucide="alert-triangle"></i> No Shows</a>
                <a href="storage.php" class="active-link"><i data-lucide="database"></i> Armazenamento</a>
                <hr>
                <a href="/SRMT/public/auth/logout.php" style="color:#ef4444"><i data-lucide="log-out"></i> Logout</a>
            </nav>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        lucide.createIcons();

        function toggleMenu() {
            document.getElementById('fullMenu').classList.toggle('open');
        }

        toastr.options = { "closeButton": true, "progressBar": true, "positionClass": "toast-top-right", "timeOut": "3000" };

        document.getElementById('backup-btn').addEventListener('click', function () {
            fetch('backup.php').then(r => {
                if(!r.ok) throw new Error('Erro no backup');
                return r.blob();
            }).then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a'); a.href = url; a.download = 'backup.sql';
                document.body.appendChild(a); a.click(); a.remove();
                toastr.success('Backup transferido com sucesso!', 'SyncRide');
                setTimeout(() => window.location.reload(), 1500);
            }).catch(e => toastr.error(e.message, 'Erro'));
        });

        document.getElementById('delete-rides-btn').addEventListener('click', function () {
            if (confirm('ATENÇÃO: Vai apagar TODAS as viagens do sistema. Esta ação é irreversível.\n\nTem a certeza?')) {
                fetch('ride-delete.php', { method: 'POST' }).then(r => r.json()).then(data => {
                    if (data.success) { toastr.success('Todas as viagens foram eliminadas.', 'Concluído'); setTimeout(() => window.location.reload(), 1500); }
                    else toastr.error(data.message, 'Erro');
                });
            }
        });

        document.getElementById('clear-data-btn').addEventListener('click', function () {
            if (confirm('Tem a certeza que deseja limpar o histórico de logs?')) {
                fetch('clear-logs.php', { method: 'POST' }).then(r => r.json()).then(data => {
                    if (data.success) { toastr.success('Histórico de logs limpo.', 'Concluído'); setTimeout(() => window.location.reload(), 1500); }
                    else toastr.error(data.message, 'Erro');
                });
            }
        });
    </script>
  </body>
</html>
