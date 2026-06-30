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

    /* ── Settings rows ── */
    .settings-row { display: flex; align-items: center; gap: 14px; padding: 15px 18px; border-bottom: 1px solid rgba(0,0,0,0.06); cursor: pointer; transition: background .12s; }
    [data-theme="dark"] .settings-row { border-bottom-color: rgba(255,255,255,0.06); }
    .settings-row:last-child { border-bottom: none; }
    .settings-row:active { background: rgba(37,99,235,0.05); }
    .settings-row-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .settings-row-label { flex: 1; font-size: .875rem; font-weight: 600; }
    .settings-row-sub { font-size: .72rem; color: #94a3b8; margin-top: 1px; }
    .settings-row-chevron { color: #cbd5e1; }

    /* ── Booking form labels ── */
    .bk-lbl { display:block; font-size:.6rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:#94a3b8; margin-bottom:3px; }
    [data-theme="dark"] .bk-lbl { color:#475569; }

    /* ── Ride cards ── */
    .pcard {
        border-radius: 18px; padding: 16px;
        border: 1px solid rgba(0,0,0,0.07);
        border-left: 3px solid #94a3b8;
        background: rgba(255,255,255,0.72);
        backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
        margin-bottom: 10px; position: relative;
        transition: transform .12s, box-shadow .12s;
    }
    [data-theme="dark"] .pcard { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.08); }
    .pcard:active { transform: scale(.99); }
    .pcard.s-pending   { border-left-color: #f59e0b; }
    .pcard.s-confirmed { border-left-color: #10b981; }
    .pcard.s-rejected  { border-left-color: #ef4444; }
    .pcard-top { display: flex; justify-content: space-between; align-items: flex-start; }
    .pcard-time { font-size: 1.25rem; font-weight: 900; letter-spacing: -.02em; line-height: 1; }
    .pcard-client { font-size: .82rem; color: #64748b; margin-top: 3px; font-weight: 500; }
    [data-theme="dark"] .pcard-client { color: #94a3b8; }
    .pcard-right { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; flex-shrink: 0; margin-left: 10px; }
    .pcard-status { font-size: .62rem; font-weight: 800; padding: 3px 10px; border-radius: 50px; }
    .pcard-meta { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; margin: 10px 0 8px; }
    .pcard-chip { font-size: .62rem; font-weight: 700; display: inline-flex; align-items: center; gap: 3px; color: #94a3b8; }
    .pcard-route { font-size: .82rem; color: #475569; display: flex; flex-direction: column; gap: 6px; }
    [data-theme="dark"] .pcard-route { color: #94a3b8; }
    .pcard-point { display: flex; align-items: flex-start; gap: 8px; }
    .rdot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
    .rdot-g { background: #10b981; }
    .rdot-r { background: #ef4444; }
    .rdot-line { width: 1px; height: 10px; background: repeating-linear-gradient(to bottom, #cbd5e1 0, #cbd5e1 2px, transparent 2px, transparent 4px); margin: 1px 0 1px 3.5px; }

    /* ── Badges ── */
    .badge { border-radius: 6px !important; font-weight: 700 !important; font-size: .65rem !important; padding: 4px 8px !important; letter-spacing: .04em; }

    /* ── Search ── */
    .search-wrap { position: relative; }
    .search-wrap input { padding: 9px 14px 9px 38px; border-radius: 50px; font-size: .8rem; width: 100%; outline: none; transition: box-shadow .15s; }
    [data-theme="light"] .search-wrap input { background: rgba(255,255,255,0.7); border: 1px solid rgba(0,0,0,0.09); color: #0f172a; }
    [data-theme="dark"]  .search-wrap input { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.10); color: #f1f5f9; }
    .search-wrap input:focus { box-shadow: 0 0 0 3px rgba(37,99,235,0.15); border-color: #2563eb; }
    .search-wrap svg { position: absolute; left: 13px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: #94a3b8; pointer-events: none; }

    /* ── Edit button ── */
    .btn-act { width: 30px; height: 30px; border-radius: 50%; border: 1px solid rgba(0,0,0,0.08); background: rgba(255,255,255,0.6); display: inline-flex; align-items: center; justify-content: center; cursor: pointer; font-size: .75rem; transition: background .15s; color: #475569; }
    [data-theme="dark"] .btn-act { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.1); color: #94a3b8; }
    .btn-act:hover { background: rgba(37,99,235,0.08); color: #2563eb; }

    /* ── Stat cards ── */
    .stat-big { border-radius: 20px; padding: 20px 16px; position: relative; overflow: hidden; }
    .stat-big .label { font-size: .6rem; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #94a3b8; margin-bottom: 8px; }
    .stat-big .value { font-size: 2.6rem; font-weight: 900; line-height: 1; letter-spacing: -.03em; }
    .stat-big .sub { font-size: .7rem; color: #94a3b8; margin-top: 5px; font-weight: 500; }
    .stat-big::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; border-radius: 20px 20px 0 0; }
    .stat-blue::before   { background: linear-gradient(90deg, #2563eb, #60a5fa); }
    .stat-green::before  { background: linear-gradient(90deg, #10b981, #34d399); }
    .stat-amber::before  { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
    .stat-red::before    { background: linear-gradient(90deg, #ef4444, #f87171); }
    .stat-purple::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
    .stat-slate::before  { background: linear-gradient(90deg, #475569, #94a3b8); }

    .no-scrollbar::-webkit-scrollbar { display: none; }

    /* ── Desktop: wider pill nav (same position, just wider) ── */
    @media (min-width: 768px) {
        .nav-bottom { max-width: 700px; }
        #app-container > div { max-width: 920px; margin: 0 auto; }
        .stats-grid { grid-template-columns: repeat(3, 1fr) !important; }
    }
</style>
</head>
<body>
<div class="bg-main">
<div id="app-container">
<div id="content-pad" style="padding-bottom: calc(66px + var(--safe-bottom) + 32px)">

    <!-- ── Shared header (mobile only — desktop uses sidebar) ─────── -->
    <header class="main-header px-6 pt-10 flex justify-between items-center">
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
    </header>

    <!-- ════════════════════════════════════════════════════════════
         SECTION: RIDES
    ═══════════════════════════════════════════════════════════════ -->
    <div id="section-rides" class="page-section active">

        <!-- Tabs + Search -->
        <section class="mt-6">
            <div class="overflow-x-auto no-scrollbar mb-3">
                <div class="partner-tabs" id="partner-tabs" style="width:max-content;padding:0 24px">
                    <button class="partner-tab glass-tab active" data-status="today">Today</button>
                    <button class="partner-tab glass-tab" data-status="pendente">Pending</button>
                    <button class="partner-tab glass-tab" data-status="aprovado">Confirmed</button>
                    <button class="partner-tab glass-tab" data-status="rejeitado">Rejected</button>
                </div>
            </div>
            <div class="px-6">
                <div id="searchWrap" class="search-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="text" id="customSearch" placeholder="Search bookings…">
                </div>
            </div>
        </section>

        <!-- Rides list -->
        <section class="px-6 mt-3">
            <div id="rideList"></div>
        </section>

    </div><!-- /section-rides -->

    <!-- ════════════════════════════════════════════════════════════
         SECTION: STATS
    ═══════════════════════════════════════════════════════════════ -->
    <div id="section-stats" class="page-section">
        <section class="px-6 mt-6">
            <h2 class="text-xl font-black mb-4">Statistics</h2>

            <!-- KPI grid — 2-col mobile, 3-col desktop -->
            <div class="grid grid-cols-2 gap-3 stats-grid">

                <div class="glass stat-big stat-blue">
                    <div class="label">Today</div>
                    <div class="value text-blue-500"><?= (int) $counts['today'] ?></div>
                    <div class="sub">rides scheduled</div>
                </div>

                <div class="glass stat-big stat-slate">
                    <div class="label">This Month</div>
                    <div class="value"><?= (int) $counts['this_month'] ?></div>
                    <div class="sub">rides</div>
                </div>

                <div class="glass stat-big stat-purple">
                    <div class="label">All Time</div>
                    <div class="value"><?= (int) $counts['total'] ?></div>
                    <div class="sub">total rides</div>
                </div>

                <div class="glass stat-big stat-green">
                    <div class="label">Confirmed</div>
                    <div class="value text-emerald-500"><?= (int) $counts['approved'] ?></div>
                    <div class="sub">approved</div>
                </div>

                <div class="glass stat-big stat-amber">
                    <div class="label">Pending</div>
                    <div class="value text-amber-500"><?= (int) $counts['pending'] ?></div>
                    <div class="sub">awaiting approval</div>
                </div>

                <div class="glass stat-big stat-red">
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

    <!-- ════════════════════════════════════════════════════════════
         SECTION: SETTINGS
    ═══════════════════════════════════════════════════════════════ -->
    <div id="section-settings" class="page-section">
        <section class="px-6 mt-6">

            <!-- Profile card -->
            <div class="glass rounded-2xl p-5 flex items-center gap-4 mb-5">
                <img src="<?= View::e($userPhoto) ?>"
                     onerror="this.onerror=null;this.src='<?= $avatarFallback ?>'"
                     class="w-14 h-14 rounded-full object-cover flex-shrink-0"
                     style="border:2px solid rgba(37,99,235,0.25);" alt="">
                <div class="min-w-0">
                    <p class="font-extrabold text-base leading-tight truncate"><?= View::e($firstName) ?></p>
                    <span class="inline-block mt-1 text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full"
                          style="background:rgba(37,99,235,.1);color:#2563eb;border:1px solid rgba(37,99,235,.2)">Partner</span>
                </div>
            </div>

            <!-- Preferences -->
            <p class="text-[9px] font-black uppercase tracking-widest text-zinc-400 mb-2 px-1">Preferences</p>
            <div class="glass rounded-2xl overflow-hidden mb-4">
                <!-- Theme toggle -->
                <div class="settings-row" onclick="toggleTheme()">
                    <div class="settings-row-icon" style="background:rgba(245,158,11,.12)">
                        <i data-lucide="sun" style="width:17px;height:17px;color:#d97706" id="sett-sun"></i>
                        <i data-lucide="moon" style="width:17px;height:17px;color:#d97706;display:none" id="sett-moon"></i>
                    </div>
                    <div class="flex-1">
                        <div class="settings-row-label">Appearance</div>
                        <div class="settings-row-sub" id="sett-theme-label">Light mode</div>
                    </div>
                    <i data-lucide="chevron-right" class="settings-row-chevron" style="width:16px;height:16px;"></i>
                </div>
            </div>

            <!-- Account -->
            <p class="text-[9px] font-black uppercase tracking-widest text-zinc-400 mb-2 px-1">Account</p>
            <div class="glass rounded-2xl overflow-hidden mb-4">
                <div class="settings-row" onclick="openChangePassword()">
                    <div class="settings-row-icon" style="background:rgba(37,99,235,.10)">
                        <i data-lucide="key-round" style="width:17px;height:17px;color:#2563eb"></i>
                    </div>
                    <div class="flex-1">
                        <div class="settings-row-label">Change Password</div>
                        <div class="settings-row-sub">Update your login credentials</div>
                    </div>
                    <i data-lucide="chevron-right" class="settings-row-chevron" style="width:16px;height:16px;"></i>
                </div>
            </div>

            <!-- Danger -->
            <div class="glass rounded-2xl overflow-hidden">
                <div class="settings-row" onclick="confirmLogout()">
                    <div class="settings-row-icon" style="background:rgba(220,38,38,.10)">
                        <i data-lucide="log-out" style="width:17px;height:17px;color:#dc2626"></i>
                    </div>
                    <div class="flex-1">
                        <div class="settings-row-label" style="color:#dc2626">Sign Out</div>
                        <div class="settings-row-sub">You'll need to log in again</div>
                    </div>
                </div>
            </div>

            <p class="text-center text-[9px] text-zinc-400 font-bold mt-6">SyncRide OS · Partner Portal</p>
        </section>
    </div><!-- /section-settings -->

</div>
</div>
</div>

<!-- ── Nav ── -->
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
    <button id="nav-stats" onclick="showSection('stats')">
        <i data-lucide="bar-chart-2"></i>Stats
    </button>
    <button id="nav-settings" onclick="showSection('settings')">
        <i data-lucide="settings"></i>Settings
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
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2 px-4">
                <h5 class="modal-title" style="font-size:.95rem;font-weight:800">New Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <form id="formNewBooking">
                    <div class="row g-2">
                        <div class="col-6"><label class="bk-lbl">Date</label><input type="date" name="date" class="form-control form-control-sm" required></div>
                        <div class="col-6"><label class="bk-lbl">Time</label><input type="time" name="time" class="form-control form-control-sm" required></div>
                        <div class="col-12"><label class="bk-lbl">Passenger</label><input type="text" name="client_name" class="form-control form-control-sm" required></div>
                        <div class="col-7"><label class="bk-lbl">Phone</label><input type="text" name="client_phone" class="form-control form-control-sm"></div>
                        <div class="col-5"><label class="bk-lbl">Price (€)</label><input type="number" step="0.01" name="price" class="form-control form-control-sm"></div>
                        <div class="col-4"><label class="bk-lbl">Adults</label><input type="number" name="pax_adt" value="1" min="0" class="form-control form-control-sm text-center" required></div>
                        <div class="col-4"><label class="bk-lbl">Children</label><input type="number" name="pax_chd" value="0" min="0" class="form-control form-control-sm text-center"></div>
                        <div class="col-4"><label class="bk-lbl">Babies</label><input type="number" name="pax_bby" value="0" min="0" class="form-control form-control-sm text-center"></div>
                        <div class="col-12"><label class="bk-lbl">Pickup</label><input type="text" name="pickup" class="form-control form-control-sm" required></div>
                        <div class="col-12"><label class="bk-lbl">Destination</label><input type="text" name="dropoff" class="form-control form-control-sm" required></div>
                        <div class="col-6"><label class="bk-lbl">Flight</label><input type="text" name="flight" class="form-control form-control-sm"></div>
                        <div class="col-6"><label class="bk-lbl">Has Key?</label><select name="has_key" class="form-select form-select-sm"><option value="0" selected>No</option><option value="1">Yes</option></select></div>
                        <div class="col-12"><label class="bk-lbl">Observations</label><textarea name="admin_note" class="form-control form-control-sm" rows="2" placeholder="Any notes for the office…"></textarea></div>
                        <div class="col-12 mt-1"><button type="submit" class="btn btn-primary w-100 fw-bold">Submit Booking</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── Change Password modal ──────────────────────────────────────── -->
<div class="modal fade" id="modalChangePassword" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formChangePassword">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required autocomplete="new-password" minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required autocomplete="new-password">
                    </div>
                    <div id="changePassError" class="text-danger small mb-2" style="display:none"></div>
                    <button type="submit" class="btn btn-primary w-100 py-3 font-bold">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── Edit Booking modal ─────────────────────────────────────────── -->
<div class="modal fade" id="modalEditBooking" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2 px-4">
                <h5 class="modal-title" style="font-size:.95rem;font-weight:800">Edit Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <form id="formEditBooking">
                    <input type="hidden" name="ride_id" id="edit_ride_id">
                    <div class="row g-2">
                        <div class="col-6"><label class="bk-lbl">Date</label><input type="date" name="date" id="edit_date" class="form-control form-control-sm" required></div>
                        <div class="col-6"><label class="bk-lbl">Time</label><input type="time" name="time" id="edit_time" class="form-control form-control-sm" required></div>
                        <div class="col-12"><label class="bk-lbl">Passenger</label><input type="text" name="client_name" id="edit_client_name" class="form-control form-control-sm" required></div>
                        <div class="col-6"><label class="bk-lbl">Phone</label><input type="text" name="client_phone" id="edit_client_phone" class="form-control form-control-sm"></div>
                        <div class="col-6"><label class="bk-lbl">Flight</label><input type="text" name="flight" id="edit_flight" class="form-control form-control-sm"></div>
                        <div class="col-4"><label class="bk-lbl">Adults</label><input type="number" name="pax_adt" id="edit_pax_adt" value="1" min="0" class="form-control form-control-sm text-center" required></div>
                        <div class="col-4"><label class="bk-lbl">Children</label><input type="number" name="pax_chd" id="edit_pax_chd" value="0" min="0" class="form-control form-control-sm text-center"></div>
                        <div class="col-4"><label class="bk-lbl">Babies</label><input type="number" name="pax_bby" id="edit_pax_bby" value="0" min="0" class="form-control form-control-sm text-center"></div>
                        <div class="col-12"><label class="bk-lbl">Pickup</label><input type="text" name="pickup" id="edit_pickup" class="form-control form-control-sm" required></div>
                        <div class="col-12"><label class="bk-lbl">Destination</label><input type="text" name="dropoff" id="edit_dropoff" class="form-control form-control-sm" required></div>
                        <div class="col-12"><label class="bk-lbl">Observations</label><textarea name="admin_note" id="edit_notes" class="form-control form-control-sm" rows="2" placeholder="Any notes for the office…"></textarea></div>
                        <div class="col-12 mt-1"><button type="submit" class="btn btn-primary w-100 fw-bold">Save Changes</button></div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
lucide.createIcons();

/* ── Theme ────────────────────────────────────────────────────────── */
function applyTheme(t, save) {
    document.documentElement.dataset.theme = t;
    const mc = document.getElementById('themeColor');
    if (mc) mc.content = t === 'dark' ? '#020617' : '#f1f5f9';
    // settings panel icons
    const sun  = document.getElementById('sett-sun');
    const moon = document.getElementById('sett-moon');
    const lbl  = document.getElementById('sett-theme-label');
    if (sun)  sun.style.display  = t === 'dark' ? 'none'  : '';
    if (moon) moon.style.display = t === 'dark' ? ''      : 'none';
    if (lbl)  lbl.textContent    = t === 'dark' ? 'Dark mode' : 'Light mode';
    if (save) localStorage.setItem('sr-theme', t);
}
function toggleTheme() {
    const cur = document.documentElement.dataset.theme || 'light';
    applyTheme(cur === 'dark' ? 'light' : 'dark', true);
}
(function () { applyTheme(localStorage.getItem('sr-theme') || 'light', false); })();

/* ── Section switching ────────────────────────────────────────────── */
const NAV_MAP = { rides: 'nav-rides', noshows: 'nav-noshows', stats: 'nav-stats', settings: 'nav-settings' };

function showSection(id) {
    document.getElementById('app-container').scrollTo({ top: 0, behavior: 'smooth' });
    document.querySelectorAll('.page-section').forEach(s => s.classList.remove('active'));
    const sec = document.getElementById('section-' + id);
    if (sec) sec.classList.add('active');
    document.querySelectorAll('.nav-bottom button, .nav-bottom a').forEach(b => b.classList.remove('sr-nav-active'));
    const navBtn = document.getElementById(NAV_MAP[id]);
    if (navBtn) navBtn.classList.add('sr-nav-active');
    if (id === 'rides') lucide.createIcons();
}

function confirmLogout() { new bootstrap.Modal(document.getElementById('modalConfirmLogout')).show(); }

function openChangePassword() {
    document.getElementById('formChangePassword').reset();
    document.getElementById('changePassError').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalChangePassword')).show();
}

document.getElementById('formChangePassword').addEventListener('submit', function (e) {
    e.preventDefault();
    const errEl = document.getElementById('changePassError');
    errEl.style.display = 'none';
    const btn  = this.querySelector('button[type="submit"]');
    const orig = btn.textContent;
    btn.disabled = true; btn.textContent = 'Saving…';
    fetch('/SRMT/public/change-password.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalChangePassword')).hide();
                showToast('Password updated!', 'success');
                this.reset();
            } else {
                errEl.textContent = res.error || 'Error updating password.';
                errEl.style.display = 'block';
            }
        })
        .catch(() => { errEl.textContent = 'Network error.'; errEl.style.display = 'block'; })
        .finally(() => { btn.disabled = false; btn.textContent = orig; });
});

/* ── Ride card renderer ───────────────────────────────────────────── */
let currentStatus = 'today';
let currentSearch = '';
let _allRows = [];

const STATUS_CLASS  = { pendente: 's-pending', aprovado: 's-confirmed', rejeitado: 's-rejected' };
const STATUS_LABEL  = { pendente: 'Pending',   aprovado: 'Confirmed',   rejeitado: 'Rejected'   };
const STATUS_BADGE  = {
    pendente:  'background:rgba(245,158,11,.12);color:#d97706;border:1px solid rgba(245,158,11,.22)',
    aprovado:  'background:rgba(16,185,129,.12);color:#059669;border:1px solid rgba(16,185,129,.22)',
    rejeitado: 'background:rgba(239,68,68,.10);color:#dc2626;border:1px solid rgba(239,68,68,.22)',
};

function h(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function renderCard(r) {
    const sc   = STATUS_CLASS[r.raw_status] || '';
    const sb   = STATUS_BADGE[r.raw_status] || 'background:rgba(148,163,184,.12);color:#64748b;border:1px solid rgba(148,163,184,.2)';
    const sl   = STATUS_LABEL[r.raw_status] || h(r.raw_status);
    const pax  = (parseInt(r.raw_pax_adt)||0) + (parseInt(r.raw_pax_chd)||0);
    const date = r.raw_date ? new Date(r.raw_date + 'T12:00:00').toLocaleDateString('en-GB',{day:'2-digit',month:'short'}) : '';

    const chips = [
        `<span class="pcard-chip" style="color:#64748b">${date}</span>`,
        `<span class="pcard-chip"><i class="bi bi-people-fill"></i> ${pax}</span>`,
        r.raw_flight ? `<span class="pcard-chip"><i class="bi bi-airplane-fill"></i> ${h(r.raw_flight)}</span>` : '',
        parseInt(r.has_key) ? `<span class="pcard-chip"><i class="bi bi-key-fill" style="color:#f59e0b"></i> Key</span>` : '',
    ].join('');

    const canEdit = (parseInt(r.raw_status_id)||0) < 3 && (r.raw_status === 'pendente' || r.raw_status === 'aprovado');
    const editBtn = canEdit
        ? `<button class="btn-act btn-edit-ride"
               data-id="${r.raw_id}"
               data-date="${h(r.raw_date)}"
               data-time="${h(r.raw_time)}"
               data-client="${h(r.raw_client)}"
               data-phone="${h(r.raw_phone)}"
               data-adt="${r.raw_pax_adt}"
               data-chd="${r.raw_pax_chd}"
               data-bby="${r.raw_pax_bby}"
               data-pickup="${h(r.raw_pickup)}"
               data-dropoff="${h(r.raw_dropoff)}"
               data-flight="${h(r.raw_flight)}"
               data-notes="${h(r.raw_notes)}"
               title="Edit"><i class="bi bi-pencil-fill" style="font-size:.7rem"></i></button>`
        : '';

    return `<div class="pcard ${sc}">
        <div class="pcard-top">
            <div>
                <div class="pcard-time">${h(r.raw_time)}</div>
                <div class="pcard-client">${h(r.raw_client)}</div>
            </div>
            <div class="pcard-right">
                <span class="pcard-status" style="${sb}">${sl}</span>
                ${editBtn}
            </div>
        </div>
        <div class="pcard-meta">${chips}</div>
        <div class="pcard-route">
            <div class="pcard-point"><span class="rdot rdot-g"></span><span>${h(r.raw_pickup)}</span></div>
            <div class="rdot-line"></div>
            <div class="pcard-point"><span class="rdot rdot-r"></span><span>${h(r.raw_dropoff)}</span></div>
        </div>
    </div>`;
}

function filterAndRender(rows) {
    if (rows !== undefined) _allRows = rows;
    const q = currentSearch.toLowerCase();
    const filtered = q
        ? _allRows.filter(r =>
            (r.raw_client||'').toLowerCase().includes(q)  ||
            (r.raw_pickup||'').toLowerCase().includes(q)  ||
            (r.raw_dropoff||'').toLowerCase().includes(q) ||
            (r.raw_flight||'').toLowerCase().includes(q))
        : _allRows;
    const list = document.getElementById('rideList');
    list.innerHTML = filtered.length
        ? filtered.map(renderCard).join('')
        : '<div class="glass rounded-2xl p-6 text-center text-zinc-400 text-sm">No results</div>';
}

function loadRides(status) {
    const list = document.getElementById('rideList');
    list.innerHTML = '<div class="glass rounded-2xl p-6 text-center text-zinc-400 text-sm">Loading…</div>';
    const apiStatus = status === 'today' ? 'aprovado' : status;
    fetch('/SRMT/public/partner/api-rides.php?status=' + apiStatus)
        .then(r => r.json())
        .then(res => {
            let rows = res.data || [];
            if (status === 'today') {
                const today = new Date().toISOString().split('T')[0];
                rows = rows.filter(r => r.raw_date === today);
            }
            if (!rows.length) {
                list.innerHTML = '<div class="glass rounded-2xl p-8 text-center"><div style="font-size:2rem;margin-bottom:8px">📅</div><p class="font-bold text-sm">No bookings</p><p style="font-size:.7rem;color:#94a3b8;margin-top:4px">Nothing here yet.</p></div>';
                _allRows = [];
                return;
            }
            filterAndRender(rows);
        })
        .catch(() => {
            list.innerHTML = '<div class="glass rounded-2xl p-6 text-center" style="color:#ef4444;font-size:.85rem">Failed to load</div>';
        });
}

/* ── Tab switching ────────────────────────────────────────────────── */
document.querySelectorAll('#partner-tabs .partner-tab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('#partner-tabs .partner-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentStatus = btn.dataset.status || 'today';
        currentSearch = '';
        document.getElementById('customSearch').value = '';
        loadRides(currentStatus);
    });
});

document.getElementById('customSearch').addEventListener('input', function () {
    currentSearch = this.value;
    filterAndRender();
});

/* ── Edit button (event delegation) ──────────────────────────────── */
document.getElementById('rideList').addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-edit-ride');
    if (!btn) return;
    const d = btn.dataset;
    const m = document.getElementById('modalEditBooking');
    m.querySelector('#edit_ride_id').value      = d.id;
    m.querySelector('#edit_date').value         = d.date;
    m.querySelector('#edit_time').value         = d.time;
    m.querySelector('#edit_client_name').value  = d.client;
    m.querySelector('#edit_client_phone').value = d.phone;
    m.querySelector('#edit_pax_adt').value      = d.adt;
    m.querySelector('#edit_pax_chd').value      = d.chd;
    m.querySelector('#edit_pax_bby').value      = d.bby;
    m.querySelector('#edit_pickup').value       = d.pickup;
    m.querySelector('#edit_dropoff').value      = d.dropoff;
    m.querySelector('#edit_flight').value       = d.flight;
    m.querySelector('#edit_notes').value        = d.notes || '';
    new bootstrap.Modal(m).show();
});

/* ── Forms ────────────────────────────────────────────────────────── */
document.getElementById('formEditBooking').addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const orig = btn.textContent;
    btn.disabled = true; btn.textContent = 'Saving…';
    fetch('/SRMT/public/partner/api-update-ride.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalEditBooking')).hide();
                loadRides(currentStatus);
                showToast('Booking updated!', 'success');
            } else { showToast(res.error || 'Update not allowed', 'error'); }
        })
        .catch(() => showToast('Network error', 'error'))
        .finally(() => { btn.disabled = false; btn.textContent = orig; });
});

document.getElementById('formNewBooking').addEventListener('submit', function (e) {
    e.preventDefault();
    const btn = this.querySelector('button[type="submit"]');
    const orig = btn.textContent;
    btn.disabled = true; btn.textContent = 'Sending…';
    fetch('/SRMT/public/partner/api-create-ride.php', { method: 'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                bootstrap.Modal.getInstance(document.getElementById('modalNewBooking')).hide();
                document.getElementById('formNewBooking').reset();
                loadRides(currentStatus);
                showToast('Booking submitted!', 'success');
            } else { showToast(res.error || 'Error', 'error'); }
        })
        .catch(() => showToast('Network error', 'error'))
        .finally(() => { btn.disabled = false; btn.textContent = orig; });
});

function showToast(msg, type) {
    const el = document.createElement('div');
    el.textContent = msg;
    const bg = type === 'success' ? '#10b981' : '#ef4444';
    Object.assign(el.style, {
        position:'fixed', bottom:'90px', left:'50%', transform:'translateX(-50%) translateY(0)',
        background: bg, color:'#fff', padding:'10px 22px', borderRadius:'50px',
        fontWeight:'700', fontSize:'.82rem', zIndex:'9999',
        boxShadow:'0 4px 20px rgba(0,0,0,0.2)', opacity:'0', transition:'opacity .2s', whiteSpace:'nowrap'
    });
    document.body.appendChild(el);
    requestAnimationFrame(() => el.style.opacity = '1');
    setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 220); }, 2500);
}

/* ── Init ─────────────────────────────────────────────────────────── */
loadRides('today');
</script>
</body>
</html>
