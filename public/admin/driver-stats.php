<?php
session_start();

// 1. VERIFICAÇÃO DE ADMIN
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    header("refresh: 1; url=/SRMT/public/");
    exit();
}

require __DIR__ . '/../../auth/dbconfig.php';
$pdo->exec("SET NAMES utf8mb4");

// =================================================================
// 2. LÓGICA DE DADOS & FILTROS (Tua lógica original integral)
// =================================================================

$userPhoto = (isset($_SESSION['profile_photo_path']) && !empty($_SESSION['profile_photo_path'])) 
    ? "../../../" . $_SESSION['profile_photo_path'] : "https://api.dicebear.com/7.x/avataaars/svg?seed=Felix";

$driver_id = isset($_GET['driver_id']) && $_GET['driver_id'] !== '' ? (int)$_GET['driver_id'] : null;
$partner_id = isset($_GET['partner_id']) && $_GET['partner_id'] !== '' ? (int)$_GET['partner_id'] : null;
$startDate = isset($_GET['start_date']) && !empty($_GET['start_date']) ? $_GET['start_date'] : date('Y-01-01');
$endDate = isset($_GET['end_date']) && !empty($_GET['end_date']) ? $_GET['end_date'] : date('Y-12-31');

$page_title = "Estatísticas";
$driver_name = "Visão Geral";
$meses_nomes = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

try {
    $available_drivers = $pdo->query("SELECT id, name FROM Users WHERE role = 2 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $available_partners = $pdo->query("SELECT id, name FROM Users WHERE role = 3 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { die("Erro BD: " . $e->getMessage()); }

// Valores padrão para os Cards
$box1_val = 0; $box1_lbl = "-"; $box1_icon = "users"; $box1_color = "text-blue-500";
$box2_val = 0; $box2_lbl = "-"; $box2_icon = "calendar"; $box2_color = "text-emerald-500";
$box3_val = 0; $box3_lbl = "-"; $box3_icon = "bar-chart-2"; $box3_color = "text-purple-500";
$box4_val = 0; $box4_lbl = "-"; $box4_icon = "globe"; $box4_color = "text-orange-500";

$chart_data = array_fill(0, 12, 0);
$table_data = [];
$partners_leaderboard = []; 
$table_title = "";

if ($driver_id) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM Users WHERE id = ? AND role = 2");
        $stmt->execute([$driver_id]);
        $driver_name = $stmt->fetchColumn() ?: "Condutor";
        $page_title = "Performance";

        $stmt = $pdo->prepare("SELECT 
            (SELECT COUNT(*) FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID WHERE sr.UserID = ? AND s.serviceDate = CURDATE()) AS trips_today,
            (SELECT AVG(s.driver_rating) FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID WHERE sr.UserID = ? AND s.driver_rating IS NOT NULL) AS avg_rating,
            (SELECT COUNT(*) FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID WHERE sr.UserID = ? AND s.serviceDate BETWEEN ? AND ?) AS trips_period,
            (SELECT COUNT(*) FROM Services_Rides sr WHERE sr.UserID = ?) AS trips_total");
        $stmt->execute([$driver_id, $driver_id, $driver_id, $startDate, $endDate, $driver_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $box1_val = $stats['trips_today']; $box1_lbl = "Hoje"; $box1_icon = "navigation";
        $box2_val = $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : "5.0";  $box2_lbl = "Rating"; $box2_icon = "star"; $box2_color = "text-orange-500";
        $box3_val = $stats['trips_period']; $box3_lbl = "No Período"; $box3_icon = "calendar-range"; $box3_color = "text-purple-500";
        $box4_val = $stats['trips_total']; $box4_lbl = "Total Histórico"; $box4_icon = "trophy"; $box4_color = "text-emerald-500";

        $stmtChart = $pdo->prepare("SELECT MONTH(s.serviceDate) AS mes, COUNT(sr.associationID) AS total FROM Services_Rides AS sr JOIN Services AS s ON sr.RideID = s.ID WHERE sr.UserID = ? AND s.serviceDate BETWEEN ? AND ? GROUP BY mes");
        $stmtChart->execute([$driver_id, $startDate, $endDate]);
        $monthly_results = $stmtChart->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($monthly_results as $mes => $total) { $chart_data[$mes - 1] = (int)$total; }

        $table_title = "Histórico Recente";
        $stmtTable = $pdo->prepare("SELECT s.ID, s.serviceDate, s.serviceStartTime, s.serviceStartPoint, s.serviceTargetPoint FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID WHERE sr.UserID = ? AND s.serviceDate BETWEEN ? AND ? ORDER BY s.serviceDate DESC LIMIT 10");
        $stmtTable->execute([$driver_id, $startDate, $endDate]);
        $table_data = $stmtTable->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { die("Erro: " . $e->getMessage()); }
} elseif ($partner_id) {
    try {
        $stmt = $pdo->prepare("SELECT name FROM Users WHERE id = ? AND role = 3");
        $stmt->execute([$partner_id]);
        $driver_name = $stmt->fetchColumn() ?: "Parceiro";
        $page_title = "Parceiro";

        $stmt = $pdo->prepare("SELECT 
            (SELECT COUNT(*) FROM Services WHERE partner_id = ? AND serviceDate = CURDATE()) AS trips_today,
            (SELECT COUNT(*) FROM Services WHERE partner_id = ? AND serviceDate BETWEEN ? AND ?) AS trips_period,
            (SELECT COUNT(*) FROM Services WHERE partner_id = ?) AS trips_total");
        $stmt->execute([$partner_id, $partner_id, $startDate, $endDate, $partner_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $box1_val = $stats['trips_today']; $box1_lbl = "Hoje"; $box1_icon = "megaphone";
        $box2_val = "-"; $box2_lbl = "Ativo"; $box2_icon = "building"; $box2_color = "text-blue-500";
        $box3_val = $stats['trips_period']; $box3_lbl = "No Período"; $box3_icon = "calendar-range"; $box3_color = "text-purple-500";
        $box4_val = $stats['trips_total']; $box4_lbl = "Total Geral"; $box4_icon = "check-circle"; $box4_color = "text-emerald-500";

        $stmtChart = $pdo->prepare("SELECT MONTH(serviceDate) AS mes, COUNT(ID) AS total FROM Services WHERE partner_id = ? AND serviceDate BETWEEN ? AND ? GROUP BY mes");
        $stmtChart->execute([$partner_id, $startDate, $endDate]);
        $monthly_results = $stmtChart->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($monthly_results as $mes => $total) { $chart_data[$mes - 1] = (int)$total; }

        $table_title = "Últimos Serviços";
        $stmtTable = $pdo->prepare("SELECT ID, serviceDate, serviceStartTime, serviceStartPoint, serviceTargetPoint FROM Services WHERE partner_id = ? AND serviceDate BETWEEN ? AND ? ORDER BY serviceDate DESC LIMIT 10");
        $stmtTable->execute([$partner_id, $startDate, $endDate]);
        $table_data = $stmtTable->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { die("Erro: " . $e->getMessage()); }
} else {
    $page_title = "Geral";
    $table_title = "Rankings";
    try {
        $box1_val = $pdo->query("SELECT COUNT(*) FROM Users WHERE role = 2")->fetchColumn(); $box1_lbl = "Condutores"; $box1_icon = "users";
        $box2_val = $pdo->query("SELECT COUNT(*) FROM Services WHERE serviceDate = CURDATE()")->fetchColumn(); $box2_lbl = "Hoje"; $box2_icon = "car";
        $stmtBox3 = $pdo->prepare("SELECT COUNT(*) FROM Services WHERE serviceDate BETWEEN ? AND ?");
        $stmtBox3->execute([$startDate, $endDate]);
        $box3_val = $stmtBox3->fetchColumn(); $box3_lbl = "No Período"; $box3_icon = "bar-chart-2"; $box3_color = "text-purple-500";
        $box4_val = $pdo->query("SELECT COUNT(*) FROM Services")->fetchColumn(); $box4_lbl = "Total Histórico"; $box4_icon = "globe"; $box4_color = "text-emerald-500";

        $stmtChart = $pdo->prepare("SELECT MONTH(serviceDate) AS mes, COUNT(ID) AS total FROM Services WHERE serviceDate BETWEEN ? AND ? GROUP BY mes");
        $stmtChart->execute([$startDate, $endDate]);
        $monthly_results = $stmtChart->fetchAll(PDO::FETCH_KEY_PAIR);
        foreach ($monthly_results as $mes => $total) { $chart_data[$mes - 1] = (int)$total; }

        $sqlTable = "SELECT u.id, u.name, 
            (SELECT COUNT(*) FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID WHERE sr.UserID = u.id AND s.serviceDate BETWEEN ? AND ?) AS trips_period, 
            (SELECT AVG(s.driver_rating) FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID WHERE sr.UserID = u.id AND s.driver_rating IS NOT NULL) AS avg_rating
            FROM Users u WHERE u.role = 2 ORDER BY trips_period DESC LIMIT 5";
        $stmtTable = $pdo->prepare($sqlTable);
        $stmtTable->execute([$startDate, $endDate]);
        $table_data = $stmtTable->fetchAll(PDO::FETCH_ASSOC);

        $sqlPartners = "SELECT u.id, u.name, (SELECT COUNT(*) FROM Services s WHERE s.partner_id = u.id AND s.serviceDate BETWEEN ? AND ?) AS trips_period FROM Users u WHERE u.role = 3 ORDER BY trips_period DESC LIMIT 5";
        $stmtP = $pdo->prepare($sqlPartners);
        $stmtP->execute([$startDate, $endDate]);
        $partners_leaderboard = $stmtP->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) { die("Erro: " . $e->getMessage()); }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#000000">
    <title>Stats | SyncRide OS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root { --safe-bottom: env(safe-area-inset-bottom, 20px); }
        html { background-color: #000; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #000; color: #fff; margin: 0; min-height: 100vh; overflow-x: hidden; -webkit-font-smoothing: antialiased; }
        
        .bg-main { 
            background: radial-gradient(circle at 50% -10%, #1e40af 0%, #000 75%); 
            background-attachment: fixed; min-height: 100vh;
        }

        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); }
        
        .nav-float {
            position: fixed; bottom: calc(16px + var(--safe-bottom)); left: 50%; transform: translateX(-50%);
            width: calc(100% - 32px); max-width: 400px; height: 72px;
            background: rgba(18, 18, 18, 0.95); backdrop-filter: blur(25px);
            border-radius: 26px; border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex; justify-content: space-around; align-items: center; z-index: 1000;
        }
        .nav-float a { flex: 1; }
        .nav-float .nav-extra { display: none !important; }
        @media (min-width: 992px) {
            .nav-float { max-width: 720px; height: 78px; border-radius: 32px; }
            .nav-float .nav-extra { display: flex !important; }
            .nav-float a span { font-size: 8px !important; }
        }

        .filter-input { background: rgba(255,255,255,0.05) !important; border: 1px solid rgba(255,255,255,0.1) !important; color: white !important; outline: none; border-radius: 12px; padding: 8px 12px; font-size: 11px; font-weight: 600; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        
        .rank-badge { width: 22px; height: 22px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800; }
        .rank-1 { background: #fbbf24; color: #000; }
        .rank-2 { background: #94a3b8; color: #000; }
        .rank-3 { background: #cd7f32; color: #000; }
    </style>
</head>
<body class="bg-main">
    <div class="pb-32">
        <header class="px-6 pt-10 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <img src="<?= $userPhoto ?>" class="w-10 h-10 rounded-full border-2 border-blue-500/20 object-cover">
                <div>
                    <h2 class="text-[15px] font-extrabold leading-tight"><?= $driver_name ?></h2>
                    <p class="text-[8px] text-zinc-500 font-black tracking-widest uppercase italic"><?= $page_title ?> OS</p>
                </div>
            </div>
            <button onclick="toggleMenu()" class="w-10 h-10 glass rounded-full flex items-center justify-center active:scale-90 transition-transform">
                <i data-lucide="menu" class="w-4 h-4 text-white"></i>
            </button>
        </header>

        <section class="px-6 mt-6">
            <form method="GET" class="space-y-3">
                <div class="grid grid-cols-2 gap-2">
                    <select name="driver_id" class="filter-input" onchange="if(this.value) this.form.partner_id.value=''; this.form.submit();">
                        <option value="">Condutores</option>
                        <?php foreach ($available_drivers as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($d['id'] == $driver_id) ? 'selected' : '' ?>>🚗 <?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="partner_id" class="filter-input" onchange="if(this.value) this.form.driver_id.value=''; this.form.submit();">
                        <option value="">Parceiros</option>
                        <?php foreach ($available_partners as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= ($p['id'] == $partner_id) ? 'selected' : '' ?>>🏢 <?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex gap-2 items-center glass p-2 rounded-2xl">
                    <input type="date" name="start_date" class="bg-transparent text-[10px] font-bold outline-none flex-1" value="<?= $startDate ?>">
                    <i data-lucide="arrow-right" class="w-3 h-3 text-zinc-600"></i>
                    <input type="date" name="end_date" class="bg-transparent text-[10px] font-bold outline-none flex-1" value="<?= $endDate ?>">
                    <button type="submit" class="w-8 h-8 bg-blue-600 rounded-xl flex items-center justify-center"><i data-lucide="search" class="w-4 h-4 text-white"></i></button>
                </div>
            </form>
        </section>

        <section class="px-6 mt-6 grid grid-cols-2 gap-3">
            <div class="glass p-4 rounded-[22px]">
                <div class="flex justify-between items-start mb-2">
                    <i data-lucide="<?= $box1_icon ?>" class="w-4 h-4 <?= $box1_color ?>"></i>
                    <span class="text-[7px] font-black text-zinc-600 uppercase italic"><?= $box1_lbl ?></span>
                </div>
                <h3 class="text-2xl font-black"><?= $box1_val ?></h3>
            </div>
            <div class="glass p-4 rounded-[22px]">
                <div class="flex justify-between items-start mb-2">
                    <i data-lucide="<?= $box2_icon ?>" class="w-4 h-4 <?= $box2_color ?>"></i>
                    <span class="text-[7px] font-black text-zinc-600 uppercase italic"><?= $box2_lbl ?></span>
                </div>
                <h3 class="text-2xl font-black"><?= $box2_val ?></h3>
            </div>
            <div class="glass p-4 rounded-[22px]">
                <div class="flex justify-between items-start mb-2">
                    <i data-lucide="<?= $box3_icon ?>" class="w-4 h-4 <?= $box3_color ?>"></i>
                    <span class="text-[7px] font-black text-zinc-600 uppercase italic"><?= $box3_lbl ?></span>
                </div>
                <h3 class="text-2xl font-black"><?= $box3_val ?></h3>
            </div>
            <div class="glass p-4 rounded-[22px]">
                <div class="flex justify-between items-start mb-2">
                    <i data-lucide="<?= $box4_icon ?>" class="w-4 h-4 <?= $box4_color ?>"></i>
                    <span class="text-[7px] font-black text-zinc-600 uppercase italic"><?= $box4_lbl ?></span>
                </div>
                <h3 class="text-2xl font-black"><?= $box4_val ?></h3>
            </div>
        </section>

        <section class="px-6 mt-4">
            <div class="glass rounded-[28px] p-5">
                <h3 class="text-[10px] font-black text-white uppercase tracking-widest mb-4 italic">Evolução Mensal</h3>
                <div id="mainChart"></div>
            </div>
        </section>

        <section class="px-6 mt-8">
            <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 italic mb-4 px-1"><?= $table_title ?></h3>
            <div class="space-y-2">
                <?php if (!$driver_id && !$partner_id): ?>
                    <?php foreach ($table_data as $k => $d): ?>
                        <div class="glass p-3.5 rounded-2xl flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="rank-badge rank-<?= $k+1 ?>"><?= $k+1 ?></div>
                                <div>
                                    <h4 class="text-xs font-bold text-white"><?= htmlspecialchars($d['name']) ?></h4>
                                    <p class="text-[9px] text-amber-500 font-bold"><i data-lucide="star" class="w-2.5 h-2.5 inline mr-1"></i><?= number_format($d['avg_rating'], 1) ?></p>
                                </div>
                            </div>
                            <span class="text-xs font-black text-blue-500"><?= $d['trips_period'] ?> rides</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($table_data as $row): ?>
                        <div class="glass p-3.5 rounded-2xl flex items-center justify-between">
                            <div>
                                <h4 class="text-[10px] font-bold text-white"><?= date('d M', strtotime($row['serviceDate'])) ?> • <?= substr($row['serviceStartTime'], 0, 5) ?></h4>
                                <p class="text-[9px] text-zinc-500 truncate w-40"><?= htmlspecialchars($row['serviceStartPoint']) ?> → <?= htmlspecialchars($row['serviceTargetPoint']) ?></p>
                            </div>
                            <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-800"></i>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <nav class="nav-float">
        <a href="/SRMT/public/admin/" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="home" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Home</span></a>
        <a href="rides.php" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="calendar" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Rides</span></a>
        <a href="live-map.php" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="locate-fixed" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Live</span></a>
        <a href="financial.php" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="wallet" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Cash</span></a>
        <a href="fleet.php" class="nav-extra flex-col items-center gap-1 text-zinc-500"><i data-lucide="truck" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Frota</span></a>
        <a href="users.php" class="nav-extra flex-col items-center gap-1 text-zinc-500"><i data-lucide="users" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Equipa</span></a>
        <a href="driver-stats.php" class="nav-extra flex-col items-center gap-1 text-blue-500"><i data-lucide="bar-chart-3" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Stats</span></a>
        <a href="no-shows.php" class="nav-extra flex-col items-center gap-1 text-zinc-500"><i data-lucide="alert-triangle" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">No Show</span></a>
        <a href="storage.php" class="nav-extra flex-col items-center gap-1 text-zinc-500"><i data-lucide="database" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Storage</span></a>
    </nav>

    <div id="fullMenu" class="fixed inset-0 z-[2000] hidden">
        <div class="absolute inset-0 bg-black/98 backdrop-blur-2xl" onclick="toggleMenu()"></div>
        <div class="relative h-full flex flex-col p-10 text-white overflow-y-auto no-scrollbar">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-3xl font-black italic tracking-tighter">SyncRide <span class="text-blue-600">OS</span></h2>
                <button onclick="toggleMenu()" class="w-12 h-12 glass rounded-full flex items-center justify-center"><i data-lucide="x"></i></button>
            </div>
            <nav class="grid grid-cols-1 gap-6 text-xl font-bold">
                <a href="/SRMT/public/admin/" class="flex items-center gap-4"><i data-lucide="layout-grid"></i> Dashboard</a>
                <a href="rides.php" class="flex items-center gap-4"><i data-lucide="navigation"></i> Viagens</a>
                <a href="live-map.php" class="flex items-center gap-4"><i data-lucide="map"></i> Live Map</a>
                <hr class="border-zinc-800">
                <a href="users.php" class="flex items-center gap-4"><i data-lucide="users"></i> Equipa</a>
                <a href="fleet.php" class="flex items-center gap-4"><i data-lucide="truck"></i> Frota</a>
                <a href="financial.php" class="flex items-center gap-4"><i data-lucide="banknote"></i> Financeiro</a>
                <hr class="border-zinc-800">
                <a href="driver-stats.php" class="flex items-center gap-4 text-blue-500"><i data-lucide="bar-chart-3"></i> Estatísticas</a>
                <a href="no-shows.php" class="flex items-center gap-4"><i data-lucide="alert-triangle"></i> No Shows</a>
                <a href="storage.php" class="flex items-center gap-4"><i data-lucide="database"></i> Armazenamento</a>
                <hr class="border-zinc-800">
                <a href="/SRMT/public/auth/logout.php" class="flex items-center gap-4 text-red-500 mt-4"><i data-lucide="log-out"></i> Logout</a>
            </nav>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function toggleMenu() { document.getElementById('fullMenu').classList.toggle('hidden'); }

        const options = {
            series: [{ name: 'Viagens', data: <?= json_encode($chart_data) ?> }],
            chart: { height: 200, type: 'area', toolbar: { show: false }, sparkline: { enabled: false } },
            colors: ['#3b82f6'],
            stroke: { curve: 'smooth', width: 2 },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0 } },
            xaxis: { categories: <?= json_encode($meses_nomes) ?>, labels: { style: { colors: '#71717a', fontSize: '9px' } } },
            yaxis: { labels: { show: false } },
            grid: { show: false },
            dataLabels: { enabled: false },
            tooltip: { theme: 'dark' }
        };
        new ApexCharts(document.querySelector("#mainChart"), options).render();
    </script>
</body>
</html>