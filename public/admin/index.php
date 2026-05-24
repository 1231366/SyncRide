<?php
session_start();

// 1. SEGURANÇA E CONEXÃO
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    header("refresh: 1; url=/SRMT/public/");
    exit();
}
require __DIR__ . '/../../auth/dbconfig.php';
$pdo->exec("SET NAMES utf8mb4");

// --- LÓGICA DE PARSING XML (INTEGRAL) ---
$successCount = 0;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['xmlFile']) && $_FILES['xmlFile']['error'] == 0) {
    $xmlContent = file_get_contents($_FILES['xmlFile']['tmp_name']);
    $xml = simplexml_load_string($xmlContent);
    if($xml && isset($xml->Groupings->Grouping)) {
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM Services WHERE serviceDate = ? AND serviceStartTime = ? AND NomeCliente = ? AND FlightNumber = ?");
        $sqlComId = "INSERT INTO Services (ID, serviceDate, serviceStartTime, paxADT, paxCHD, serviceStartPoint, serviceTargetPoint, FlightNumber, NomeCliente, ClientNumber, serviceType) VALUES (:ID, :sd, :st, :pa, :pc, :sp, :tp, :fn, :nc, :cn, :stype)";
        $sqlSemId = "INSERT INTO Services (serviceDate, serviceStartTime, paxADT, paxCHD, serviceStartPoint, serviceTargetPoint, FlightNumber, NomeCliente, ClientNumber, serviceType) VALUES (:sd, :st, :pa, :pc, :sp, :tp, :fn, :nc, :cn, :stype)";
        $stmtCom = $pdo->prepare($sqlComId); $stmtSem = $pdo->prepare($sqlSemId);

        foreach ($xml->Groupings->Grouping as $group) {
            $sDate = (string)$group->serviceDate; $sTime = (string)$group->serviceStartTime;
            $sType = (stripos((string)$group->serviceUnitVehicleName, 'Shared') !== false) ? 0 : 1;
            $xmlId = (int)$group->serviceId;

            foreach ($group->bookings->bookingItem as $index => $item) {
                $paxName = (string)$item->paxLeadName;
                $fNum = 'N/A';
                $fNode = (isset($item->pickup->pickupPoint->flightNumber) && (string)$item->pickup->pickupPoint->flightNumber !== "") ? $item->pickup->pickupPoint : ((isset($item->dropoff->pickupPoint->flightNumber) && (string)$item->dropoff->pickupPoint->flightNumber !== "") ? $item->dropoff->pickupPoint : null);
                if ($fNode) $fNum = (string)$fNode->flightCompanyCode . (string)$fNode->flightNumber;

                $checkStmt->execute([$sDate, $sTime, $paxName, $fNum]);
                if ($checkStmt->fetchColumn() == 0) {
                    $startPt = (string)$group->serviceStartPoint; $targetPt = (string)$group->serviceTargetPoint;
                    if (isset($item->pickup->accommodationtName) && (string)$item->pickup->accommodationtName !== "") $startPt = (string)$item->pickup->accommodationtName;
                    if (isset($item->dropoff->accommodationtName) && (string)$item->dropoff->accommodationtName !== "") $targetPt = (string)$item->dropoff->accommodationtName;
                    
                    $phone = 'N/A';
                    if (isset($item->remarks)) {
                        if (preg_match('/Mobile:\s*([^,]+)/i', (string)$item->remarks, $m)) $phone = trim($m[1]);
                        elseif (preg_match('/Phone number:\s*([^,]+)/i', (string)$item->remarks, $m)) $phone = trim($m[1]);
                    }

                    $params = [':sd'=>$sDate, ':st'=>$sTime, ':pa'=>(int)$item->paxADT, ':pc'=>(int)$item->paxCHD, ':sp'=>$startPt, ':tp'=>$targetPt, ':fn'=>$fNum, ':nc'=>$paxName, ':cn'=>$phone, ':stype'=>$sType];
                    try {
                        if ($index === 0) { $params[':ID'] = $xmlId; $stmtCom->execute($params); } 
                        else { $stmtSem->execute($params); }
                        $successCount++;
                    } catch (PDOException $e) { unset($params[':ID']); $stmtSem->execute($params); $successCount++; }
                }
            }
        }
    }
}

// 2. EXTRAÇÃO DE DADOS
try {
    $userPhoto = (isset($_SESSION['profile_photo_path']) && !empty($_SESSION['profile_photo_path'])) 
        ? "../../../" . $_SESSION['profile_photo_path'] : "https://api.dicebear.com/7.x/avataaars/svg?seed=Felix";
    $totalGeral = $pdo->query("SELECT COUNT(*) FROM Services")->fetchColumn();
    $totalHoje = $pdo->query("SELECT COUNT(*) FROM Services WHERE serviceDate = CURDATE()")->fetchColumn();
    $totalSemana = $pdo->query("SELECT COUNT(*) FROM Services WHERE WEEK(serviceDate, 1) = WEEK(CURDATE(), 1)")->fetchColumn();
    
    $stmtNext = $pdo->query("SELECT * FROM Services WHERE (serviceDate > CURDATE()) OR (serviceDate = CURDATE() AND serviceStartTime >= CURTIME()) ORDER BY serviceDate ASC, serviceStartTime ASC LIMIT 3");
    $nextRides = $stmtNext->fetchAll(PDO::FETCH_ASSOC);
    
    $monthlyData = array_fill(0, 12, 0);
    $stmtM = $pdo->query("SELECT MONTH(serviceDate) as mes, COUNT(*) as qtd FROM Services WHERE YEAR(serviceDate) = YEAR(CURDATE()) GROUP BY mes");
    while($row = $stmtM->fetch(PDO::FETCH_ASSOC)) { $monthlyData[$row['mes']-1] = (int)$row['qtd']; }
} catch (PDOException $e) { $error = $e->getMessage(); }
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#000000">
    <title>SyncRide OS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root { --safe-bottom: env(safe-area-inset-bottom, 20px); }
        html, body { height: 100%; overflow: hidden; background-color: #000; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: #fff; margin: 0; -webkit-font-smoothing: antialiased; }

        #app-container { height: 100%; overflow-y: auto; -webkit-overflow-scrolling: touch; }

        .bg-main {
            background: radial-gradient(circle at 50% -10%, #1e40af 0%, #000 75%);
            background-attachment: fixed;
            min-height: 100vh;
        }

        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); }

        #ai-overlay {
            position: fixed; top: 100%; left: 0; width: 100%; height: 92vh;
            background: rgba(10, 10, 10, 0.95); backdrop-filter: blur(40px);
            border-radius: 32px 32px 0 0; border: 1px solid rgba(255,255,255,0.1);
            z-index: 3000; transition: top 0.4s cubic-bezier(0.19, 1, 0.22, 1);
            display: flex; flex-direction: column;
        }
        #ai-overlay.active { top: 8vh; }

        /* PREVENT ZOOM & KEYBOARD JUMP */
        #ai-input { font-size: 16px !important; }

        #rideModal {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9);
            width: 85%; max-width: 360px; visibility: hidden; opacity: 0;
            background: rgba(20, 20, 20, 0.95); backdrop-filter: blur(30px);
            border-radius: 28px; border: 1px solid rgba(255,255,255,0.15);
            z-index: 4000; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            padding: 20px;
        }
        #rideModal.active { visibility: visible; opacity: 1; transform: translate(-50%, -50%) scale(1); }

        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px);
            visibility: hidden; opacity: 0; z-index: 3999; transition: all 0.3s;
        }
        .modal-overlay.active { visibility: visible; opacity: 1; }

        .action-circle {
            width: 52px; height: 52px; border-radius: 999px;
            background: rgba(255, 255, 255, 0.08); display: flex; align-items: center; justify-content: center;
            border: 1px solid rgba(255,255,255,0.1); transition: all 0.2s;
        }

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
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-main">
    <div id="app-container">
        <div class="pb-32">
            <header class="px-6 pt-10 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <img src="<?= $userPhoto ?>" class="w-10 h-10 rounded-full border-2 border-blue-500/20 object-cover">
                    <div>
                        <h2 class="text-[15px] font-extrabold leading-tight">Olá, <?= explode(' ', $_SESSION['name'])[0] ?></h2>
                        <p class="text-[8px] text-zinc-500 font-black tracking-widest uppercase italic">System Admin</p>
                    </div>
                </div>
                <button onclick="toggleMenu()" class="w-10 h-10 glass rounded-full flex items-center justify-center active:scale-90 transition-transform">
                    <i data-lucide="menu" class="w-4 h-4 text-white"></i>
                </button>
            </header>

            <section class="px-6 mt-4">
                <div onclick="toggleAI()" class="p-3 rounded-[18px] flex items-center justify-between cursor-pointer border border-indigo-500/30 bg-indigo-500/10">
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 bg-indigo-500/20 rounded-full flex items-center justify-center"><i data-lucide="sparkles" class="w-4 h-4 text-indigo-400"></i></div>
                        <p class="text-[10px] font-bold text-indigo-100 italic">SyncAI Intelligence</p>
                    </div>
                    <i data-lucide="chevron-right" class="w-4 h-4 text-indigo-500/50"></i>
                </div>
            </section>

            <section class="px-6 mt-4 grid grid-cols-2 gap-3">
                <div class="glass p-4 rounded-[22px] flex flex-col justify-center">
                    <p class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Viagens Hoje</p>
                    <h3 class="text-2xl font-black"><?= $totalHoje ?></h3>
                </div>
                <div class="glass p-4 rounded-[22px] flex flex-col justify-center">
                    <p class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest">Semanal</p>
                    <h3 class="text-2xl font-black"><?= $totalSemana ?></h3>
                </div>
            </section>

            <section class="px-6 mt-4">
                <div class="glass rounded-[28px] p-5 relative">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <h3 class="text-[10px] font-black text-white uppercase tracking-widest">Performance</h3>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-black text-blue-500 leading-none"><?= $totalGeral ?></span>
                            <span class="text-[7px] font-bold text-zinc-600 block uppercase">Histórico</span>
                        </div>
                    </div>
                    <div id="chartAnual" class="mt-0"></div>
                </div>
            </section>

            <section class="px-6 mt-6 grid grid-cols-4 gap-2 text-center">
                <div class="flex flex-col items-center gap-1.5">
                    <a href="rides.php" class="action-circle text-blue-500 bg-blue-600/10"><i data-lucide="plus" class="w-5 h-5"></i></a>
                    <span class="text-[8px] font-black text-zinc-500 uppercase">Criar</span>
                </div>
                <div class="flex flex-col items-center gap-1.5">
                    <button onclick="document.getElementById('xmlFile').click()" class="action-circle text-zinc-400"><i data-lucide="file-up" class="w-5 h-5"></i></button>
                    <span class="text-[8px] font-black text-zinc-500 uppercase">XML</span>
                </div>
                <div class="flex flex-col items-center gap-1.5">
                    <a href="driver-stats.php" class="action-circle text-zinc-400"><i data-lucide="bar-chart-3" class="w-5 h-5"></i></a>
                    <span class="text-[8px] font-black text-zinc-500 uppercase">Stats</span>
                </div>
                <div class="flex flex-col items-center gap-1.5">
                    <a href="users.php" class="action-circle text-zinc-400"><i data-lucide="users" class="w-5 h-5"></i></a>
                    <span class="text-[8px] font-black text-zinc-500 uppercase">Equipa</span>
                </div>
            </section>

            <section class="px-6 mt-8">
                <div class="flex justify-between items-center mb-4 px-1">
                    <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 italic">Próximas Viagens</h3>
                    <a href="rides.php" class="text-[9px] font-black text-blue-500 uppercase">Ver Tudo</a>
                </div>
                <div class="space-y-2">
                    <?php foreach($nextRides as $ride): ?>
                    <div onclick='openRideModal(<?= json_encode($ride) ?>)' class="glass p-3.5 rounded-2xl flex items-center justify-between cursor-pointer active:scale-95 transition-all">
                        <div class="flex items-center gap-4">
                            <div class="w-9 h-9 bg-blue-600/10 rounded-full flex items-center justify-center border border-blue-500/20"><i data-lucide="car" class="w-4 h-4 text-blue-500"></i></div>
                            <div>
                                <h4 class="text-xs font-bold"><?= htmlspecialchars($ride['NomeCliente']) ?></h4>
                                <p class="text-[9px] text-zinc-500 font-bold"><?= $ride['serviceStartTime'] ?> • <?= $ride['FlightNumber'] ?></p>
                            </div>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-800"></i>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </div>

    <div class="modal-overlay" id="modalOverlay" onclick="closeRideModal()"></div>
    <div id="rideModal">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 id="modalClient" class="text-lg font-black text-white">---</h3>
                <p id="modalID" class="text-[9px] text-blue-500 font-bold uppercase">VIAGEM #000</p>
            </div>
            <button onclick="closeRideModal()" class="text-zinc-600"><i data-lucide="x-circle"></i></button>
        </div>
        <div class="space-y-4 text-[10px]">
            <div class="flex gap-2">
                <div class="flex-1 bg-white/5 p-3 rounded-2xl"><p class="text-zinc-500 font-bold uppercase mb-1">Horário</p><p id="modalTime" class="font-black">--:--</p></div>
                <div class="flex-1 bg-white/5 p-3 rounded-2xl"><p class="text-zinc-500 font-bold uppercase mb-1">Voo</p><p id="modalFlight" class="font-black">---</p></div>
            </div>
            <div class="bg-white/5 p-4 rounded-2xl space-y-3">
                <div class="flex items-center gap-3"><i data-lucide="map-pin" class="w-3 h-3 text-emerald-500"></i><p id="modalStart" class="truncate text-zinc-300 font-medium">---</p></div>
                <div class="flex items-center gap-3"><i data-lucide="flag" class="w-3 h-3 text-red-500"></i><p id="modalEnd" class="truncate text-zinc-300 font-medium">---</p></div>
            </div>
            <div class="flex justify-between px-2 text-zinc-400 font-bold uppercase"><p>Ocupação</p><p id="modalPax" class="text-white">0 / 0</p></div>
        </div>
    </div>

    <div id="ai-overlay">
        <div class="flex-1 flex flex-col p-6 relative min-h-0">
            <div class="w-12 h-1 bg-zinc-800 rounded-full mx-auto mb-8 cursor-pointer" onclick="toggleAI()"></div>
            
            <div class="flex justify-between items-center mb-6 px-2">
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <i data-lucide="bot" class="w-7 h-7 text-indigo-500"></i>
                        <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-emerald-500 rounded-full border-2 border-black animate-pulse"></span>
                    </div>
                    <div>
                        <h3 class="text-xl font-black text-white italic tracking-tighter">SyncAI Command</h3>
                        <p class="text-[8px] text-zinc-500 font-bold uppercase tracking-widest">Active Intelligence</p>
                    </div>
                </div>
                <button onclick="toggleAI()" class="w-9 h-9 glass rounded-full flex items-center justify-center"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>

            <div id="ai-chat-content" class="flex-1 overflow-y-auto no-scrollbar space-y-4 px-2 pb-4">
                <div class="glass p-4 rounded-2xl rounded-tl-none text-zinc-300 border-indigo-500/20 max-w-[85%]">
                    Olá, eu sou a AI da SyncRide e serei o teu assistente pessoal. Como te posso ajudar?
                </div>
            </div>

            <div id="ai-input-container" class="pb-6 px-2 mt-2">
                <div id="ai-typing" class="hidden text-[9px] text-indigo-400 font-black mb-2 ml-4 uppercase italic tracking-widest">SyncAI a processar...</div>
                <div class="glass p-2 rounded-[24px] flex items-center gap-2 border-indigo-500/30">
                    <input type="text" id="ai-input" placeholder="Fala comigo..." 
                           class="bg-transparent flex-1 outline-none text-white px-4 py-2" style="font-size: 16px;">
                    <button onclick="sendToAI()" class="w-11 h-11 bg-indigo-600 rounded-full flex items-center justify-center text-white active:scale-90 transition-all shadow-lg shadow-indigo-500/20">
                        <i data-lucide="send" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <nav class="nav-float">
        <a href="/SRMT/public/admin/" class="flex flex-col items-center gap-1 text-blue-500"><i data-lucide="home" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Home</span></a>
        <a href="rides.php" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="calendar" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Rides</span></a>
        <a href="live-map.php" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="locate-fixed" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Live</span></a>
        <a href="financial.php" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="wallet" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Cash</span></a>
        <a href="fleet.php" class="nav-extra flex-col items-center gap-1 text-zinc-500"><i data-lucide="truck" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Frota</span></a>
        <a href="users.php" class="nav-extra flex-col items-center gap-1 text-zinc-500"><i data-lucide="users" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Equipa</span></a>
        <a href="driver-stats.php" class="nav-extra flex-col items-center gap-1 text-zinc-500"><i data-lucide="bar-chart-3" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Stats</span></a>
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
                <a href="/SRMT/public/admin/" class="flex items-center gap-4 text-blue-500"><i data-lucide="layout-grid"></i> Dashboard</a>
                <a href="rides.php" class="flex items-center gap-4"><i data-lucide="navigation"></i> Viagens</a>
                <a href="live-map.php" class="flex items-center gap-4"><i data-lucide="map"></i> Live Map</a>
                <hr class="border-zinc-800">
                <a href="users.php" class="flex items-center gap-4"><i data-lucide="users"></i> Equipa</a>
                <a href="fleet.php" class="flex items-center gap-4"><i data-lucide="truck"></i> Frota</a>
                <a href="financial.php" class="flex items-center gap-4"><i data-lucide="banknote"></i> Financeiro</a>
                <hr class="border-zinc-800">
                <a href="driver-stats.php" class="flex items-center gap-4"><i data-lucide="bar-chart-3"></i> Estatísticas</a>
                <a href="no-shows.php" class="flex items-center gap-4"><i data-lucide="alert-triangle"></i> No Shows</a>
                <a href="storage.php" class="flex items-center gap-4"><i data-lucide="database"></i> Armazenamento</a>
                <hr class="border-zinc-800">
                <a href="/SRMT/public/auth/logout.php" class="flex items-center gap-4 text-red-500 mt-4"><i data-lucide="log-out"></i> Logout</a>
            </nav>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data" class="hidden"><input type="file" name="xmlFile" id="xmlFile" onchange="this.form.submit()"></form>

    <script>
        lucide.createIcons();
        function toggleMenu() { document.getElementById('fullMenu').classList.toggle('hidden'); }
        
        function toggleAI() { 
            const overlay = document.getElementById('ai-overlay');
            overlay.classList.toggle('active');
            if(overlay.classList.contains('active')) {
                // Lock background scroll
                document.body.style.overflow = 'hidden';
                setTimeout(() => document.getElementById('ai-input').focus(), 400);
            } else {
                document.body.style.overflow = '';
            }
        }

        // --- KEYBOARD REFLOW FIX ---
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', () => {
                const overlay = document.getElementById('ai-overlay');
                if(overlay.classList.contains('active')) {
                    const keyboardHeight = window.innerHeight - window.visualViewport.height;
                    // Se o teclado estiver visível, ajustamos a margem do container de input
                    const inputCont = document.getElementById('ai-input-container');
                    inputCont.style.paddingBottom = keyboardHeight > 0 ? `${keyboardHeight + 10}px` : '24px';
                    document.getElementById('ai-chat-content').scrollTop = document.getElementById('ai-chat-content').scrollHeight;
                }
            });
        }

        function openRideModal(data) {
            document.getElementById('modalClient').innerText = data.NomeCliente;
            document.getElementById('modalID').innerText = 'VIAGEM #' + data.ID;
            document.getElementById('modalTime').innerText = data.serviceStartTime.substring(0,5);
            document.getElementById('modalFlight').innerText = data.FlightNumber;
            document.getElementById('modalStart').innerText = data.serviceStartPoint;
            document.getElementById('modalEnd').innerText = data.serviceTargetPoint;
            document.getElementById('modalPax').innerText = data.paxADT + ' / ' + data.paxCHD;
            document.getElementById('modalOverlay').classList.add('active');
            document.getElementById('rideModal').classList.add('active');
        }
        function closeRideModal() { 
            document.getElementById('modalOverlay').classList.remove('active');
            document.getElementById('rideModal').classList.remove('active'); 
        }

        async function sendToAI() {
            const input = document.getElementById('ai-input');
            const content = document.getElementById('ai-chat-content');
            const typing = document.getElementById('ai-typing');
            const msg = input.value.trim();
            if (!msg) return;

            content.innerHTML += `<div class="flex justify-end"><div class="bg-indigo-600 p-3 rounded-2xl rounded-tr-none max-w-[80%] border border-white/10 text-white text-xs font-medium shadow-lg">${msg}</div></div>`;
            input.value = '';
            typing.classList.remove('hidden');
            content.scrollTop = content.scrollHeight;

            try {
                const response = await fetch('/SRMT/public/api/sync-ai-engine.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: msg })
                });
                const data = await response.json();
                typing.classList.add('hidden');
                content.innerHTML += `<div class="flex justify-start animate-in fade-in duration-300"><div class="glass p-4 rounded-2xl rounded-tl-none max-w-[90%] text-zinc-200 border-white/10 text-xs leading-relaxed shadow-xl">${data.response}</div></div>`;
            } catch (e) {
                typing.classList.add('hidden');
                content.innerHTML += `<div class="text-red-500 text-[9px] font-black text-center uppercase tracking-widest">Erro de Sincronização AI</div>`;
            }
            content.scrollTop = content.scrollHeight;
        }

        document.getElementById('ai-input').addEventListener('keypress', (e) => { if(e.key === 'Enter') sendToAI(); });

        const options = {
            series: [{ name: 'Viagens', data: <?= json_encode($monthlyData) ?> }],
            chart: { type: 'area', height: 110, toolbar: { show: false }, sparkline: { enabled: true } },
            stroke: { curve: 'smooth', width: 2, colors: ['#ffffff'] },
            fill: { type: 'gradient', gradient: { opacityFrom: 0.3, opacityTo: 0 } },
            colors: ['#ffffff'],
            tooltip: { theme: 'dark', x: { show: false } }
        };
        new ApexCharts(document.querySelector("#chartAnual"), options).render();
    </script>
</body>
</html>