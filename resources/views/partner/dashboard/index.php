<?php
use App\Http\View;

/**
 * @var array{total:int,pending:int,approved:int,today:int,this_month:int,noshows:int} $counts
 * @var array<array<string,mixed>> $noShows
 * @var string $userName
 */
$firstName = explode(' ', trim($userName))[0];

$rawPhoto  = $_SESSION['profile_photo_path'] ?? null;
if ($rawPhoto !== null && $rawPhoto !== '') {
    $rawPhoto  = str_replace('Includes/dist/pages/', '', $rawPhoto);
    $userPhoto = str_starts_with($rawPhoto, '/') || str_starts_with($rawPhoto, 'http')
        ? $rawPhoto
        : '/SRMT/public/' . $rawPhoto;
} else {
    $userPhoto = '';
}
$initial        = mb_strtoupper(mb_substr($firstName, 0, 1, 'UTF-8'));
$svgAvatar      = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><circle cx="20" cy="20" r="20" fill="#2563eb"/><text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="white" font-size="17" font-weight="bold" font-family="system-ui">' . htmlspecialchars($initial) . '</text></svg>';
$avatarFallback = 'data:image/svg+xml;base64,' . base64_encode($svgAvatar);
?>
<!DOCTYPE html>
<html lang="en" translate="no" data-theme="light">
<head>
<script>(function(){var t=localStorage.getItem('sr-theme')||'light';document.documentElement.dataset.theme=t;var m=document.querySelector('meta[name="theme-color"]');if(m)m.content=t==='dark'?'#020617':'#f1f5f9';})()</script>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" id="themeColor" content="#f1f5f9">
<title>Partner Portal — SyncRide OS</title>

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">

<style>
    :root { --safe-bottom: env(safe-area-inset-bottom, 0px); }
    html, body { height: 100%; overflow: hidden; }
    body {
        font-family: 'Inter', sans-serif; margin: 0;
        -webkit-font-smoothing: antialiased;
        background-color: #f1f5f9; color: #0f172a;
    }
    #app-container { height: 100%; overflow-y: auto; -webkit-overflow-scrolling: touch; }
    .bg-main {
        height: 100%;
        background: radial-gradient(circle at 50% -10%, #bfdbfe 0%, #f1f5f9 65%);
        background-attachment: fixed; min-height: 100vh;
    }
    [data-theme="dark"] .bg-main { background: radial-gradient(circle at 50% -10%, #1e3a8a 0%, #020617 70%); background-color: #020617; }
    [data-theme="dark"] body { background-color: #020617; color: #f1f5f9; }

    /* Glass */
    .glass { background: rgba(255,255,255,0.62); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(0,0,0,0.08); }
    [data-theme="dark"] .glass { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.10); }

    /* ── Bottom nav pill ── */
    .nav-bottom {
        position: fixed; bottom: 0; left: 50%; transform: translateX(-50%);
        width: calc(100% - 24px); max-width: 480px;
        height: 66px; margin-bottom: calc(10px + var(--safe-bottom));
        background: rgba(255,255,255,0.90); backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(0,0,0,0.07); border-radius: 26px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.10), 0 2px 8px rgba(0,0,0,0.06);
        display: flex; align-items: stretch; z-index: 1000; overflow: hidden;
    }
    [data-theme="dark"] .nav-bottom { background: rgba(10,14,30,0.95); border: 1px solid rgba(255,255,255,0.09); box-shadow: 0 8px 32px rgba(0,0,0,0.5); }
    .nav-bottom a, .nav-bottom button {
        flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px;
        font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        color: #94a3b8; background: none; border: none; cursor: pointer; text-decoration: none; transition: color .15s; padding: 0;
    }
    .nav-bottom a i, .nav-bottom button i { width: 20px; height: 20px; display: block; }
    .nav-bottom a:hover, .nav-bottom button:hover { color: #64748b; }
    [data-theme="dark"] .nav-bottom a, [data-theme="dark"] .nav-bottom button { color: #475569; }
    .nav-bottom a.sr-nav-active, .nav-bottom button.sr-nav-active { color: #2563eb; }
    [data-theme="dark"] .nav-bottom a.sr-nav-active, [data-theme="dark"] .nav-bottom button.sr-nav-active { color: #60a5fa; }
    /* noshow badge on nav icon */
    .nav-badge {
        position: absolute; top: 8px; right: calc(50% - 18px);
        min-width: 16px; height: 16px; border-radius: 8px;
        background: #dc2626; color: #fff;
        font-size: 8px; font-weight: 800; line-height: 16px;
        text-align: center; padding: 0 4px;
        border: 2px solid rgba(255,255,255,0.9);
    }
    [data-theme="dark"] .nav-badge { border-color: rgba(10,14,30,0.9); }

    /* ── FAB ── */
    .nav-fab {
        position: fixed; left: 50%; transform: translateX(-50%);
        bottom: calc(10px + var(--safe-bottom) + 36px);
        width: 52px; height: 52px; border-radius: 50%;
        background: #2563eb; color: #fff;
        border: 4px solid #f1f5f9;
        box-shadow: 0 4px 20px rgba(37,99,235,0.45);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; z-index: 1001; transition: transform .15s, box-shadow .15s;
    }
    .nav-fab:active { transform: translateX(-50%) scale(.93); }
    [data-theme="dark"] .nav-fab { border-color: #020617; box-shadow: 0 4px 20px rgba(37,99,235,0.6); }

    /* ── Sections ── */
    .page-section { display: none; }
    .page-section.active { display: block; }

    /* Section enter animation */
    .page-section.active { animation: fadeUp .22s ease; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

    /* ── Light overrides ── */
    [data-theme="light"] .text-white      { color: #0f172a !important; }
    [data-theme="light"] .bg-white\/5     { background: rgba(0,0,0,0.04) !important; }
    [data-theme="light"] .border-white\/10  { border-color: rgba(0,0,0,0.08) !important; }

    /* ── Bootstrap modal override ── */
    .modal-backdrop { backdrop-filter: blur(4px) !important; -webkit-backdrop-filter: blur(4px) !important; background: rgba(0,0,0,0.3) !important; }
    .modal-backdrop.show { opacity: 1 !important; }
    .modal-content { border-radius: 28px !important; border: 1px solid rgba(0,0,0,0.10) !important; overflow: hidden; }
    [data-theme="light"] .modal-content { background: rgba(255,255,255,0.97) !important; box-shadow: 0 24px 64px rgba(0,0,0,0.14) !important; color: #0f172a !important; }
    [data-theme="dark"]  .modal-content { background: rgba(10,12,20,0.97) !important; border: 1px solid rgba(255,255,255,0.12) !important; color: #f1f5f9 !important; }
    .modal-header { padding: 20px 24px 16px !important; border-bottom: 1px solid rgba(0,0,0,0.08) !important; }
    [data-theme="dark"] .modal-header { border-bottom-color: rgba(255,255,255,0.08) !important; }
    .modal-body { padding: 20px 24px 24px !important; }
    .modal-title { font-weight: 800 !important; font-size: 1rem !important; }
    .btn-close { opacity: .4 !important; }
    [data-theme="dark"] .btn-close { filter: invert(1) !important; }

    /* ── Form controls ── */
    .form-control, .form-select { border-radius: 12px !important; font-size: .875rem !important; padding: 10px 14px !important; transition: border-color .15s, box-shadow .15s !important; }
    [data-theme="light"] .form-control, [data-theme="light"] .form-select { background: rgba(0,0,0,0.04) !important; border: 1px solid rgba(0,0,0,0.12) !important; color: #0f172a !important; }
    [data-theme="dark"]  .form-control, [data-theme="dark"]  .form-select { background: rgba(255,255,255,0.06) !important; border: 1px solid rgba(255,255,255,0.10) !important; color: #f1f5f9 !important; }
    .form-control:focus, .form-select:focus { box-shadow: 0 0 0 3px rgba(37,99,235,0.18) !important; border-color: #2563eb !important; }
    label.form-label, .modal label { font-size: .7rem !important; font-weight: 700 !important; text-transform: uppercase; letter-spacing: .06em; color: #94a3b8; margin-bottom: 5px !important; }
    .btn-primary { background: #2563eb !important; border-color: #2563eb !important; border-radius: 12px !important; font-weight: 700 !important; font-size: .875rem !important; }
    .btn-primary:hover { background: #1d4ed8 !important; border-color: #1d4ed8 !important; }

    /* ── Tab pills ── */
    .partner-tabs { display: flex; gap: 6px; }
    .partner-tab { padding: 7px 18px; border-radius: 50px; font-size: .75rem; font-weight: 700; cursor: pointer; border: none; color: #94a3b8; transition: all .15s; }
    .glass-tab { background: rgba(255,255,255,0.62); backdrop-filter: blur(10px); border: 1px solid rgba(0,0,0,0.08); }
    [data-theme="dark"] .glass-tab { background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.1); }
    .partner-tab.active { background: #2563eb !important; color: #fff !important; border-color: transparent !important; }
    [data-theme="dark"] .partner-tab { color: #64748b; }

    /* ── DataTable ── */
    .dataTables_wrapper { padding: 0 !important; }
    .dataTables_wrapper .dataTables_info, .dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter { display: none !important; }
    .dataTables_wrapper .dataTables_paginate { padding: 12px 16px !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button { border-radius: 8px !important; font-size: .75rem !important; font-weight: 700 !important; border: none !important; padding: 5px 11px !important; color: #94a3b8 !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover { background: #2563eb !important; color: #fff !important; border: none !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: rgba(37,99,235,0.1) !important; color: #2563eb !important; border: none !important; }
    #tabelaPartner { border-collapse: separate !important; border-spacing: 0 !important; }
    #tabelaPartner thead th { font-size: .65rem !important; font-weight: 800 !important; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8 !important; border-bottom: 1px solid rgba(0,0,0,0.07) !important; padding: 12px 16px !important; background: transparent !important; white-space: nowrap; }
    [data-theme="dark"] #tabelaPartner thead th { color: #475569 !important; border-bottom-color: rgba(255,255,255,0.07) !important; }
    #tabelaPartner tbody td { padding: 11px 16px !important; vertical-align: middle !important; border-bottom: 1px solid rgba(0,0,0,0.05) !important; font-size: .875rem !important; color: #0f172a; }
    [data-theme="dark"] #tabelaPartner tbody td { border-bottom-color: rgba(255,255,255,0.05) !important; color: #f1f5f9 !important; }
    #tabelaPartner tbody tr:last-child td { border-bottom: none !important; }
    #tabelaPartner tbody tr:hover td { background: rgba(37,99,235,0.03) !important; }
    [data-theme="dark"] #tabelaPartner tbody tr:hover td { background: rgba(255,255,255,0.03) !important; }

    @media (max-width: 767.98px) {
        #tabelaPartner thead { display: none; }
        #tabelaPartner tbody tr { display: flex; flex-direction: column; background: rgba(255,255,255,0.62) !important; backdrop-filter: blur(20px); border: 1px solid rgba(0,0,0,0.08) !important; border-radius: 16px; margin-bottom: 10px; padding: 14px; position: relative; }
        [data-theme="dark"] #tabelaPartner tbody tr { background: rgba(255,255,255,0.05) !important; border-color: rgba(255,255,255,0.10) !important; }
        #tabelaPartner tbody tr:hover td { background: transparent !important; }
        #tabelaPartner tbody td { display: block; width: 100%; padding: 0 !important; margin-bottom: 3px; border: none !important; }
        #tabelaPartner tbody td:nth-child(1) { font-size: .65rem; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 5px; }
        #tabelaPartner tbody td:nth-child(2) { font-size: .95rem; font-weight: 800; margin-bottom: 6px; padding-right: 70px !important; }
        #tabelaPartner tbody td:nth-child(3), #tabelaPartner tbody td:nth-child(4), #tabelaPartner tbody td:nth-child(5) { font-size: .8rem; color: #475569; display: flex; align-items: center; min-height: 20px; }
        [data-theme="dark"] #tabelaPartner tbody td:nth-child(3), [data-theme="dark"] #tabelaPartner tbody td:nth-child(4), [data-theme="dark"] #tabelaPartner tbody td:nth-child(5) { color: #94a3b8; }
        #tabelaPartner tbody td:nth-child(7) { position: absolute; top: 14px; right: 14px; width: auto !important; margin: 0; }
        #tabelaPartner tbody td:nth-child(8) { position: absolute; top: 44px; right: 14px; width: auto !important; margin: 0; }
        #tabelaPartner tbody td:nth-child(6) { display: none; }
    }

    /* ── Badges ── */
    .badge { border-radius: 6px !important; font-weight: 700 !important; font-size: .65rem !important; padding: 4px 8px !important; letter-spacing: .04em; }

    /* ── Search ── */
    .search-wrap { position: relative; }
    .search-wrap input { padding: 9px 14px 9px 38px; border-radius: 50px; font-size: .8rem; width: 100%; outline: none; transition: box-shadow .15s; }
    [data-theme="light"] .search-wrap input { background: rgba(255,255,255,0.7); border: 1px solid rgba(0,0,0,0.09); color: #0f172a; }
    [data-theme="dark"]  .search-wrap input { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.10); color: #f1f5f9; }
    .search-wrap input:focus { box-shadow: 0 0 0 3px rgba(37,99,235,0.15); border-color: #2563eb; }
    .search-wrap svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: #94a3b8; pointer-events: none; }

    /* ── Table action button ── */
    .btn-act { width: 30px; height: 30px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.08); background: rgba(255,255,255,0.6); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: .75rem; transition: background .15s; color: #475569; }
    [data-theme="dark"] .btn-act { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.1); color: #94a3b8; }
    .btn-act:hover { background: rgba(37,99,235,0.08); color: #2563eb; }

    /* ── Stat card (Stats section) ── */
    .stat-big { border-radius: 20px; padding: 20px 16px; }
    .stat-big .label { font-size: .65rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #94a3b8; margin-bottom: 6px; }
    .stat-big .value { font-size: 2.4rem; font-weight: 900; line-height: 1; letter-spacing: -.02em; }
    .stat-big .sub { font-size: .72rem; color: #94a3b8; margin-top: 4px; }

    .no-scrollbar::-webkit-scrollbar { display: none; }
</style>
</head>
<body>
<div class="bg-main">
<div id="app-container">
<div style="padding-bottom: calc(66px + var(--safe-bottom) + 32px)">

    <!-- ── Shared header ──────────────────────────────────────────── -->
    <header class="px-6 pt-10 flex justify-between items-center">
        <div class="flex items-center gap-3">
            <img src="<?= View::e($userPhoto) ?>"
                 onerror="this.onerror=null;this.src='<?= $avatarFallback ?>'"
                 class="w-10 h-10 rounded-full object-cover"
                 style="border:2px solid rgba(37,99,235,0.2);" alt="">
            <div>
                <h2 class="text-[15px] font-extrabold leading-tight">Hi, <?= View::e($firstName) ?></h2>
                <p class="text-[8px] text-zinc-500 font-black tracking-widest uppercase italic">Partner Portal</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button onclick="applyTheme(document.documentElement.dataset.theme==='dark'?'light':'dark',true)"
                    class="glass w-9 h-9 rounded-full flex items-center justify-center active:scale-90 transition-transform border-0">
                <i data-lucide="sun"  class="w-4 h-4 hidden" id="icon-light"></i>
                <i data-lucide="moon" class="w-4 h-4"        id="icon-dark"></i>
            </button>
            <button onclick="confirmLogout()"
                    class="glass w-9 h-9 rounded-full flex items-center justify-center active:scale-90 transition-transform border-0 text-red-500/70">
                <i data-lucide="log-out" class="w-4 h-4"></i>
            </button>
        </div>
    </header>

    <!-- ════════════════════════════════════════════════════════════
         SECTION: RIDES
    ═══════════════════════════════════════════════════════════════ -->
    <div id="section-rides" class="page-section active">

        <!-- Tabs + Search -->
        <section class="px-6 mt-6">
            <div class="flex items-center gap-3 mb-3 overflow-x-auto no-scrollbar">
                <div class="partner-tabs flex-shrink-0" id="partner-tabs">
                    <button class="partner-tab glass-tab active" data-status="pendente">Pending</button>
                    <button class="partner-tab glass-tab" data-status="aprovado">Confirmed</button>
                    <button class="partner-tab glass-tab" data-status="rejeitado">Rejected</button>
                </div>
            </div>
            <div class="search-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                <input type="text" id="customSearch" placeholder="Search bookings…">
            </div>
        </section>

        <!-- Table -->
        <section class="px-6 mt-3">
            <div class="glass rounded-2xl overflow-hidden">
                <table id="tabelaPartner" class="table mb-0 w-100">
                    <thead>
                        <tr>
                            <th class="ps-4">Date</th>
                            <th>Passenger</th>
                            <th>Flight</th>
                            <th>Route</th>
                            <th>Pax</th>
                            <th class="text-center">Key</th>
                            <th class="text-center">Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </section>

        <!-- Desktop new booking -->
        <div class="hidden md:flex justify-end px-6 mt-4">
            <button class="bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm flex items-center gap-2 active:scale-95 transition-transform"
                    data-bs-toggle="modal" data-bs-target="#modalNewBooking">
                <i data-lucide="plus" class="w-4 h-4"></i> New Booking
            </button>
        </div>
    </div><!-- /section-rides -->

    <!-- ════════════════════════════════════════════════════════════
         SECTION: STATS
    ═══════════════════════════════════════════════════════════════ -->
    <div id="section-stats" class="page-section">
        <section class="px-6 mt-6">
            <h2 class="text-xl font-black mb-4">Statistics</h2>

            <!-- 2-col KPI grid -->
            <div class="grid grid-cols-2 gap-3">

                <div class="glass stat-big">
                    <div class="label">Today</div>
                    <div class="value text-blue-500"><?= (int) $counts['today'] ?></div>
                    <div class="sub">rides scheduled</div>
                </div>

                <div class="glass stat-big">
                    <div class="label">This Month</div>
                    <div class="value"><?= (int) $counts['this_month'] ?></div>
                    <div class="sub">rides</div>
                </div>

                <div class="glass stat-big">
                    <div class="label">All Time</div>
                    <div class="value"><?= (int) $counts['total'] ?></div>
                    <div class="sub">total rides</div>
                </div>

                <div class="glass stat-big">
                    <div class="label">Confirmed</div>
                    <div class="value text-emerald-500"><?= (int) $counts['approved'] ?></div>
                    <div class="sub">approved</div>
                </div>

            </div>

            <!-- Pending + No-Shows row -->
            <div class="grid grid-cols-2 gap-3 mt-3">

                <div class="glass stat-big">
                    <div class="label">Pending</div>
                    <div class="value text-amber-500"><?= (int) $counts['pending'] ?></div>
                    <div class="sub">awaiting approval</div>
                </div>

                <div class="glass stat-big">
                    <div class="label">No-Shows</div>
                    <div class="value <?= $counts['noshows'] > 0 ? 'text-red-500' : 'text-zinc-400' ?>"><?= (int) $counts['noshows'] ?></div>
                    <div class="sub">incidents recorded</div>
                </div>

            </div>

            <!-- Approval rate pill -->
            <?php
                $rate = $counts['total'] > 0
                    ? round($counts['approved'] / $counts['total'] * 100)
                    : 0;
            ?>
            <div class="glass rounded-2xl p-4 mt-3 flex items-center justify-between">
                <div>
                    <p class="text-[9px] font-black uppercase tracking-widest text-zinc-500">Approval Rate</p>
                    <p class="text-lg font-black mt-0.5"><?= $rate ?>%</p>
                </div>
                <!-- progress bar -->
                <div class="flex-1 mx-4">
                    <div class="h-2 rounded-full overflow-hidden" style="background:rgba(0,0,0,0.07)">
                        <div class="h-full rounded-full bg-blue-500 transition-all"
                             style="width:<?= $rate ?>%"></div>
                    </div>
                </div>
                <p class="text-[9px] text-zinc-500 font-bold whitespace-nowrap"><?= (int) $counts['approved'] ?>/<?= (int) $counts['total'] ?></p>
            </div>
        </section>
    </div><!-- /section-stats -->

    <!-- ════════════════════════════════════════════════════════════
         SECTION: NO-SHOWS
    ═══════════════════════════════════════════════════════════════ -->
    <div id="section-noshows" class="page-section">
        <section class="px-6 mt-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-black">No-Shows</h2>
                <?php if ($counts['noshows'] > 0): ?>
                <span class="text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded-full"
                      style="background:rgba(239,68,68,.12);color:#dc2626;border:1px solid rgba(239,68,68,.2)">
                    <?= (int) $counts['noshows'] ?> incident<?= $counts['noshows'] !== 1 ? 's' : '' ?>
                </span>
                <?php endif; ?>
            </div>

            <?php if (empty($noShows)): ?>
            <div class="glass rounded-2xl p-8 text-center">
                <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-3"
                     style="background:rgba(16,185,129,.1)">
                    <i data-lucide="check-circle" style="width:26px;height:26px;color:#10b981;"></i>
                </div>
                <p class="font-bold text-sm">No incidents recorded</p>
                <p class="text-[10px] text-zinc-500 mt-1">No no-shows have been reported on your rides.</p>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($noShows as $ns):
                    $nsTs      = !empty($ns['serviceDate']) ? strtotime((string) $ns['serviceDate']) : false;
                    $nsDate    = $nsTs !== false ? date('d M Y', $nsTs) : '—';
                    $nsTime    = !empty($ns['serviceStartTime'])
                        ? substr((string) $ns['serviceStartTime'], 0, 5)
                        : '';
                    $nsClient  = strtoupper((string) ($ns['NomeCliente'] ?? '—'));
                    $nsOrigin  = (string) ($ns['serviceStartPoint']  ?? '');
                    $nsDestiny = (string) ($ns['serviceTargetPoint'] ?? '');
                    $nsReport  = (string) ($ns['noShowReportPath'] ?? '');
                    $nsPhoto   = (string) ($ns['noShowPhotoPath']  ?? '');
                    $reportUrl = $nsReport !== '' ? '/SRMT/public/' . ltrim($nsReport, '/') : '';
                    $photoUrl  = $nsPhoto  !== '' ? '/SRMT/public/' . ltrim($nsPhoto,  '/') : '';
                    $nsId      = (int) $ns['ID'];
                ?>
                <div class="glass rounded-2xl p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <!-- Date + badge -->
                            <div class="flex items-center gap-2 mb-2 flex-wrap">
                                <span class="text-[9px] font-black uppercase tracking-widest text-zinc-500">
                                    <?= View::e($nsDate) ?>
                                    <?php if ($nsTime): ?>
                                    <span class="text-zinc-400 mx-1">·</span><?= View::e($nsTime) ?>
                                    <?php endif; ?>
                                </span>
                                <span class="text-[8px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full flex-shrink-0"
                                      style="background:rgba(239,68,68,.12);color:#dc2626;border:1px solid rgba(239,68,68,.18)">
                                    No-Show
                                </span>
                            </div>
                            <!-- Client -->
                            <p class="text-sm font-bold leading-tight mb-1.5"><?= View::e($nsClient) ?></p>
                            <!-- Route -->
                            <div class="flex items-start gap-1 text-[10px] text-zinc-500">
                                <i data-lucide="map-pin" style="width:11px;height:11px;flex-shrink:0;margin-top:1px;color:#2563eb"></i>
                                <span class="leading-snug">
                                    <?= View::e($nsOrigin) ?>
                                    <span class="text-zinc-400 mx-1">→</span>
                                    <?= View::e($nsDestiny) ?>
                                </span>
                            </div>
                            <?php if (!empty($ns['noShowLat']) && !empty($ns['noShowLng'])): ?>
                            <p class="text-[9px] text-zinc-400 mt-1 flex items-center gap-1">
                                <i data-lucide="locate" style="width:9px;height:9px;"></i>
                                <?= View::e((string) $ns['noShowLat']) ?>, <?= View::e((string) $ns['noShowLng']) ?>
                            </p>
                            <?php endif; ?>
                        </div>

                        <!-- Action buttons -->
                        <div class="flex flex-col gap-2 flex-shrink-0">
                            <?php if ($photoUrl !== ''): ?>
                            <a href="<?= View::e($photoUrl) ?>" target="_blank"
                               class="w-9 h-9 glass rounded-full flex items-center justify-center text-zinc-500 active:scale-90 transition-transform"
                               title="View photo">
                                <i data-lucide="camera" style="width:15px;height:15px;"></i>
                            </a>
                            <?php endif; ?>

                            <?php if ($reportUrl !== ''): ?>
                            <a href="<?= View::e($reportUrl) ?>"
                               download="NoShow-Report-<?= $nsId ?>.pdf"
                               class="w-9 h-9 rounded-full flex items-center justify-center text-white active:scale-90 transition-transform"
                               style="background:#2563eb;box-shadow:0 2px 8px rgba(37,99,235,.4)"
                               title="Download PDF">
                                <i data-lucide="file-down" style="width:15px;height:15px;"></i>
                            </a>
                            <?php else: ?>
                            <div class="w-9 h-9 glass rounded-full flex items-center justify-center text-zinc-400"
                                 title="Report not available">
                                <i data-lucide="file-x" style="width:15px;height:15px;"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>
    </div><!-- /section-noshows -->

</div>
</div>
</div>

<!-- ── Bottom nav (5 slots: rides | noshows | [FAB] | stats | logout) ── -->
<nav class="nav-bottom">
    <button id="nav-rides" onclick="showSection('rides')" class="sr-nav-active">
        <i data-lucide="calendar"></i>Rides
    </button>
    <button id="nav-noshows" onclick="showSection('noshows')" style="position:relative">
        <i data-lucide="alert-triangle"></i>No-Shows
        <?php if ($counts['noshows'] > 0): ?>
        <span class="nav-badge"><?= (int) $counts['noshows'] ?></span>
        <?php endif; ?>
    </button>
    <span style="flex:1"></span><!-- FAB spacer -->
    <button id="nav-stats" onclick="showSection('stats')">
        <i data-lucide="bar-chart-2"></i>Stats
    </button>
    <button onclick="confirmLogout()" class="text-red-500/70">
        <i data-lucide="log-out"></i>Logout
    </button>
</nav>

<!-- FAB -->
<button class="nav-fab" data-bs-toggle="modal" data-bs-target="#modalNewBooking" title="New Booking">
    <i data-lucide="plus" style="width:22px;height:22px;"></i>
</button>

<!-- ── Logout confirm ─────────────────────────────────────────────── -->
<div class="modal fade" id="modalConfirmLogout" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center p-5">
                <div class="w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-3"
                     style="background:rgba(220,38,38,.1)">
                    <i data-lucide="log-out" style="width:22px;height:22px;color:#dc2626;"></i>
                </div>
                <h5 class="font-extrabold mb-1 text-sm">Sign out?</h5>
                <p class="text-zinc-400 text-xs mb-4">You'll need to log in again.</p>
                <div class="flex gap-2 justify-center">
                    <button type="button" class="glass px-5 py-2 rounded-xl font-bold text-xs" data-bs-dismiss="modal">Cancel</button>
                    <a href="/SRMT/public/auth/logout.php" class="bg-red-600 text-white px-5 py-2 rounded-xl font-bold text-xs">Sign out</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── New Booking modal ──────────────────────────────────────────── -->
<div class="modal fade" id="modalNewBooking" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNewBooking">
                    <div class="row g-3">
                        <div class="col-6"><label class="form-label">Date</label><input type="date" name="date" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Time</label><input type="time" name="time" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Passenger name</label><input type="text" name="client_name" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Phone</label><input type="text" name="client_phone" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Price</label><input type="number" step="0.01" name="price" class="form-control"></div>
                        <div class="col-4"><label class="form-label">Adults</label><input type="number" name="pax_adt" value="1" class="form-control" required></div>
                        <div class="col-4"><label class="form-label">Children</label><input type="number" name="pax_chd" value="0" class="form-control"></div>
                        <div class="col-4"><label class="form-label">Babies</label><input type="number" name="pax_bby" value="0" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Pickup</label><input type="text" name="pickup" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Destination</label><input type="text" name="dropoff" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Flight</label><input type="text" name="flight" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Has Key?</label>
                            <select name="has_key" class="form-select"><option value="0" selected>No</option><option value="1">Yes</option></select>
                        </div>
                        <div class="col-12 mt-2"><button type="submit" class="btn btn-primary w-100 py-3 font-bold">Submit Booking</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── Edit Booking modal ─────────────────────────────────────────── -->
<div class="modal fade" id="modalEditBooking" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formEditBooking">
                    <input type="hidden" name="ride_id" id="edit_ride_id">
                    <div class="row g-3">
                        <div class="col-6"><label class="form-label">Date</label><input type="date" name="date" id="edit_date" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Time</label><input type="time" name="time" id="edit_time" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Passenger name</label><input type="text" name="client_name" id="edit_client_name" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Phone</label><input type="text" name="client_phone" id="edit_client_phone" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Flight</label><input type="text" name="flight" id="edit_flight" class="form-control"></div>
                        <div class="col-4"><label class="form-label">Adults</label><input type="number" name="pax_adt" id="edit_pax_adt" value="1" class="form-control" required></div>
                        <div class="col-4"><label class="form-label">Children</label><input type="number" name="pax_chd" id="edit_pax_chd" value="0" class="form-control"></div>
                        <div class="col-4"><label class="form-label">Babies</label><input type="number" name="pax_bby" id="edit_pax_bby" value="0" class="form-control"></div>
                        <div class="col-12"><label class="form-label">Pickup</label><input type="text" name="pickup" id="edit_pickup" class="form-control" required></div>
                        <div class="col-12"><label class="form-label">Destination</label><input type="text" name="dropoff" id="edit_dropoff" class="form-control" required></div>
                        <div class="col-12 mt-2"><button type="submit" class="btn btn-primary w-100 py-3 font-bold">Save Changes</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
lucide.createIcons();

/* ── Theme ────────────────────────────────────────────────────────── */
function applyTheme(t, save) {
    document.documentElement.dataset.theme = t;
    const mc = document.getElementById('themeColor');
    if (mc) mc.content = t === 'dark' ? '#020617' : '#f1f5f9';
    document.getElementById('icon-light').classList.toggle('hidden', t !== 'dark');
    document.getElementById('icon-dark').classList.toggle('hidden',  t === 'dark');
    if (save) localStorage.setItem('sr-theme', t);
}
(function () { applyTheme(localStorage.getItem('sr-theme') || 'light', false); })();

/* ── Section switching ────────────────────────────────────────────── */
const NAV_MAP = { rides: 'nav-rides', noshows: 'nav-noshows', stats: 'nav-stats' };

function showSection(id) {
    // scroll to top
    document.getElementById('app-container').scrollTo({ top: 0, behavior: 'smooth' });

    // toggle sections
    document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
    const sec = document.getElementById('section-' + id);
    if (sec) sec.classList.add('active');

    // toggle nav active state
    document.querySelectorAll('.nav-bottom button, .nav-bottom a').forEach(b => b.classList.remove('sr-nav-active'));
    const navBtn = document.getElementById(NAV_MAP[id]);
    if (navBtn) navBtn.classList.add('sr-nav-active');
}

function confirmLogout() { new bootstrap.Modal(document.getElementById('modalConfirmLogout')).show(); }

/* ── DataTable ────────────────────────────────────────────────────── */
let table, currentStatus = 'pendente';
$(document).ready(function () {
    table = $('#tabelaPartner').DataTable({
        language: { emptyTable: 'No bookings in this status', zeroRecords: 'No results found' },
        ajax: { url: '/SRMT/public/partner/api-rides.php', data: d => { d.status = currentStatus; } },
        columns: [
            { data: 'data_hora', className: 'ps-4' },
            { data: 'cliente', className: 'fw-bold' },
            { data: 'voo' },
            { data: 'rota' },
            { data: 'pax' },
            { data: 'has_key', className: 'text-center', render: d => d == 1
                ? '<span class="badge" style="background:rgba(16,185,129,.12);color:#059669;border:1px solid rgba(16,185,129,.2)"><i class="bi bi-key-fill me-1"></i>Yes</span>'
                : '<span class="badge" style="background:rgba(239,68,68,.10);color:#dc2626;border:1px solid rgba(239,68,68,.2)"><i class="bi bi-x me-1"></i>No</span>' },
            { data: 'status', className: 'text-center', render: d => {
                const s = (d||'').toLowerCase();
                if (s==='pendente') return '<span class="badge" style="background:rgba(245,158,11,.12);color:#d97706;border:1px solid rgba(245,158,11,.2)">Pending</span>';
                if (s==='aprovado') return '<span class="badge" style="background:rgba(16,185,129,.12);color:#059669;border:1px solid rgba(16,185,129,.2)">Confirmed</span>';
                if (s==='rejeitado') return '<span class="badge" style="background:rgba(239,68,68,.10);color:#dc2626;border:1px solid rgba(239,68,68,.2)">Rejected</span>';
                return '<span class="badge">' + d + '</span>';
            }},
            { data: 'acoes', className: 'text-end pe-3', orderable: false, searchable: false }
        ],
        order: [[0, 'desc']],
        dom: 'rt<"d-flex justify-content-center mt-3 pb-2"p>',
        pageLength: 10,
        autoWidth: false
    });

    $('#customSearch').on('keyup', function () { table.search(this.value).draw(); });

    document.querySelectorAll('#partner-tabs .partner-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('#partner-tabs .partner-tab').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentStatus = btn.dataset.status;
            table.ajax.reload();
        });
    });

    $('#tabelaPartner tbody').on('click', '.btn-edit-ride', function () {
        const row = table.row($(this).closest('tr')).data();
        if (!row) return;
        const m = document.getElementById('modalEditBooking');
        m.querySelector('#edit_ride_id').value     = row.raw_id;
        m.querySelector('#edit_date').value        = row.raw_date;
        m.querySelector('#edit_time').value        = row.raw_time;
        m.querySelector('#edit_client_name').value = row.raw_client;
        m.querySelector('#edit_client_phone').value= row.raw_phone;
        m.querySelector('#edit_pax_adt').value     = row.raw_pax_adt;
        m.querySelector('#edit_pax_chd').value     = row.raw_pax_chd;
        m.querySelector('#edit_pax_bby').value     = row.raw_pax_bby;
        m.querySelector('#edit_pickup').value      = row.raw_pickup;
        m.querySelector('#edit_dropoff').value     = row.raw_dropoff;
        m.querySelector('#edit_flight').value      = row.raw_flight;
        new bootstrap.Modal(m).show();
    });

    $('#formEditBooking').on('submit', function (e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('Saving…');
        fetch('/SRMT/public/partner/api-update-ride.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    toastr.success('Booking updated!');
                    bootstrap.Modal.getInstance(document.getElementById('modalEditBooking')).hide();
                    table.ajax.reload();
                } else { toastr.error(res.error || 'Update not allowed'); }
            })
            .catch(() => toastr.error('Network error'))
            .finally(() => btn.prop('disabled', false).html('Save Changes'));
    });

    $('#formNewBooking').on('submit', function (e) {
        e.preventDefault();
        const btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true).html('Sending…');
        fetch('/SRMT/public/partner/api-create-ride.php', { method: 'POST', body: new FormData(this) })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    toastr.success('Booking submitted!');
                    bootstrap.Modal.getInstance(document.getElementById('modalNewBooking')).hide();
                    document.getElementById('formNewBooking').reset();
                    table.ajax.reload();
                } else { toastr.error(res.error || 'Error'); }
            })
            .catch(() => toastr.error('Network error'))
            .finally(() => btn.prop('disabled', false).html('Submit Booking'));
    });
});
</script>
</body>
</html>
