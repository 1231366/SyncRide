<?php
session_start();

// 1. VERIFICAÇÃO DE ADMIN
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    header("refresh: 1; url=/SRMT/public/");
    exit();
}

require __DIR__ . '/../../auth/dbconfig.php';

// 2. LÓGICA DE DADOS
try {
    $stmt = $pdo->prepare("SELECT ID, name FROM Users WHERE role = 2 ORDER BY name ASC");
    $stmt->execute();
    $condutores = $stmt->fetchAll();
} catch (PDOException $e) {
    $condutores = [];
}

// Contagens para badges das tabs (mesma lógica do rides-data.php)
try {
    $stmtPending = $pdo->prepare("SELECT COUNT(*) FROM Services WHERE status_pedido = 'pendente'");
    $stmtPending->execute();
    $pendingRequestsCount = (int) $stmtPending->fetchColumn();

    $stmtToday = $pdo->prepare("
        SELECT COUNT(*) FROM Services s
        WHERE s.serviceDate = CURDATE()
          AND (s.status_pedido = 'aprovado' OR s.status_pedido IS NULL)
    ");
    $stmtToday->execute();
    $todayCount = (int) $stmtToday->fetchColumn();

    $stmtUnassigned = $pdo->prepare("
        SELECT COUNT(*) FROM Services s
        LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
        WHERE sr.UserID IS NULL
          AND (s.status_pedido = 'aprovado' OR s.status_pedido IS NULL)
    ");
    $stmtUnassigned->execute();
    $unassignedCount = (int) $stmtUnassigned->fetchColumn();
} catch (PDOException $e) {
    $pendingRequestsCount = 0;
    $todayCount = 0;
    $unassignedCount = 0;
}

// Foto de Perfil
$defaultPhoto = "../assets/img/user2-160x160.jpg";
$userPhoto = $defaultPhoto;
if (isset($_SESSION['profile_photo_path']) && !empty($_SESSION['profile_photo_path'])) {
    $userPhoto = "../../../" . $_SESSION['profile_photo_path'];
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Gerir Viagens | SyncRide</title>
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
            color: #fff;
            margin: 0;
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
            background: radial-gradient(circle at 50% -10%, #1e40af 0%, #000 75%);
            background-attachment: fixed;
            padding-bottom: calc(110px + var(--safe-bottom));
            padding-top: var(--safe-top);
        }
        @media (min-width: 992px) {
            body { padding-bottom: calc(110px + var(--safe-bottom)); }
        }

        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .glass-strong {
            background: rgba(20, 20, 20, 0.92);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* --- HEADER --- */
        .app-top {
            padding: 28px 24px 0 24px;
            display: flex; justify-content: space-between; align-items: center;
        }
        .icon-btn {
            width: 40px; height: 40px; border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff; transition: all .2s;
        }
        .icon-btn:hover { background: rgba(255,255,255,0.1); }
        .icon-btn:active { transform: scale(0.92); }

        /* --- SECTION TITLES --- */
        .eyebrow { font-size: 10px; font-weight: 800; color: #71717a; letter-spacing: 0.2em; text-transform: uppercase; font-style: italic; }

        /* --- PILLS / TABS --- */
        .pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 7px 14px; border-radius: 999px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            color: #a1a1aa; font-size: 11px; font-weight: 700;
            cursor: pointer; transition: all .2s;
            white-space: nowrap;
            text-decoration: none;
        }
        .pill:hover { background: rgba(255,255,255,0.08); color: #fff; }
        .pill.active { background: #fff; color: #000; border-color: #fff; }
        .pill.warning-pill { color: #fbbf24; border-color: rgba(251,191,36,0.3); background: rgba(251,191,36,0.08); }
        .pill.warning-pill.active { background: #fbbf24; color: #000; }

        /* Pill count badge (Pedidos / Hoje / Pendentes) */
        .pill-count {
            display: inline-flex; align-items: center; justify-content: center;
            min-width: 18px; height: 18px; padding: 0 6px;
            border-radius: 999px; font-size: 10px; font-weight: 800;
            background: rgba(255,255,255,0.08); color: inherit;
            margin-left: 2px;
        }
        /* Quando a pill está ativa (fundo branco), inverter para preto/branco subtil */
        .pill.active .pill-count {
            background: rgba(0,0,0,0.12); color: #000;
        }
        .pill.warning-pill .pill-count.has-pending {
            background: #fbbf24; color: #000;
            box-shadow: 0 0 0 0 rgba(251,191,36,0.6);
            animation: pulseBadge 1.8s ease-out infinite;
        }
        .pill.warning-pill.active .pill-count.has-pending {
            background: #000; color: #fbbf24;
            animation: none;
        }
        @keyframes pulseBadge {
            0%   { box-shadow: 0 0 0 0 rgba(251,191,36,0.55); }
            70%  { box-shadow: 0 0 0 6px rgba(251,191,36,0); }
            100% { box-shadow: 0 0 0 0 rgba(251,191,36,0); }
        }

        /* --- BUTTONS --- */
        .btn-primary-modern {
            background: #2563eb; color: #fff; border: none; border-radius: 12px;
            padding: 10px 18px; font-weight: 700; font-size: 12px;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all .2s;
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

        /* --- SEARCH --- */
        .search-wrap { position: relative; flex: 1; min-width: 0; }
        .search-wrap input {
            width: 100%; background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            color: #fff; padding: 10px 14px 10px 38px;
            border-radius: 14px; font-size: 13px; outline: none;
            font-family: inherit;
        }
        .search-wrap input::placeholder { color: #71717a; }
        .search-wrap input:focus { border-color: rgba(255,255,255,0.2); }
        .search-wrap .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #71717a; pointer-events: none; }

        /* --- RIDE CARDS (REPLACING TABLE ROWS) --- */
        #tabelaViagens { display: block; width: 100%; }
        #tabelaViagens thead { display: none; }
        #tabelaViagens tbody { display: block; }
        #tabelaViagens tbody tr {
            display: block; position: relative;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 22px; margin-bottom: 10px; padding: 16px 18px;
            backdrop-filter: blur(20px);
            transition: all .2s;
        }
        #tabelaViagens tbody tr:hover { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.15); }
        #tabelaViagens tbody td {
            display: block; border: none !important; padding: 0 !important;
            background: transparent !important; color: #fff;
        }
        /* hide checkbox column header */
        #tabelaViagens tbody td.col-selection { display: none; position: absolute; top: 16px; left: 14px; }
        .selection-active #tabelaViagens tbody td.col-selection { display: block; }
        .selection-active #tabelaViagens tbody tr { padding-left: 46px; }

        /* ID */
        #tabelaViagens tbody td:nth-child(2) {
            font-size: 10px; font-weight: 800; color: #71717a; letter-spacing: 0.1em;
            text-transform: uppercase; margin-bottom: 2px; font-family: monospace;
        }
        /* Data & Hora */
        #tabelaViagens tbody td:nth-child(3) {
            font-size: 14px; font-weight: 800; color: #fff; margin-bottom: 8px;
        }
        /* Condutor */
        #tabelaViagens tbody td:nth-child(4) {
            font-size: 12px; color: #d4d4d8; margin-bottom: 10px;
        }
        /* Recolha & Entrega */
        #tabelaViagens tbody td:nth-child(5),
        #tabelaViagens tbody td:nth-child(6) {
            font-size: 12px; color: #e4e4e7; padding-left: 20px !important;
            position: relative; line-height: 1.4; margin-bottom: 6px;
        }
        #tabelaViagens tbody td:nth-child(5):before {
            content: ""; width: 8px; height: 8px; border-radius: 50%;
            background: #10b981; position: absolute; left: 4px; top: 6px;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.15);
        }
        #tabelaViagens tbody td:nth-child(6):before {
            content: ""; width: 8px; height: 8px; border-radius: 50%;
            background: #ef4444; position: absolute; left: 4px; top: 6px;
            box-shadow: 0 0 0 3px rgba(239,68,68,0.15);
        }
        /* Tipo */
        #tabelaViagens tbody td:nth-child(7) {
            display: inline-block; width: auto !important; font-size: 9px;
            padding: 3px 10px; border-radius: 999px; font-weight: 800;
            background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
            color: #a1a1aa; text-transform: uppercase; letter-spacing: 0.1em;
            margin-top: 4px;
        }
        /* Chave (desktop only - hide) */
        #tabelaViagens tbody td:nth-child(8) {
            position: absolute; bottom: 16px; right: 60px; width: auto !important;
        }
        /* Ações */
        #tabelaViagens tbody td:last-child {
            position: absolute; top: 14px; right: 14px;
            display: flex; gap: 6px; width: auto !important;
        }

        @media (min-width: 992px) {
            #tabelaViagens tbody { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
            #tabelaViagens tbody tr { margin-bottom: 0; }
        }

        /* Action mini buttons inside rides */
        #tabelaViagens .btn,
        #tabelaViagens button:not(.btn-circle-mobile):not([data-bs-dismiss]):not(.btn-close) {
            width: 32px; height: 32px; padding: 0;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 999px; font-size: 12px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            color: #d4d4d8;
            transition: all .15s;
        }
        #tabelaViagens .btn:hover { background: rgba(255,255,255,0.12); color: #fff; }
        #tabelaViagens .btn-primary, #tabelaViagens .btn-info { color: #60a5fa; border-color: rgba(96,165,250,0.3); }
        #tabelaViagens .btn-danger { color: #f87171; border-color: rgba(248,113,113,0.3); }
        #tabelaViagens .btn-success { color: #34d399; border-color: rgba(52,211,153,0.3); }
        #tabelaViagens .btn-warning { color: #fbbf24; border-color: rgba(251,191,36,0.3); }
        #tabelaViagens .btn-secondary { color: #a1a1aa; }

        /* Agency badge */
        .agency-badge {
            font-size: 9px; background-color: rgba(99, 102, 241, 0.15);
            color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.3);
            padding: 2px 8px; border-radius: 999px;
            display: inline-flex; align-items: center; gap: 4px;
            font-weight: 700; margin-right: 5px;
        }
        .req-pending {
            background-color: rgba(245, 158, 11, 0.12); color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
            padding: 4px 12px; border-radius: 999px;
            font-size: 10px; font-weight: 700;
            display: inline-flex; align-items: center; gap: 4px;
        }

        /* --- TOOLBAR / DROPDOWN STACKING --- */
        /* Toolbar precisa de ficar acima dos cards para o dropdown de ordenação aparecer por cima */
        .rides-toolbar { position: relative; z-index: 20; }
        .rides-toolbar .dropdown { position: static; }
        .rides-toolbar .dropdown-menu {
            z-index: 1080;
            margin-top: 6px;
            padding: 6px;
            min-width: 180px;
        }
        .rides-toolbar .dropdown-menu .dropdown-item {
            border-radius: 10px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 600;
            transition: background .15s;
        }
        .rides-toolbar .dropdown-menu .dropdown-item:hover,
        .rides-toolbar .dropdown-menu .dropdown-item:focus {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }
        /* Cards devem ficar abaixo do toolbar */
        #tabelaViagens, #tabelaViagens_wrapper { position: relative; z-index: 1; }

        /* --- MODERN LOADING --- */
        .dataTables_processing {
            position: absolute !important;
            top: 24px !important; left: 50% !important;
            transform: translateX(-50%) !important;
            width: auto !important; margin: 0 !important;
            padding: 10px 18px !important;
            background: rgba(20,20,20,0.92) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1) !important;
            border-radius: 999px !important;
            color: #d4d4d8 !important;
            font-size: 0 !important;
            box-shadow: 0 8px 30px rgba(0,0,0,0.4);
            z-index: 10;
        }
        .dataTables_processing::before {
            content: "";
            display: inline-block;
            width: 14px; height: 14px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.15);
            border-top-color: #60a5fa;
            animation: spinModern 0.7s linear infinite;
            vertical-align: middle;
        }
        .dataTables_processing::after {
            content: "A carregar";
            font-size: 12px; font-weight: 700;
            color: #e4e4e7; margin-left: 10px;
            letter-spacing: 0.02em;
            vertical-align: middle;
        }
        @keyframes spinModern { to { transform: rotate(360deg); } }

        /* Skeleton placeholder rows enquanto carrega */
        .rides-skeleton {
            display: grid; gap: 10px; margin-top: 4px;
        }
        @media (min-width: 992px) {
            .rides-skeleton { grid-template-columns: repeat(2, 1fr); }
        }
        .rides-skeleton .sk-card {
            height: 130px; border-radius: 22px;
            background: linear-gradient(110deg,
                rgba(255,255,255,0.04) 8%,
                rgba(255,255,255,0.08) 18%,
                rgba(255,255,255,0.04) 33%);
            background-size: 200% 100%;
            border: 1px solid rgba(255,255,255,0.06);
            animation: shimmer 1.4s linear infinite;
        }
        @keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        /* --- DataTables overrides --- */
        .dataTables_filter, .dataTables_length, .dataTables_info { display: none !important; }

        /* Mas o filter dentro do toolbar tem de aparecer (input da lupa) */
        #filter-container .dataTables_filter {
            display: block !important;
            margin: 0; padding: 0;
        }
        #filter-container .dataTables_filter label {
            display: block; margin: 0; color: transparent; font-size: 0;
        }
        #filter-container .dataTables_filter input {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            color: #fff;
            padding: 10px 14px 10px 38px;
            border-radius: 14px;
            font-size: 13px;
            outline: none;
            font-family: inherit;
        }
        #filter-container .dataTables_filter input::placeholder { color: #71717a; }
        #filter-container .dataTables_filter input:focus { border-color: rgba(255,255,255,0.2); }
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

        /* --- MODALS --- */
        .modal-content {
            background: rgba(20, 20, 20, 0.95);
            backdrop-filter: blur(30px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 28px; color: #fff;
        }
        .modal-header, .modal-footer { border-color: rgba(255,255,255,0.08); }
        .modal-title { color: #fff; font-weight: 800; }
        .btn-close { filter: invert(1) brightness(2); opacity: 0.6; }
        .btn-close:hover { opacity: 1; }

        .form-control-custom, .form-select-custom {
            background-color: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff; border-radius: 14px;
            padding: 11px 14px; font-size: 14px;
            width: 100%; outline: none;
        }
        .form-control-custom:focus, .form-select-custom:focus {
            border-color: rgba(96, 165, 250, 0.6);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
            background-color: rgba(255,255,255,0.06);
            color: #fff;
        }
        .form-control-custom::placeholder { color: #71717a; }
        .form-control-custom:disabled, .form-select-custom:disabled { opacity: 0.6; }
        .form-select-custom option { background: #18181b; color: #fff; }
        .form-label.small, .form-label { color: #a1a1aa !important; font-size: 10px !important;
            font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-calendar-picker-indicator,
        input[type="datetime-local"]::-webkit-calendar-picker-indicator { filter: invert(1); opacity: 0.6; }

        .btn-modern {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #fff; border: none; border-radius: 14px;
            padding: 12px 18px; font-weight: 800; font-size: 13px;
            transition: all .2s; box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
        }
        .btn-modern:hover { background: linear-gradient(135deg, #1d4ed8, #1e3a8a); color: #fff; }
        .btn-modern:active { transform: scale(0.97); }

        /* --- FLOATING BOTTOM NAV (Mobile: 4 items / Desktop: expanded) --- */
        .nav-float {
            position: fixed; bottom: calc(12px + var(--safe-bottom));
            left: 50%; transform: translateX(-50%);
            width: calc(100% - 32px); max-width: 380px; height: 68px;
            background: #0c0c0e;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 14px 40px rgba(0,0,0,0.55), 0 2px 6px rgba(0,0,0,0.4);
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
            .nav-float { max-width: 720px; height: 74px; border-radius: 28px; }
            .nav-float .nav-extra { display: flex; }
            .nav-float a span { font-size: 8px; }
        }

        /* --- HAMBURGER FULL MENU --- */
        #fullMenu {
            position: fixed; inset: 0; z-index: 2000; display: none;
        }
        #fullMenu.open { display: block; }
        #fullMenu .mask {
            position: absolute; inset: 0;
            background: rgba(0,0,0,0.98); backdrop-filter: blur(40px);
        }
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

        /* --- DELETE MODAL & TIMELINE --- */
        .logs-timeline { border-left: 2px solid rgba(255,255,255,0.1); margin-left: 10px; padding-left: 25px; }
        .logs-item { position: relative; margin-bottom: 25px; }
        .logs-dot { width: 14px; height: 14px; background: #18181b; border: 3px solid rgba(255,255,255,0.15);
            border-radius: 50%; position: absolute; left: -33px; top: 5px; z-index: 1; }
        .logs-item.completed .logs-dot { border-color: #10b981; background: #10b981; }
        .logs-title { font-weight: 700; color: #fff; margin-bottom: 2px; font-size: 14px; }
        .logs-date { font-size: 12px; color: #71717a; }

        @media (max-width: 576px) {
            .modal-body { padding: 1rem !important; }
            .modal-title { font-size: 1rem; }
            .form-control-custom, .form-select-custom { padding: 10px 12px; font-size: 13px; }
        }

        /* Header card title block */
        .page-title { font-size: 24px; font-weight: 800; color: #fff; letter-spacing: -0.02em; }
        .page-subtitle { font-size: 11px; color: #71717a; font-weight: 600; }

        /* Compatibility shims for old btn-circle-mobile class */
        .btn-circle-mobile {
            width: 32px; height: 32px; padding: 0;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 999px;
        }
    </style>
</head>
<body>

    <!-- ===================== HEADER ===================== -->
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

    <!-- ===================== MAIN ===================== -->
    <main class="px-6 mt-8">

        <div class="mb-6">
            <h1 class="page-title">Gestão de Viagens</h1>
            <p class="page-subtitle mt-1">Controlo de frota, atribuições e estado dos serviços.</p>
        </div>

        <!-- TABS -->
        <div class="flex flex-wrap gap-2 no-scrollbar pb-2 mb-3" id="ride-tabs">
            <a class="pill active" data-bs-toggle="tab" href="#today" data-status="today">
                Hoje
                <span id="todayBadge" class="pill-count" data-tab="today"><?= $todayCount ?></span>
            </a>
            <a class="pill" data-bs-toggle="tab" href="#pending" data-status="pending">
                Pendentes
                <span id="pendingBadge" class="pill-count" data-tab="pending"><?= $unassignedCount ?></span>
            </a>
            <a class="pill" data-bs-toggle="tab" href="#assigned" data-status="assigned">Atribuídas</a>
            <a class="pill" data-bs-toggle="tab" href="#all" data-status="all">Todas</a>
            <a class="pill warning-pill" data-bs-toggle="tab" href="#requests" data-status="requests">
                <i class="bi bi-bell-fill"></i> Pedidos
                <span id="pendingRequestsBadge" class="pill-count<?= $pendingRequestsCount > 0 ? ' has-pending' : '' ?>"><?= $pendingRequestsCount ?></span>
            </a>
        </div>

        <!-- TOOLBAR -->
        <div class="glass rounded-[22px] p-3 mb-4 flex items-center gap-2 flex-wrap rides-toolbar">
            <div id="filter-container" class="search-wrap">
                <i data-lucide="search" class="search-icon w-4 h-4"></i>
            </div>
            <button id="btnBulkDelete" class="btn-ghost" style="display:none; color:#f87171; border-color:rgba(248,113,113,0.3); background:rgba(248,113,113,0.08);" onclick="bulkDelete()">
                <i class="bi bi-trash3-fill"></i> Apagar (<span id="selectedCount">0</span>)
            </button>
            <button id="toggleSelectionMode" class="icon-btn" title="Modo Seleção" onclick="toggleSelectionMode()">
                <i data-lucide="check-square" class="w-4 h-4"></i>
            </button>
            <div class="dropdown">
                <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i data-lucide="arrow-down-wide-narrow" class="w-4 h-4"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end glass-strong border-0 shadow-lg" style="border-radius: 18px;">
                    <li><a class="dropdown-item text-white" href="#" onclick="sortRides(2, 'asc')">Mais antigas</a></li>
                    <li><a class="dropdown-item text-white" href="#" onclick="sortRides(2, 'desc')">Mais recentes</a></li>
                </ul>
            </div>
            <button class="btn-primary-modern" data-bs-toggle="modal" data-bs-target="#modalCriarViagem">
                <i data-lucide="plus" class="w-4 h-4"></i> Nova
            </button>
        </div>

        <!-- SKELETON (mostrado enquanto carrega pela primeira vez) -->
        <div id="ridesSkeleton" class="rides-skeleton">
            <div class="sk-card"></div>
            <div class="sk-card"></div>
            <div class="sk-card"></div>
            <div class="sk-card"></div>
        </div>

        <!-- RIDES TABLE / CARDS -->
        <table id="tabelaViagens" class="table" style="width:100%; display:none;">
            <thead>
                <tr>
                    <th class="col-selection"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                    <th>ID</th>
                    <th>Data & Hora</th>
                    <th>Condutor / Estado</th>
                    <th>Recolha</th>
                    <th>Entrega</th>
                    <th>Tipo</th>
                    <th>Chave</th>
                    <th>Ações</th>
                    <th>Cliente</th>
                    <th>Voo</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

    </main>

    <!-- ===================== BOTTOM NAV (Mobile 4 / Desktop expanded) ===================== -->
    <nav class="nav-float">
        <a href="/SRMT/public/admin/"><i data-lucide="home" class="w-5 h-5"></i><span>Home</span></a>
        <a href="rides.php" class="active"><i data-lucide="calendar" class="w-5 h-5"></i><span>Rides</span></a>
        <a href="live-map.php"><i data-lucide="locate-fixed" class="w-5 h-5"></i><span>Live</span></a>
        <a href="financial.php"><i data-lucide="wallet" class="w-5 h-5"></i><span>Cash</span></a>
        <a href="fleet.php" class="nav-extra"><i data-lucide="truck" class="w-5 h-5"></i><span>Frota</span></a>
        <a href="users.php" class="nav-extra"><i data-lucide="users" class="w-5 h-5"></i><span>Equipa</span></a>
        <a href="driver-stats.php" class="nav-extra"><i data-lucide="bar-chart-3" class="w-5 h-5"></i><span>Stats</span></a>
        <a href="no-shows.php" class="nav-extra"><i data-lucide="alert-triangle" class="w-5 h-5"></i><span>No Show</span></a>
        <a href="storage.php" class="nav-extra"><i data-lucide="database" class="w-5 h-5"></i><span>Storage</span></a>
    </nav>

    <!-- ===================== HAMBURGER FULL MENU ===================== -->
    <div id="fullMenu">
        <div class="mask" onclick="toggleMenu()"></div>
        <div class="panel no-scrollbar">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-3xl font-black italic tracking-tighter">SyncRide <span style="color:#2563eb">OS</span></h2>
                <button onclick="toggleMenu()" class="icon-btn"><i data-lucide="x"></i></button>
            </div>
            <nav class="flex flex-col gap-2 text-lg font-bold">
                <a href="/SRMT/public/admin/"><i data-lucide="layout-grid"></i> Dashboard</a>
                <a href="rides.php" class="active-link"><i data-lucide="navigation"></i> Viagens</a>
                <a href="live-map.php"><i data-lucide="map"></i> Live Map</a>
                <hr>
                <a href="users.php"><i data-lucide="users"></i> Equipa</a>
                <a href="fleet.php"><i data-lucide="truck"></i> Frota</a>
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

    <!-- ===================== MODALS (preserve all forms, restyled) ===================== -->
    <div class="modal fade" id="modalCriarViagem" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title">Nova Viagem</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form action="ride-add.php" method="POST">
                        <div class="row mb-3">
                            <div class="col-6"><label class="form-label small">Data</label><input type="date" class="form-control-custom" name="serviceDate" required /></div>
                            <div class="col-6"><label class="form-label small">Hora</label><input type="time" class="form-control-custom" name="serviceStartTime" required /></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6"><label class="form-label small">Adultos</label><input type="number" class="form-control-custom" name="paxADT" required placeholder="0" /></div>
                            <div class="col-6"><label class="form-label small">Crianças</label><input type="number" class="form-control-custom" name="paxCHD" required placeholder="0" /></div>
                        </div>
                        <div class="mb-3"><label class="form-label small">Partida</label><input type="text" class="form-control-custom" name="serviceStartPoint" required placeholder="Morada ou Hotel" /></div>
                        <div class="mb-3"><label class="form-label small">Chegada</label><input type="text" class="form-control-custom" name="serviceTargetPoint" required placeholder="Destino final" /></div>
                        <div class="row mb-3">
                            <div class="col-6"><label class="form-label small">Condutor</label><select class="form-select-custom" name="driver"><option value="later">Depois</option><?php foreach ($condutores as $c) echo "<option value='{$c['ID']}'>".htmlspecialchars($c['name'])."</option>"; ?></select></div>
                            <div class="col-6"><label class="form-label small">Tipo</label><select class="form-select-custom" name="serviceType"><option value="1">Privado</option><option value="0">Partilhado</option></select></div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-6"><label class="form-label small">Voo</label><input type="text" class="form-control-custom" name="FlightNumber" placeholder="Ex: TP1234" /></div>
                            <div class="col-6"><label class="form-label small">Cliente</label><input type="text" class="form-control-custom" name="NomeCliente" placeholder="Nome" /></div>
                        </div>
                        <div class="mb-3"><label class="form-label small">Número do Cliente</label><input type="text" class="form-control-custom" name="ClientNumber" placeholder="+351..." /></div>
                        <div class="mb-3"><label class="form-label small" style="color:#34d399 !important;"><i class="bi bi-cash-coin"></i> Valor a Cobrar (€)</label><input type="number" step="0.01" class="form-control-custom" name="totalPrice" placeholder="Ex: 45.50"></div>
                        <button type="submit" class="btn-modern w-100 mt-2">Criar Viagem</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="changeTripTypeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0"><h5 class="modal-title">Alterar Tipo</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form action="update-trip-type.php" method="POST">
                    <div class="modal-body text-center">
                        <input type="hidden" id="tripId_changeType" name="tripId">
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="tripType" id="private" value="1" autocomplete="off">
                            <label class="btn btn-outline-light" for="private">Privado</label>
                            <input type="radio" class="btn-check" name="tripType" id="shared" value="0" autocomplete="off">
                            <label class="btn btn-outline-warning" for="shared">Partilhado</label>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0"><button type="submit" class="btn-modern w-100">Guardar</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="atribuirCondutorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0"><h5 class="modal-title">Atribuir Condutor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form action="assign-driver.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" id="viagemId_assign" name="viagemId">
                        <select name="condutorId" class="form-select-custom text-center fw-bold">
                            <?php foreach ($condutores as $c): ?><option value="<?= $c['ID'] ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer border-top-0"><button type="submit" class="btn-modern w-100">Confirmar</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteTripModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-5">
                    <div style="color:#f87171" class="mb-3"><i class="bi bi-trash3-fill" style="font-size: 3rem;"></i></div>
                    <h5 class="fw-bold mb-2">Apagar Viagem?</h5>
                    <p class="text-zinc-400 mb-4">Vai eliminar <strong id="deleteTripName"></strong>.</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn-ghost px-4" data-bs-dismiss="modal">Não</button>
                        <a href="#" id="confirmDeleteTripBtn" class="btn-modern px-4" style="background:linear-gradient(135deg, #ef4444, #b91c1c); box-shadow: 0 8px 20px rgba(239, 68, 68, 0.25);">Sim</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-bottom-0"><h5 class="modal-title">Editar Detalhes</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body p-4">
                <form id="editTripForm" action="ride-update.php" method="POST">
                    <input type="hidden" name="edit_trip_id" id="editTripId">
                    <div class="row mb-3"><div class="col-6"><label class="small">Data/Hora</label><input type="datetime-local" class="form-control-custom" id="editDataHora" name="edit_departure_datetime" disabled></div><div class="col-6"><label class="small">Condutor</label><input type="text" class="form-control-custom" id="editCondutor" name="edit_driverName" disabled></div></div>
                    <div class="mb-3"><label class="small">Recolha</label><input type="text" class="form-control-custom" id="editRecolha" name="edit_origin" disabled></div>
                    <div class="mb-3"><label class="small">Entrega</label><input type="text" class="form-control-custom" id="editEntrega" name="edit_destination" disabled></div>
                    <div class="row mb-3"><div class="col-4"><label class="small">ADT</label><input type="number" class="form-control-custom" id="editpaxADT" name="edit_paxADT" disabled></div><div class="col-4"><label class="small">CHD</label><input type="number" class="form-control-custom" id="editpaxCHD" name="edit_paxCHD" disabled></div><div class="col-4"><label class="small">Voo</label><input type="text" class="form-control-custom" id="editflightNumber" name="edit_flightNumber" disabled></div></div>
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="small">Cliente</label><input type="text" class="form-control-custom" id="editclientName" name="edit_clientName" disabled></div>
                        <div class="col-md-6"><label class="small">Nº Cliente</label><input type="text" class="form-control-custom" id="editclientNumber" name="edit_clientNumber" disabled></div>
                    </div>
                    <div class="mb-3"><label class="form-label small" style="color:#34d399 !important;">Valor a Cobrar (€)</label><input type="number" step="0.01" class="form-control-custom" id="editTotalPrice" name="edit_totalPrice" disabled></div>
                    <div class="mt-4 pt-3 d-flex justify-content-between align-items-center" style="border-top: 1px solid rgba(255,255,255,0.08);">
                        <div><span class="text-zinc-500 small me-2">Tipo:</span><input type="text" class="d-inline-block border-0 bg-transparent fw-bold text-white" style="width: 100px;" id="editTripTypeDisplay" disabled></div>
                        <button type="button" id="btnChangeTypeEdit" class="btn-ghost"><i class="bi bi-shuffle"></i> Alterar</button>
                    </div>
                </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn-ghost w-100" style="color:#fbbf24; border-color:rgba(251,191,36,0.3); background:rgba(251,191,36,0.08); padding: 12px;" id="enableEditBtn" onclick="enableEdit()">Editar Dados</button>
                    <button type="submit" class="btn-modern w-100" form="editTripForm" id="saveChangesBtn" style="display: none;">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalLogs" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0"><h5 class="modal-title">Histórico</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body pt-4">
                    <div id="logsContent"><div class="text-center py-3"><div class="spinner-border text-light" role="status"></div></div></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        lucide.createIcons();

        function toggleMenu() {
            document.getElementById('fullMenu').classList.toggle('open');
        }

        // --- DataTables ---
        let tabelaViagens;
        let selectionMode = false;

        $(document).ready(function () {
            const urlParams = new URLSearchParams(window.location.search);
            const currentStatus = urlParams.get('tab') || 'today';

            $(`#ride-tabs a[data-status="${currentStatus}"]`).addClass('active');
            $(`#ride-tabs a:not([data-status="${currentStatus}"])`).removeClass('active');

            tabelaViagens = $('#tabelaViagens').DataTable({
                "processing": true, "serverSide": false,
                "ajax": { "url": `rides-data.php?status=${currentStatus}`, "type": "GET", "dataSrc": "data", "cache": false },
                "columns": [
                    {
                        "data": "raw_id",
                        "className": "col-selection",
                        "orderable": false,
                        "render": function(data, type, row) {
                            const id = row.raw_id ? row.raw_id : row.id.replace('#', '');
                            return `<div class="mobile-checkbox-container"><input type="checkbox" class="form-check-input ride-checkbox" value="${id}"></div>`;
                        }
                    },
                    { "data": "id" },
                    { "data": "data_hora" },
                    { "data": "condutor", "render": function(data, type, row) {
                        if (row.status_pedido === 'pendente') return `<span class="req-pending"><i class="bi bi-shop me-1"></i> ${row.partner_name || 'Agência'}</span>`;
                        let html = '';
                        if (row.partner_name && row.partner_name.trim() !== '') html += `<span class="agency-badge"><i class="bi bi-shop"></i> ${row.partner_name}</span> `;
                        if (data) html += `<span>${data}</span>`; else html += `<span class="text-zinc-500 fst-italic">Sem Condutor</span>`;
                        if (row.chave && row.chave.includes('bi-key-fill')) html += ` <span>${row.chave}</span>`;
                        return html;
                    }},
                    { "data": "recolha" },
                    { "data": "entrega" },
                    { "data": "tipo" },
                    { "data": "chave", "orderable": false },
                    { "data": "acoes", "orderable": false, "render": function (data, type, row) {
                        if (row.status_pedido === 'pendente') {
                            const tripId = row.raw_id ? row.raw_id : row.id.replace('#', '');
                            return `
                            <div class="d-flex gap-2 justify-content-end">
                                <button class="btn btn-success btn-sm" onclick="handleRequest(${tripId}, 'approve')"><i class="bi bi-check-lg"></i></button>
                                <button class="btn btn-danger btn-sm" onclick="handleRequest(${tripId}, 'reject')"><i class="bi bi-x-lg"></i></button>
                            </div>`;
                        }
                        return `<div class="d-flex gap-1 justify-content-end align-items-center">${data}</div>`;
                    }},
                    { "data": "client_name", "visible": false, "searchable": true, "defaultContent": "" },
                    { "data": "flight_number", "visible": false, "searchable": true, "defaultContent": "" }
                ],
                "language": { "search": "", "searchPlaceholder": "Procurar por ID, partida, destino, cliente…", "lengthMenu": "", "info": "", "paginate": { "next": "→", "previous": "←" }, "zeroRecords": "Sem dados" },
                "order": [[2, 'asc']], "pageLength": 10, "dom": 'frt<"p-wrap"p>'
            });

            // Move filter input into our custom search-wrap
            $('#tabelaViagens_filter').appendTo('#filter-container');
            $('#tabelaViagens_filter input').addClass('form-control-search');

            $('#ride-tabs a').on('click', function (e) {
                e.preventDefault();
                $('#ride-tabs a').removeClass('active');
                $(this).addClass('active');
                const status = $(this).data('status');
                const url = new URL(window.location);
                url.searchParams.set('tab', status);
                window.history.replaceState({}, '', url);

                tabelaViagens.search('').draw();
                tabelaViagens.ajax.url(`rides-data.php?status=${status}&_=${Date.now()}`).load();

                disableSelectionMode();
            });

            $('#selectAll').on('change', function() {
                $('.ride-checkbox').prop('checked', $(this).prop('checked'));
                updateBulkButton();
            });

            $(document).on('change', '.ride-checkbox', function() {
                updateBulkButton();
            });

            $('#tabelaViagens').on('draw.dt', function() {
                lucide.createIcons();
                // Esconder skeleton e mostrar tabela após o primeiro draw
                const skel = document.getElementById('ridesSkeleton');
                if (skel) skel.style.display = 'none';
                $('#tabelaViagens').show();
                refreshPendingBadge();
            });

            // Mostrar skeleton sempre que houver um novo carregamento (mudança de tab)
            $('#tabelaViagens').on('preXhr.dt', function() {
                const skel = document.getElementById('ridesSkeleton');
                if (skel) skel.style.display = 'grid';
                $('#tabelaViagens').hide();
            });

            // Atualizar badge da tab ativa com a contagem REAL vinda do servidor
            // (lê da resposta JSON — imune a estado interno desincronizado)
            $('#tabelaViagens').on('xhr.dt', function(e, settings, json) {
                if (!json || !Array.isArray(json.data)) return;
                const status = $('#ride-tabs a.active').data('status');
                const n = json.data.length;
                if (status === 'today') {
                    const b = document.getElementById('todayBadge');
                    if (b) b.textContent = n;
                } else if (status === 'pending') {
                    const b = document.getElementById('pendingBadge');
                    if (b) b.textContent = n;
                }
            });
        });

        // Atualiza o contador de pedidos pendentes via tab "requests"
        function refreshPendingBadge() {
            $.getJSON(`rides-data.php?status=requests&_=${Date.now()}`, function(res) {
                const n = (res && res.data) ? res.data.length : 0;
                const badge = document.getElementById('pendingRequestsBadge');
                if (!badge) return;
                badge.textContent = n;
                badge.classList.toggle('has-pending', n > 0);
            }).fail(function() { /* silent */ });
        }

        // Atualiza o badge da tab atualmente ativa (Hoje / Pendentes) com a contagem real
        function refreshActiveTabBadge() {
            const status = $('#ride-tabs a.active').data('status');
            const n = tabelaViagens ? tabelaViagens.rows().count() : 0;
            if (status === 'today') {
                const b = document.getElementById('todayBadge');
                if (b) b.textContent = n;
            } else if (status === 'pending') {
                const b = document.getElementById('pendingBadge');
                if (b) b.textContent = n;
            }
        }

        function toggleSelectionMode() {
            selectionMode = !selectionMode;
            const t = document.getElementById('tabelaViagens');
            if (selectionMode) {
                t.parentElement.classList.add('selection-active');
                document.body.classList.add('selection-active');
            } else {
                disableSelectionMode();
            }
        }

        function disableSelectionMode() {
            selectionMode = false;
            const t = document.getElementById('tabelaViagens');
            t.parentElement.classList.remove('selection-active');
            document.body.classList.remove('selection-active');
            $('.ride-checkbox').prop('checked', false);
            $('#selectAll').prop('checked', false);
            updateBulkButton();
        }

        function updateBulkButton() {
            const selected = $('.ride-checkbox:checked').length;
            $('#selectedCount').text(selected);
            $('#btnBulkDelete').toggle(selected > 0);
        }

        function bulkDelete() {
            const ids = [];
            $('.ride-checkbox:checked').each(function() { ids.push($(this).val()); });

            if (confirm(`Tem a certeza que deseja apagar ${ids.length} viagens selecionadas?`)) {
                const currentStatus = $('#ride-tabs a.active').data('status') || 'today';
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete-ride.php';
                const inputIds = document.createElement('input'); inputIds.type = 'hidden'; inputIds.name = 'ids_bulk'; inputIds.value = JSON.stringify(ids); form.appendChild(inputIds);
                const inputTab = document.createElement('input'); inputTab.type = 'hidden'; inputTab.name = 'from_tab'; inputTab.value = currentStatus; form.appendChild(inputTab);
                document.body.appendChild(form);
                form.submit();
            }
        }

        function sortRides(col, dir) { tabelaViagens.order([col, dir]).draw(); }
        function setViagemId(id) { document.getElementById('viagemId_assign').value = id; }
        function changeTripType(tripId, currentType) {
            document.getElementById('tripId_changeType').value = tripId;
            if (currentType == 1) document.getElementById('private').checked = true; else document.getElementById('shared').checked = true;
            new bootstrap.Modal(document.getElementById('changeTripTypeModal')).show();
        }
        function handleRequest(id, action) {
            if(!confirm(action === 'approve' ? 'Aprovar?' : 'Rejeitar?')) return;
            $.post('request-handle.php', { id: id, action: action }, function(res) {
                if(res.success) { toastr.success('Feito!'); tabelaViagens.ajax.reload(); } else { toastr.error('Erro'); }
            });
        }
        function editTravel(id, dataHora, condutor, recolha, entrega, paxADT, paxCHD, flightNumber, clientName, clientNumber, serviceType, totalPrice) {
            disableEdit();
            document.getElementById('editTripId').value = id;
            document.getElementById('editDataHora').value = dataHora.replace(" ", "T");
            document.getElementById('editCondutor').value = condutor;
            document.getElementById('editRecolha').value = recolha;
            document.getElementById('editEntrega').value = entrega;
            document.getElementById('editpaxADT').value = paxADT;
            document.getElementById('editpaxCHD').value = paxCHD;
            document.getElementById('editflightNumber').value = flightNumber;
            document.getElementById('editclientName').value = clientName;
            document.getElementById('editclientNumber').value = clientNumber;
            document.getElementById('editTotalPrice').value = totalPrice;
            document.getElementById('editTripTypeDisplay').value = serviceType == 1 ? "Privado" : "Partilhado";
            document.getElementById('btnChangeTypeEdit').onclick = function() { bootstrap.Modal.getInstance(document.getElementById('editModal')).hide(); changeTripType(id, serviceType); };
        }
        function enableEdit() {
            document.querySelectorAll('#editTripForm input').forEach(input => { if(input.id !== 'editCondutor' && input.id !== 'editTripTypeDisplay') input.disabled = false; });
            document.getElementById('saveChangesBtn').style.display = 'inline-block';
            const btn = document.getElementById('enableEditBtn'); btn.textContent = 'Cancelar'; btn.setAttribute('onclick', 'disableEdit()');
        }
        function disableEdit() {
            document.querySelectorAll('#editTripForm input').forEach(input => input.disabled = true);
            document.getElementById('saveChangesBtn').style.display = 'none';
            const btn = document.getElementById('enableEditBtn'); btn.textContent = 'Editar Dados'; btn.setAttribute('onclick', 'enableEdit()');
        }
        function setDeleteTrip(id, name) {
            document.getElementById("deleteTripName").textContent = name;
            const currentStatus = $('#ride-tabs a.active').data('status') || 'today';
            document.getElementById("confirmDeleteTripBtn").href = `delete-ride.php?id=${id}&from_tab=${currentStatus}`;
        }

        function viewTripLogs(id) {
            const modal = new bootstrap.Modal(document.getElementById('modalLogs'));
            const content = document.getElementById('logsContent');
            content.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-light" role="status"></div></div>';
            modal.show();

            fetch(`ride-logs.php?id=${id}`).then(r => r.json()).then(res => {
                if (res.success) {
                    const d = res.data;
                    const formatTime = (t) => {
                        if (!t) return '<span class="text-zinc-500 fst-italic small">Pendente</span>';
                        const dd = new Date(t.replace(' ', 'T'));
                        return dd.toLocaleString('pt-PT', { day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' });
                    };

                    const steps = [
                        { label: 'Iniciou Recolha', time: d.ts_start_pickup },
                        { label: 'Chegou ao Ponto', time: d.ts_arrived_pickup },
                        { label: 'Estou com o Cliente', time: d.ts_with_client },
                        { label: 'Iniciou Viagem', time: d.ts_start_trip },
                        { label: 'Terminou Viagem', time: d.ts_completed }
                    ];

                    let html = '<div class="logs-timeline">';
                    steps.forEach(step => {
                        const isDone = step.time !== null;
                        const statusClass = isDone ? 'completed' : 'pending';
                        html += `<div class="logs-item ${statusClass}"><div class="logs-dot"></div><div class="logs-content"><div class="logs-title">${step.label}</div><div class="logs-date">${formatTime(step.time)}</div></div></div>`;
                    });
                    content.innerHTML = html + '</div>';
                } else {
                    content.innerHTML = '<p class="text-center" style="color:#f87171">Erro ao carregar logs.</p>';
                }
            });
        }

        toastr.options = { "closeButton": true, "progressBar": true, "positionClass": "toast-top-right", "timeOut": "3000" };
        const success = "<?php echo isset($_GET['success']) ? $_GET['success'] : ''; ?>";
        if (success) {
            let msg = success === "ride_created" ? "Viagem criada!" : (success === "rideUpdated" ? "Viagem atualizada!" : (success === "ride_deleted" ? "Viagem eliminada!" : ""));
            if(msg) toastr.success(msg);
            const url = new URL(window.location);
            url.searchParams.delete('success');
            window.history.replaceState({}, '', url);
        }
    </script>
</body>
</html>
