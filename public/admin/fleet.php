<?php
session_start();

// 1. VERIFICAÇÃO DE ADMIN
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    header("refresh: 1; url=/SRMT/public/");
    exit();
}

require __DIR__ . '/../../auth/dbconfig.php';

// 2. Lógica da Foto de Perfil (User Logado)
$defaultPhoto = "../assets/img/user2-160x160.jpg";
$userPhoto = $defaultPhoto;
if (isset($_SESSION['profile_photo_path']) && !empty($_SESSION['profile_photo_path'])) {
    $userPhoto = "../../../" . $_SESSION['profile_photo_path'];
}

// 3. BUSCAR DADOS
try {
    $stmt = $pdo->query("
        SELECT
            v.*,
            u.name AS assigned_driver_name,
            u.id AS assigned_driver_user_id
        FROM Vehicles v
        LEFT JOIN Users u ON u.assigned_vehicle_id = v.id
        ORDER BY v.status DESC, v.brand ASC
    ");
    $vehiclesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtDrivers = $pdo->query("SELECT id, name FROM Users WHERE role = 2 ORDER BY name ASC");
    $drivers = $stmtDrivers->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $vehiclesRaw = []; $drivers = [];
}

// 4. PROCESSAMENTO DE DADOS (Alertas e Caminhos de Imagem)
$vehicles = [];
$alerts = 0;
$activeVehicles = 0;

foreach($vehiclesRaw as $v) {
    $fotoNome = $v['photo'] ?? $v['foto_veiculo'] ?? '';
    $caminhoVisual = __DIR__ . "/../uploads/vehicles/" . $fotoNome;
    $caminhoSistema = __DIR__ . "/../uploads/vehicles/" . $fotoNome;

    if (!empty($fotoNome) && file_exists($caminhoSistema)) {
        $v['final_photo_url'] = $caminhoVisual;
    } else {
        $v['final_photo_url'] = "";
    }

    if($v['status'] == 1) $activeVehicles++;

    if (!empty($v['inspection_date']) && !empty($v['insurance_date'])) {
        $today = new DateTime();
        $insp = new DateTime($v['inspection_date']);
        $insu = new DateTime($v['insurance_date']);

        if($today->diff($insp)->format("%r%a") < 30 || $today->diff($insu)->format("%r%a") < 30) {
            $alerts++;
        }
    }

    $vehicles[] = $v;
}

$totalVehicles = count($vehicles);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Frota | SyncRide</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no" />
    <meta name="theme-color" content="#000000">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
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

        /* STAT CARDS */
        .stat-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(20px);
            border-radius: 22px; padding: 20px;
            display: flex; flex-direction: column; gap: 8px; height: 100%;
            transition: all .2s;
        }
        .stat-card:hover { background: rgba(255,255,255,0.07); transform: translateY(-2px); }
        .stat-icon-wrapper {
            width: 40px; height: 40px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.1rem; margin-bottom: 4px;
        }
        .stat-value { font-size: 28px; font-weight: 800; line-height: 1; color: #fff; letter-spacing: -0.02em; }
        .stat-label { color: #a1a1aa; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; }

        .stat-blue .stat-icon-wrapper { background: rgba(59, 130, 246, 0.15); color: #60a5fa; }
        .stat-green .stat-icon-wrapper { background: rgba(16, 185, 129, 0.15); color: #34d399; }
        .stat-orange .stat-icon-wrapper { background: rgba(249, 115, 22, 0.15); color: #fb923c; }

        /* SEARCH */
        .search-wrap { position: relative; flex: 1; min-width: 0; }
        .search-wrap input {
            width: 100%; background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08); color: #fff;
            padding: 10px 14px 10px 38px; border-radius: 14px; font-size: 13px; outline: none;
            font-family: inherit;
        }
        .search-wrap input::placeholder { color: #71717a; }
        .search-wrap input:focus { border-color: rgba(255,255,255,0.2); }
        .search-wrap .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #71717a; pointer-events: none; }

        .btn-primary-modern {
            background: #2563eb; color: #fff; border: none; border-radius: 12px;
            padding: 10px 18px; font-weight: 700; font-size: 12px;
            display: inline-flex; align-items: center; gap: 6px; transition: all .2s;
        }
        .btn-primary-modern:hover { background: #1d4ed8; color: #fff; }
        .btn-primary-modern:active { transform: scale(0.96); }

        .btn-ghost {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            color: #d4d4d8; border-radius: 12px;
            padding: 8px 12px; font-weight: 600; font-size: 12px;
            transition: all .2s;
            display: inline-flex; align-items: center; gap: 6px;
        }
        .btn-ghost:hover { background: rgba(255,255,255,0.08); color: #fff; }

        /* FLEET CARDS (replacing table rows) */
        #fleetTable { display: block; width: 100%; }
        #fleetTable thead { display: none; }
        #fleetTable tbody { display: grid; grid-template-columns: 1fr; gap: 10px; }
        @media (min-width: 768px) { #fleetTable tbody { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1280px) { #fleetTable tbody { grid-template-columns: repeat(3, 1fr); } }
        #fleetTable tbody tr {
            display: block; position: relative;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 22px; padding: 18px;
            transition: all .2s; backdrop-filter: blur(20px);
        }
        #fleetTable tbody tr:hover { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.15); }
        #fleetTable tbody td {
            display: block; border: none !important; padding: 0 !important;
            background: transparent !important; color: #fff; width: 100% !important;
        }

        /* Status badge (top-right) */
        #fleetTable tbody td:nth-child(1) { position: absolute; top: 16px; right: 16px; width: auto !important; }
        /* Vehicle (brand + model + photo) */
        #fleetTable tbody td:nth-child(2) {
            display: flex !important; align-items: center; margin-bottom: 10px;
            font-size: 15px; font-weight: 800; color: #fff;
            padding-right: 80px !important;
        }
        /* Matrícula */
        #fleetTable tbody td:nth-child(3) { margin-bottom: 14px; }
        /* Condutor */
        #fleetTable tbody td:nth-child(4) {
            font-size: 12px; color: #a1a1aa; display: flex !important; align-items: center;
            gap: 8px; margin-bottom: 12px;
        }
        #fleetTable tbody td:nth-child(4):before {
            content: ""; width: 6px; height: 6px; border-radius: 50%;
            background: #60a5fa; box-shadow: 0 0 0 3px rgba(96,165,250,0.15);
        }
        /* Inspeção / Seguro */
        #fleetTable tbody td:nth-child(5),
        #fleetTable tbody td:nth-child(6) {
            font-size: 11px; color: #d4d4d8; display: flex !important;
            justify-content: space-between; padding: 6px 0 !important;
            border-top: 1px solid rgba(255,255,255,0.06) !important;
        }
        #fleetTable tbody td:nth-child(5):before { content: "INSPEÇÃO"; font-weight: 800; color: #71717a; font-size: 9px; letter-spacing: 0.1em; }
        #fleetTable tbody td:nth-child(6):before { content: "SEGURO"; font-weight: 800; color: #71717a; font-size: 9px; letter-spacing: 0.1em; }
        /* Ações */
        #fleetTable tbody td:last-child {
            display: flex !important; gap: 8px; justify-content: flex-end;
            margin-top: 12px; padding-top: 14px !important;
            border-top: 1px solid rgba(255,255,255,0.08) !important;
        }

        .badge-state-active {
            background: rgba(16, 185, 129, 0.15); color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 3px 10px; border-radius: 999px; font-size: 9px;
            font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em;
        }
        .badge-state-inactive {
            background: rgba(239, 68, 68, 0.12); color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 3px 10px; border-radius: 999px; font-size: 9px;
            font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em;
        }
        .badge-plate {
            font-family: 'Courier New', monospace; font-size: 13px; font-weight: 700;
            background: rgba(255,255,255,0.06); color: #fff;
            border: 1px solid rgba(255,255,255,0.12);
            padding: 5px 10px; border-radius: 8px; letter-spacing: 2px;
            display: inline-block;
        }
        .vehicle-thumb-sm {
            width: 44px; height: 44px; border-radius: 12px; object-fit: cover;
            margin-right: 12px; border: 1px solid rgba(255,255,255,0.08);
            background-color: rgba(255,255,255,0.04);
            display: inline-flex; align-items: center; justify-content: center;
            color: #71717a; flex-shrink: 0;
        }
        .text-danger-soft { color: #f87171 !important; font-weight: 700; }
        .text-muted-soft { color: #a1a1aa; }

        /* Action buttons */
        .btn-action {
            background: transparent;
            border: 1px solid rgba(255,255,255,0.1);
            color: #a1a1aa;
            width: 36px; height: 36px; border-radius: 999px;
            padding: 0; display: inline-flex; align-items: center; justify-content: center;
            transition: all .2s; text-decoration: none;
        }
        .btn-action:hover { background: rgba(255,255,255,0.06); color: #fff; }
        .btn-action.delete:hover { color: #f87171; border-color: rgba(248,113,113,0.4); background: rgba(248,113,113,0.1); }
        .btn-action.edit-btn:hover { color: #60a5fa; border-color: rgba(96,165,250,0.4); background: rgba(96,165,250,0.08); }

        /* DataTables overrides */
        .dataTables_filter, .dataTables_length, .dataTables_info { display: none !important; }
        .dataTables_paginate { padding: 20px 0 4px 0; }
        .dataTables_paginate .pagination {
            margin: 0; padding: 0; gap: 4px;
            display: flex; justify-content: center; flex-wrap: wrap; list-style: none;
        }
        .dataTables_paginate .page-item { list-style: none; }
        .dataTables_paginate .page-link {
            background: rgba(255,255,255,0.04) !important;
            border: 1px solid rgba(255,255,255,0.08) !important;
            color: #d4d4d8 !important;
            padding: 0 !important;
            min-width: 34px; height: 34px; line-height: 32px;
            border-radius: 999px !important;
            font-size: 12px; font-weight: 700;
            text-align: center; box-shadow: none !important;
            transition: all .15s; outline: none !important;
            display: inline-flex; align-items: center; justify-content: center;
        }
        .dataTables_paginate .page-link:hover {
            background: rgba(255,255,255,0.09) !important;
            color: #fff !important;
            border-color: rgba(255,255,255,0.15) !important;
        }
        .dataTables_paginate .page-link:focus { box-shadow: none !important; }
        .dataTables_paginate .page-item.active .page-link {
            background: #2563eb !important;
            border-color: #2563eb !important;
            color: #fff !important;
        }
        .dataTables_paginate .page-item.disabled .page-link {
            opacity: 0.3; color: #71717a !important;
            background: transparent !important;
            border-color: rgba(255,255,255,0.04) !important;
        }
        .dataTables_paginate .page-item.previous .page-link,
        .dataTables_paginate .page-item.next .page-link { font-size: 14px; }
        table.dataTable.no-footer { border-bottom: none; }

        /* Modals */
        .modal-content {
            background: rgba(20, 20, 20, 0.95); backdrop-filter: blur(30px);
            border: 1px solid rgba(255,255,255,0.1); border-radius: 28px; color: #fff;
        }
        .modal-header, .modal-footer { border-color: rgba(255,255,255,0.08); }
        .modal-title { color: #fff; font-weight: 800; }
        .btn-close { filter: invert(1) brightness(2); opacity: 0.6; }
        .btn-close:hover { opacity: 1; }

        .form-control-custom, .form-select-custom {
            background-color: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1); color: #fff;
            border-radius: 14px; padding: 11px 14px; font-size: 14px;
            width: 100%; outline: none;
        }
        .form-control-custom:focus, .form-select-custom:focus {
            border-color: rgba(96, 165, 250, 0.6);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
            background-color: rgba(255,255,255,0.06); color: #fff;
        }
        .form-control-custom::placeholder { color: #71717a; }
        .form-select-custom option { background: #18181b; color: #fff; }
        .form-label { color: #a1a1aa !important; font-size: 10px !important; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
        input[type="date"]::-webkit-calendar-picker-indicator { filter: invert(1); opacity: 0.6; }

        .btn-modern {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff; border: none; border-radius: 14px;
            padding: 12px 18px; font-weight: 800; font-size: 13px;
            transition: all .2s; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
        }
        .btn-modern:hover { background: linear-gradient(135deg, #1d4ed8, #1e3a8a); color: #fff; }
        .btn-modern:active { transform: scale(0.97); }

        .vehicle-photo-preview {
            width: 100%; max-height: 200px; object-fit: cover; border-radius: 16px;
            margin-bottom: 12px; border: 1px solid rgba(255,255,255,0.08); display: none;
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
            <h1 class="page-title">Gestão de Frota</h1>
            <p class="page-subtitle mt-1">Manutenção de veículos e atribuição de condutores.</p>
        </div>

        <!-- STATS -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 mb-6">
            <div class="stat-card stat-blue">
                <div class="stat-icon-wrapper"><i data-lucide="truck" class="w-5 h-5"></i></div>
                <div class="stat-value"><?= $totalVehicles ?></div>
                <div class="stat-label">Total Veículos</div>
            </div>
            <div class="stat-card stat-green">
                <div class="stat-icon-wrapper"><i data-lucide="check-circle" class="w-5 h-5"></i></div>
                <div class="stat-value"><?= $activeVehicles ?></div>
                <div class="stat-label">Ativos</div>
            </div>
            <div class="stat-card stat-orange col-span-2 lg:col-span-1">
                <div class="stat-icon-wrapper"><i data-lucide="alert-triangle" class="w-5 h-5"></i></div>
                <div class="stat-value"><?= $alerts ?></div>
                <div class="stat-label">Alertas (Doc)</div>
            </div>
        </div>

        <!-- TOOLBAR -->
        <div class="glass rounded-[22px] p-3 mb-4 flex items-center gap-2 flex-wrap">
            <div id="filter-container" class="search-wrap">
                <i data-lucide="search" class="search-icon w-4 h-4"></i>
            </div>
            <button class="btn-primary-modern" data-bs-toggle="modal" data-bs-target="#modalVehicle">
                <i data-lucide="plus" class="w-4 h-4"></i> <span class="d-none d-sm-inline">Adicionar</span>
            </button>
        </div>

        <!-- FLEET TABLE -->
        <table id="fleetTable" class="table" style="width:100%">
            <thead>
                <tr><th>Estado</th><th>Viatura</th><th>Matrícula</th><th>Condutor</th><th>Inspeção</th><th>Seguro</th><th>Ações</th></tr>
            </thead>
            <tbody>
                <?php foreach($vehicles as $v):
                    $today = new DateTime();
                    $inspDate = new DateTime($v['inspection_date']); $diffInsp = $today->diff($inspDate)->format("%r%a");
                    $insuDate = new DateTime($v['insurance_date']); $diffInsu = $today->diff($insuDate)->format("%r%a");
                ?>
                <tr>
                    <td>
                        <?php if($v['status'] == 1): ?>
                            <span class="badge-state-active">Ativo</span>
                        <?php else: ?>
                            <span class="badge-state-inactive">Inativo</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if(!empty($v['final_photo_url'])): ?>
                            <img src="<?php echo $v['final_photo_url']; ?>" class="vehicle-thumb-sm" alt="Foto">
                        <?php else: ?>
                            <span class="vehicle-thumb-sm"><i data-lucide="truck" class="w-5 h-5"></i></span>
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($v['brand'] . ' ' . $v['model']); ?></span>
                    </td>
                    <td><span class="badge-plate"><?php echo htmlspecialchars($v['license_plate']); ?></span></td>
                    <td><span><?php echo htmlspecialchars($v['assigned_driver_name'] ?? 'Sem condutor'); ?></span></td>
                    <td><span class="<?php echo ($diffInsp < 30) ? 'text-danger-soft' : 'text-muted-soft'; ?>"><?php echo date('d/m/Y', strtotime($v['inspection_date'])); ?></span></td>
                    <td><span class="<?php echo ($diffInsu < 30) ? 'text-danger-soft' : 'text-muted-soft'; ?>"><?php echo date('d/m/Y', strtotime($v['insurance_date'])); ?></span></td>
                    <td>
                        <button class="btn-action edit-btn" data-json='<?php echo json_encode($v); ?>' title="Editar"><i data-lucide="pencil" class="w-4 h-4"></i></button>
                        <a href="save-vehicle.php?action=delete&id=<?php echo $v['id']; ?>" class="btn-action delete" onclick="return confirm('Tem a certeza que deseja apagar?');" title="Apagar"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </main>

    <!-- Floating bottom nav -->
    <nav class="nav-float">
        <a href="/SRMT/public/admin/"><i data-lucide="home" class="w-5 h-5"></i><span>Home</span></a>
        <a href="rides.php"><i data-lucide="calendar" class="w-5 h-5"></i><span>Rides</span></a>
        <a href="live-map.php"><i data-lucide="locate-fixed" class="w-5 h-5"></i><span>Live</span></a>
        <a href="financial.php"><i data-lucide="wallet" class="w-5 h-5"></i><span>Cash</span></a>
        <a href="fleet.php" class="nav-extra active"><i data-lucide="truck" class="w-5 h-5"></i><span>Frota</span></a>
        <a href="users.php" class="nav-extra"><i data-lucide="users" class="w-5 h-5"></i><span>Equipa</span></a>
        <a href="driver-stats.php" class="nav-extra"><i data-lucide="bar-chart-3" class="w-5 h-5"></i><span>Stats</span></a>
        <a href="no-shows.php" class="nav-extra"><i data-lucide="alert-triangle" class="w-5 h-5"></i><span>No Show</span></a>
        <a href="storage.php" class="nav-extra"><i data-lucide="database" class="w-5 h-5"></i><span>Storage</span></a>
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
                <a href="fleet.php" class="active-link"><i data-lucide="truck"></i> Frota</a>
                <a href="financial.php"><i data-lucide="banknote"></i> Financeiro</a>
                <hr>
                <a href="driver-stats.php"><i data-lucide="bar-chart-3"></i> Estatísticas</a>
                <a href="no-shows.php"><i data-lucide="alert-triangle"></i> No Shows</a>
                <a href="storage.php"><i data-lucide="database"></i> Armazenamento</a>
                <hr>
                <a href="/SRMT/public/auth/logout.php" style="color:#ef4444"><i data-lucide="log-out"></i> Logout</a>
            </nav>
        </div>
    </div>

    <!-- VEHICLE MODAL -->
    <div class="modal fade" id="modalVehicle" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <form id="vehicleForm" action="save-vehicle.php" method="POST" enctype="multipart/form-data">
              <div class="modal-header border-bottom-0 pb-0">
                  <h5 class="modal-title" id="modalTitle">Adicionar Veículo</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body p-4">
                <input type="hidden" name="vehicle_id" id="vehicle_id">

                <div class="text-center mb-4">
                    <img id="currentVehiclePhoto" src="" class="vehicle-photo-preview">
                    <label class="btn-ghost w-100 text-center justify-content-center" style="cursor: pointer;">
                        <i class="bi bi-camera me-2"></i> Carregar Foto
                        <input type="file" name="vehicle_photo" id="vehicle_photo_input" hidden accept="image/*">
                    </label>
                    <small class="text-zinc-500 d-block mt-2" style="font-size: 11px;">Formatos: JPG, PNG, WEBP</small>
                    <input type="hidden" name="existing_photo" id="existing_photo">
                </div>

                <div class="row g-3">
                    <div class="col-6"><label class="form-label">Marca</label><input type="text" name="brand" id="brand" class="form-control-custom" required></div>
                    <div class="col-6"><label class="form-label">Modelo</label><input type="text" name="model" id="model" class="form-control-custom" required></div>
                    <div class="col-12"><label class="form-label">Matrícula</label><input type="text" name="license_plate" id="license_plate" class="form-control-custom text-center fw-bold" style="letter-spacing: 2px;" required></div>
                    <div class="col-6"><label class="form-label">Inspeção</label><input type="date" name="inspection_date" id="inspection_date" class="form-control-custom" required></div>
                    <div class="col-6"><label class="form-label">Seguro</label><input type="date" name="insurance_date" id="insurance_date" class="form-control-custom" required></div>
                    <div class="col-12"><label class="form-label">Condutor Atribuído</label><select name="assigned_driver_id" id="assigned_driver_id" class="form-select-custom"><option value="">Nenhum</option><?php foreach($drivers as $driver): ?><option value="<?php echo $driver['id']; ?>"><?php echo htmlspecialchars($driver['name']); ?></option><?php endforeach; ?></select></div>
                    <div class="col-12"><label class="form-label">Estado</label><select name="status" id="status" class="form-select-custom"><option value="1">Ativo</option><option value="0">Inativo</option></select></div>
                </div>
              </div>
              <div class="modal-footer border-top-0 pt-0 px-4 pb-4">
                <button type="submit" class="btn-modern w-100">Guardar</button>
              </div>
          </form>
        </div>
      </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        lucide.createIcons();

        function toggleMenu() {
            document.getElementById('fullMenu').classList.toggle('open');
        }

        $(document).ready(function() {
            var table = $('#fleetTable').DataTable({
                language: { search: "", searchPlaceholder: "Procurar..." },
                pageLength: 24, lengthChange: false, ordering: false, dom: 'frtp'
            });
            $('#fleetTable_filter').appendTo('#filter-container');

            $(document).on('click', '.edit-btn', function() {
                const data = $(this).data('json');

                $('#modalTitle').text('Editar Veículo');
                $('#vehicle_id').val(data.id);
                $('#brand').val(data.brand);
                $('#model').val(data.model);
                $('#license_plate').val(data.license_plate);
                $('#inspection_date').val(data.inspection_date);
                $('#insurance_date').val(data.insurance_date);
                $('#status').val(data.status);

                if(data.assigned_driver_user_id) { $('#assigned_driver_id').val(data.assigned_driver_user_id); }
                else if (data.assigned_driver_id) { $('#assigned_driver_id').val(data.assigned_driver_id); }
                else { $('#assigned_driver_id').val(""); }

                if (data.final_photo_url && data.final_photo_url !== "") {
                    $('#currentVehiclePhoto').attr('src', data.final_photo_url).show();
                    $('#existing_photo').val(data.photo || data.foto_veiculo);
                }
                else {
                    $('#currentVehiclePhoto').hide();
                    $('#existing_photo').val('');
                }

                new bootstrap.Modal(document.getElementById('modalVehicle')).show();
            });

            $('#modalVehicle').on('hidden.bs.modal', function () {
                $(this).find('form').trigger('reset');
                $('#modalTitle').text('Adicionar Veículo');
                $('#vehicle_id').val('');
                $('#currentVehiclePhoto').hide();
                $('#existing_photo').val('');
            });

            $('#vehicle_photo_input').on('change', function(event) {
                const [file] = event.target.files;
                if (file) {
                    $('#currentVehiclePhoto').attr('src', URL.createObjectURL(file)).show();
                }
            });

            $('#fleetTable').on('draw.dt', function() {
                lucide.createIcons();
            });

            toastr.options = { "closeButton": true, "progressBar": true, "positionClass": "toast-top-right", "timeOut": "5000" };
        });
    </script>
  </body>
</html>
