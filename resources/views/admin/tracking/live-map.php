<?php
use App\Http\View;
/** @var string $userPhoto */
$safePhoto      = View::e($userPhoto);
$userName       = isset($_SESSION['name']) ? explode(' ', (string) $_SESSION['name'])[0] : 'Admin';
$initial        = mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'));
$svgAvatar      = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><circle cx="20" cy="20" r="20" fill="#2563eb"/><text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="white" font-size="17" font-weight="bold" font-family="system-ui">' . htmlspecialchars($initial) . '</text></svg>';
$avatarFallback = 'data:image/svg+xml;base64,' . base64_encode($svgAvatar);
?><!DOCTYPE html>
<html lang="en"><script>(function(){var t=localStorage.getItem('sr-theme')||'light';document.documentElement.dataset.theme=t;var m=document.querySelector('meta[name="theme-color"]');if(m)m.content=t==='dark'?'#000000':'#f1f5f9';})()</script>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#000000">
    <title>Live Radar — SyncRide OS</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css">
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root { --safe-bottom: env(safe-area-inset-bottom, 20px); }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: #000; margin: 0; overflow: hidden; height: 100vh; color: #fff; }

        #adminMap { position: absolute; top: 0; bottom: 0; left: 0; right: 0; z-index: 1; background: #0b1220; }

        /* Glass — adapts to theme but map overlays are always on a dark bg */
        .glass {
            background: rgba(255,255,255,0.90);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(0,0,0,0.08);
        }
        [data-theme="dark"] .glass {
            background: rgba(20,20,20,0.88);
            border: 1px solid rgba(255,255,255,0.10);
        }

        .map-header {
            position: absolute; top: 30px; left: 15px; right: 15px;
            z-index: 1000; height: 64px; display: flex; align-items: center;
            padding: 0 16px; justify-content: space-between; border-radius: 24px;
        }
        /* Header text */
        .map-header h2 { color: #0f172a; }
        [data-theme="dark"] .map-header h2 { color: #fff; }
        .map-header .icon-btn {
            width: 40px; height: 40px; border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            background: rgba(0,0,0,0.07); border: 1px solid rgba(0,0,0,0.1);
            color: #475569; transition: all .2s;
        }
        [data-theme="dark"] .map-header .icon-btn { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.15); color: #fff; }

        .radar-pill {
            position: absolute; top: 110px; left: 50%; transform: translateX(-50%);
            z-index: 1000; padding: 8px 16px; border-radius: 99px;
            display: flex; align-items: center; gap: 10px;
            font-size: 10px; font-weight: 800; letter-spacing: 0.1em; color: white;
            background: rgba(20,20,20,0.80); backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .pulse-dot {
            width: 8px; height: 8px; background: #3b82f6; border-radius: 50%;
            box-shadow: 0 0 12px #3b82f6; animation: radar-pulse 2s infinite;
        }
        @keyframes radar-pulse {
            0%   { opacity: 1; transform: scale(1);   }
            50%  { opacity: 0.4; transform: scale(1.2); }
            100% { opacity: 1; transform: scale(1);   }
        }

        /* Driver sheet */
        .driver-sheet {
            position: fixed; bottom: calc(86px + var(--safe-bottom)); left: 12px; right: 12px;
            z-index: 2000; padding: 16px 18px 14px; border-radius: 26px; transform: translateY(200%);
            transition: transform 0.45s cubic-bezier(0.19, 1, 0.22, 1);
            background: rgba(255,255,255,0.97); backdrop-filter: blur(30px);
            border: 1px solid rgba(0,0,0,0.09); color: #0f172a;
            box-shadow: 0 16px 48px rgba(0,0,0,0.14), 0 4px 12px rgba(0,0,0,0.06);
        }
        [data-theme="dark"] .driver-sheet {
            background: rgba(15,17,25,0.97);
            border-color: rgba(255,255,255,0.12); color: #f1f5f9;
            box-shadow: 0 16px 48px rgba(0,0,0,0.6);
        }
        .driver-sheet.active { transform: translateY(0); }
        .driver-sheet .drag-handle { background: #cbd5e1; }
        [data-theme="dark"] .driver-sheet .drag-handle { background: #334155; }
        .driver-sheet .ds-name { font-size: 15px; font-weight: 800; color: #0f172a; line-height: 1.2; }
        [data-theme="dark"] .driver-sheet .ds-name { color: #f1f5f9; }
        .driver-sheet .ds-company { font-size: 9px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; color: #2563eb; }
        .driver-sheet .ds-speed-box {
            background: rgba(37,99,235,0.08); border: 1px solid rgba(37,99,235,0.15);
            border-radius: 14px; padding: 8px 12px; text-align: center; min-width: 72px;
        }
        [data-theme="dark"] .driver-sheet .ds-speed-box { background: rgba(59,130,246,0.12); border-color: rgba(59,130,246,0.2); }
        .driver-sheet .ds-speed-val { font-size: 20px; font-weight: 900; color: #2563eb; line-height: 1; }
        [data-theme="dark"] .driver-sheet .ds-speed-val { color: #60a5fa; }
        .driver-sheet .ds-speed-lbl { font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; margin-top: 2px; }
        .driver-sheet .ds-route {
            display: flex; align-items: center; gap: 8px; margin-top: 10px;
            background: rgba(0,0,0,0.04); border-radius: 14px; padding: 10px 12px;
        }
        [data-theme="dark"] .driver-sheet .ds-route { background: rgba(255,255,255,0.05); }
        .driver-sheet .ds-route-text { font-size: 11px; font-weight: 600; color: #475569; flex: 1; min-width: 0; }
        [data-theme="dark"] .driver-sheet .ds-route-text { color: #94a3b8; }
        .driver-sheet .ds-route-text strong { color: #0f172a; display: block; font-size: 11px; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        [data-theme="dark"] .driver-sheet .ds-route-text strong { color: #e2e8f0; }
        .driver-sheet .ds-ping { font-size: 8px; font-weight: 700; color: #94a3b8; margin-top: 6px; text-align: right; }
        .driver-sheet .ds-close {
            position: absolute; top: 14px; right: 14px;
            width: 28px; height: 28px; border-radius: 999px;
            background: rgba(0,0,0,0.06); border: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center; color: #64748b;
            transition: background .15s;
        }
        [data-theme="dark"] .driver-sheet .ds-close { background: rgba(255,255,255,0.08); color: #94a3b8; }
        .driver-sheet .ds-close:hover { background: rgba(0,0,0,0.12); }
        [data-theme="dark"] .driver-sheet .ds-close:hover { background: rgba(255,255,255,0.14); }

        .status-pill {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em;
            padding: 4px 10px; border-radius: 999px; margin-top: 6px;
            border: 1px solid rgba(0,0,0,0.08);
        }
        [data-theme="dark"] .status-pill { border-color: rgba(255,255,255,0.1); }
        .status-pill::before { content: ""; width: 6px; height: 6px; border-radius: 999px; }
        .status-pill.s-pending   { color: #64748b; background: rgba(0,0,0,0.05); }
        [data-theme="dark"] .status-pill.s-pending { color: #d4d4d8; background: rgba(255,255,255,0.04); }
        .status-pill.s-pending::before  { background: #94a3b8; }
        .status-pill.s-onway    { color: #2563eb; background: rgba(37,99,235,0.10); border-color: rgba(37,99,235,0.25); }
        [data-theme="dark"] .status-pill.s-onway { color: #60a5fa; background: rgba(59,130,246,0.10); border-color: rgba(59,130,246,0.25); }
        .status-pill.s-onway::before    { background: #3b82f6; box-shadow: 0 0 8px #3b82f6; }
        .status-pill.s-arrived  { color: #d97706; background: rgba(251,191,36,0.10); border-color: rgba(251,191,36,0.25); }
        [data-theme="dark"] .status-pill.s-arrived { color: #fbbf24; }
        .status-pill.s-arrived::before  { background: #fbbf24; box-shadow: 0 0 8px #fbbf24; }
        .status-pill.s-withclient { color: #16a34a; background: rgba(16,185,129,0.10); border-color: rgba(16,185,129,0.25); }
        [data-theme="dark"] .status-pill.s-withclient { color: #34d399; }
        .status-pill.s-withclient::before { background: #34d399; box-shadow: 0 0 8px #34d399; }
        .status-pill.s-intrip   { color: #6366f1; background: rgba(99,102,241,0.10); border-color: rgba(99,102,241,0.30); }
        [data-theme="dark"] .status-pill.s-intrip { color: #a5b4fc; }
        .status-pill.s-intrip::before   { background: #a5b4fc; box-shadow: 0 0 8px #a5b4fc; }

        .fit-btn {
            position: absolute; right: 20px; bottom: calc(180px + var(--safe-bottom));
            z-index: 1000; width: 44px; height: 44px; border-radius: 14px;
            background: rgba(255,255,255,0.90); backdrop-filter: blur(20px);
            border: 1px solid rgba(0,0,0,0.10); color: #0f172a;
            display: flex; align-items: center; justify-content: center; transition: all .2s;
        }
        .fit-btn:hover { background: rgba(255,255,255,1); }
        .fit-btn:active { transform: scale(0.92); }
        [data-theme="dark"] .fit-btn { background: rgba(20,20,20,0.92); border-color: rgba(255,255,255,0.12); color: #fff; }
        [data-theme="dark"] .fit-btn:hover { background: rgba(40,40,40,0.95); }

        /* ── Shared nav pill base ───────────────────────────── */
        .nav-pill-base {
            position: fixed; bottom: 0; left: 50%; transform: translateX(-50%);
            height: 66px; margin-bottom: calc(10px + var(--safe-bottom));
            background: rgba(255,255,255,0.90);
            backdrop-filter: blur(24px); -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(0,0,0,0.07); border-radius: 26px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.10), 0 2px 8px rgba(0,0,0,0.06);
            display: flex; align-items: stretch; z-index: 3000; overflow: hidden;
        }
        [data-theme="dark"] .nav-pill-base {
            background: rgba(10,14,30,0.95); border-color: rgba(255,255,255,0.09);
            box-shadow: 0 8px 32px rgba(0,0,0,0.5), 0 2px 8px rgba(0,0,0,0.3);
        }
        /* Mobile nav */
        .nav-mobile { width: calc(100% - 24px); max-width: 480px; }
        .nav-mobile a, .nav-mobile button {
            flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 3px; font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
            color: #94a3b8; background: none; border: none; cursor: pointer;
            text-decoration: none; transition: color .15s; padding: 0;
        }
        .nav-mobile a i, .nav-mobile button i { width: 20px; height: 20px; display: block; }
        .nav-mobile a:hover, .nav-mobile button:hover { color: #64748b; }
        [data-theme="dark"] .nav-mobile a, [data-theme="dark"] .nav-mobile button { color: #475569; }
        [data-theme="dark"] .nav-mobile a:hover, [data-theme="dark"] .nav-mobile button:hover { color: #94a3b8; }
        .nav-mobile a.sr-nav-active { color: #2563eb; }
        [data-theme="dark"] .nav-mobile a.sr-nav-active { color: #60a5fa; }
        /* Desktop nav */
        .nav-desktop {
            display: none; width: calc(100% - 40px); max-width: 1400px;
            overflow-x: auto; overflow-y: hidden; scrollbar-width: none;
        }
        .nav-desktop::-webkit-scrollbar { display: none; }
        .nav-desktop-inner { display: flex; align-items: stretch; height: 100%; min-width: max-content; padding: 0 8px; }
        .nav-desktop a, .nav-desktop button {
            display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px;
            min-width: 72px; padding: 0 10px;
            font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
            color: #94a3b8; background: none; border: none; cursor: pointer;
            text-decoration: none; transition: color .15s; white-space: nowrap;
        }
        .nav-desktop a i, .nav-desktop button i { width: 20px; height: 20px; display: block; }
        .nav-desktop a:hover, .nav-desktop button:hover { color: #64748b; }
        [data-theme="dark"] .nav-desktop a, [data-theme="dark"] .nav-desktop button { color: #475569; }
        [data-theme="dark"] .nav-desktop a:hover, [data-theme="dark"] .nav-desktop button:hover { color: #94a3b8; }
        .nav-desktop a.sr-nav-active { color: #2563eb; }
        [data-theme="dark"] .nav-desktop a.sr-nav-active { color: #60a5fa; }
        .nav-desktop-sep { width: 1px; flex-shrink: 0; background: rgba(0,0,0,0.07); margin: 14px 6px; }
        [data-theme="dark"] .nav-desktop-sep { background: rgba(255,255,255,0.08); }
        .nav-desktop .nav-danger { color: #ef4444 !important; }
        [data-theme="dark"] .nav-desktop .nav-danger { color: #f87171 !important; }
        /* Responsive swap */
        @media (min-width: 768px) {
            .nav-mobile  { display: none !important; }
            .nav-desktop { display: flex; }
            #fullMenu    { display: none !important; }
        }
        /* ── More overlay — settings-style ─────────────────── */
        .more-overlay {
            position: fixed; inset: 0; z-index: 2000;
            overflow-y: auto; -webkit-overflow-scrolling: touch;
            background: #f1f5f9;
        }
        [data-theme="dark"] .more-overlay { background: #020617; }
        .more-sticky-hdr {
            position: sticky; top: 0; z-index: 10;
            display: flex; justify-content: space-between; align-items: center;
            padding: calc(env(safe-area-inset-top, 0px) + 40px) 20px 12px;
            background: rgba(241,245,249,0.95);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        [data-theme="dark"] .more-sticky-hdr { background: rgba(2,6,23,0.95); border-bottom-color: rgba(255,255,255,0.05); }
        .more-profile {
            margin: 14px 16px 0; padding: 14px 16px; border-radius: 18px;
            display: flex; align-items: center; gap: 13px;
            background: rgba(255,255,255,0.75); border: 1px solid rgba(0,0,0,0.07);
        }
        [data-theme="dark"] .more-profile { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.09); }
        .more-sec-label {
            font-size: 10px; font-weight: 800; text-transform: uppercase;
            letter-spacing: .1em; color: #94a3b8; padding: 20px 20px 6px;
        }
        .more-card { margin: 0 16px; border-radius: 18px; overflow: hidden; background: rgba(255,255,255,0.75); border: 1px solid rgba(0,0,0,0.07); }
        [data-theme="dark"] .more-card { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.09); }
        .more-row {
            display: flex; align-items: center; gap: 13px; padding: 13px 14px;
            text-decoration: none; color: #0f172a;
            -webkit-tap-highlight-color: transparent; transition: background .1s;
        }
        [data-theme="dark"] .more-row { color: #f1f5f9; }
        .more-row:active { background: rgba(0,0,0,0.04); }
        .more-row-danger { color: #ef4444 !important; }
        .more-icon {
            width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
        }
        .more-divider { height: 1px; background: rgba(0,0,0,0.06); margin: 0 14px; }
        [data-theme="dark"] .more-divider { background: rgba(255,255,255,0.07); }
        .no-scrollbar::-webkit-scrollbar { display: none; }

        /* Map decorations — always dark since map is dark */
        .leaflet-vignette { position: absolute; inset: 0; pointer-events: none; z-index: 500; background: radial-gradient(circle, transparent 30%, black 150%); }
        .leaflet-control-zoom { border: none !important; margin-right: 20px !important; margin-bottom: 110px !important; }
        .leaflet-bar a { background: rgba(20,20,20,0.9) !important; color: white !important; border: 1px solid rgba(255,255,255,0.1) !important; border-radius: 12px !important; }
        .leaflet-routing-container, .leaflet-routing-alt { display: none !important; }
        .car-marker { filter: drop-shadow(0 4px 8px rgba(0,0,0,0.8)); transition: all 1s ease; }

        .dest-pin { position: relative; width: 32px; height: 32px; pointer-events: none; }
        .dest-pin .pin-core {
            position: absolute; inset: 10px; background: #10b981; border-radius: 50%;
            border: 2px solid #050505;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.28), 0 6px 14px rgba(0,0,0,0.55), inset 0 1px 1px rgba(255,255,255,0.35);
        }
        .dest-pin .pin-ring {
            position: absolute; inset: 0; border-radius: 50%;
            border: 1.5px solid rgba(16,185,129,0.55);
            animation: pin-pulse 2.4s ease-out infinite;
        }
        .dest-pin.dropoff .pin-core {
            background: #ef4444;
            box-shadow: 0 0 0 3px rgba(239,68,68,0.28), 0 6px 14px rgba(0,0,0,0.55), inset 0 1px 1px rgba(255,255,255,0.35);
        }
        .dest-pin.dropoff .pin-ring { border-color: rgba(239,68,68,0.55); }
        @keyframes pin-pulse { 0% { transform: scale(0.55); opacity: 1; } 100% { transform: scale(1.7); opacity: 0; } }

        /* Icon btn */
        .icon-btn {
            width: 40px; height: 40px; border-radius: 999px;
            display: inline-flex; align-items: center; justify-content: center;
            background: rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.10);
            color: #475569; transition: all .2s; cursor: pointer;
        }
        .icon-btn:hover { background: rgba(0,0,0,0.12); }
        [data-theme="dark"] .icon-btn { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.12); color: #fff; }
        [data-theme="dark"] .icon-btn:hover { background: rgba(255,255,255,0.14); }
    </style>
</head>
<body>

    <div id="adminMap"></div>
    <div class="leaflet-vignette"></div>

    <header class="map-header glass">
        <div class="flex items-center gap-3">
            <img src="<?= $safePhoto ?>" onerror="this.onerror=null;this.src='<?= $avatarFallback ?>'" class="w-10 h-10 rounded-full border-2 border-blue-500/20 object-cover" alt="">
            <div>
                <h2 class="text-[15px] font-extrabold leading-tight">SyncRide <span class="text-blue-500">Radar</span></h2>
                <p class="text-[8px] text-zinc-500 font-black tracking-widest uppercase italic"><?= t('map.subtitle') ?></p>
            </div>
        </div>
        <button onclick="toggleMenu()" class="glass w-10 h-10 rounded-full flex items-center justify-center active:scale-90 transition-transform border-0">
            <i data-lucide="menu" class="w-4 h-4"></i>
        </button>
    </header>

    <div class="radar-pill">
        <div class="pulse-dot"></div>
        <span id="activeCount" class="font-black">0</span>
        <span class="opacity-70"><?= t('map.active_rides') ?></span>
    </div>

    <button class="fit-btn" onclick="fitAllDrivers()" aria-label="Fit all drivers">
        <i data-lucide="maximize-2" class="w-5 h-5"></i>
    </button>

    <div id="driverSheet" class="driver-sheet">
        <button class="ds-close" onclick="closeSheet()"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
        <div class="drag-handle w-8 h-1 rounded-full mx-auto mb-3 cursor-pointer" onclick="closeSheet()"></div>

        <div class="flex items-center gap-3">
            <div class="flex-1 min-w-0">
                <div class="ds-name" id="sDriver" data-did=""><?= t('map.driver') ?></div>
                <div class="ds-company" id="sVehicle"><?= t('map.vehicle') ?></div>
                <span id="sStatus" class="status-pill s-pending" style="margin-top:5px">—</span>
            </div>
            <div class="ds-speed-box">
                <div class="ds-speed-val" id="sSpeed">0</div>
                <div class="ds-speed-lbl">km/h</div>
            </div>
        </div>

        <div class="ds-route">
            <i data-lucide="navigation" class="w-3.5 h-3.5 text-blue-500 shrink-0"></i>
            <div class="ds-route-text">
                <strong id="sDest">---</strong>
                <span id="sClient">---</span>
            </div>
        </div>
        <div class="ds-ping" id="sLastUpdate"><?= t('map.ping') ?> —</div>
    </div>

    <!-- Mobile nav (5 items) -->
    <nav class="nav-pill-base nav-mobile">
        <a href="/SRMT/public/admin/"><i data-lucide="home"></i><?= t('nav.home') ?></a>
        <a href="/SRMT/public/admin/rides.php"><i data-lucide="calendar"></i><?= t('nav.rides') ?></a>
        <a href="/SRMT/public/admin/schedule-board.php"><i data-lucide="calendar-days"></i><?= t('nav.board') ?></a>
        <a href="/SRMT/public/admin/live-map.php" class="sr-nav-active"><i data-lucide="locate-fixed"></i><?= t('nav.live') ?></a>
        <button onclick="toggleMenu()"><i data-lucide="grid-2x2"></i><?= t('nav.more') ?></button>
    </nav>

    <!-- Desktop nav (all items) -->
    <nav class="nav-pill-base nav-desktop">
        <div class="nav-desktop-inner">
            <a href="/SRMT/public/admin/"><i data-lucide="home"></i><?= t('nav.home') ?></a>
            <a href="/SRMT/public/admin/rides.php"><i data-lucide="calendar"></i><?= t('nav.rides') ?></a>
            <a href="/SRMT/public/admin/schedule-board.php"><i data-lucide="calendar-days"></i><?= t('nav.board') ?></a>
            <a href="/SRMT/public/admin/live-map.php" class="sr-nav-active"><i data-lucide="locate-fixed"></i><?= t('nav.live') ?></a>
            <div class="nav-desktop-sep"></div>
            <a href="/SRMT/public/admin/financial.php"><i data-lucide="wallet"></i><?= t('nav.cash') ?></a>
            <a href="/SRMT/public/admin/import.php"><i data-lucide="file-spreadsheet"></i><?= t('nav.import') ?></a>
            <div class="nav-desktop-sep"></div>
            <a href="/SRMT/public/admin/users.php"><i data-lucide="users"></i><?= t('nav.team') ?></a>
            <a href="/SRMT/public/admin/fleet.php"><i data-lucide="truck"></i><?= t('nav.fleet') ?></a>
            <a href="/SRMT/public/admin/pricing.php"><i data-lucide="tag"></i><?= t('nav.pricing') ?></a>
            <div class="nav-desktop-sep"></div>
            <a href="/SRMT/public/admin/driver-stats.php"><i data-lucide="bar-chart-3"></i><?= t('nav.stats') ?></a>
            <a href="/SRMT/public/admin/no-shows.php"><i data-lucide="alert-triangle"></i><?= t('nav.noshows') ?></a>
            <a href="/SRMT/public/admin/partnerships.php"><i data-lucide="handshake"></i><?= t('nav.partnerships') ?></a>
            <div class="nav-desktop-sep"></div>
            <a href="/SRMT/public/admin/storage.php"><i data-lucide="database"></i><?= t('nav.storage') ?></a>
            <a href="/SRMT/public/admin/settings.php"><i data-lucide="mail"></i><?= t('nav.automations') ?></a>
            <div class="nav-desktop-sep"></div>
            <a href="/SRMT/public/auth/logout.php" class="nav-danger"><i data-lucide="log-out"></i><?= t('nav.logout') ?></a>
        </div>
    </nav>

    <!-- More overlay — iOS settings style (mobile only) -->
    <div id="fullMenu" class="more-overlay hidden">
        <div class="more-sticky-hdr">
            <h1 class="text-2xl font-black"><?= t('nav.more') ?></h1>
            <button onclick="toggleMenu()" class="glass w-10 h-10 rounded-full flex items-center justify-center border-0">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <div class="more-profile">
            <img src="<?= $safePhoto ?>" onerror="this.onerror=null;this.src='<?= $avatarFallback ?>'"
                 class="w-14 h-14 rounded-full border-2 border-blue-500/20 object-cover flex-shrink-0" alt="">
            <div class="flex-1 min-w-0">
                <h3 class="font-bold text-[16px] leading-snug truncate"><?= htmlspecialchars($userName) ?></h3>
                <p class="text-[11px] text-zinc-500 font-semibold uppercase tracking-wider mt-0.5"><?= t('nav.system_admin') ?></p>
            </div>
        </div>
        <p class="more-sec-label"><?= t('nav.sec_operations') ?></p>
        <div class="more-card">
            <a href="/SRMT/public/admin/financial.php" onclick="toggleMenu()" class="more-row">
                <div class="more-icon" style="background:rgba(37,99,235,.12)"><i data-lucide="wallet" class="w-[17px] h-[17px] text-blue-500"></i></div>
                <span class="flex-1 text-[15px] font-semibold"><?= t('nav.financial') ?></span>
                <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
            </a>
        </div>
        <p class="more-sec-label"><?= t('nav.sec_team') ?></p>
        <div class="more-card">
            <a href="/SRMT/public/admin/users.php" onclick="toggleMenu()" class="more-row">
                <div class="more-icon" style="background:rgba(139,92,246,.12)"><i data-lucide="users" class="w-[17px] h-[17px] text-violet-500"></i></div>
                <span class="flex-1 text-[15px] font-semibold"><?= t('nav.team') ?></span>
                <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
            </a>
            <div class="more-divider"></div>
            <a href="/SRMT/public/admin/fleet.php" onclick="toggleMenu()" class="more-row">
                <div class="more-icon" style="background:rgba(249,115,22,.12)"><i data-lucide="truck" class="w-[17px] h-[17px] text-orange-500"></i></div>
                <span class="flex-1 text-[15px] font-semibold"><?= t('nav.fleet') ?></span>
                <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
            </a>
            <div class="more-divider"></div>
            <a href="/SRMT/public/admin/pricing.php" onclick="toggleMenu()" class="more-row">
                <div class="more-icon" style="background:rgba(6,182,212,.12)"><i data-lucide="tag" class="w-[17px] h-[17px] text-cyan-500"></i></div>
                <span class="flex-1 text-[15px] font-semibold"><?= t('nav.pricing') ?></span>
                <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
            </a>
        </div>
        <p class="more-sec-label"><?= t('nav.sec_reports') ?></p>
        <div class="more-card">
            <a href="/SRMT/public/admin/driver-stats.php" onclick="toggleMenu()" class="more-row">
                <div class="more-icon" style="background:rgba(37,99,235,.12)"><i data-lucide="bar-chart-3" class="w-[17px] h-[17px] text-blue-500"></i></div>
                <span class="flex-1 text-[15px] font-semibold"><?= t('nav.stats') ?></span>
                <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
            </a>
            <div class="more-divider"></div>
            <a href="/SRMT/public/admin/no-shows.php" onclick="toggleMenu()" class="more-row">
                <div class="more-icon" style="background:rgba(245,158,11,.12)"><i data-lucide="alert-triangle" class="w-[17px] h-[17px] text-amber-500"></i></div>
                <span class="flex-1 text-[15px] font-semibold"><?= t('nav.noshows') ?></span>
                <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
            </a>
            <div class="more-divider"></div>
            <a href="/SRMT/public/admin/partnerships.php" onclick="toggleMenu()" class="more-row">
                <div class="more-icon" style="background:rgba(34,197,94,.12)"><i data-lucide="handshake" class="w-[17px] h-[17px] text-green-500"></i></div>
                <span class="flex-1 text-[15px] font-semibold"><?= t('nav.partnerships') ?></span>
                <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
            </a>
        </div>
        <p class="more-sec-label"><?= t('nav.sec_system') ?></p>
        <div class="more-card">
            <a href="/SRMT/public/admin/storage.php" onclick="toggleMenu()" class="more-row">
                <div class="more-icon" style="background:rgba(100,116,139,.12)"><i data-lucide="database" class="w-[17px] h-[17px] text-slate-500"></i></div>
                <span class="flex-1 text-[15px] font-semibold"><?= t('nav.storage') ?></span>
                <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
            </a>
            <div class="more-divider"></div>
            <a href="/SRMT/public/admin/settings.php" onclick="toggleMenu()" class="more-row">
                <div class="more-icon" style="background:rgba(251,146,60,.12)"><i data-lucide="mail" class="w-[17px] h-[17px] text-orange-400"></i></div>
                <span class="flex-1 text-[15px] font-semibold"><?= t('nav.automations') ?></span>
                <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-400"></i>
            </a>
        </div>
        <p class="more-sec-label"><?= t('nav.sec_account') ?></p>
        <div class="more-card">
            <a href="/SRMT/public/auth/logout.php" class="more-row more-row-danger">
                <div class="more-icon" style="background:rgba(239,68,68,.12)"><i data-lucide="log-out" class="w-[17px] h-[17px] text-red-500"></i></div>
                <span class="flex-1 text-[15px] font-semibold"><?= t('nav.logout') ?></span>
                <i data-lucide="chevron-right" class="w-4 h-4 text-red-300"></i>
            </a>
        </div>
        <div style="height:48px"></div>
    </div>

    <script>
        var SR_MAP = {
            awaiting:   "<?= addslashes(t('map.awaiting')) ?>",
            onTheWay:   "<?= addslashes(t('map.on_the_way')) ?>",
            arrived:    "<?= addslashes(t('map.arrived')) ?>",
            withClient: "<?= addslashes(t('map.with_client')) ?>",
            inTrip:     "<?= addslashes(t('map.in_trip')) ?>",
            ping:       "<?= addslashes(t('map.ping')) ?>"
        };
        lucide.createIcons();
        function toggleMenu() {
            const m = document.getElementById('fullMenu');
            m.classList.toggle('hidden');
            if (!m.classList.contains('hidden')) m.scrollTop = 0;
        }

        const map = L.map('adminMap', { zoomControl: false, attributionControl: false, center: [41.15, -8.62], zoom: 13 });
        L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19, keepBuffer: 8, updateWhenIdle: false, updateWhenZooming: false
        }).addTo(map);
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        const carIconSvg = (heading = 0, statusId = 0) => {
            const s      = parseInt(statusId);
            const inTrip = (s === 3 || s === 5);
            const body   = inTrip ? '#7c3aed' : '#2563eb';
            const dark   = inTrip ? '#4c1d95' : '#1e3a8a';
            const glass  = 'rgba(219,234,254,0.82)';
            return `<svg width="54" height="54" viewBox="-27 -27 54 54" xmlns="http://www.w3.org/2000/svg" class="car-marker">
                <g transform="rotate(${heading})">
                    <!-- body — smooth sedan silhouette, no protruding wheels -->
                    <path d="M0,-21 C5,-20 12,-16 12,-9 C12,-2 12,6 11,12 C9,17 6,20 3,21 L-3,21 C-6,20 -9,17 -11,12 C-12,6 -12,-2 -12,-9 C-12,-16 -5,-20 0,-21 Z" fill="${body}"/>
                    <!-- front windshield -->
                    <path d="M-8,-14 C-9,-11 -9,-6 -8,-4 L8,-4 C9,-6 9,-11 8,-14 Z" fill="${glass}"/>
                    <!-- panoramic roof panel -->
                    <path d="M-9,-4 L-9,9 L9,9 L9,-4 Z" fill="${dark}" opacity="0.30"/>
                    <!-- rear window -->
                    <path d="M-7,9 C-7,13 -4,15 0,16 C4,15 7,13 7,9 Z" fill="${glass}" opacity="0.65"/>
                    <!-- headlights -->
                    <ellipse cx="-9" cy="-17" rx="2.5" ry="1.4" fill="#fef9c3" opacity="0.95"/>
                    <ellipse cx="9"  cy="-17" rx="2.5" ry="1.4" fill="#fef9c3" opacity="0.95"/>
                    <!-- taillights -->
                    <ellipse cx="-8" cy="17"  rx="2.3" ry="1.4" fill="#fca5a5" opacity="0.95"/>
                    <ellipse cx="8"  cy="17"  rx="2.3" ry="1.4" fill="#fca5a5" opacity="0.95"/>
                    <!-- nose dot — direction indicator -->
                    <circle cx="0" cy="-21" r="2" fill="white" opacity="0.90"/>
                </g>
            </svg>`;
        };

        const geoCache = {};
        async function getCoords(address) {
            if (!address || address === 'N/A') return null;
            if (geoCache[address]) return geoCache[address];
            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&countrycodes=pt&q=${encodeURIComponent(address + ', Portugal')}`);
                const data = await res.json();
                if (data.length) { const ll = L.latLng(data[0].lat, data[0].lon); geoCache[address] = ll; return ll; }
            } catch (e) {}
            return null;
        }

        function destIconFor(pinType) {
            return L.divIcon({
                html: `<div class="dest-pin ${pinType}"><div class="pin-ring"></div><div class="pin-core"></div></div>`,
                className: '', iconSize: [32, 32], iconAnchor: [16, 16]
            });
        }

        function targetAddressFor(d) {
            // Aggregate masters: use the current active stop location from the DB
            if (d.is_aggregate_master == 1 && d.current_stop_location) {
                return d.current_stop_location;
            }
            const s = parseInt(d.status_id);
            return (s === 3 || s === 5) ? d.serviceTargetPoint : d.serviceStartPoint;
        }

        async function ensureRoute(entry) {
            const d = entry.data;
            const status = parseInt(d.status_id);
            const targetAddr = targetAddressFor(d);
            const inTrip = (status === 3 || status === 5);
            const lineColor = inTrip ? '#8b5cf6' : '#3b82f6';
            if (status === 4 || !targetAddr) {
                if (entry.routingControl) { map.removeControl(entry.routingControl); entry.routingControl = null; entry.lastTarget = null; entry.lastColor = null; }
                if (entry.destMarker)     { map.removeLayer(entry.destMarker); entry.destMarker = null; entry.destPinType = null; }
                return;
            }
            const targetCoords = await getCoords(targetAddr);
            if (!targetCoords) return;
            const driverPos = entry.marker.getLatLng();
            const pinType = inTrip ? 'dropoff' : 'pickup';
            if (!entry.destMarker) {
                entry.destMarker = L.marker(targetCoords, { icon: destIconFor(pinType), interactive: false }).addTo(map);
                entry.destPinType = pinType;
            } else {
                entry.destMarker.setLatLng(targetCoords);
                if (entry.destPinType !== pinType) { entry.destMarker.setIcon(destIconFor(pinType)); entry.destPinType = pinType; }
            }
            if (!entry.routingControl || entry.lastTarget !== targetAddr || entry.lastColor !== lineColor) {
                if (entry.routingControl) map.removeControl(entry.routingControl);
                entry.routingControl = L.Routing.control({
                    waypoints: [driverPos, targetCoords],
                    routeWhileDragging: false, addWaypoints: false, show: false,
                    lineOptions: { styles: [{ color: lineColor, opacity: 0.9, weight: 6 }] },
                    createMarker: () => null, fitSelectedRoutes: false
                }).addTo(map);
                entry.lastTarget = targetAddr;
                entry.lastColor = lineColor;
                // Fit map to show full route — pad enough to see car + destination
                entry.routingControl.on('routesfound', e => {
                    const bounds = e.routes[0]?.bounds;
                    if (bounds) map.fitBounds(bounds, { padding: [80, 120], maxZoom: 15, animate: true });
                });
            } else {
                entry.routingControl.setWaypoints([driverPos, targetCoords]);
            }
        }

        function formatAgo(lastUpdate) {
            if (!lastUpdate) return "—";
            const ts = new Date(String(lastUpdate).replace(' ', 'T')).getTime();
            if (isNaN(ts)) return "—";
            const diff = Math.max(0, Date.now() - ts);
            const s = Math.floor(diff / 1000);
            if (s < 3)  return "just now";
            if (s < 60) return s + "s ago";
            const m = Math.floor(s / 60);
            if (m < 60) return m + "m ago";
            return Math.floor(m / 60) + "h ago";
        }

        function setStatusPill(d) {
            const el = document.getElementById('sStatus');
            if (!el) return;
            const s = parseInt(d.status_id);
            el.className = 'status-pill';
            let cls = 's-pending', label = SR_MAP.awaiting;
            if (s === 1) { cls = 's-onway';      label = SR_MAP.onTheWay; }
            else if (s === 2) { cls = 's-arrived';    label = SR_MAP.arrived; }
            else if (s === 5) { cls = 's-withclient'; label = SR_MAP.withClient; }
            else if (s === 3) { cls = 's-intrip';     label = SR_MAP.inTrip; }
            el.classList.add(cls);
            el.textContent = label;
        }

        let drivers = {}, isFetching = false;
        const today = new Date().toLocaleDateString('en-CA'); // local YYYY-MM-DD (not UTC)
        let lastUpdateTimer = null, didInitialFit = false;

        function startLastUpdateTimer() {
            stopLastUpdateTimer();
            lastUpdateTimer = setInterval(() => {
                const did = document.getElementById('sDriver').dataset.did;
                if (!did || !drivers[did]) return;
                const el = document.getElementById('sLastUpdate');
                if (el) el.textContent = SR_MAP.ping + " " + formatAgo(drivers[did].data.last_update);
            }, 1000);
        }
        function stopLastUpdateTimer() { if (lastUpdateTimer) { clearInterval(lastUpdateTimer); lastUpdateTimer = null; } }

        function fitAllDrivers() {
            const pos = Object.values(drivers).map(e => e.marker.getLatLng()).filter(Boolean);
            if (!pos.length) return;
            if (pos.length === 1) { map.setView(pos[0], 15, { animate: true }); return; }
            map.fitBounds(L.latLngBounds(pos), { padding: [80, 80], maxZoom: 14, animate: true });
        }

        function refresh() {
            if (isFetching) return;
            isFetching = true;
            fetch('/SRMT/public/api/tracking-get.php').then(r => r.json()).then(res => {
                isFetching = false;
                if (!res.success || !res.data) return;
                const NOW = Date.now(), STALE = 10 * 60 * 1000;
                let active = res.data.filter(d => {
                    if (d.serviceDate !== today) return false;
                    if (parseInt(d.status_id) === 4) return false;
                    if (d.last_update) {
                        const ts = new Date(String(d.last_update).replace(' ', 'T')).getTime();
                        if (!isNaN(ts) && (NOW - ts) > STALE) return false;
                    }
                    return true;
                });
                const grouped = {};
                active.forEach(d => { if (!grouped[d.driver_id] || parseInt(d.ride_id) > parseInt(grouped[d.driver_id].ride_id)) grouped[d.driver_id] = d; });
                const processed = Object.values(grouped);
                document.getElementById('activeCount').textContent = processed.length;
                const activeIds = [];

                processed.forEach(d => {
                    const id = String(d.driver_id);
                    activeIds.push(id);
                    const pos = [parseFloat(d.latitude), parseFloat(d.longitude)];
                    if (!drivers[id]) {
                        const icon = L.divIcon({ className: 'custom-car', html: carIconSvg(d.heading, d.status_id), iconSize: [54, 54], iconAnchor: [27, 27] });
                        const m = L.marker(pos, { icon }).addTo(map);
                        m.on('click', () => openSheet(id));
                        drivers[id] = { marker: m, data: d, routingControl: null, lastTarget: null, lastColor: null, destMarker: null, destPinType: null };
                    } else {
                        const prev = drivers[id].data;
                        drivers[id].data = d;
                        drivers[id].marker.setLatLng(pos);
                        if (Math.abs((d.heading || 0) - (prev.heading || 0)) > 4 || d.status_id !== prev.status_id) {
                            drivers[id].marker.setIcon(L.divIcon({ className: 'custom-car', html: carIconSvg(d.heading, d.status_id), iconSize: [54, 54], iconAnchor: [27, 27] }));
                        }
                    }
                    ensureRoute(drivers[id]);
                    if (isSheetOpen(id)) updateSheetData(d);
                });

                Object.keys(drivers).forEach(eid => {
                    if (!activeIds.includes(eid)) {
                        if (drivers[eid].routingControl) map.removeControl(drivers[eid].routingControl);
                        if (drivers[eid].destMarker)     map.removeLayer(drivers[eid].destMarker);
                        map.removeLayer(drivers[eid].marker);
                        if (isSheetOpen(eid)) closeSheet();
                        delete drivers[eid];
                    }
                });

                if (!didInitialFit && processed.length > 0) { didInitialFit = true; fitAllDrivers(); }
            }).catch(() => isFetching = false);
        }

        function openSheet(id) {
            const d = drivers[id].data;
            const isInTrip = (parseInt(d.status_id) === 3 || parseInt(d.status_id) === 5);
            document.getElementById('sDriver').textContent = d.driver_name;
            document.getElementById('sDriver').dataset.did = id;
            document.getElementById('sSpeed').textContent = Math.round((d.speed || 0) * 3.6);
            document.getElementById('sLastUpdate').textContent = SR_MAP.ping + " " + formatAgo(d.last_update);
            document.getElementById('sDest').textContent = (isInTrip ? d.serviceTargetPoint : d.serviceStartPoint) || "En route…";
            document.getElementById('sClient').textContent = d.NomeCliente || "—";
            document.getElementById('sVehicle').textContent = d.vehicle_plate || "SyncRide";
            setStatusPill(d);
            document.getElementById('driverSheet').classList.add('active');
            startLastUpdateTimer();
            map.flyTo(drivers[id].marker.getLatLng(), 16, { duration: 1 });
        }

        function updateSheetData(d) {
            const isInTrip = (parseInt(d.status_id) === 3 || parseInt(d.status_id) === 5);
            document.getElementById('sSpeed').textContent = Math.round((d.speed || 0) * 3.6);
            document.getElementById('sLastUpdate').textContent = SR_MAP.ping + " " + formatAgo(d.last_update);
            document.getElementById('sDest').textContent = (isInTrip ? d.serviceTargetPoint : d.serviceStartPoint) || "En route…";
            setStatusPill(d);
        }

        function closeSheet() {
            document.getElementById('driverSheet').classList.remove('active');
            document.getElementById('sDriver').dataset.did = "";
            stopLastUpdateTimer();
        }

        function isSheetOpen(id) {
            return document.getElementById('driverSheet').classList.contains('active') &&
                   document.getElementById('sDriver').dataset.did === id;
        }

        setInterval(refresh, 3000);
        refresh();
        window.addEventListener('resize', () => map.invalidateSize());
    </script>
</body>
</html>
