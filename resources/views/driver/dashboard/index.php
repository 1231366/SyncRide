<?php
use App\Http\View;

/**
 * @var array[] $rides       initial ride rows (joined data)
 * @var int     $todayCount
 * @var int     $weekCount
 * @var int     $driverId
 * @var string  $userName
 */
$firstName    = explode(' ', trim($userName))[0];
$userPhotoSrc = isset($_SESSION['profile_photo_path']) && $_SESSION['profile_photo_path'] !== ''
    ? '/SRMT/' . ltrim((string) $_SESSION['profile_photo_path'], '/')
    : '/SRMT/public/assets/images/icons/Syncride.png';
?><!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#09090f">
    <title>Rides — SyncRide</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        /* ── Tokens ──────────────────────────────────────────────────────── */
        :root {
            --font-body:    'Inter', sans-serif;
            --font-display: 'Poppins', sans-serif;

            /* Light (default) — mirrors admin design system */
            --bg:        #f8fafc;
            --bg-card:   #ffffff;
            --bg-raised: #f1f5f9;
            --bg-input:  #f8fafc;
            --border:    #e2e8f0;
            --border-strong: #cbd5e0;
            --text-1:    #0f172a;
            --text-2:    #475569;
            --text-3:    #94a3b8;

            --accent:       #2563eb;
            --accent-glow:  rgba(37,99,235,.2);
            --accent-soft:  #eff6ff;
            --success:      #16a34a;
            --success-soft: #f0fdf4;
            --warning:      #d97706;
            --warning-soft: #fffbeb;
            --danger:       #dc2626;
            --danger-soft:  #fef2f2;
            --info:         #2563eb;

            --radius-sm: 8px;
            --radius:    14px;
            --radius-lg: 20px;
            --shadow:    0 1px 3px rgb(0 0 0 / .1), 0 1px 2px -1px rgb(0 0 0 / .1);
            --safe-top:    env(safe-area-inset-top, 0px);
            --safe-bottom: env(safe-area-inset-bottom, 0px);
        }
        [data-bs-theme="dark"] {
            --bg:        #0f172a;
            --bg-card:   #1e293b;
            --bg-raised: #293548;
            --bg-input:  #1e293b;
            --border:    #334155;
            --border-strong: #475569;
            --text-1:    #f1f5f9;
            --text-2:    #94a3b8;
            --text-3:    #64748b;
            --accent:       #3b82f6;
            --accent-glow:  rgba(59,130,246,.25);
            --accent-soft:  rgba(59,130,246,.12);
            --success:      #22c55e;
            --success-soft: rgba(34,197,94,.12);
            --warning:      #f59e0b;
            --warning-soft: rgba(245,158,11,.12);
            --danger:       #ef4444;
            --danger-soft:  rgba(239,68,68,.12);
            --shadow:    0 2px 12px rgba(0,0,0,.5);
        }

        /* ── Base ────────────────────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: var(--font-body);
            background: var(--bg);
            color: var(--text-1);
            margin: 0;
            min-height: 100dvh;
            padding-bottom: calc(72px + var(--safe-bottom));
            -webkit-tap-highlight-color: transparent;
        }

        /* ── Header ──────────────────────────────────────────────────────── */
        .app-header {
            position: sticky; top: 0; z-index: 200;
            display: flex; align-items: center; justify-content: space-between;
            padding: calc(14px + var(--safe-top)) 20px 14px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .brand-logo { height: 28px; width: auto; }
        .header-right { display: flex; align-items: center; gap: 14px; }
        .theme-btn {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--bg-raised); border: 1px solid var(--border);
            color: var(--text-2); display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: .95rem; transition: color .2s, background .2s;
        }
        .theme-btn:hover { color: var(--text-1); background: var(--bg-input); }
        .user-avatar {
            width: 36px; height: 36px; border-radius: 50%;
            object-fit: cover; border: 2px solid var(--border-strong);
            cursor: pointer; transition: opacity .15s;
        }
        .user-avatar:active { opacity: .75; }

        /* ── Greeting ────────────────────────────────────────────────────── */
        .greeting-row { padding: 20px 20px 4px; }
        .greeting-row h4 {
            font-family: var(--font-display); font-size: 1.4rem;
            font-weight: 700; margin: 0; color: var(--text-1);
        }
        .greeting-row p { font-size: .8rem; color: var(--text-2); margin: 4px 0 0; }

        /* ── Stat chips ──────────────────────────────────────────────────── */
        .stat-row { display: flex; gap: 12px; padding: 16px 20px; }
        .stat-chip {
            flex: 1; display: flex; align-items: center; gap: 12px;
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 14px 16px;
            box-shadow: var(--shadow);
        }
        .stat-icon {
            width: 40px; height: 40px; border-radius: 10px; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: 1.1rem;
        }
        .stat-icon.indigo { background: var(--accent-soft); color: var(--accent); }
        .stat-icon.green  { background: var(--success-soft); color: var(--success); }
        .stat-num { font-family: var(--font-display); font-size: 1.6rem; font-weight: 800; line-height: 1; color: var(--text-1); }
        .stat-label { font-size: .7rem; text-transform: uppercase; letter-spacing: .6px; color: var(--text-2); margin-top: 2px; }

        /* ── Filter tabs ─────────────────────────────────────────────────── */
        .filter-bar { padding: 0 20px 16px; }
        .filter-pills {
            display: flex; gap: 0; padding: 4px;
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: 50px; overflow: hidden;
        }
        .filter-btn {
            flex: 1; padding: 9px 0; border: none; background: transparent;
            color: var(--text-2); font-size: .85rem; font-weight: 500;
            border-radius: 50px; transition: all .2s; cursor: pointer;
        }
        .filter-btn.active {
            background: var(--accent); color: #fff;
            box-shadow: 0 2px 10px var(--accent-glow);
        }

        /* ── Ride cards ──────────────────────────────────────────────────── */
        .ride-list { padding: 0 20px; }
        .ride-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius-lg); margin-bottom: 12px;
            padding: 16px; position: relative; cursor: pointer;
            transition: transform .12s, box-shadow .12s;
            border-left: 3px solid var(--accent);
            overflow: hidden;
        }
        .ride-card:active { transform: scale(.98); box-shadow: 0 1px 4px rgba(0,0,0,.3); }
        .ride-card[data-stype="1"] { border-left-color: var(--accent); }
        .ride-card[data-stype="2"] { border-left-color: var(--warning); }
        .ride-card[data-stype="0"] { border-left-color: var(--text-3); }
        .ride-card.is-done { opacity: .55; border-left-color: var(--success); }
        .ride-card.is-done::after {
            content: ''; position: absolute; inset: 0;
            background: repeating-linear-gradient(
                -45deg,
                transparent, transparent 6px,
                rgba(16,185,129,.04) 6px, rgba(16,185,129,.04) 7px
            );
            pointer-events: none;
        }

        .ride-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .ride-time { font-family: var(--font-display); font-size: 1.3rem; font-weight: 800; color: var(--text-1); line-height: 1; }
        .ride-client-name { font-size: .8rem; color: var(--text-2); margin-top: 3px; font-weight: 500; }
        .badge-row { display: flex; gap: 5px; flex-wrap: wrap; align-items: center; }
        .ride-badge {
            font-size: .65rem; font-weight: 600; padding: 3px 8px;
            border-radius: 20px; text-transform: uppercase; letter-spacing: .3px;
            display: inline-flex; align-items: center; gap: 3px;
        }
        .badge-private { background: var(--accent-soft); color: var(--accent); border: 1px solid rgba(99,102,241,.2); }
        .badge-shared   { background: var(--warning-soft); color: var(--warning); border: 1px solid rgba(245,158,11,.2); }
        .status-dot {
            width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; margin-top: 6px;
        }
        .dot-0 { background: var(--text-3); }
        .dot-1, .dot-2, .dot-5, .dot-3 { background: var(--warning); box-shadow: 0 0 6px var(--warning); }
        .dot-4 { background: var(--success); }

        .route-line { display: flex; gap: 10px; align-items: stretch; }
        .route-dots { display: flex; flex-direction: column; align-items: center; gap: 0; padding-top: 5px; }
        .rdot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .rdot-pickup  { background: var(--success); }
        .rdot-dropoff { background: var(--danger); }
        .rdot-line {
            width: 2px; flex: 1; min-height: 12px;
            background: repeating-linear-gradient(to bottom, var(--border-strong) 0, var(--border-strong) 3px, transparent 3px, transparent 6px);
            margin: 2px 0;
        }
        .route-text { flex: 1; display: flex; flex-direction: column; gap: 10px; }
        .rt-point { font-size: .88rem; color: var(--text-1); font-weight: 500; line-height: 1.3; }
        .rt-label { font-size: .65rem; text-transform: uppercase; letter-spacing: .5px; color: var(--text-3); margin-bottom: 1px; }

        .price-badge {
            position: absolute; bottom: 14px; right: 14px;
            background: var(--success); color: #fff;
            font-size: .75rem; font-weight: 700; padding: 3px 9px;
            border-radius: 8px; display: flex; align-items: center; gap: 4px;
        }

        /* ── Empty state ─────────────────────────────────────────────────── */
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-3); }
        .empty-state i { font-size: 2.5rem; opacity: .4; }
        .empty-state p { font-size: .9rem; margin-top: 12px; }

        /* ── Bottom nav ──────────────────────────────────────────────────── */
        .bottom-nav {
            position: fixed; bottom: 0; left: 0; width: 100%;
            height: calc(64px + var(--safe-bottom));
            background: var(--bg-card); border-top: 1px solid var(--border);
            display: flex; z-index: 500; padding-bottom: var(--safe-bottom);
        }
        .nav-item {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            color: var(--text-3); text-decoration: none;
            font-size: .68rem; font-weight: 500;
            gap: 4px; transition: color .2s;
        }
        .nav-item i { font-size: 1.35rem; }
        .nav-item.active { color: var(--accent); }
        .nav-item.danger  { color: var(--danger); }

        /* ── Details modal — bottom sheet ────────────────────────────────── */
        #detailsModal .modal-dialog {
            position: fixed; bottom: 0; left: 0; right: 0;
            margin: 0; width: 100%; max-width: 100%;
            max-height: 93dvh;
        }
        #detailsModal.fade .modal-dialog {
            transform: translateY(110%);
            transition: transform .38s cubic-bezier(0.32, 0.72, 0, 1);
        }
        #detailsModal.show .modal-dialog { transform: translateY(0); }
        #detailsModal .modal-content {
            border-radius: 24px 24px 0 0;
            border: 1px solid var(--border);
            border-bottom: none;
            background: var(--bg-card);
            max-height: 93dvh;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            transition: background .4s, border-color .4s;
        }
        #detailsModal .modal-content.is-completed {
            background: #f0f4f8;
            border-color: #cbd5e0;
        }
        [data-bs-theme="dark"] #detailsModal .modal-content.is-completed {
            background: #1a2235;
            border-color: #334155;
        }
        .drag-handle {
            width: 36px; height: 4px; border-radius: 2px;
            background: var(--border-strong); margin: 10px auto 0;
        }
        .modal-top-bar {
            display: flex; align-items: flex-start; justify-content: space-between;
            padding: 12px 20px 8px;
        }
        .modal-ride-id { font-size: .72rem; color: var(--text-3); font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
        .modal-status-badge {
            font-size: .68rem; font-weight: 700; padding: 4px 10px;
            border-radius: 20px; text-transform: uppercase; letter-spacing: .4px;
        }
        .modal-close-btn {
            width: 30px; height: 30px; border-radius: 50%;
            background: var(--bg-raised); border: none; color: var(--text-2);
            display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: .85rem;
        }

        /* Status stepper */
        .status-stepper {
            display: flex; align-items: center; padding: 0 20px 16px; gap: 0;
        }
        .step-node {
            display: flex; flex-direction: column; align-items: center; flex: 1;
        }
        .step-dot {
            width: 10px; height: 10px; border-radius: 50%;
            background: var(--bg-raised); border: 2px solid var(--border-strong);
            transition: background .3s, border-color .3s, box-shadow .3s;
        }
        .step-node.done .step-dot   { background: var(--success); border-color: var(--success); }
        .step-node.active .step-dot { background: var(--accent); border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-glow); }
        .step-line {
            flex: 1; height: 2px; margin-bottom: 0; align-self: center;
            background: var(--border-strong); transition: background .3s;
        }
        .step-line.done { background: var(--success); }
        .step-label {
            font-size: .55rem; color: var(--text-3); margin-top: 5px;
            text-transform: uppercase; letter-spacing: .3px; text-align: center;
        }
        .step-node.active .step-label,
        .step-node.done .step-label  { color: var(--text-2); }

        /* Modal body sections */
        .modal-section { padding: 0 20px 16px; }
        .modal-section + .modal-section { border-top: 1px solid var(--border); padding-top: 16px; }

        /* Compact client block */
        .client-name-row {
            display: flex; align-items: center; gap: 8px; margin-bottom: 9px;
        }
        .client-name  { font-size: 1rem; font-weight: 700; color: var(--text-1); line-height: 1.2; }
        .wa-icon-btn {
            width: 24px; height: 24px; border-radius: 50%; flex-shrink: 0;
            background: #25D366; color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: .72rem; text-decoration: none;
            transition: opacity .15s;
        }
        .wa-icon-btn:active { opacity: .75; }
        .badge-row-inline {
            display: flex; flex-wrap: wrap; gap: 6px; align-items: center;
        }
        .pax-pill {
            display: inline-flex; align-items: center; gap: 5px;
            background: var(--accent-soft); color: var(--accent);
            border: 1px solid rgba(37,99,235,.2); border-radius: 20px;
            font-size: .72rem; font-weight: 600; padding: 4px 10px;
            transition: background .2s, color .2s, border-color .2s;
        }
        .pax-pill.has-baby {
            background: var(--warning-soft); color: var(--warning);
            border-color: rgba(217,119,6,.3);
        }

        /* Route boarding-pass style */
        .route-card {
            background: var(--bg-raised); border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden;
        }
        .route-point {
            display: flex; align-items: flex-start; gap: 12px; padding: 14px 16px;
        }
        .route-point + .route-point { border-top: 1px dashed var(--border); }
        .route-indicator {
            width: 32px; height: 32px; border-radius: 50%; flex-shrink: 0;
            display: flex; align-items: center; justify-content: center; font-size: .85rem;
        }
        .ri-pickup  { background: var(--success-soft); color: var(--success); }
        .ri-dropoff { background: var(--danger-soft);  color: var(--danger); }
        .route-loc-label { font-size: .65rem; text-transform: uppercase; letter-spacing: .5px; color: var(--text-3); }
        .route-loc-text  { font-size: .92rem; font-weight: 600; color: var(--text-1); line-height: 1.3; margin-top: 2px; }

        /* Utility buttons row */
        .util-row { display: flex; gap: 10px; margin-top: 14px; }
        .util-btn {
            flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px;
            padding: 10px 0; border-radius: 10px; font-size: .82rem; font-weight: 600;
            border: 1px solid var(--border-strong); background: var(--bg-raised);
            color: var(--text-1); cursor: pointer; text-decoration: none;
            transition: opacity .15s;
        }
        .util-btn:active { opacity: .7; }
        .util-btn.dark-bg { background: #000; border-color: rgba(255,255,255,.15); color: #fff; }
        .util-btn.info-bg { background: var(--info); border-color: var(--info); color: #fff; }

        .badge-strip { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
        .strip-badge {
            font-size: .7rem; font-weight: 600; padding: 4px 10px; border-radius: 20px;
            display: inline-flex; align-items: center; gap: 4px;
        }
        .sb-green  { background: var(--success-soft); color: var(--success); border: 1px solid rgba(16,185,129,.2); }
        .sb-red    { background: var(--danger-soft);  color: var(--danger);  border: 1px solid rgba(239,68,68,.2); }
        .sb-blue   { background: rgba(59,130,246,.12); color: var(--info);   border: 1px solid rgba(59,130,246,.2); }
        .sb-purple { background: var(--accent-soft);  color: var(--accent);  border: 1px solid rgba(99,102,241,.2); }

        /* Action button */
        .action-btn {
            width: 100%; padding: 15px; font-size: 1rem; font-weight: 700;
            font-family: var(--font-display); border-radius: 14px; border: none;
            color: #fff; text-transform: uppercase; letter-spacing: .6px;
            transition: transform .15s, box-shadow .15s; cursor: pointer;
        }
        .action-btn:active { transform: scale(.97); }
        .action-btn:disabled { opacity: .45; cursor: default; transform: none; }
        .status-btn-0 { background: linear-gradient(135deg, #2563eb, #1d4ed8); box-shadow: 0 4px 16px rgba(37,99,235,.35); }
        .status-btn-1 { background: linear-gradient(135deg, #f59e0b, #d97706); box-shadow: 0 4px 16px rgba(245,158,11,.4); }
        .status-btn-2 { background: linear-gradient(135deg, #3b82f6, #2563eb); box-shadow: 0 4px 16px rgba(59,130,246,.4); }
        .status-btn-5 { background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 16px rgba(16,185,129,.4); }
        .status-btn-3 { background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 4px 16px rgba(239,68,68,.4); }
        .status-btn-4 { background: var(--bg-raised); color: var(--success); border: 1px solid rgba(16,185,129,.3); box-shadow: none; }

        .secondary-row { display: flex; gap: 10px; margin-top: 10px; }
        .sec-btn {
            flex: 1; padding: 11px 0; border-radius: 10px; font-size: .82rem; font-weight: 600;
            display: flex; align-items: center; justify-content: center; gap: 6px;
            border: 1px solid var(--border-strong); background: transparent;
            color: var(--text-2); cursor: pointer; transition: opacity .15s;
        }
        .sec-btn-danger { color: var(--danger); border-color: rgba(239,68,68,.3); background: var(--danger-soft); }
        .sec-btn:active, .sec-btn-danger:active { opacity: .7; }

        .wa-btn {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 12px; border-radius: 12px;
            background: #128C7E; color: #fff; font-weight: 600; font-size: .88rem;
            text-decoration: none; border: none; margin-top: 10px;
            transition: opacity .15s;
        }
        .wa-btn:active { opacity: .8; color: #fff; }

        /* ── Airport sign overlay ────────────────────────────────────────── */
        #airportOverlay {
            position: fixed; inset: 0; background: #000; z-index: 2000;
            display: none; flex-direction: column; justify-content: center;
            align-items: center; overflow: hidden; color: white;
        }
        #airportContentWrapper {
            width: 100%; height: 100%; display: flex; flex-direction: column;
            align-items: center; justify-content: center; text-align: center; overflow: hidden;
        }
        #airportOverlay.landscape-mode #airportContentWrapper {
            position: absolute; top: 50%; left: 50%;
            width: 100vh; height: 100vw; transform: translate(-50%,-50%) rotate(90deg);
        }
        #airportClientName {
            font-family: var(--font-display); font-weight: 900; line-height: .9;
            text-transform: uppercase; width: 100%; margin: 0; padding: 0 4vw;
            font-size: 15vw; word-wrap: normal; display: block;
        }
        .name-part { display: inline-block; white-space: nowrap; }
        #airportFlight { font-family: var(--font-body); font-weight: 600; color: #FFD700; margin-top: 2vh; font-size: 6vw; letter-spacing: 2px; }
        .airport-controls {
            position: absolute; top: 20px; right: 20px;
            display: flex; gap: 25px; z-index: 2001; opacity: .2; transition: opacity .3s;
        }
        .airport-controls:hover { opacity: 1; }
        .airport-zoom-controls {
            position: absolute; bottom: 40px; left: 50%; transform: translateX(-50%);
            display: flex; gap: 20px; z-index: 2002; opacity: .2; transition: opacity .3s;
        }
        .airport-zoom-controls:hover { opacity: 1; }
        .zoom-btn {
            width: 60px; height: 60px; border-radius: 50%;
            background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.3);
            color: white; display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; backdrop-filter: blur(5px); cursor: pointer;
        }

        /* ── Camera overlay ──────────────────────────────────────────────── */
        #cameraOverlay {
            position: fixed; inset: 0; background: #000; z-index: 99999;
            display: none; flex-direction: column;
        }
        #cameraViewArea { flex: 1; position: relative; overflow: hidden; background: #000; }
        #cameraStream, #photoCanvas {
            width: 100%; height: 100%; object-fit: cover; position: absolute; inset: 0;
        }
        .camera-ui-controls {
            position: absolute; bottom: 0; left: 0; width: 100%;
            padding: 40px 20px calc(40px + var(--safe-bottom)) 20px;
            background: linear-gradient(to top, #000, transparent);
            display: flex; justify-content: center; gap: 20px; align-items: center;
        }
        .camera-btn {
            width: 70px; height: 70px; border-radius: 50%;
            border: 4px solid white; background: transparent;
            display: flex; align-items: center; justify-content: center; padding: 0;
        }
        .camera-btn-inner { width: 56px; height: 56px; background: white; border-radius: 50%; transition: transform .1s; }
        .btn-circle-action {
            width: 50px; height: 50px; border-radius: 50%; border: none;
            background: rgba(255,255,255,.2); color: white;
            display: flex; align-items: center; justify-content: center; backdrop-filter: blur(5px);
        }
    </style>
</head>
<body>

<!-- ── Airport sign ───────────────────────────────────────────────────── -->
<div id="airportOverlay">
    <div class="airport-controls">
        <i class="bi bi-arrow-repeat text-white fs-1" id="rotateScreenBtn"></i>
        <i class="bi bi-x-lg text-white fs-1" id="closeAirportMode"></i>
    </div>
    <div id="airportContentWrapper">
        <h1 id="airportClientName">NOME</h1>
        <h2 id="airportFlight"></h2>
    </div>
    <div class="airport-zoom-controls">
        <div class="zoom-btn" id="btnZoomOut"><i class="bi bi-dash-lg"></i></div>
        <div class="zoom-btn" id="btnZoomIn"><i class="bi bi-plus-lg"></i></div>
    </div>
</div>

<!-- ── Camera ─────────────────────────────────────────────────────────── -->
<div id="cameraOverlay">
    <div style="position:absolute;top:max(20px,env(safe-area-inset-top));left:0;width:100%;text-align:center;color:white;z-index:10;font-weight:600;text-shadow:0 2px 4px rgba(0,0,0,.8);" id="cameraInstruction">Photograph</div>
    <div id="cameraViewArea">
        <div id="cameraLoading" style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:white;display:none;text-align:center;">
            <div class="spinner-border mb-2"></div><div>Starting…</div>
        </div>
        <video id="cameraStream" autoplay playsinline style="transform-origin:center center;"></video>
        <canvas id="photoCanvas" style="display:none;"></canvas>
        <div id="cameraZoomStrip" style="position:absolute;right:16px;top:50%;transform:translateY(-50%);display:flex;flex-direction:column;gap:12px;z-index:10;opacity:.7;">
            <button class="btn-circle-action" id="btnCameraZoomIn"><i class="bi bi-zoom-in"></i></button>
            <button class="btn-circle-action" id="btnCameraZoomOut"><i class="bi bi-zoom-out"></i></button>
        </div>
    </div>
    <div class="camera-ui-controls">
        <div id="stepCaptureControls" class="d-flex align-items-center gap-4">
            <button class="btn-circle-action" onclick="closeCameraOverlay()"><i class="bi bi-x-lg"></i></button>
            <button class="camera-btn" id="btnCapture"><div class="camera-btn-inner"></div></button>
            <button class="btn-circle-action" id="btnRotateCamera"><i class="bi bi-arrow-repeat"></i></button>
        </div>
        <div id="stepConfirmControls" class="d-none d-flex gap-3 w-100 justify-content-center">
            <button class="btn btn-light rounded-pill px-4 py-2 fw-bold" id="btnRetake">Retake</button>
            <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold" id="btnConfirmSend">Send</button>
        </div>
    </div>
</div>

<!-- ── App header ─────────────────────────────────────────────────────── -->
<header class="app-header">
    <img src="/SRMT/public/assets/images/icons/Syncride.png" alt="SyncRide" class="brand-logo" id="driver-logo">
    <div class="header-right">
        <button class="theme-btn" id="theme-toggle"><i class="bi bi-sun-fill" id="theme-icon"></i></button>
        <img src="<?= View::e($userPhotoSrc) ?>" class="user-avatar" alt="" data-bs-toggle="modal" data-bs-target="#photoModal">
    </div>
</header>

<!-- ── Main content ───────────────────────────────────────────────────── -->
<div class="greeting-row">
    <h4>Hey, <?= View::e($firstName) ?> 👋</h4>
    <p>Here's your schedule</p>
</div>

<div class="stat-row">
    <div class="stat-chip">
        <div class="stat-icon indigo"><i class="bi bi-calendar-check-fill"></i></div>
        <div><div class="stat-num"><?= $todayCount ?></div><div class="stat-label">Today</div></div>
    </div>
    <div class="stat-chip">
        <div class="stat-icon green"><i class="bi bi-calendar-week-fill"></i></div>
        <div><div class="stat-num"><?= $weekCount ?></div><div class="stat-label">This Week</div></div>
    </div>
</div>

<div class="filter-bar">
    <div class="filter-pills">
        <button class="filter-btn" data-filter="yesterday">Yesterday</button>
        <button class="filter-btn active" data-filter="today">Today</button>
        <button class="filter-btn" data-filter="tomorrow">Tomorrow</button>
    </div>
</div>

<div class="ride-list" id="rideList">
    <div class="empty-state">
        <div class="spinner-border" style="color:var(--accent);" role="status"></div>
        <p style="margin-top:14px;">Loading…</p>
    </div>
</div>

<!-- ── Details modal (bottom sheet) ───────────────────────────────────── -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="drag-handle"></div>

            <div class="modal-top-bar">
                <div>
                    <div class="modal-ride-id">Ride #<span id="modalIdDisplay"></span></div>
                </div>
                <button class="modal-close-btn" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>

            <!-- Status stepper -->
            <div class="status-stepper" id="statusStepper">
                <div class="step-node" data-step="0">
                    <div class="step-dot"></div>
                    <div class="step-label">Pickup</div>
                </div>
                <div class="step-line" data-line="0"></div>
                <div class="step-node" data-step="1">
                    <div class="step-dot"></div>
                    <div class="step-label">Arrived</div>
                </div>
                <div class="step-line" data-line="1"></div>
                <div class="step-node" data-step="2">
                    <div class="step-dot"></div>
                    <div class="step-label">Client</div>
                </div>
                <div class="step-line" data-line="2"></div>
                <div class="step-node" data-step="3">
                    <div class="step-dot"></div>
                    <div class="step-label">Trip</div>
                </div>
                <div class="step-line" data-line="3"></div>
                <div class="step-node" data-step="4">
                    <div class="step-dot"></div>
                    <div class="step-label">Done</div>
                </div>
            </div>

            <!-- Client section -->
            <div class="modal-section">
                <div class="client-name-row">
                    <div class="client-name" id="modalClient">—</div>
                    <div id="whatsappContainer" style="display:none;"></div>
                </div>

                <div class="badge-row-inline">
                    <span class="pax-pill" id="paxPill">
                        <i class="bi bi-people-fill"></i>
                        <span id="modalADT"></span>A + <span id="modalCHD"></span>C<span id="modalBBYPart" style="display:none;"> + <span id="modalBBY"></span>B</span>
                    </span>
                    <div id="modalBadgesContainer" style="display:contents;"></div>
                </div>

                <div class="util-row">
                    <button class="util-btn dark-bg" id="btnAirportMode"><i class="bi bi-signpost-2-fill"></i> Sign</button>
                    <a href="#" id="trackFlightLink" target="_blank" class="util-btn info-bg" style="display:none;">
                        <i class="bi bi-airplane-fill"></i> <span id="modalFlight"></span>
                    </a>
                </div>
            </div>

            <!-- Route section -->
            <div class="modal-section">
                <div class="route-card">
                    <div class="route-point">
                        <div class="route-indicator ri-pickup"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <div class="route-loc-label">Pickup</div>
                            <div class="route-loc-text" id="modalPickup">—</div>
                        </div>
                    </div>
                    <div class="route-point">
                        <div class="route-indicator ri-dropoff"><i class="bi bi-flag-fill"></i></div>
                        <div>
                            <div class="route-loc-label">Dropoff</div>
                            <div class="route-loc-text" id="modalDropoff">—</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions section -->
            <div class="modal-section">
                <button id="btnDynamicAction" class="action-btn status-btn-0">START PICKUP</button>

                <div class="secondary-row">
                    <button class="sec-btn" id="uploadVoucher"><i class="bi bi-ticket-perforated"></i> Voucher</button>
                    <button class="sec-btn sec-btn-danger" id="uploadNoShow"><i class="bi bi-camera"></i> No-Show</button>
                </div>

                <a href="#" id="whatsappAlojamento" target="_blank" class="wa-btn" style="display:none;">
                    <i class="bi bi-whatsapp"></i> Leaving airport
                </a>
            </div>

            <div style="height: calc(10px + var(--safe-bottom));"></div>
        </div>
    </div>
</div>

<!-- ── Profile photo modal ────────────────────────────────────────────── -->
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border);border-radius:20px;">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h5 class="modal-title" style="color:var(--text-1);">Profile Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    style="filter:<?= strpos($_SESSION['theme'] ?? 'dark', 'light') !== false ? 'none' : 'invert(1)' ?>"></button>
            </div>
            <div class="modal-body p-4 text-center" style="background:var(--bg-card);">
                <form action="/SRMT/public/save-profile-photo.php" method="POST" enctype="multipart/form-data">
                    <img id="currentProfilePhoto" src="<?= View::e($userPhotoSrc) ?>"
                        class="rounded-circle mb-4" style="width:110px;height:110px;object-fit:cover;border:3px solid var(--border-strong);">
                    <input type="file" name="profile_photo" id="profilePhotoInput"
                        class="form-control mb-3" accept="image/*" required
                        style="background:var(--bg-input);border-color:var(--border);color:var(--text-1);">
                    <button type="submit"
                        class="btn w-100 py-2 fw-bold rounded-pill"
                        style="background:var(--accent);color:#fff;border:none;">Save Photo</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── Bottom nav ─────────────────────────────────────────────────────── -->
<nav class="bottom-nav">
    <a href="/SRMT/public/driver/"           class="nav-item active"><i class="bi bi-car-front-fill"></i>Rides</a>
    <a href="/SRMT/public/driver/agenda.php" class="nav-item"><i class="bi bi-calendar3"></i>Agenda</a>
    <a href="/SRMT/public/driver/stats.php"  class="nav-item"><i class="bi bi-bar-chart-fill"></i>Stats</a>
    <a href="/SRMT/public/auth/logout.php"   class="nav-item danger"><i class="bi bi-box-arrow-right"></i>Logout</a>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
var viagens = <?= json_encode($rides, JSON_UNESCAPED_UNICODE) ?>;
var currentDriverId = <?= $driverId ?>;

// ── Theme ─────────────────────────────────────────────────────────────────
(function () {
    const html   = document.documentElement;
    const toggle = document.getElementById('theme-toggle');
    const icon   = document.getElementById('theme-icon');
    const logo   = document.getElementById('driver-logo');
    function applyTheme(t) {
        html.setAttribute('data-bs-theme', t);
        icon.className = t === 'light' ? 'bi bi-moon-stars-fill fs-5' : 'bi bi-sun-fill fs-5';
        if (logo) logo.src = t === 'dark' ? '/SRMT/public/assets/images/icons/Syncridewhite.png' : '/SRMT/public/assets/images/icons/Syncride.png';
    }
    applyTheme(localStorage.getItem('theme') || 'light');
    toggle.addEventListener('click', () => {
        const next = html.getAttribute('data-bs-theme') === 'light' ? 'dark' : 'light';
        localStorage.setItem('theme', next);
        applyTheme(next);
    });
})();

// ── State ─────────────────────────────────────────────────────────────────
let backgroundWatcherId = null, trackingInterval = null, currentRideId = null, currentRideData = null;
let localTripStatus = {}, currentFilter = 'today', stream = null, currentMode = 'noshow';
let currentFacingMode = 'environment', locationWatcher = null, currentLat = null, currentLng = null;
let cameraZoomLevel = 1, pinchInitialDistance = 0, wakeLock = null;
viagens.forEach(v => { localTripStatus[String(v.ServiceID)] = parseInt(v.status_id) || 0; });

// ── Auto-refresh ──────────────────────────────────────────────────────────
function fetchLatestRides() {
    fetch('/SRMT/public/driver/?api=refresh').then(r => r.json()).then(data => {
        if (!Array.isArray(data)) return;
        viagens = data;
        viagens.forEach(v => { if (localTripStatus[String(v.ServiceID)] === undefined) localTripStatus[String(v.ServiceID)] = parseInt(v.status_id) || 0; });
        filterTrips(currentFilter);
    }).catch(() => {});
}
setInterval(fetchLatestRides, 15000);

// ── GPS ───────────────────────────────────────────────────────────────────
function sendPosition(position) {
    const finishBg = () => { if (window.Capacitor?.Plugins?.BackgroundGeolocation) Capacitor.Plugins.BackgroundGeolocation.finish(); };
    if (!currentRideId) { finishBg(); return; }
    const lat = position.latitude  ?? position.coords?.latitude;
    const lng = position.longitude ?? position.coords?.longitude;
    if (lat === undefined || lng === undefined) { finishBg(); return; }
    fetch('/SRMT/public/api/location-update.php', {
        method: 'POST',
        body: JSON.stringify({ ride_id: currentRideId, driver_id: currentDriverId, lat, lng, speed: position.speed ?? position.coords?.speed ?? 0, heading: position.bearing ?? position.coords?.heading ?? 0 }),
        headers: {'Content-Type': 'application/json'}
    }).catch(() => {}).finally(finishBg);
}
function startLiveTracking(rideId) {
    currentRideId = rideId;
    sessionStorage.setItem('activeRideId', rideId);
    if (window.Capacitor?.Plugins?.BackgroundGeolocation) {
        const BGeo = Capacitor.Plugins.BackgroundGeolocation;
        if (backgroundWatcherId) return;
        BGeo.addWatcher({ backgroundTitle: 'SyncRide in Service', backgroundMessage: 'Location is being shared', requestAllowAlwaysLocation: true, distanceFilter: 10, staleLocationThreshold: 30, radius: 20 },
            (loc, err) => { if (loc) sendPosition(loc); }).then(id => { backgroundWatcherId = id; });
    } else if ('geolocation' in navigator) {
        if (trackingInterval) clearInterval(trackingInterval);
        navigator.geolocation.getCurrentPosition(p => sendPosition(p));
        trackingInterval = setInterval(() => navigator.geolocation.getCurrentPosition(p => sendPosition(p)), 5000);
    }
}
function stopLiveTracking() {
    if (!currentRideId) return;
    sessionStorage.removeItem('activeRideId');
    fetch('/SRMT/public/api/tracking-stop.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({ ride_id: currentRideId, driver_id: currentDriverId }) });
    currentRideId = null;
    if (window.Capacitor?.Plugins?.BackgroundGeolocation && backgroundWatcherId) { Capacitor.Plugins.BackgroundGeolocation.removeWatcher({ id: backgroundWatcherId }); backgroundWatcherId = null; }
    if (trackingInterval) { clearInterval(trackingInterval); trackingInterval = null; }
}

// ── DOMContentLoaded ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    if (window.Capacitor?.isNativePlatform()) {
        const { Geolocation, Camera, BackgroundGeolocation } = Capacitor.Plugins;
        await Geolocation.requestPermissions(); await Camera.requestPermissions();
        if (BackgroundGeolocation.requestPermissions) await BackgroundGeolocation.requestPermissions();
    }
    const savedRideId = sessionStorage.getItem('activeRideId');
    if (savedRideId && !currentRideId) {
        fetch('/SRMT/public/driver/?api=refresh').then(r => r.json()).then(data => {
            if (!Array.isArray(data)) return;
            const ride = data.find(v => String(v.ServiceID) === savedRideId);
            if (ride && [1,2,5,3].includes(parseInt(ride.status_id))) { startLiveTracking(savedRideId); }
            else { sessionStorage.removeItem('activeRideId'); }
        }).catch(() => {});
    }
});
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible') {
        const savedRideId = sessionStorage.getItem('activeRideId');
        if (savedRideId && !currentRideId) startLiveTracking(savedRideId);
    }
});

// ── External links ────────────────────────────────────────────────────────
async function openExternal(url) {
    try { if (window.Capacitor?.Plugins?.App?.openUrl) { await Capacitor.Plugins.App.openUrl({ url }); return; } } catch(e) {}
    window.open(url, '_system');
}
function openWaze(address) { openExternal('waze://?q=' + encodeURIComponent(address) + '&navigate=yes'); }
document.addEventListener('click', e => {
    const a = e.target.closest('a[target="_blank"]');
    if (!a) return;
    const href = a.getAttribute('href');
    if (!href || href === '#') return;
    if (window.Capacitor?.isNativePlatform?.()) { e.preventDefault(); openExternal(href); }
});

// ── Status UI ─────────────────────────────────────────────────────────────
const STEP_MAP = {0:0, 1:1, 2:2, 5:3, 3:4, 4:5};
function updateButtonUI(status) {
    const btn = document.getElementById('btnDynamicAction'); if (!btn) return;
    btn.className = 'action-btn';
    const map = {
        0: ['status-btn-0', '<i class="bi bi-car-front-fill me-2"></i>START PICKUP',    false],
        1: ['status-btn-1', '<i class="bi bi-geo-alt-fill me-2"></i>ARRIVED',           false],
        2: ['status-btn-2', '<i class="bi bi-person-check-fill me-2"></i>WITH CLIENT',  false],
        5: ['status-btn-5', '<i class="bi bi-play-circle-fill me-2"></i>START TRIP',    false],
        3: ['status-btn-3', '<i class="bi bi-stop-circle-fill me-2"></i>FINISH',        false],
    };
    const def = ['status-btn-4', '<i class="bi bi-check-circle-fill me-2"></i>COMPLETED', true];
    const [cls, html, dis] = map[parseInt(status)] ?? def;
    btn.classList.add(cls); btn.innerHTML = html; btn.disabled = dis;

    // Stepper
    const currentStep = STEP_MAP[parseInt(status)] ?? 0;
    document.querySelectorAll('#statusStepper .step-node').forEach((node, i) => {
        node.classList.toggle('done',   i < currentStep);
        node.classList.toggle('active', i === currentStep && parseInt(status) !== 4);
    });
    document.querySelectorAll('#statusStepper .step-line').forEach((line, i) => {
        line.classList.toggle('done', i < currentStep);
    });

    // Darken modal when completed
    const mc = document.getElementById('detailsModal').querySelector('.modal-content');
    mc.classList.toggle('is-completed', parseInt(status) === 4);
}
function updateStatusBackend(rideId, nextStatus) {
    const fd = new FormData(); fd.append('ride_id', rideId); fd.append('status', nextStatus);
    fetch('/SRMT/public/api/status-update.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => {
            if (!d.success) return;
            localTripStatus[rideId] = nextStatus; updateButtonUI(nextStatus);
            if (parseInt(nextStatus) === 4) {
                fetch('/SRMT/public/api/final-trip-report.php?ride_id=' + rideId);
                setTimeout(() => { bootstrap.Modal.getInstance(document.getElementById('detailsModal')).hide(); fetchLatestRides(); }, 1200);
            }
        });
}
document.getElementById('btnDynamicAction').addEventListener('click', function () {
    if (!currentRideData) return;
    const rideId = currentRideData.id;
    const cur = parseInt(localTripStatus[rideId]);
    const nextMap = {0:1, 1:2, 2:5, 5:3, 3:4};
    const nextStatus = nextMap[cur];
    if (nextStatus === undefined) return;
    if (cur === 0) {
        if (!confirm('Start pickup and open GPS?')) return;
        if (currentRideData.clientnumber) {
            const cNum = currentRideData.clientnumber.replace(/[^0-9]/g, '');
            if (cNum.length > 7) {
                const trackLink = window.location.origin + '/track.php?id=' + rideId;
                openExternal('https://wa.me/' + cNum + '?text=' + encodeURIComponent('Hello! Your driver is on the way. Track here: ' + trackLink));
            }
        }
        startLiveTracking(rideId); openWaze(currentRideData.start);
    } else if (cur === 1) { if (!confirm('Confirm you have arrived?')) return; }
    else if (cur === 2) { if (!confirm('Confirm you are with the client?')) return; }
    else if (cur === 5) { if (!confirm('Start trip to destination?')) return; openWaze(currentRideData.end); }
    else if (cur === 3) { if (!confirm('Finish service?')) return; stopLiveTracking(); }
    updateStatusBackend(rideId, nextStatus);
});

// ── Render ────────────────────────────────────────────────────────────────
function formatDate(d) { return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0'); }
function filterTrips(filter) {
    currentFilter = filter;
    const t = new Date(), y = new Date(t), tm = new Date(t);
    y.setDate(t.getDate()-1); tm.setDate(t.getDate()+1);
    const map = { yesterday: formatDate(y), today: formatDate(t), tomorrow: formatDate(tm) };
    renderList(viagens.filter(v => v.serviceDate === map[filter]));
}
function renderList(data) {
    const el = document.getElementById('rideList'); if (!el) return; el.innerHTML = '';
    if (data.length === 0) {
        el.innerHTML = "<div class='empty-state'><i class='bi bi-calendar-x'></i><p>No services.</p></div>";
        return;
    }
    data.forEach(v => {
        const status = localTripStatus[String(v.ServiceID)] ?? parseInt(v.status_id) ?? 0;
        const isDone = status === 4;
        const stype  = parseInt(v.serviceType) || 0;
        const isPriv = stype === 1;
        const badgeCls  = isPriv ? 'badge-private' : 'badge-shared';
        const badgeText = isPriv ? 'Private' : 'Shared';
        const dotCls    = 'dot-' + (isDone ? 4 : (status >= 1 ? status : 0));

        let extraBadges = '';
        if (v.partner_id && v.partner_id > 0) {
            if (v.AgencyName) extraBadges += `<span class="ride-badge" style="background:rgba(59,130,246,.12);color:#3b82f6;border:1px solid rgba(59,130,246,.2);"><i class="bi bi-building-fill"></i> ${v.AgencyName}</span>`;
            extraBadges += `<span class="ride-badge ${v.has_key == 1 ? 'sb-green' : 'sb-red'} ride-badge"><i class="bi bi-key-fill"></i> ${v.has_key == 1 ? 'Key' : 'No Key'}</span>`;
        }

        el.innerHTML += `
<div class="ride-card open-modal${isDone ? ' is-done' : ''}"
    data-stype="${stype}"
    data-id="${v.ServiceID}"
    data-start="${v.serviceStartPoint}"
    data-end="${v.serviceTargetPoint}"
    data-time="${v.serviceStartTime.substr(0,5)}"
    data-date="${v.serviceDate}"
    data-paxadt="${v.paxADT||0}"
    data-paxchd="${v.paxCHD||0}"
    data-paxbby="${v.paxBBY||0}"
    data-flight="${v.FlightNumber||''}"
    data-client="${v.NomeCliente||''}"
    data-clientnumber="${v.ClientNumber||''}"
    data-price="${v.total_price||''}"
    data-haskey="${v.has_key||0}"
    data-partnerid="${v.partner_id||0}"
    data-agencyname="${v.AgencyName||''}"
    data-agencyphone="${v.AgencyPhone||''}">
    <div class="ride-top">
        <div>
            <div class="ride-time">${v.serviceStartTime.substr(0,5)}</div>
            <div class="ride-client-name">${v.NomeCliente || ''}</div>
        </div>
        <div class="d-flex align-items-start gap-2">
            <div class="badge-row">
                <span class="ride-badge ${badgeCls}">${badgeText}</span>
                ${extraBadges}
            </div>
            <div class="status-dot ${dotCls} mt-1"></div>
        </div>
    </div>
    <div class="route-line">
        ${(v.is_aggregate_master == 1 && Array.isArray(v.stops) && v.stops.length > 0)
            ? (() => {
                const dots = v.stops.map((s,i) => {
                    const isP  = s.type === 'pickup';
                    const isLast = i === v.stops.length - 1;
                    return `<div class="rdot ${isP ? 'rdot-pickup' : 'rdot-dropoff'}"></div>${isLast ? '' : '<div class="rdot-line"></div>'}`;
                }).join('');
                const pts  = v.stops.map(s => {
                    const lbl = s.type === 'pickup' ? 'Recolha' : 'Entrega';
                    const sub = [s.time ? s.time.substring(0,5) : '', s.client || '', s.pax ? s.pax + ' pax' : ''].filter(Boolean).join(' · ');
                    return `<div class="rt-point"><div class="rt-label">${lbl}</div>${s.location || ''}${sub ? '<div style="font-size:10px;color:#71717a;margin-top:1px">'+sub+'</div>' : ''}</div>`;
                }).join('');
                return `<div class="route-dots">${dots}</div><div class="route-text">${pts}</div>`;
              })()
            : `<div class="route-dots">
                <div class="rdot rdot-pickup"></div>
                <div class="rdot-line"></div>
                <div class="rdot rdot-dropoff"></div>
               </div>
               <div class="route-text">
                <div class="rt-point"><div class="rt-label">From</div>${v.serviceStartPoint}</div>
                <div class="rt-point"><div class="rt-label">To</div>${v.serviceTargetPoint}</div>
               </div>`
        }
    </div>
    ${v.total_price > 0 ? `<div class="price-badge"><i class="bi bi-cash-coin"></i>${parseFloat(v.total_price).toFixed(2)}€</div>` : ''}
</div>`;
    });

    document.querySelectorAll('.open-modal').forEach(card => {
        card.addEventListener('click', () => {
            const d = card.dataset; currentRideData = d;
            const m = document.getElementById('detailsModal');
            m.querySelector('#modalIdDisplay').textContent = d.id;
            m.querySelector('#modalPickup').textContent   = d.start;
            m.querySelector('#modalDropoff').textContent  = d.end;
            m.querySelector('#modalADT').textContent      = d.paxadt;
            m.querySelector('#modalCHD').textContent      = d.paxchd;
            m.querySelector('#modalClient').textContent   = d.client || 'Client';

            const wa = document.getElementById('whatsappContainer'); wa.innerHTML = ''; wa.style.display = 'none';
            if (d.clientnumber && d.clientnumber.replace(/[^0-9]/g,'').length > 7) {
                wa.style.display = 'flex';
                wa.innerHTML = `<a href="https://wa.me/${d.clientnumber.replace(/[^0-9]/g,'')}" target="_blank" class="wa-icon-btn"><i class="bi bi-whatsapp"></i></a>`;
            }

            const waAloj = document.getElementById('whatsappAlojamento');
            if (d.partnerid && d.partnerid > 0 && d.agencyphone) {
                const ph = '351' + String(d.agencyphone).replace(/[^0-9]/g,'');
                waAloj.href = 'https://wa.me/' + ph + '?text=' + encodeURIComponent(`Leaving the airport 🛬\nClient: ${d.client}\nDestination: ${d.end}`);
                waAloj.style.display = 'flex';
            } else { waAloj.style.display = 'none'; }

            const bc = document.getElementById('modalBadgesContainer'); bc.innerHTML = '';
            if (d.price && parseFloat(d.price) > 0) bc.innerHTML += `<span class="strip-badge sb-green"><i class="bi bi-currency-euro"></i>${parseFloat(d.price).toFixed(2)}</span>`;
            if (d.partnerid && d.partnerid > 0) {
                if (d.agencyname) bc.innerHTML += `<span class="strip-badge sb-blue"><i class="bi bi-building"></i>${d.agencyname}</span>`;
                bc.innerHTML += `<span class="strip-badge ${d.haskey==1?'sb-green':'sb-red'}"><i class="bi bi-key-fill"></i>${d.haskey==1?'With Key':'No Key'}</span>`;
            }

            const tl = document.getElementById('trackFlightLink');
            if (d.flight && d.flight.trim()) {
                tl.style.display = 'inline-flex'; document.getElementById('modalFlight').textContent = d.flight;
                tl.href = 'https://www.flightradar24.com/data/flights/' + d.flight.replace(/\s/g,'');
            } else { tl.style.display = 'none'; }

            const bebes = parseInt(d.paxbby || 0, 10);
            const paxPill = document.getElementById('paxPill');
            const bbyPart = document.getElementById('modalBBYPart');
            if (bebes > 0) {
                document.getElementById('modalBBY').textContent = bebes;
                bbyPart.style.display = 'inline';
                paxPill.classList.add('has-baby');
            } else {
                bbyPart.style.display = 'none';
                paxPill.classList.remove('has-baby');
            }

            updateButtonUI(localTripStatus[d.id]);
            new bootstrap.Modal(m).show();
        });
    });
}

document.querySelectorAll('.filter-btn').forEach(b => b.addEventListener('click', function () {
    document.querySelectorAll('.filter-btn').forEach(x => x.classList.remove('active'));
    this.classList.add('active'); filterTrips(this.dataset.filter);
}));
filterTrips('today');

// ── Airport sign ──────────────────────────────────────────────────────────
const airportOverlay = document.getElementById('airportOverlay');
const nameEl = document.getElementById('airportClientName');
let currentFontSize = 15;
document.getElementById('btnAirportMode').onclick = async () => {
    const rawName = currentRideData?.client || 'CLIENT';
    nameEl.innerHTML = rawName.trim().split(/\s+/).map(w => `<span class="name-part">${w}</span>`).join(' ');
    document.getElementById('airportFlight').textContent = currentRideData?.flight || '';
    currentFontSize = (rawName.trim().split(/\s+/).length <= 2) ? 18 : 12;
    nameEl.style.fontSize = currentFontSize + 'vw';
    airportOverlay.style.display = 'flex';
    if (document.documentElement.requestFullscreen) document.documentElement.requestFullscreen();
    if ('wakeLock' in navigator) { try { wakeLock = await navigator.wakeLock.request('screen'); } catch(e) {} }
};
function updateZoom(delta) {
    currentFontSize = Math.min(Math.max(currentFontSize + delta, 5), 50);
    const unit = airportOverlay.classList.contains('landscape-mode') ? 'vh' : 'vw';
    nameEl.style.fontSize = currentFontSize + unit;
}
document.getElementById('btnZoomIn').onclick  = e => { e.stopPropagation(); updateZoom(2); };
document.getElementById('btnZoomOut').onclick = e => { e.stopPropagation(); updateZoom(-2); };
document.getElementById('closeAirportMode').onclick = () => {
    airportOverlay.style.display = 'none';
    if (document.exitFullscreen) document.exitFullscreen().catch(() => {});
    if (wakeLock) { wakeLock.release().catch(() => {}); wakeLock = null; }
};
document.getElementById('rotateScreenBtn').onclick = () => {
    airportOverlay.classList.toggle('landscape-mode');
    const isLandscape = airportOverlay.classList.contains('landscape-mode');
    nameEl.style.fontSize = currentFontSize + (isLandscape ? 'vh' : 'vw');
};
document.addEventListener('visibilitychange', async () => {
    if (wakeLock === null && document.visibilityState === 'visible' && airportOverlay.style.display !== 'none') {
        if ('wakeLock' in navigator) { try { wakeLock = await navigator.wakeLock.request('screen'); } catch(e) {} }
    }
});

// ── Camera ────────────────────────────────────────────────────────────────
const video = document.getElementById('cameraStream'), canvas = document.getElementById('photoCanvas');
const camOverlay = document.getElementById('cameraOverlay'), loading = document.getElementById('cameraLoading');
const btnSend = document.getElementById('btnConfirmSend');
function stopCameraStream() { if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; } if (locationWatcher) navigator.geolocation.clearWatch(locationWatcher); }
function closeCameraOverlay() { stopCameraStream(); camOverlay.style.display = 'none'; cameraZoomLevel = 1; video.style.transform = 'scale(1)'; }
function applyCameraZoom(delta) { cameraZoomLevel = Math.min(Math.max(cameraZoomLevel + delta, 1), 8); video.style.transform = `scale(${cameraZoomLevel})`; }
function updateCameraUI(state) {
    const cCap = document.getElementById('stepCaptureControls'), cConf = document.getElementById('stepConfirmControls');
    if (state==='loading')  { loading.style.display='block'; video.style.display='none'; canvas.style.display='none'; cCap.classList.add('d-none'); cConf.classList.add('d-none'); }
    else if (state==='capture') { loading.style.display='none'; video.style.display='block'; canvas.style.display='none'; cCap.classList.remove('d-none'); cCap.classList.add('d-flex'); cConf.classList.add('d-none'); }
    else if (state==='review')  { loading.style.display='none'; video.style.display='none'; canvas.style.display='block'; cCap.classList.add('d-none'); cConf.classList.remove('d-none'); cConf.classList.add('d-flex'); btnSend.disabled=false; }
}
async function startCamera() {
    stopCameraStream(); cameraZoomLevel = 1; video.style.transform = 'scale(1)';
    camOverlay.style.display = 'flex'; updateCameraUI('loading');
    try { stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: currentFacingMode } }); video.srcObject = stream; video.onloadedmetadata = () => updateCameraUI('capture'); }
    catch(e) { alert('Camera error: ' + e.message); closeCameraOverlay(); }
    if ('geolocation' in navigator) locationWatcher = navigator.geolocation.watchPosition(p => { currentLat = p.coords.latitude; currentLng = p.coords.longitude; });
}
document.getElementById('uploadNoShow').onclick  = () => { currentMode = 'noshow';  document.getElementById('cameraInstruction').textContent = 'Photograph No-Show';  startCamera(); };
document.getElementById('uploadVoucher').onclick = () => { currentMode = 'voucher'; document.getElementById('cameraInstruction').textContent = 'Photograph Voucher'; startCamera(); };
document.getElementById('btnRotateCamera').onclick = () => { currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment'; startCamera(); };
document.getElementById('btnCapture').onclick = () => {
    const vw = video.videoWidth, vh = video.videoHeight;
    const sw = vw/cameraZoomLevel, sh = vh/cameraZoomLevel;
    canvas.width = vw; canvas.height = vh;
    canvas.getContext('2d').drawImage(video, (vw-sw)/2, (vh-sh)/2, sw, sh, 0, 0, vw, vh);
    updateCameraUI('review');
};
document.getElementById('btnRetake').onclick = () => updateCameraUI('capture');
document.getElementById('btnCameraZoomIn').onclick  = e => { e.stopPropagation(); applyCameraZoom(.5); };
document.getElementById('btnCameraZoomOut').onclick = e => { e.stopPropagation(); applyCameraZoom(-.5); };
const cameraViewArea = document.getElementById('cameraViewArea');
cameraViewArea.addEventListener('touchstart', e => { if (e.touches.length === 2) pinchInitialDistance = Math.hypot(e.touches[0].clientX-e.touches[1].clientX, e.touches[0].clientY-e.touches[1].clientY); }, { passive: true });
cameraViewArea.addEventListener('touchmove', e => {
    if (e.touches.length === 2 && pinchInitialDistance > 0) {
        const dist = Math.hypot(e.touches[0].clientX-e.touches[1].clientX, e.touches[0].clientY-e.touches[1].clientY);
        cameraZoomLevel = Math.min(Math.max(cameraZoomLevel + (dist - pinchInitialDistance) * .01, 1), 8);
        video.style.transform = `scale(${cameraZoomLevel})`; pinchInitialDistance = dist;
    }
}, { passive: true });
btnSend.onclick = () => {
    btnSend.disabled = true;
    const img = canvas.toDataURL('image/jpeg');
    const url = currentMode === 'voucher' ? '/SRMT/public/admin/upload-voucher.php' : '/SRMT/public/admin/upload-no-show.php';
    fetch(url, { method: 'POST', body: JSON.stringify({ trip_id: currentRideData.id, image_data: img, lat: currentLat, lng: currentLng }) })
        .then(r => r.json()).then(d => {
            if (d.success) { alert(d.message); closeCameraOverlay(); if (currentMode === 'noshow') updateStatusBackend(currentRideData.id, 4); }
            else { btnSend.disabled = false; }
        });
};
document.getElementById('profilePhotoInput').onchange = e => { const [f] = e.target.files; if (f) document.getElementById('currentProfilePhoto').src = URL.createObjectURL(f); };
</script>
</body>
</html>
