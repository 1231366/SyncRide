<?php
session_start();

// 1. VERIFICAÇÃO DE ADMIN
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    header("refresh: 1; url=/SRMT/public/");
    exit();
}
require __DIR__ . '/../../auth/dbconfig.php';
$pdo->exec("SET NAMES utf8mb4");

// 2. FILTROS E LÓGICA DE DADOS
$mesFiltro = $_GET['month'] ?? date('Y-m');
$ano = date('Y', strtotime($mesFiltro));
$mes = date('m', strtotime($mesFiltro));

$userPhoto = (isset($_SESSION['profile_photo_path']) && !empty($_SESSION['profile_photo_path'])) 
    ? "../../../" . $_SESSION['profile_photo_path'] : "https://api.dicebear.com/7.x/avataaars/svg?seed=Felix";

// 3. CÁLCULOS FINANCEIROS (TUA LÓGICA ORIGINAL INTEGRAL)
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM Services WHERE YEAR(serviceDate) = ? AND MONTH(serviceDate) = ?");
    $stmt->execute([$ano, $mes]);
    $totalViagens = $stmt->fetchColumn();
    $receitaEstimada = $totalViagens * 15; 

    $stmt = $pdo->prepare("SELECT SUM(amount) FROM Expenses WHERE YEAR(date) = ? AND MONTH(date) = ?");
    $stmt->execute([$ano, $mes]);
    $totalDespesas = $stmt->fetchColumn() ?: 0;
    $lucroLiquido = $receitaEstimada - $totalDespesas;

    $stmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM Expenses WHERE YEAR(date) = ? AND MONTH(date) = ? GROUP BY category");
    $stmt->execute([$ano, $mes]);
    $chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $catLabels = []; $catValues = [];
    foreach($chartData as $d) { $catLabels[] = $d['category']; $catValues[] = (float)$d['total']; }

    $stmt = $pdo->prepare("SELECT * FROM Expenses WHERE YEAR(date) = ? AND MONTH(date) = ? ORDER BY date DESC");
    $stmt->execute([$ano, $mes]);
    $listaDespesas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $listaDespesas = []; $receitaEstimada = 0; $totalDespesas = 0; $lucroLiquido = 0; }
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#000000">
    <title>Financeiro | SyncRide OS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root { --safe-bottom: env(safe-area-inset-bottom, 20px); }
        html { background-color: #000; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #000; color: #fff; margin: 0; min-height: 100vh; overflow-x: hidden; -webkit-font-smoothing: antialiased; }
        
        /* Gradiente Uniforme com o Admin.php */
        .bg-main { 
            background: radial-gradient(circle at 50% -10%, #1e40af 0%, #000 75%); 
            background-attachment: fixed;
            min-height: 100vh;
        }

        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); }
        
        .modal-os {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9);
            width: 85%; max-width: 360px; visibility: hidden; opacity: 0;
            background: rgba(20, 20, 20, 0.95); backdrop-filter: blur(30px);
            border-radius: 28px; border: 1px solid rgba(255,255,255,0.15);
            z-index: 4000; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            padding: 20px;
        }
        .modal-os.active { visibility: visible; opacity: 1; transform: translate(-50%, -50%) scale(1); }
        
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px);
            visibility: hidden; opacity: 0; z-index: 3999; transition: all 0.3s;
        }
        .modal-overlay.active { visibility: visible; opacity: 1; }

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

        input, select { background: rgba(255,255,255,0.05) !important; border: 1px solid rgba(255,255,255,0.1) !important; color: white !important; outline: none; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="bg-main">
    <div class="pb-32">
        <header class="px-6 pt-10 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <img src="<?= $userPhoto ?>" class="w-10 h-10 rounded-full border-2 border-blue-500/20 object-cover">
                <div>
                    <h2 class="text-[15px] font-extrabold leading-tight">Olá, <?= explode(' ', $_SESSION['name'])[0] ?></h2>
                    <p class="text-[8px] text-zinc-500 font-black tracking-widest uppercase italic">Finance OS</p>
                </div>
            </div>
            <div class="flex gap-2 items-center">
                <form id="monthForm" method="GET">
                    <input type="month" name="month" value="<?= $mesFiltro ?>" onchange="this.form.submit()"
                           class="text-[10px] font-bold px-3 py-2 rounded-full glass border-none text-white">
                </form>
                <button onclick="toggleMenu()" class="w-10 h-10 glass rounded-full flex items-center justify-center active:scale-90 transition-transform" aria-label="Menu">
                    <i data-lucide="menu" class="w-4 h-4 text-white"></i>
                </button>
            </div>
        </header>

        <section class="px-6 mt-6 grid grid-cols-1 gap-3">
            <div class="glass p-5 rounded-[22px] flex justify-between items-center border-l-4 border-l-emerald-500">
                <div>
                    <p class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Receita Est.</p>
                    <h3 class="text-2xl font-black text-emerald-500"><?= number_format($receitaEstimada, 0, ',', '.'); ?>€</h3>
                </div>
                <div class="w-10 h-10 bg-emerald-500/10 rounded-full flex items-center justify-center"><i data-lucide="trending-up" class="w-5 h-5 text-emerald-500"></i></div>
            </div>
            
            <div class="grid grid-cols-2 gap-3">
                <div class="glass p-5 rounded-[22px] border-l-4 border-l-red-500">
                    <p class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Despesas</p>
                    <h3 class="text-xl font-black text-red-500"><?= number_format($totalDespesas, 0, ',', '.'); ?>€</h3>
                </div>
                <div class="glass p-5 rounded-[22px] border-l-4 border-l-blue-500">
                    <p class="text-[8px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Lucro Liq.</p>
                    <h3 class="text-xl font-black text-blue-500"><?= number_format($lucroLiquido, 0, ',', '.'); ?>€</h3>
                </div>
            </div>
        </section>

        <section class="px-6 mt-4">
            <div class="glass rounded-[28px] p-5">
                <h3 class="text-[10px] font-black text-white uppercase tracking-widest mb-4 italic">Distribuição de Gastos</h3>
                <div id="expensesChart"></div>
            </div>
        </section>

        <section class="px-6 mt-6">
            <button onclick="openExpenseModal()" class="w-full py-4 glass rounded-2xl border-dashed border-zinc-700 flex items-center justify-center gap-3 active:scale-95 transition-all">
                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center"><i data-lucide="plus" class="w-4 h-4 text-white"></i></div>
                <span class="text-xs font-black uppercase tracking-tighter">Registar Nova Despesa</span>
            </button>
        </section>

        <section class="px-6 mt-8">
            <div class="flex justify-between items-center mb-4 px-1">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 italic">Movimentos Recentes</h3>
                <span class="text-[9px] font-black text-zinc-600 uppercase"><?= count($listaDespesas) ?> Items</span>
            </div>
            <div class="space-y-2">
                <?php foreach($listaDespesas as $d): ?>
                <div class="glass p-3.5 rounded-2xl flex items-center justify-between">
                    <div class="flex items-center gap-4 overflow-hidden">
                        <div class="w-9 h-9 bg-white/5 rounded-xl flex items-center justify-center border border-white/10 shrink-0">
                            <?php 
                                $icon = "package";
                                if(stripos($d['category'], 'Combustível') !== false) $icon = "fuel";
                                if(stripos($d['category'], 'Manutenção') !== false) $icon = "wrench";
                                if(stripos($d['category'], 'Portagens') !== false) $icon = "map-pin";
                            ?>
                            <i data-lucide="<?= $icon ?>" class="w-4 h-4 text-zinc-400"></i>
                        </div>
                        <div class="overflow-hidden">
                            <h4 class="text-xs font-bold text-white truncate"><?= htmlspecialchars($d['description']) ?></h4>
                            <p class="text-[9px] text-zinc-500 font-bold uppercase"><?= date('d M', strtotime($d['date'])) ?> • <?= $d['category'] ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <span class="text-xs font-black text-red-500">-<?= number_format($d['amount'], 2, ',', '.'); ?>€</span>
                        <button onclick='editExpense(<?= json_encode($d) ?>)' class="w-7 h-7 glass rounded-full flex items-center justify-center text-zinc-600"><i data-lucide="chevron-right" class="w-4 h-4"></i></button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($listaDespesas)): ?>
                    <div class="text-center py-10 opacity-30 italic text-sm font-bold">Sem movimentos este mês.</div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="modal-overlay" id="modalOverlay" onclick="closeExpenseModal()"></div>
    <div id="expenseModal" class="modal-os">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h3 id="modalTitle" class="text-lg font-black text-white italic">Nova Despesa</h3>
                <p class="text-[9px] text-blue-500 font-bold uppercase">Registo Financeiro</p>
            </div>
            <button onclick="closeExpenseModal()" class="text-zinc-600"><i data-lucide="x-circle"></i></button>
        </div>
        <form action="save-expense.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="expense_id" id="expense_id">
            <div class="grid grid-cols-2 gap-2">
                <div class="bg-white/5 p-3 rounded-2xl">
                    <p class="text-zinc-500 text-[8px] font-bold uppercase mb-1">Data</p>
                    <input type="date" name="date" id="date" class="w-full bg-transparent border-none text-xs font-black" required>
                </div>
                <div class="bg-white/5 p-3 rounded-2xl">
                    <p class="text-zinc-500 text-[8px] font-bold uppercase mb-1">Valor (€)</p>
                    <input type="number" step="0.01" name="amount" id="amount" class="w-full bg-transparent border-none text-xs font-black" required>
                </div>
            </div>
            <div class="bg-white/5 p-3 rounded-2xl">
                <p class="text-zinc-500 text-[8px] font-bold uppercase mb-1">Categoria</p>
                <select name="category" id="category" class="w-full bg-transparent border-none text-xs font-black" required>
                    <option value="Combustível">⛽ Combustível</option>
                    <option value="Manutenção">🔧 Manutenção</option>
                    <option value="Pessoal">👔 Pessoal</option>
                    <option value="Portagens">🛣️ Portagens</option>
                    <option value="Outros">📝 Outros</option>
                </select>
            </div>
            <div class="bg-white/5 p-3 rounded-2xl">
                <p class="text-zinc-500 text-[8px] font-bold uppercase mb-1">Descrição</p>
                <input type="text" name="description" id="description" class="w-full bg-transparent border-none text-xs font-black" placeholder="Ex: Galp A1..." required>
            </div>
            <button type="submit" class="w-full py-4 bg-blue-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest active:scale-95 transition-all">Guardar Registo</button>
            <button type="button" id="deleteBtn" class="hidden w-full py-2 text-red-500 text-[9px] font-black uppercase" onclick="confirmDelete()">Apagar Registo</button>
        </form>
    </div>

    <nav class="nav-float">
        <a href="/SRMT/public/admin/" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="home" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Home</span></a>
        <a href="rides.php" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="calendar" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Rides</span></a>
        <a href="live-map.php" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="locate-fixed" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Live</span></a>
        <a href="financial.php" class="flex flex-col items-center gap-1 text-blue-500"><i data-lucide="wallet" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Cash</span></a>
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
                <a href="/SRMT/public/admin/" class="flex items-center gap-4"><i data-lucide="layout-grid"></i> Dashboard</a>
                <a href="rides.php" class="flex items-center gap-4"><i data-lucide="navigation"></i> Viagens</a>
                <a href="live-map.php" class="flex items-center gap-4"><i data-lucide="map"></i> Live Map</a>
                <hr class="border-zinc-800">
                <a href="users.php" class="flex items-center gap-4"><i data-lucide="users"></i> Equipa</a>
                <a href="fleet.php" class="flex items-center gap-4"><i data-lucide="truck"></i> Frota</a>
                <a href="financial.php" class="flex items-center gap-4 text-blue-500"><i data-lucide="banknote"></i> Financeiro</a>
                <hr class="border-zinc-800">
                <a href="driver-stats.php" class="flex items-center gap-4"><i data-lucide="bar-chart-3"></i> Estatísticas</a>
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

        function openExpenseModal() {
            document.getElementById('modalTitle').innerText = "Nova Despesa";
            document.getElementById('expense_id').value = "";
            document.getElementById('deleteBtn').classList.add('hidden');
            document.getElementById('modalOverlay').classList.add('active');
            document.getElementById('expenseModal').classList.add('active');
        }

        function closeExpenseModal() {
            document.getElementById('modalOverlay').classList.remove('active');
            document.getElementById('expenseModal').classList.remove('active');
        }

        function editExpense(data) {
            openExpenseModal();
            document.getElementById('modalTitle').innerText = "Editar Registo";
            document.getElementById('expense_id').value = data.id;
            document.getElementById('date').value = data.date;
            document.getElementById('amount').value = data.amount;
            document.getElementById('category').value = data.category;
            document.getElementById('description').value = data.description;
            document.getElementById('deleteBtn').classList.remove('hidden');
        }

        function confirmDelete() {
            const id = document.getElementById('expense_id').value;
            if(confirm('Apagar esta despesa permanentemente?')) {
                window.location.href = 'save-expense.php?action=delete&id=' + id;
            }
        }

        const options = {
            series: <?= json_encode($catValues) ?>,
            labels: <?= json_encode($catLabels) ?>,
            chart: { type: 'donut', height: 220 },
            colors: ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#6366f1'],
            stroke: { show: false },
            dataLabels: { enabled: false },
            legend: {
                position: 'bottom',
                fontSize: '10px',
                fontFamily: 'Plus Jakarta Sans',
                labels: { colors: '#71717a' }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '80%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                color: '#71717a',
                                formatter: () => '<?= number_format($totalDespesas, 0, ',', '.') ?>€'
                            }
                        }
                    }
                }
            }
        };

        if(<?= count($catValues) ?> > 0) {
            new ApexCharts(document.querySelector("#expensesChart"), options).render();
        }
    </script>
</body>
</html>