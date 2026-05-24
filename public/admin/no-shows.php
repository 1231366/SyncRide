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
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Gerir No Shows | SyncRide</title>
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

        /* NO-SHOW CARDS */
        #tabelaNoShows { display: block; width: 100%; }
        #tabelaNoShows thead { display: none; }
        #tabelaNoShows tbody { display: grid; grid-template-columns: 1fr; gap: 10px; }
        @media (min-width: 768px) { #tabelaNoShows tbody { grid-template-columns: repeat(2, 1fr); } }
        @media (min-width: 1280px) { #tabelaNoShows tbody { grid-template-columns: repeat(3, 1fr); } }
        #tabelaNoShows tbody tr {
            display: block; position: relative;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 22px; padding: 18px;
            transition: all .2s; backdrop-filter: blur(20px);
        }
        #tabelaNoShows tbody tr:hover { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.15); }
        #tabelaNoShows tbody td {
            display: block; border: none !important; padding: 0 !important;
            background: transparent !important; color: #fff; width: 100% !important;
        }

        /* ID */
        #tabelaNoShows tbody td:nth-child(1) {
            font-size: 9px; color: #71717a; font-weight: 800; margin-bottom: 4px;
            text-transform: uppercase; letter-spacing: 0.15em;
            font-family: monospace;
        }
        #tabelaNoShows tbody td:nth-child(1)::before { content: "ID #"; }

        /* Data & Hora */
        #tabelaNoShows tbody td:nth-child(2) {
            font-size: 16px; font-weight: 800; color: #fff; margin-bottom: 14px;
            padding-right: 60px !important;
        }

        /* Condutor */
        #tabelaNoShows tbody td:nth-child(3) {
            font-size: 12px; color: #d4d4d8; display: flex !important; align-items: center;
            gap: 8px; margin-bottom: 6px;
        }
        #tabelaNoShows tbody td:nth-child(3):before {
            content: ""; width: 6px; height: 6px; border-radius: 50%;
            background: #60a5fa; box-shadow: 0 0 0 3px rgba(96,165,250,0.15);
            flex-shrink: 0;
        }

        /* Rota */
        #tabelaNoShows tbody td:nth-child(4) {
            font-size: 12px; color: #a1a1aa; display: flex !important; align-items: center;
            gap: 8px; margin-bottom: 4px;
        }
        #tabelaNoShows tbody td:nth-child(4):before {
            content: ""; width: 6px; height: 6px; border-radius: 50%;
            background: #ef4444; box-shadow: 0 0 0 3px rgba(239,68,68,0.15);
            flex-shrink: 0;
        }

        /* Ações */
        #tabelaNoShows tbody td:last-child {
            position: absolute; top: 16px; right: 16px; width: auto !important;
        }

        /* Action buttons inside cards */
        #tabelaNoShows .btn,
        #tabelaNoShows button:not([data-bs-dismiss]):not(.btn-close) {
            width: 36px; height: 36px; padding: 0;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 999px;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            color: #d4d4d8; transition: all .15s;
        }
        #tabelaNoShows .btn:hover { background: rgba(255,255,255,0.12); color: #fff; }
        #tabelaNoShows .btn-primary, #tabelaNoShows .btn-info { color: #60a5fa; border-color: rgba(96,165,250,0.3); }
        #tabelaNoShows .btn-danger { color: #f87171; border-color: rgba(248,113,113,0.3); }
        #tabelaNoShows .btn-warning { color: #fbbf24; border-color: rgba(251,191,36,0.3); }

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

        #photoModalImage { width: 100%; height: auto; border-radius: 18px; border: 1px solid rgba(255,255,255,0.08); }

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

        /* Empty state */
        .empty-state {
            padding: 40px; text-align: center; color: #71717a;
            font-size: 13px; font-weight: 600;
        }
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
            <h1 class="page-title">No Shows</h1>
            <p class="page-subtitle mt-1">Registo fotográfico de viagens falhadas.</p>
        </div>

        <!-- TOOLBAR -->
        <div class="glass rounded-[22px] p-3 mb-4 flex items-center gap-2 flex-wrap">
            <div id="filter-container" class="search-wrap">
                <i data-lucide="search" class="search-icon w-4 h-4"></i>
            </div>
        </div>

        <!-- TABLE / CARDS -->
        <table id="tabelaNoShows" class="table" style="width:100%">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data & Hora</th>
                    <th>Condutor</th>
                    <th>Rota</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

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
        <a href="no-shows.php" class="nav-extra active"><i data-lucide="alert-triangle" class="w-5 h-5"></i><span>No Show</span></a>
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
                <a href="fleet.php"><i data-lucide="truck"></i> Frota</a>
                <a href="financial.php"><i data-lucide="banknote"></i> Financeiro</a>
                <hr>
                <a href="driver-stats.php"><i data-lucide="bar-chart-3"></i> Estatísticas</a>
                <a href="no-shows.php" class="active-link"><i data-lucide="alert-triangle"></i> No Shows</a>
                <a href="storage.php"><i data-lucide="database"></i> Armazenamento</a>
                <hr>
                <a href="/SRMT/public/auth/logout.php" style="color:#ef4444"><i data-lucide="log-out"></i> Logout</a>
            </nav>
        </div>
    </div>

    <!-- Photo Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header border-bottom-0">
            <h5 class="modal-title" id="photoModalTitle">Comprovativo</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center p-4">
            <img src="" id="photoModalImage" class="img-fluid" alt="Comprovativo">
          </div>
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

      let tabelaNoShows;
      let photoModal;

      $(document).ready(function () {
        photoModal = new bootstrap.Modal(document.getElementById('photoModal'));

        tabelaNoShows = $('#tabelaNoShows').DataTable({
            "processing": true, "serverSide": false,
            "ajax": { "url": "no-shows-data.php", "type": "GET", "dataSrc": "data" },
            "columns": [
                { "data": "id" },
                { "data": "data_hora" },
                { "data": "condutor" },
                { "data": "rota" },
                { "data": "acoes", "orderable": false }
            ],
            "language": { "search": "", "searchPlaceholder": "Procurar...", "lengthMenu": "_MENU_", "info": "", "paginate": { "next": "→", "previous": "←" }, "zeroRecords": "Sem registos" },
            "order": [[1, 'desc']], "pageLength": 12,
            "dom": 'frtp'
        });

        $('#tabelaNoShows_filter').appendTo('#filter-container');

        $('#tabelaNoShows').on('draw.dt', function() {
            lucide.createIcons();
        });
      });

      function openPhotoModal(tripId, photoPath) {
        $('#photoModalTitle').text('No Show #' + tripId);
        $('#photoModalImage').attr('src', photoPath);
        photoModal.show();
      }

      toastr.options = { "closeButton": true, "progressBar": true, "positionClass": "toast-top-right", "timeOut": "5000" };
    </script>
  </body>
</html>
