<?php
session_start();

// 1. VERIFICAÇÃO DE ADMIN
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    header("refresh: 1; url=/SRMT/public/");
    exit();
}

require __DIR__ . '/../../auth/dbconfig.php';
$pdo->exec("SET NAMES utf8mb4");

// 2. Lógica da Foto de Perfil (Utilizador Logado)
$userPhoto = (isset($_SESSION['profile_photo_path']) && !empty($_SESSION['profile_photo_path'])) 
    ? "../../../" . $_SESSION['profile_photo_path'] : "https://api.dicebear.com/7.x/avataaars/svg?seed=Felix";

// 3. LÓGICA DE DADOS DA PÁGINA (Toda a tua lógica original mantida)
try {
    $stmt = $pdo->query("SELECT id, name, email, phone, role, profile_photo_path FROM Users ORDER BY name ASC");
    $users = $stmt->fetchAll();
    if (!$users) $users = [];

    $totalAdmins = $pdo->query("SELECT COUNT(*) FROM Users WHERE role = 1")->fetchColumn();
    $totalDrivers = $pdo->query("SELECT COUNT(*) FROM Users WHERE role = 2")->fetchColumn();
    $totalPartners = $pdo->query("SELECT COUNT(*) FROM Users WHERE role = 3")->fetchColumn(); 
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#000000">
    <title>SyncTeam | SyncRide OS</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>

    <style>
        /* --- CORE UI: COPIADO 100% DO TEU ADMIN.PHP --- */
        :root { --safe-bottom: env(safe-area-inset-bottom, 20px); }
        html { background-color: #000; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #000; color: #fff; margin: 0; min-height: 100vh; overflow-x: hidden; -webkit-font-smoothing: antialiased; }
        
        /* Gradiente Radial Exato do Dashboard */
        .bg-main { 
            background: radial-gradient(circle at 50% -10%, #1e40af 0%, #000 75%); 
            background-attachment: fixed;
            min-height: 100vh;
        }

        /* Estilo Glass Coerente */
        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); }
        
        /* Modal Style OS - Padrão RideModal */
        .modal-os {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9);
            width: 85%; max-width: 400px; visibility: hidden; opacity: 0;
            background: rgba(20, 20, 20, 0.95); backdrop-filter: blur(30px);
            border-radius: 28px; border: 1px solid rgba(255,255,255,0.15);
            z-index: 4000; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            padding: 24px;
        }
        .modal-os.active { visibility: visible; opacity: 1; transform: translate(-50%, -50%) scale(1); }
        
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px);
            visibility: hidden; opacity: 0; z-index: 3999; transition: all 0.3s;
        }
        .modal-overlay.active { visibility: visible; opacity: 1; }

        /* Nav Float: Coerência com safe-area */
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

        /* Form Elements Únicos */
        input, select { background: rgba(255,255,255,0.05) !important; border: 1px solid rgba(255,255,255,0.1) !important; color: white !important; outline: none; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        
        /* Badges de Role */
        .role-pill { font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; padding: 4px 10px; border-radius: 99px; }
    </style>
</head>
<body class="bg-main">
    <div class="pb-32"> 
        <header class="px-6 pt-10 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <img src="<?= $userPhoto ?>" class="w-10 h-10 rounded-full border-2 border-blue-500/20 object-cover">
                <div>
                    <h2 class="text-[15px] font-extrabold leading-tight">Olá, <?= explode(' ', $_SESSION['name'])[0] ?></h2>
                    <p class="text-[8px] text-zinc-500 font-black tracking-widest uppercase italic">Staff Management</p>
                </div>
            </div>
            <div class="flex gap-2 items-center">
                <button onclick="openNewUserModal()" class="w-10 h-10 glass rounded-full flex items-center justify-center active:scale-90 transition-transform" aria-label="Novo">
                    <i data-lucide="user-plus" class="w-4 h-4 text-white"></i>
                </button>
                <button onclick="toggleMenu()" class="w-10 h-10 glass rounded-full flex items-center justify-center active:scale-90 transition-transform" aria-label="Menu">
                    <i data-lucide="menu" class="w-4 h-4 text-white"></i>
                </button>
            </div>
        </header>

        <section class="px-6 mt-6 grid grid-cols-3 gap-3">
            <div class="glass p-4 rounded-[22px] flex flex-col items-center">
                <p class="text-[7px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Admins</p>
                <h3 class="text-xl font-black text-blue-500"><?= $totalAdmins ?></h3>
            </div>
            <div class="glass p-4 rounded-[22px] flex flex-col items-center">
                <p class="text-[7px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Drivers</p>
                <h3 class="text-xl font-black text-emerald-500"><?= $totalDrivers ?></h3>
            </div>
            <div class="glass p-4 rounded-[22px] flex flex-col items-center">
                <p class="text-[7px] font-bold text-zinc-500 uppercase tracking-widest mb-1">Partners</p>
                <h3 class="text-xl font-black text-purple-500"><?= $totalPartners ?></h3>
            </div>
        </section>

        <section class="px-6 mt-4">
            <div class="glass p-1.5 rounded-2xl flex items-center gap-3 px-4">
                <i data-lucide="search" class="w-4 h-4 text-zinc-500"></i>
                <input type="text" id="userSearch" placeholder="Pesquisar equipa..." class="bg-transparent border-none w-full text-xs py-2 text-white">
            </div>
        </section>

        <section class="px-6 mt-8">
            <div class="flex justify-between items-center mb-4 px-1">
                <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 italic">Membros da Equipa</h3>
                <span class="text-[9px] font-black text-zinc-600 uppercase"><?= count($users) ?> total</span>
            </div>
            
            <div class="space-y-3" id="userList">
                <?php foreach ($users as $user): ?>
                    <?php 
                        $photo = !empty($user['profile_photo_path']) ? "../../../" . $user['profile_photo_path'] : null;
                        $roleName = "Staff"; $roleColor = "bg-zinc-800 text-zinc-400";
                        if($user['role'] == 1) { $roleName = "Admin"; $roleColor = "bg-blue-500/10 text-blue-500"; }
                        elseif($user['role'] == 2) { $roleName = "Driver"; $roleColor = "bg-emerald-500/10 text-emerald-500"; }
                        elseif($user['role'] == 3) { $roleName = "Partner"; $roleColor = "bg-purple-500/10 text-purple-500"; }
                    ?>
                    <div class="glass p-4 rounded-2xl flex items-center justify-between user-card" data-name="<?= strtolower($user['name']) ?>">
                        <div class="flex items-center gap-4">
                            <?php if($photo): ?>
                                <img src="<?= $photo ?>" class="w-10 h-10 rounded-xl object-cover border border-white/10 shadow-sm">
                            <?php else: ?>
                                <div class="w-10 h-10 bg-blue-600/10 rounded-xl flex items-center justify-center border border-blue-500/20 text-blue-500 font-bold text-sm shadow-sm">
                                    <?= strtoupper(substr($user['name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h4 class="text-xs font-bold text-white"><?= htmlspecialchars($user['name']) ?></h4>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="role-pill <?= $roleColor ?>"><?= $roleName ?></span>
                                    <p class="text-[9px] text-zinc-500 font-bold"><?= htmlspecialchars($user['phone'] ?? '---') ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick='openEditModal(<?= json_encode($user) ?>)' class="w-8 h-8 glass rounded-full flex items-center justify-center text-zinc-500 active:scale-90 transition-all"><i data-lucide="edit-3" class="w-3.5 h-3.5"></i></button>
                            <button onclick="confirmDelete(<?= $user['id'] ?>, '<?= addslashes($user['name']) ?>')" class="w-8 h-8 glass rounded-full flex items-center justify-center text-red-500/40 active:scale-90 transition-all"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <div class="modal-overlay" id="modalOverlay" onclick="closeAllModals()"></div>
    
    <div id="newUserModal" class="modal-os">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-black text-white italic">Criar Conta</h3>
            <button onclick="closeAllModals()" class="text-zinc-600"><i data-lucide="x-circle"></i></button>
        </div>
        <form action="user-create.php" method="POST" class="space-y-4">
            <div class="space-y-1">
                <label class="text-[8px] font-black uppercase text-zinc-500 ml-2">Nome Completo</label>
                <input type="text" name="name" class="w-full p-3 rounded-xl text-xs font-bold text-white" required>
            </div>
            <div class="space-y-1">
                <label class="text-[8px] font-black uppercase text-zinc-500 ml-2">Email Profissional</label>
                <input type="email" name="email" class="w-full p-3 rounded-xl text-xs font-bold text-white" required>
            </div>
            <div class="space-y-1">
                <label class="text-[8px] font-black uppercase text-zinc-500 ml-2">Password</label>
                <div class="flex gap-2">
                    <input type="text" name="password" id="genPass" class="w-full p-3 rounded-xl text-xs font-bold bg-white/5 border-white/10 text-white" readonly required>
                    <button type="button" onclick="generatePass('genPass')" class="glass px-4 rounded-xl text-blue-500"><i data-lucide="wand-2" class="w-4 h-4"></i></button>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="text-[8px] font-black uppercase text-zinc-500 ml-2">Telefone</label>
                    <input type="text" name="phone" class="w-full p-3 rounded-xl text-xs font-bold text-white" required>
                </div>
                <div class="space-y-1">
                    <label class="text-[8px] font-black uppercase text-zinc-500 ml-2">Cargo</label>
                    <select name="role" class="w-full p-3 rounded-xl text-xs font-bold" required>
                        <option value="2">Condutor</option>
                        <option value="3">Parceiro</option>
                        <option value="1">Admin</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full py-4 bg-blue-600 text-white rounded-2xl text-xs font-black uppercase tracking-widest mt-2 active:scale-95 transition-all">Ativar Nova Conta</button>
        </form>
    </div>

    <div id="editUserModal" class="modal-os">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-black text-white italic">Editar Perfil</h3>
            <button onclick="closeAllModals()" class="text-zinc-600"><i data-lucide="x-circle"></i></button>
        </div>
        <form action="user-edit.php" method="POST" class="space-y-4">
            <input type="hidden" name="id" id="editId">
            <div class="space-y-1">
                <label class="text-[8px] font-black uppercase text-zinc-500 ml-2">Nome</label>
                <input type="text" name="name" id="editName" class="w-full p-3 rounded-xl text-xs font-bold text-white" required>
            </div>
            <div class="space-y-1">
                <label class="text-[8px] font-black uppercase text-zinc-500 ml-2">Email</label>
                <input type="email" name="email" id="editEmail" class="w-full p-3 rounded-xl text-xs font-bold text-white" required>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1">
                    <label class="text-[8px] font-black uppercase text-zinc-500 ml-2">Telefone</label>
                    <input type="text" name="phone" id="editPhone" class="w-full p-3 rounded-xl text-xs font-bold text-white" required>
                </div>
                <div class="space-y-1">
                    <label class="text-[8px] font-black uppercase text-zinc-500 ml-2">Cargo</label>
                    <select name="role" id="editRole" class="w-full p-3 rounded-xl text-xs font-bold" required>
                        <option value="1">Admin</option>
                        <option value="2">Condutor</option>
                        <option value="3">Parceiro</option>
                    </select>
                </div>
            </div>
            <div class="space-y-1">
                <label class="text-[8px] font-black uppercase text-zinc-500 ml-2">Nova Password (opcional)</label>
                <input type="password" name="password" class="w-full p-3 rounded-xl text-xs font-bold text-white" placeholder="Manter atual">
            </div>
            <button type="submit" class="w-full py-4 bg-white text-black rounded-2xl text-xs font-black uppercase tracking-widest mt-2 active:scale-95 transition-all">Guardar Alterações</button>
        </form>
    </div>

    <nav class="nav-float">
        <a href="/SRMT/public/admin/" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="home" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Home</span></a>
        <a href="rides.php" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="calendar" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Rides</span></a>
        <a href="live-map.php" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="locate-fixed" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Live</span></a>
        <a href="financial.php" class="flex flex-col items-center gap-1 text-zinc-500"><i data-lucide="wallet" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Cash</span></a>
        <a href="fleet.php" class="nav-extra flex-col items-center gap-1 text-zinc-500"><i data-lucide="truck" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Frota</span></a>
        <a href="users.php" class="nav-extra flex-col items-center gap-1 text-blue-500"><i data-lucide="users" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Equipa</span></a>
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
                <a href="users.php" class="flex items-center gap-4 text-blue-500"><i data-lucide="users"></i> Equipa</a>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        // Inicializar ícones Lucide
        lucide.createIcons();

        // Hamburger menu
        function toggleMenu() { document.getElementById('fullMenu').classList.toggle('hidden'); }

        // Funções de controlo dos Modais
        function openNewUserModal() {
            document.getElementById('modalOverlay').classList.add('active');
            document.getElementById('newUserModal').classList.add('active');
        }

        function openEditModal(user) {
            document.getElementById('editId').value = user.id;
            document.getElementById('editName').value = user.name;
            document.getElementById('editEmail').value = user.email;
            document.getElementById('editPhone').value = user.phone;
            document.getElementById('editRole').value = user.role;
            document.getElementById('modalOverlay').classList.add('active');
            document.getElementById('editUserModal').classList.add('active');
        }

        function closeAllModals() {
            document.getElementById('modalOverlay').classList.remove('active');
            document.getElementById('newUserModal').classList.remove('active');
            document.getElementById('editUserModal').classList.remove('active');
        }

        function confirmDelete(id, name) {
            // Confirm tradicional JS, mas funcional
            if(confirm(`Eliminar permanentemente a conta de ${name}?`)) {
                window.location.href = `delete.php?id=${id}`;
            }
        }

        // Gerador de Password
        function generatePass(targetId) {
            const chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*";
            let pass = "";
            for (let i = 0; i < 12; i++) pass += chars.charAt(Math.floor(Math.random() * chars.length));
            document.getElementById(targetId).value = pass;
        }

        // Live Search Dinâmico
        document.getElementById('userSearch').addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.user-card').forEach(card => {
                const name = card.getAttribute('data-name');
                card.style.display = name.includes(term) ? 'flex' : 'none';
            });
        });

        // Configuração Toastr (Notificações)
        const urlParams = new URLSearchParams(window.location.search);
        toastr.options = { "progressBar": true, "positionClass": "toast-top-right", "timeOut": "3000" };
        if (urlParams.get('success') === "user_created") toastr.success("Utilizador criado!", "SyncRide");
        if (urlParams.get('success') === "user_deleted") toastr.success("Colaborador removido.", "SyncRide");
        if (urlParams.get('success') === "user_updated") toastr.success("Perfil atualizado.", "SyncRide");
    </script>
</body>
</html>