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
$hasPhoto     = isset($_SESSION['profile_photo_path']) && $_SESSION['profile_photo_path'] !== '';
$userPhotoSrc = $hasPhoto ? '/SRMT/' . ltrim((string) $_SESSION['profile_photo_path'], '/') : '';
// JS translation bundle (all dynamic strings used in JavaScript)
$T_js = [
    'today'            => t('drv.today'),
    'this_week'        => t('drv.this_week'),
    'yesterday'        => t('drv.yesterday'),
    'tomorrow'         => t('drv.tomorrow'),
    'loading'          => t('drv.loading'),
    'no_services'      => t('drv.no_services'),
    'private'          => t('drv.private'),
    'shared'           => t('drv.shared'),
    'with_key'         => t('drv.with_key'),
    'no_key'           => t('drv.no_key'),
    'from'             => t('drv.from'),
    'to'               => t('drv.to'),
    'pickup'           => t('drv.pickup'),
    'dropoff'          => t('drv.dropoff'),
    'btn_start_pickup' => t('drv.btn_start_pickup'),
    'btn_arrived'      => t('drv.btn_arrived'),
    'btn_with_client'  => t('drv.btn_with_client'),
    'btn_start_trip'   => t('drv.btn_start_trip'),
    'btn_finish'       => t('drv.btn_finish'),
    'btn_completed'    => t('drv.btn_completed'),
    'btn_navigate'     => t('drv.btn_navigate'),
    'btn_at_stop'      => t('drv.btn_at_stop'),
    'btn_next_stop'    => t('drv.btn_next_stop'),
    'btn_finish_svc'   => t('drv.btn_finish_svc'),
    'confirm_start'    => t('drv.confirm_start'),
    'confirm_arrived'  => t('drv.confirm_arrived'),
    'confirm_client'   => t('drv.confirm_client'),
    'confirm_trip'     => t('drv.confirm_trip'),
    'confirm_finish'   => t('drv.confirm_finish'),
    'confirm_all'      => t('drv.confirm_all'),
    'wa_on_way'        => t('drv.wa_on_way'),
    'wa_leaving'       => t('drv.wa_leaving'),
    'wa_dest'          => t('drv.wa_dest'),
    'photo_noshow'     => t('drv.photo_noshow'),
    'photo_voucher'    => t('drv.photo_voucher'),
    'select_ride'      => t('drv.select_ride'),
    'client_n'         => t('drv.client_n'),
    'retake'           => t('drv.retake'),
    'send'             => t('drv.send'),
    'save_note'        => t('drv.save_note'),
    'saved'            => t('drv.saved'),
    'add_obs'          => t('drv.add_obs'),
    'edit_obs'         => t('drv.edit_obs'),
];
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
            padding-bottom: calc(94px + var(--safe-bottom));
            -webkit-tap-highlight-color: transparent;
        }

        /* ── Header ──────────────────────────────────────────────────────── */
        .app-header {
            position: sticky; top: 0; z-index: 200;
            display: flex; align-items: center; justify-content: space-between;
            padding: calc(16px + var(--safe-top)) 20px 12px;
            background: var(--bg);
            background: color-mix(in srgb, var(--bg) 82%, transparent);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
        }
        .hdr-greeting { display: flex; flex-direction: column; gap: 1px; min-width: 0; }
        .hdr-hello {
            font-family: var(--font-display); font-size: 1.35rem; font-weight: 800;
            color: var(--text-1); line-height: 1.1; letter-spacing: -.01em;
        }
        .hdr-sub { font-size: .76rem; color: var(--text-2); font-weight: 500; }
        .brand-logo { height: 28px; width: auto; }
        .header-right { display: flex; align-items: center; gap: 12px; }
        .theme-btn {
            width: 36px; height: 36px; border-radius: 50%;
            background: var(--bg-raised); border: 1px solid var(--border);
            color: var(--text-2); display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: .95rem; transition: color .2s, background .2s;
        }
        .theme-btn:hover { color: var(--text-1); background: var(--bg-input); }
        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            object-fit: cover; border: 2px solid var(--border-strong);
            cursor: pointer; transition: opacity .15s, transform .15s;
        }
        .user-avatar:active { opacity: .75; transform: scale(.92); }
        .user-avatar-default {
            display: flex; align-items: center; justify-content: center;
            padding: 0; font-size: 1.1rem;
            background: var(--accent-soft); color: var(--accent);
        }

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

        /* ── Bottom nav — floating pill ──────────────────────────────────── */
        .bottom-nav {
            position: fixed;
            bottom: calc(12px + var(--safe-bottom)); left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 28px); max-width: 440px;
            height: 62px;
            background: rgba(255,255,255,0.82);
            backdrop-filter: blur(22px) saturate(180%);
            -webkit-backdrop-filter: blur(22px) saturate(180%);
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: 24px;
            box-shadow: 0 10px 34px rgba(0,0,0,0.14), 0 2px 8px rgba(0,0,0,0.06);
            display: flex; align-items: stretch; gap: 2px; padding: 6px;
            z-index: 500;
        }
        [data-bs-theme="dark"] .bottom-nav {
            background: rgba(20,28,46,0.86);
            border-color: rgba(255,255,255,0.08);
            box-shadow: 0 10px 34px rgba(0,0,0,0.5), 0 2px 8px rgba(0,0,0,0.3);
        }
        .nav-item {
            flex: 1; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            color: var(--text-3); text-decoration: none;
            font-size: .64rem; font-weight: 600; letter-spacing: .01em;
            gap: 3px; border-radius: 18px;
            transition: color .2s, background .2s;
            -webkit-tap-highlight-color: transparent;
        }
        .nav-item i { font-size: 1.25rem; }
        .nav-item:active { transform: scale(.94); }
        .nav-item.active { color: var(--accent); background: var(--accent-soft); }
        [data-bs-theme="dark"] .nav-item.active { background: rgba(59,130,246,.16); }
        .nav-item.danger  { color: var(--text-3); }
        .nav-item.danger:active { color: var(--danger); }

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
        /* Scroll hint — overlays bottom of the sheet, fades content + bouncing chevron */
        #detailsModal .scroll-hint {
            position: absolute; left: 0; right: 0; bottom: 0; height: 64px;
            pointer-events: none; z-index: 5;
            display: flex; align-items: flex-end; justify-content: center; padding-bottom: 10px;
            background: linear-gradient(to top, var(--bg-card) 35%, transparent);
            opacity: 1; transition: opacity .25s ease;
        }
        #detailsModal .scroll-hint.hidden { opacity: 0; }
        #detailsModal .scroll-hint .chev {
            font-size: 20px; color: var(--accent);
            background: var(--bg-card); border: 1px solid var(--border);
            width: 30px; height: 30px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,.12);
            animation: scrollHintBounce 1.5s ease-in-out infinite;
        }
        @keyframes scrollHintBounce {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(5px); }
        }
        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }
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
        .util-btn.wa-bg   { background: #25D366; border-color: #25D366; color: #fff; }

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
            width: 100%; padding: 19px; font-size: 1.15rem; font-weight: 800;
            font-family: var(--font-display); border-radius: 18px; border: none;
            color: #fff; text-transform: uppercase; letter-spacing: .8px;
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

        /* ── Multi-stop flow ────────────────────────────────────────────── */
        #multiStopSection { display: none; }
        /* Multi-stop stepper — reuses .step-node/.step-dot/.step-label/.step-line, adds scroll */
        .ms-stepper {
            display: flex; align-items: center; padding: 0 20px 16px; gap: 0;
            overflow-x: auto; scrollbar-width: none; -webkit-overflow-scrolling: touch;
        }
        .ms-stepper::-webkit-scrollbar { display: none; }
        .ms-stepper .step-node { flex: none; flex-shrink: 0; }
        .ms-stepper .step-line { flex: none; min-width: 22px; flex-shrink: 0; }
        /* Current stop info */
        .ms-stop-label { font-size: 15px; font-weight: 800; color: var(--text-1); text-align: center; margin-bottom: 4px; }
        .ms-stop-location { font-size: 12px; color: var(--text-2); text-align: center; margin-bottom: 16px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        /* Route card highlighting */
        .route-point.stop-done { opacity: 0.35; }
        .route-point.stop-current .route-indicator { box-shadow: 0 0 0 3px var(--accent-glow); }
        .route-point.stop-current .route-loc-label { color: var(--accent); font-weight: 800; }
        /* Multi-client split (shared rides) */
        #multiClientSection { display: none; }
        /* Multi-client: one full-width row per client (full info each) */
        .client-split { display: grid; grid-template-columns: 1fr; gap: 10px; }
        .client-split.cols-1,
        .client-split.cols-3 { grid-template-columns: 1fr; }
        .client-split .client-col-name { white-space: normal; }
        .client-col {
            background: var(--bg-raised); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 11px 12px; min-width: 0;
        }
        .client-col-idx { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--text-3); margin-bottom: 3px; }
        .client-col-name { font-size: 14px; font-weight: 800; color: var(--text-1); line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .client-col-meta { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 7px; }
        .client-col-chip {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 10px; font-weight: 700; padding: 3px 7px; border-radius: 7px;
            background: rgba(0,0,0,0.05); color: var(--text-2);
        }
        [data-bs-theme="dark"] .client-col-chip { background: rgba(255,255,255,0.06); }
        .client-col-chip.flight { background: rgba(37,99,235,.12); color: var(--accent); }
        .client-col-actions { display: flex; gap: 6px; margin-top: 9px; }
        .client-col-btn {
            flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 4px;
            font-size: 11px; font-weight: 700; padding: 7px 4px; border-radius: 9px;
            border: none; cursor: pointer; text-decoration: none;
        }
        .client-col-btn.wa     { background: #25D366; color: #fff; }
        .client-col-btn.sign   { background: var(--text-1); color: var(--bg-card); }
        .client-col-btn.flight { background: rgba(37,99,235,.15); color: var(--accent); }
        /* Notes */
        .admin-note-banner {
            display: flex; align-items: flex-start; gap: 9px; margin-bottom: 12px;
            padding: 10px 12px; border-radius: var(--radius);
            background: var(--warning-soft); border: 1px solid rgba(217,119,6,.25); color: var(--warning);
        }
        .admin-note-banner i { font-size: 15px; margin-top: 1px; }
        .admin-note-label { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; opacity: .8; }
        #adminNoteText { font-size: 13px; font-weight: 600; color: var(--text-1); line-height: 1.35; }
        .note-label { display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; color: var(--text-2); margin-bottom: 6px; }
        .note-textarea {
            width: 100%; resize: vertical; font-family: var(--font-body); font-size: 13px;
            padding: 9px 11px; border-radius: var(--radius); color: var(--text-1);
            background: var(--bg-input); border: 1px solid var(--border);
        }
        .note-textarea:focus { outline: none; border-color: var(--accent); }
        .note-save-btn {
            margin-top: 8px; width: 100%; display: flex; align-items: center; justify-content: center; gap: 6px;
            font-size: 12px; font-weight: 700; padding: 9px; border-radius: var(--radius);
            border: 1px solid var(--accent); background: var(--accent-soft); color: var(--accent); cursor: pointer;
        }
        .note-save-btn:disabled { opacity: .6; }
        .note-save-btn.saved { border-color: var(--success); background: var(--success-soft); color: var(--success); }

        /* ── Profile photo modal ─────────────────────────────────────────── */
        .profile-photo-preview {
            width: 110px; height: 110px; border-radius: 50%; margin: 0 auto;
            display: flex; align-items: center; justify-content: center; overflow: hidden;
            background: var(--accent-soft); border: 3px solid var(--border-strong);
        }
        .profile-photo-preview img { width: 100%; height: 100%; object-fit: cover; }
        .profile-photo-preview i { font-size: 3.2rem; color: var(--accent); }
        .photo-choice-btn {
            flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            padding: 11px 0; border-radius: 12px; font-size: .85rem; font-weight: 600;
            border: 1px solid var(--border-strong); background: var(--bg-raised);
            color: var(--text-1); cursor: pointer; transition: opacity .15s;
        }
        .photo-choice-btn:active { opacity: .7; }

        /* ── IN / OUT card label ─────────────────────────────────────────── */
        .io-badge {
            font-size: .6rem; font-weight: 800; letter-spacing: .06em;
            padding: 3px 8px; border-radius: 7px; text-transform: uppercase;
            display: inline-flex; align-items: center; gap: 3px;
        }
        .io-in  { background: var(--accent-soft); color: var(--accent); border: 1px solid rgba(37,99,235,.25); }
        .io-out { background: var(--danger-soft);  color: var(--danger);  border: 1px solid rgba(220,38,38,.25); }

        /* ── Driver note: inline display + accordion editor ──────────────── */
        .driver-note-inline {
            display: flex; align-items: flex-start; gap: 9px; margin-bottom: 12px;
            padding: 10px 12px; border-radius: var(--radius);
            background: var(--accent-soft); border: 1px solid rgba(37,99,235,.18);
        }
        .driver-note-inline i { font-size: 15px; color: var(--accent); margin-top: 1px; }
        .driver-note-inline-label { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: var(--accent); opacity: .85; }
        #driverNoteInlineText { font-size: 13px; font-weight: 600; color: var(--text-1); line-height: 1.35; }
        .obs-accordion-hdr {
            width: 100%; display: flex; align-items: center; justify-content: space-between;
            padding: 12px 14px; border-radius: var(--radius);
            background: var(--bg-raised); border: 1px solid var(--border);
            color: var(--text-1); font-size: 13px; font-weight: 700; cursor: pointer;
        }
        .obs-chevron { transition: transform .25s; color: var(--text-2); }
        .obs-accordion-hdr[aria-expanded="true"] .obs-chevron { transform: rotate(180deg); }
        .obs-accordion-body { display: none; margin-top: 10px; }
        .obs-accordion-body.open { display: block; }
        .obs-accordion-body .note-textarea { margin-bottom: 8px; }
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
    <div style="position:absolute;top:max(20px,env(safe-area-inset-top));left:0;width:100%;display:flex;align-items:center;justify-content:center;z-index:10;">
        <button onclick="closeCameraOverlay()" style="position:absolute;left:16px;background:rgba(0,0,0,.35);border:none;color:white;width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:18px;backdrop-filter:blur(6px);"><i class="bi bi-x-lg"></i></button>
        <span style="color:white;font-weight:600;text-shadow:0 2px 4px rgba(0,0,0,.8);" id="cameraInstruction">Photograph</span>
    </div>
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
            <button class="btn-circle-action" id="btnPickGallery" onclick="document.getElementById('cameraGalleryInput').click()"><i class="bi bi-images"></i></button>
            <button class="camera-btn" id="btnCapture"><div class="camera-btn-inner"></div></button>
            <button class="btn-circle-action" id="btnRotateCamera"><i class="bi bi-arrow-repeat"></i></button>
        </div>
        <div id="stepConfirmControls" class="d-none d-flex gap-3 w-100 justify-content-center">
            <button class="btn btn-light rounded-pill px-4 py-2 fw-bold" id="btnRetake"><?= t('drv.retake') ?></button>
            <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold" id="btnConfirmSend"><?= t('drv.send') ?></button>
        </div>
    </div>
</div>

<!-- ── Trip Finish Confirmation Modal ─────────────────────────────────── -->
<div id="tripFinishOverlay" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,.55);backdrop-filter:blur(6px);align-items:flex-end;justify-content:center;">
    <div onclick="event.stopPropagation()" style="background:#fff;border-radius:28px 28px 0 0;width:100%;max-width:480px;padding:24px 20px calc(24px + env(safe-area-inset-bottom));animation:slideUp .28s cubic-bezier(.32,1.2,.56,1);">

        <!-- handle -->
        <div style="width:36px;height:4px;background:#e2e8f0;border-radius:4px;margin:0 auto 20px;"></div>

        <!-- header -->
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;">
            <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#22c55e,#16a34a);display:flex;align-items:center;justify-content:center;flex-shrink:0;box-shadow:0 4px 12px rgba(34,197,94,.35);">
                <i class="bi bi-flag-fill" style="color:#fff;font-size:1.25rem;"></i>
            </div>
            <div>
                <div style="font-weight:800;font-size:1.1rem;color:#0f172a;"><?= t('drv.finish_modal_title') ?></div>
                <div style="font-size:.78rem;color:#64748b;margin-top:1px;"><?= t('drv.finish_modal_sub') ?></div>
            </div>
        </div>

        <!-- duration badge -->
        <div id="finishModalDurationWrap" style="display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:10px 14px;margin-bottom:14px;display:flex;align-items:center;gap:8px;">
            <i class="bi bi-clock-fill" style="color:#16a34a;font-size:.9rem;"></i>
            <span style="font-size:.85rem;color:#166534;font-weight:600;" id="finishModalDuration"></span>
        </div>

        <!-- trip card -->
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:16px;margin-bottom:14px;">

            <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                <div style="width:32px;height:32px;border-radius:10px;background:#eff6ff;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-person-fill" style="color:#2563eb;font-size:.85rem;"></i>
                </div>
                <span id="finishModalClient" style="font-weight:700;font-size:.95rem;color:#0f172a;"></span>
            </div>

            <div style="display:flex;flex-direction:column;gap:0;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
                <div style="display:flex;align-items:flex-start;gap:10px;padding:11px 14px;">
                    <div style="margin-top:3px;width:10px;height:10px;border-radius:50%;background:#22c55e;flex-shrink:0;"></div>
                    <span id="finishModalStart" style="font-size:.83rem;color:#374151;line-height:1.4;"></span>
                </div>
                <div style="height:1px;background:#f1f5f9;margin-left:34px;"></div>
                <div style="display:flex;align-items:flex-start;gap:10px;padding:11px 14px;">
                    <div style="margin-top:3px;width:10px;height:10px;border-radius:50%;background:#f59e0b;flex-shrink:0;"></div>
                    <span id="finishModalEnd" style="font-size:.83rem;color:#374151;line-height:1.4;"></span>
                </div>
            </div>

            <div style="display:flex;gap:12px;margin-top:12px;">
                <div style="display:flex;align-items:center;gap:6px;font-size:.8rem;color:#64748b;background:#eff6ff;padding:5px 10px;border-radius:8px;">
                    <i class="bi bi-people-fill" style="color:#2563eb;font-size:.75rem;"></i>
                    <span id="finishModalPax"></span>
                </div>
                <div id="finishModalPriceWrap" style="display:flex;align-items:center;gap:6px;font-size:.8rem;color:#64748b;background:#f0fdf4;padding:5px 10px;border-radius:8px;">
                    <i class="bi bi-cash-coin" style="color:#16a34a;font-size:.75rem;"></i>
                    <span id="finishModalPrice" style="font-weight:700;color:#166534;"></span>
                </div>
            </div>
        </div>

        <!-- obs -->
        <textarea id="finishModalObs" rows="2"
            placeholder="<?= t('drv.obs_placeholder') ?>"
            style="width:100%;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:12px;padding:12px 14px;color:#0f172a;font-size:.9rem;resize:none;margin-bottom:14px;outline:none;font-family:inherit;box-sizing:border-box;-webkit-user-select:text;user-select:text;"
            onfocus="this.style.borderColor='#2563eb'" onblur="this.style.borderColor='#e2e8f0'"></textarea>

        <!-- buttons -->
        <div style="display:flex;gap:10px;">
            <button onclick="closeFinishModal()"
                style="flex:1;padding:14px;border-radius:14px;border:1.5px solid #e2e8f0;background:#fff;color:#374151;font-weight:600;font-size:.95rem;cursor:pointer;">
                <?= t('drv.cancel') ?>
            </button>
            <button id="btnFinishConfirm"
                style="flex:2;padding:14px;border-radius:14px;border:none;background:linear-gradient(135deg,#22c55e,#16a34a);color:#fff;font-weight:700;font-size:.95rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 4px 12px rgba(34,197,94,.4);">
                <i class="bi bi-check-circle-fill"></i> <?= t('drv.finish_modal_confirm') ?>
            </button>
        </div>
    </div>
</div>

<!-- ── App header — native top bar ────────────────────────────────────── -->
<header class="app-header">
    <div class="hdr-greeting">
        <div class="hdr-hello">Hey, <?= View::e($firstName) ?> 👋</div>
        <div class="hdr-sub"><?= t('drv.greeting') ?></div>
    </div>
    <div class="header-right">
        <button class="theme-btn" id="theme-toggle"><i class="bi bi-sun-fill" id="theme-icon"></i></button>
        <?php if ($hasPhoto): ?>
            <img src="<?= View::e($userPhotoSrc) ?>" class="user-avatar" alt="" data-bs-toggle="modal" data-bs-target="#photoModal">
        <?php else: ?>
            <button class="user-avatar user-avatar-default" data-bs-toggle="modal" data-bs-target="#photoModal" aria-label="Profile photo"><i class="bi bi-person-fill"></i></button>
        <?php endif; ?>
    </div>
</header>

<!-- ── Main content ───────────────────────────────────────────────────── -->

<div class="stat-row">
    <div class="stat-chip">
        <div class="stat-icon indigo"><i class="bi bi-calendar-check-fill"></i></div>
        <div><div class="stat-num"><?= $todayCount ?></div><div class="stat-label"><?= t('drv.today') ?></div></div>
    </div>
    <div class="stat-chip">
        <div class="stat-icon green"><i class="bi bi-calendar-week-fill"></i></div>
        <div><div class="stat-num"><?= $weekCount ?></div><div class="stat-label"><?= t('drv.this_week') ?></div></div>
    </div>
</div>

<div class="filter-bar">
    <div class="filter-pills">
        <button class="filter-btn" data-filter="yesterday"><?= t('drv.yesterday') ?></button>
        <button class="filter-btn active" data-filter="today"><?= t('drv.today') ?></button>
        <button class="filter-btn" data-filter="tomorrow"><?= t('drv.tomorrow') ?></button>
    </div>
</div>

<div class="ride-list" id="rideList">
    <div class="empty-state">
        <div class="spinner-border" style="color:var(--accent);" role="status"></div>
        <p style="margin-top:14px;"><?= t('drv.loading') ?></p>
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
                    <div class="step-label"><?= t('drv.step_pickup') ?></div>
                </div>
                <div class="step-line" data-line="0"></div>
                <div class="step-node" data-step="1">
                    <div class="step-dot"></div>
                    <div class="step-label"><?= t('drv.step_arrived') ?></div>
                </div>
                <div class="step-line" data-line="1"></div>
                <div class="step-node" data-step="2">
                    <div class="step-dot"></div>
                    <div class="step-label"><?= t('drv.step_client') ?></div>
                </div>
                <div class="step-line" data-line="2"></div>
                <div class="step-node" data-step="3">
                    <div class="step-dot"></div>
                    <div class="step-label"><?= t('drv.step_trip') ?></div>
                </div>
                <div class="step-line" data-line="3"></div>
                <div class="step-node" data-step="4">
                    <div class="step-dot"></div>
                    <div class="step-label"><?= t('drv.step_done') ?></div>
                </div>
            </div>

            <!-- Multi-stop stepper + current stop info (aggregate rides only) -->
            <div id="multiStopSection">
                <div class="ms-stepper" id="msStepperContainer"></div>
                <div class="ms-stop-label" id="msStopLabel"></div>
                <div class="ms-stop-location" id="msStopLoc"></div>
            </div>

            <!-- Client section -->
            <div class="modal-section">
                <!-- Single client (normal rides) -->
                <div id="singleClientBlock">
                    <div class="client-name-row">
                        <div class="client-name" id="modalClient">—</div>
                        <span class="pax-pill" id="paxPill" style="margin-left:auto;flex-shrink:0;">
                            <i class="bi bi-people-fill"></i>
                            <span id="modalADT"></span>A + <span id="modalCHD"></span>C<span id="modalBBYPart" style="display:none;"> + <span id="modalBBY"></span>B</span>
                        </span>
                    </div>

                    <div class="badge-row-inline" id="modalBadgesRow">
                        <div id="modalBadgesContainer" style="display:contents;"></div>
                    </div>

                    <div class="util-row">
                        <button class="util-btn dark-bg" id="btnAirportMode"><i class="bi bi-signpost-2-fill"></i> Sign</button>
                        <a href="#" id="trackFlightLink" target="_blank" class="util-btn info-bg" style="display:none;">
                            <i class="bi bi-airplane-fill"></i> <span id="modalFlight"></span>
                        </a>
                        <a href="#" id="waUtilBtn" target="_blank" class="util-btn wa-bg" style="display:none;">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>

                <!-- Multiple clients side-by-side (shared rides) -->
                <div id="multiClientSection"></div>
            </div>

            <!-- Route section -->
            <div class="modal-section">
                <div class="route-card" id="modalRouteCard">
                    <div class="route-point">
                        <div class="route-indicator ri-pickup"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <div class="route-loc-label"><?= t('drv.pickup') ?></div>
                            <div class="route-loc-text" id="modalPickup">—</div>
                        </div>
                    </div>
                    <div class="route-point">
                        <div class="route-indicator ri-dropoff"><i class="bi bi-flag-fill"></i></div>
                        <div>
                            <div class="route-loc-label"><?= t('drv.dropoff') ?></div>
                            <div class="route-loc-text" id="modalDropoff">—</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes section -->
            <div class="modal-section">
                <div id="adminNoteBanner" class="admin-note-banner" style="display:none;">
                    <i class="bi bi-megaphone-fill"></i>
                    <div><div class="admin-note-label"><?= t('drv.obs_service') ?></div><div id="adminNoteText"></div></div>
                </div>

                <!-- Existing driver note, shown inline in the flow -->
                <div id="driverNoteInline" class="driver-note-inline" style="display:none;">
                    <i class="bi bi-chat-left-text-fill"></i>
                    <div><div class="driver-note-inline-label"><?= t('drv.your_obs') ?></div><div id="driverNoteInlineText"></div></div>
                </div>

                <!-- Accordion: write / edit observation -->
                <button type="button" class="obs-accordion-hdr" id="obsAccordionHdr" aria-expanded="false">
                    <span><i class="bi bi-pencil-square"></i> <span id="obsAccordionTitle"><?= t('drv.add_obs') ?></span></span>
                    <i class="bi bi-chevron-down obs-chevron" id="obsChevron"></i>
                </button>
                <div class="obs-accordion-body" id="obsAccordionBody">
                    <textarea id="driverNoteInput" class="note-textarea" rows="2" placeholder="<?= t('drv.obs_placeholder') ?>"></textarea>
                    <button type="button" id="saveDriverNoteBtn" class="note-save-btn"><i class="bi bi-check-lg"></i> <?= t('drv.save_note') ?></button>
                </div>
            </div>

            <!-- Actions section -->
            <div class="modal-section">
                <button id="btnDynamicAction" class="action-btn status-btn-0">START PICKUP</button>
                <button id="msNavBtn"         class="action-btn status-btn-0" style="display:none"></button>
                <button id="msArrivedBtn"     class="action-btn status-btn-1" style="display:none"><i class="bi bi-geo-alt-fill me-2"></i><?= t('drv.btn_arrived') ?></button>
                <button id="msWithClientBtn"  class="action-btn status-btn-2" style="display:none"><i class="bi bi-person-check-fill me-2"></i><?= t('drv.btn_with_client') ?></button>
                <button id="msNextBtn"        class="action-btn status-btn-5" style="display:none"><?= t('drv.btn_next_stop') ?></button>

                <div class="secondary-row">
                    <button class="sec-btn" id="uploadVoucher"><i class="bi bi-camera"></i> <?= t('drv.voucher') ?></button>
                    <button class="sec-btn sec-btn-danger" id="uploadNoShow"><i class="bi bi-camera"></i> <?= t('drv.no_show') ?></button>
                </div>
                <input type="file" id="cameraGalleryInput" accept="image/*" style="display:none">

                <a href="#" id="whatsappAlojamento" target="_blank" class="wa-btn" style="display:none;">
                    <i class="bi bi-whatsapp"></i> Leaving airport
                </a>
            </div>

            <div style="height: calc(10px + var(--safe-bottom));"></div>
        </div>
        <!-- Scroll affordance: fade + bouncing chevron when there's more below -->
        <div class="scroll-hint" id="scrollHint" aria-hidden="true">
            <i class="bi bi-chevron-down chev"></i>
        </div>
    </div>
</div>

<!-- ── Profile photo modal ────────────────────────────────────────────── -->
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background:var(--bg-card);border:1px solid var(--border);border-radius:20px;">
            <div class="modal-header" style="border-bottom:1px solid var(--border);">
                <h5 class="modal-title" style="color:var(--text-1);"><?= t('drv.profile_photo') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"
                    style="filter:<?= strpos($_SESSION['theme'] ?? 'dark', 'light') !== false ? 'none' : 'invert(1)' ?>"></button>
            </div>
            <div class="modal-body p-4 text-center" style="background:var(--bg-card);">
                <form action="/SRMT/public/save-profile-photo.php" method="POST" enctype="multipart/form-data" id="photoForm">
                    <div class="profile-photo-preview mb-4">
                        <?php if ($hasPhoto): ?>
                            <img id="currentProfilePhoto" src="<?= View::e($userPhotoSrc) ?>" alt="">
                            <i class="bi bi-person-fill" id="currentProfilePlaceholder" style="display:none;"></i>
                        <?php else: ?>
                            <img id="currentProfilePhoto" src="" alt="" style="display:none;">
                            <i class="bi bi-person-fill" id="currentProfilePlaceholder"></i>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="profile_photo" id="profilePhotoInput" accept="image/*" required hidden>
                    <div class="d-flex gap-2 mb-3">
                        <button type="button" id="btnTakePhoto" class="photo-choice-btn">
                            <i class="bi bi-camera-fill"></i> <?= t('drv.take_photo') ?>
                        </button>
                        <button type="button" id="btnPickPhoto" class="photo-choice-btn">
                            <i class="bi bi-images"></i> <?= t('drv.gallery') ?>
                        </button>
                    </div>
                    <button type="submit" id="btnSavePhoto"
                        class="btn w-100 py-2 fw-bold rounded-pill" disabled
                        style="background:var(--accent);color:#fff;border:none;"><?= t('drv.save_photo') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ── Bottom nav ─────────────────────────────────────────────────────── -->
<nav class="bottom-nav">
    <a href="/SRMT/public/driver/"           class="nav-item active"><i class="bi bi-car-front-fill"></i><?= t('drv.nav_rides') ?></a>
    <a href="/SRMT/public/driver/agenda.php" class="nav-item"><i class="bi bi-calendar3"></i><?= t('drv.nav_agenda') ?></a>
    <a href="/SRMT/public/driver/stats.php"  class="nav-item"><i class="bi bi-bar-chart-fill"></i><?= t('drv.nav_stats') ?></a>
    <a href="/SRMT/public/driver/settings.php" class="nav-item"><i class="bi bi-gear-fill"></i><?= t('drv.nav_settings') ?></a>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const T = <?= json_encode($T_js, JSON_UNESCAPED_UNICODE) ?>;
var viagens = <?= json_encode($rides, JSON_UNESCAPED_UNICODE) ?>;
var currentDriverId = <?= $driverId ?>;
const WPP_TRACK_AUTO = <?= json_encode((bool) ($wppTrackEnabled ?? false)) ?>;

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
let localStopState = {}, currentMultiStopRide = null;
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
const WAF_HEADERS = { 'Content-Type': 'application/x-www-form-urlencoded' };
function wafBody(obj) { return 'p=' + encodeURIComponent(btoa(unescape(encodeURIComponent(JSON.stringify(obj))))); }

function sendPosition(position) {
    const finishBg = () => { if (window.Capacitor?.Plugins?.BackgroundGeolocation) Capacitor.Plugins.BackgroundGeolocation.finish(); };
    if (!currentRideId) { finishBg(); return; }
    const lat = position.latitude  ?? position.coords?.latitude;
    const lng = position.longitude ?? position.coords?.longitude;
    if (lat === undefined || lng === undefined) { finishBg(); return; }
    fetch('/SRMT/public/api/location-update.php', {
        method: 'POST',
        body: wafBody({ ride_id: currentRideId, driver_id: currentDriverId, lat, lng, speed: position.speed ?? position.coords?.speed ?? 0, heading: position.bearing ?? position.coords?.heading ?? 0 }),
        headers: WAF_HEADERS
    }).catch(() => {}).finally(finishBg);
}
function startLiveTracking(rideId) {
    currentRideId = rideId;
    sessionStorage.setItem('activeRideId', rideId);
    if (window.Capacitor?.Plugins?.BackgroundGeolocation) {
        const BGeo = Capacitor.Plugins.BackgroundGeolocation;
        if (backgroundWatcherId) return;
        BGeo.addWatcher({ rideId: parseInt(rideId), driverId: currentDriverId, backgroundTitle: 'SyncRide em serviço', backgroundMessage: 'A localização está ativa em segundo plano.', requestAllowAlwaysLocation: true, distanceFilter: 10, staleLocationThreshold: 30, radius: 20 },
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
    fetch('/SRMT/public/api/tracking-stop.php', { method: 'POST', headers: WAF_HEADERS, body: wafBody({ ride_id: currentRideId, driver_id: currentDriverId }) });
    currentRideId = null;
    if (window.Capacitor?.Plugins?.BackgroundGeolocation && backgroundWatcherId) { Capacitor.Plugins.BackgroundGeolocation.removeWatcher({ id: backgroundWatcherId }); backgroundWatcherId = null; }
    if (trackingInterval) { clearInterval(trackingInterval); trackingInterval = null; }
}

// ── Logout: stop ALL native tracking before signing out ────────────────────
// The native foreground GPS service is independent of the web session, so a plain
// logout would leave it reporting forever. Tear everything down first, then sign out.
(function () {
    const link = document.querySelector('.bottom-nav a[href*="logout"]');
    if (!link) return;
    link.addEventListener('click', async function (e) {
        e.preventDefault();
        const dest = this.getAttribute('href');
        const rid  = currentRideId || sessionStorage.getItem('activeRideId');
        // 1. Remove the live dot server-side (this endpoint needs no session).
        if (rid) {
            try { await fetch('/SRMT/public/api/tracking-stop.php', { method: 'POST', headers: WAF_HEADERS, body: wafBody({ ride_id: rid, driver_id: currentDriverId }) }); } catch (_) {}
        }
        // 2. Kill the native foreground GPS service unconditionally.
        try { await window.Capacitor?.Plugins?.BackgroundGeolocation?.stopTracking?.(); } catch (_) {}
        // 3. Tear down any web-side watcher/interval + local state.
        try { if (backgroundWatcherId && window.Capacitor?.Plugins?.BackgroundGeolocation) Capacitor.Plugins.BackgroundGeolocation.removeWatcher({ id: backgroundWatcherId }); } catch (_) {}
        if (trackingInterval) { clearInterval(trackingInterval); trackingInterval = null; }
        currentRideId = null; backgroundWatcherId = null;
        sessionStorage.removeItem('activeRideId');
        window.location.href = dest;
    });
})();

// ── DOMContentLoaded ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', async () => {
    if (window.Capacitor?.isNativePlatform()) {
        const { Geolocation, Camera, BackgroundGeolocation } = Capacitor.Plugins ?? {};
        // Register the FCM token first (live fetch + send), then ask for permissions.
        try { if (BackgroundGeolocation?.registerFcmToken) await BackgroundGeolocation.registerFcmToken(); } catch {}
        try { if (Geolocation?.requestPermissions) await Geolocation.requestPermissions(); } catch {}
        try { if (Camera?.requestPermissions) await Camera.requestPermissions(); } catch {}
        try { if (BackgroundGeolocation?.requestPermissions) await BackgroundGeolocation.requestPermissions(); } catch {}
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
    if (!href || href === '#') { e.preventDefault(); return; }
    if (window.Capacitor?.isNativePlatform?.()) { e.preventDefault(); openExternal(href); }
});

// ── Status UI ─────────────────────────────────────────────────────────────
const STEP_MAP = {0:0, 1:1, 2:2, 5:3, 3:4, 4:5};
function updateButtonUI(status) {
    const btn = document.getElementById('btnDynamicAction'); if (!btn) return;
    btn.className = 'action-btn';
    const map = {
        0: ['status-btn-0', '<i class="bi bi-car-front-fill me-2"></i>' + T.btn_start_pickup, false],
        1: ['status-btn-1', '<i class="bi bi-geo-alt-fill me-2"></i>' + T.btn_arrived,        false],
        2: ['status-btn-2', '<i class="bi bi-person-check-fill me-2"></i>' + T.btn_with_client, false],
        5: ['status-btn-5', '<i class="bi bi-play-circle-fill me-2"></i>' + T.btn_start_trip,  false],
        3: ['status-btn-3', '<i class="bi bi-stop-circle-fill me-2"></i>' + T.btn_finish,      false],
    };
    const def = ['status-btn-4', '<i class="bi bi-check-circle-fill me-2"></i>' + T.btn_completed, true];
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
// ── Observações accordion ──────────────────────────────────────────────────
function setObsAccordion(open) {
    const body = document.getElementById('obsAccordionBody');
    const hdr  = document.getElementById('obsAccordionHdr');
    if (!body || !hdr) return;
    body.classList.toggle('open', open);
    hdr.setAttribute('aria-expanded', open ? 'true' : 'false');
}
function refreshDriverNoteInline(note) {
    const inline     = document.getElementById('driverNoteInline');
    const inlineText = document.getElementById('driverNoteInlineText');
    const obsTitle   = document.getElementById('obsAccordionTitle');
    const trimmed    = (note || '').trim();
    if (trimmed) {
        inlineText.textContent = trimmed;
        inline.style.display = 'flex';
        obsTitle.textContent = T.edit_obs;
    } else {
        inline.style.display = 'none';
        obsTitle.textContent = T.add_obs;
    }
}
document.getElementById('obsAccordionHdr')?.addEventListener('click', function () {
    setObsAccordion(this.getAttribute('aria-expanded') !== 'true');
});

document.getElementById('saveDriverNoteBtn').addEventListener('click', function () {
    if (!currentRideData) return;
    const btn = this;
    const note = document.getElementById('driverNoteInput').value.trim();
    const fd = new FormData();
    fd.append('ride_id', currentRideData.id);
    fd.append('note_b64', btoa(unescape(encodeURIComponent(note))));
    btn.disabled = true;
    fetch('/SRMT/public/api/driver-note.php', { method: 'POST', body: fd })
        .then(r => r.json()).then(d => {
            btn.disabled = false;
            if (d && d.success) {
                // keep the in-memory ride in sync so reopening shows the saved note
                const v = viagens.find(x => String(x.ServiceID) === String(currentRideData.id));
                if (v) v.driver_note = note;
                refreshDriverNoteInline(note);
                btn.classList.add('saved');
                btn.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + T.saved;
                setTimeout(() => { btn.classList.remove('saved'); btn.innerHTML = '<i class="bi bi-check-lg"></i> ' + T.save_note; }, 1800);
            }
        }).catch(() => { btn.disabled = false; });
});

function updateStatusBackend(rideId, nextStatus) {
    const noteEl = document.getElementById('driverNoteInput');
    const note = noteEl ? noteEl.value.trim() : '';
    const payload = { ride_id: rideId, status: nextStatus };
    if (note) payload.note = note;
    fetch('/SRMT/public/api/status-update.php', { method: 'POST', headers: WAF_HEADERS, body: wafBody(payload) })
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
        if (!confirm(T.confirm_start)) return;
        sessionStorage.setItem('tripStart_' + rideId, Date.now());
        if (WPP_TRACK_AUTO && currentRideData.clientnumber) {
            const cNum = currentRideData.clientnumber.replace(/[^0-9]/g, '');
            if (cNum.length > 7) {
                const trackLink = window.location.origin + '/SRMT/public/track.php?id=' + rideId;
                openExternal('https://wa.me/' + cNum + '?text=' + encodeURIComponent(T.wa_on_way + trackLink));
            }
        }
        startLiveTracking(rideId); openWaze(currentRideData.start);
    } else if (cur === 1) { if (!confirm(T.confirm_arrived)) return; }
    else if (cur === 2) { if (!confirm(T.confirm_client)) return; }
    else if (cur === 5) { if (!confirm(T.confirm_trip)) return; openWaze(currentRideData.end); }
    else if (cur === 3) { showFinishModal(rideId); return; }
    updateStatusBackend(rideId, nextStatus);
});

// ── Multi-stop flow ───────────────────────────────────────────────────────
function initStopStateFromTimestamps(rideId, stops) {
    let idx = 0, phase = 'nav';
    for (let i = 0; i < stops.length; i++) {
        const s = stops[i];
        if (!s.ts_departed) {
            idx = i;
            if (s.ts_arrived) {
                phase = s.type === 'pickup' ? 'with_client' : 'done';
            } else {
                phase = 'nav';
            }
            break;
        }
        if (i === stops.length - 1) { idx = i; phase = 'done'; }
    }
    localStopState[rideId] = { idx, phase };
    return localStopState[rideId];
}

function buildMultiStepper(stops, curIdx, curPhase) {
    const container = document.getElementById('msStepperContainer');
    if (!container) return;

    // Phase order within a stop
    const PO = { nav: 0, at_stop: 1, with_client: 2, done: 3 };
    const curPO = PO[curPhase] ?? 0;

    // Build flat step list: every phase of every stop, plus a final Fim step
    const steps = [];
    let pickupN = 0, dropoffN = 0;
    stops.forEach((s, i) => {
        const isP = s.type === 'pickup';
        const n   = isP ? ++pickupN : ++dropoffN;
        steps.push({ stopIdx: i, phase: 'nav',         label: isP ? `R${n}` : `E${n}` });
        steps.push({ stopIdx: i, phase: 'at_stop',     label: isP ? 'Arr'   : 'Dest'  });
        if (isP) steps.push({ stopIdx: i, phase: 'with_client', label: 'Cli' });
    });
    steps.push({ stopIdx: stops.length, phase: 'nav', label: 'Fim' });

    container.innerHTML = '';
    let activeEl = null;
    steps.forEach((step, i) => {
        const allDone  = curIdx >= stops.length;
        const isDone   = allDone && step.stopIdx < stops.length
                      || (!allDone && (step.stopIdx < curIdx
                          || (step.stopIdx === curIdx && PO[step.phase] < curPO)));
        const isActive = !allDone && step.stopIdx === curIdx && step.phase === curPhase;

        const node = document.createElement('div');
        node.className = 'step-node' + (isDone ? ' done' : isActive ? ' active' : '');
        node.innerHTML = `<div class="step-dot"></div><div class="step-label">${step.label}</div>`;
        container.appendChild(node);
        if (isActive) activeEl = node;

        if (i < steps.length - 1) {
            const line = document.createElement('div');
            line.className = 'step-line' + (isDone ? ' done' : '');
            container.appendChild(line);
        }
    });

    if (activeEl) activeEl.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' });
}

function msShow(ids) {
    ['msNavBtn','msArrivedBtn','msWithClientBtn','msNextBtn'].forEach(id => {
        document.getElementById(id).style.display = ids.includes(id) ? '' : 'none';
    });
}

function updateMultiStopUI(rideId, stops) {
    const state = localStopState[rideId] || (localStopState[rideId] = { idx: 0, phase: 'nav' });
    const { idx, phase } = state;
    const stop = stops[idx];
    if (!stop) return;
    const isPickup = stop.type === 'pickup';
    const isLast   = idx === stops.length - 1;
    const typeLabel = isPickup ? T.pickup : T.dropoff;
    const stopNum   = idx + 1;

    buildMultiStepper(stops, idx, phase);
    document.getElementById('msStopLabel').textContent = `${typeLabel} ${stopNum} / ${stops.length}`;
    document.getElementById('msStopLoc').textContent   = stop.location || '';

    const nav  = document.getElementById('msNavBtn');
    const next = document.getElementById('msNextBtn');

    if (phase === 'nav') {
        nav.innerHTML = `<i class="bi bi-car-front-fill me-2"></i>${T.btn_navigate} → ${typeLabel}`;
        msShow(['msNavBtn']);
    } else if (phase === 'at_stop') {
        msShow(['msArrivedBtn']);
    } else if (phase === 'with_client') {
        // only for pickups — driver confirmed client is in the car
        msShow(['msWithClientBtn']);
    } else { // done
        next.innerHTML  = isLast
            ? `<i class="bi bi-flag-fill me-2"></i>${T.btn_finish_svc}`
            : `<i class="bi bi-arrow-right-circle-fill me-2"></i>${T.btn_next_stop}`;
        next.className  = 'action-btn ' + (isLast ? 'status-btn-3' : 'status-btn-5');
        msShow(['msNextBtn']);
    }

    document.querySelectorAll('#modalRouteCard .route-point').forEach((el, i) => {
        el.classList.toggle('stop-done',    i < idx);
        el.classList.toggle('stop-current', i === idx);
        el.classList.toggle('stop-pending', i > idx);
    });
}

function stopStatusApi(masterId, stopId, action) {
    return fetch('/SRMT/public/api/stop-status.php', {
        method: 'POST',
        headers: WAF_HEADERS,
        body: wafBody({ master_ride_id: masterId, stop_id: stopId, action })
    }).catch(() => {});
}

// ── Multi-client header (shared rides) ────────────────────────────────────
let _mcClients = [];
function mcEsc(s) { return (s == null ? '' : String(s)).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])); }

function buildMultiClient(v) {
    const seen = {}, clients = [];
    (v.stops || []).forEach(s => {
        const key = s.source_id || s.client || '?';
        if (!seen[key]) { seen[key] = { name: s.client || T.client_n, phone: '', flight: '', adt: 0, chd: 0, bby: 0 }; clients.push(seen[key]); }
        if (s.client_phone) seen[key].phone = s.client_phone;
        if (s.type === 'pickup') {
            if (s.flight) seen[key].flight = s.flight;
            if (!seen[key].adt && (s.pax_adt || s.pax_chd || s.pax_bby)) {
                seen[key].adt = s.pax_adt || 0;
                seen[key].chd = s.pax_chd || 0;
                seen[key].bby = s.pax_bby || 0;
            } else if (!seen[key].adt && s.pax) {
                seen[key].adt = s.pax; // fallback: treat total as adults
            }
        }
    });
    _mcClients = clients;

    const cols = Math.min(clients.length, 3);
    const html = clients.map((c, i) => {
        const phoneDigits = String(c.phone || '').replace(/[^0-9]/g, '');
        const waBtn = phoneDigits.length > 7
            ? `<a href="https://wa.me/${phoneDigits}" target="_blank" class="client-col-btn wa"><i class="bi bi-whatsapp"></i></a>` : '';
        const paxParts = [];
        if (c.adt > 0) paxParts.push(`${c.adt}A`);
        if (c.chd > 0) paxParts.push(`${c.chd}C`);
        if (c.bby > 0) paxParts.push(`${c.bby}B`);
        const paxChip = paxParts.length
            ? `<span class="client-col-chip"><i class="bi bi-people-fill"></i>${paxParts.join(' + ')}</span>` : '';
        const flightBtn  = c.flight
            ? `<a href="https://www.flightradar24.com/data/flights/${encodeURIComponent(c.flight.replace(/\s/g,''))}" target="_blank" class="client-col-btn flight"><i class="bi bi-airplane-fill"></i>${mcEsc(c.flight)}</a>`
            : '';
        return `<div class="client-col">
            <div class="client-col-idx">${T.client_n} ${i + 1}</div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <div class="client-col-name" style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${mcEsc(c.name)}</div>
                ${paxChip}
            </div>
            <div class="client-col-actions">
                ${waBtn}
                ${flightBtn}
                <button class="client-col-btn sign" onclick="signClient(${i})"><i class="bi bi-signpost-2-fill"></i> Sign</button>
            </div>
        </div>`;
    }).join('');

    const sec = document.getElementById('multiClientSection');
    sec.innerHTML = `<div class="client-split cols-${cols}">${html}</div>`;
}
function signClient(i) { const c = _mcClients[i]; if (c) openAirportSign(c.name, c.flight); }

document.getElementById('msNavBtn').addEventListener('click', function () {
    const v = currentMultiStopRide; if (!v) return;
    const rideId = String(v.ServiceID);
    const state  = localStopState[rideId] || (localStopState[rideId] = { idx: 0, phase: 'nav' });
    const stop   = v.stops[state.idx];
    if (state.idx === 0 && !currentRideId) {
        startLiveTracking(rideId);
        updateStatusBackend(rideId, 1);
        sessionStorage.setItem('tripStart_' + rideId, Date.now());
    }
    // Send WA tracking link to each pickup client individually (only if auto-send enabled)
    if (WPP_TRACK_AUTO && stop.type === 'pickup') {
        const cNum = String(stop.client_phone || '').replace(/[^0-9]/g, '');
        if (cNum.length > 7) {
            const trackId   = stop.source_id || rideId;
            const trackLink = window.location.origin + '/SRMT/public/track.php?id=' + trackId;
            openExternal('https://wa.me/' + cNum + '?text=' + encodeURIComponent(T.wa_on_way + trackLink));
        }
    }
    openWaze(stop.location);
    state.phase = 'at_stop';
    updateMultiStopUI(rideId, v.stops);
});

document.getElementById('msArrivedBtn').addEventListener('click', function () {
    const v = currentMultiStopRide; if (!v) return;
    const rideId = String(v.ServiceID);
    const state  = localStopState[rideId];
    const stop   = v.stops[state.idx];
    stopStatusApi(v.ServiceID, stop.id, 'arrived');
    // pickup → with_client phase; dropoff → done
    state.phase = stop.type === 'pickup' ? 'with_client' : 'done';
    updateMultiStopUI(rideId, v.stops);
});

document.getElementById('msWithClientBtn').addEventListener('click', function () {
    const v = currentMultiStopRide; if (!v) return;
    const rideId = String(v.ServiceID);
    const state  = localStopState[rideId];
    // client confirmed in car — move to 'done' so next btn appears
    state.phase = 'done';
    updateMultiStopUI(rideId, v.stops);
});

document.getElementById('msNextBtn').addEventListener('click', function () {
    const v = currentMultiStopRide; if (!v) return;
    const rideId = String(v.ServiceID);
    const state  = localStopState[rideId];
    const stop   = v.stops[state.idx];
    const isLast = state.idx === v.stops.length - 1;
    stopStatusApi(v.ServiceID, stop.id, 'departed');
    if (isLast) {
        showFinishModal(rideId);
    } else {
        state.idx++;
        state.phase = 'nav';
        updateMultiStopUI(rideId, v.stops);
    }
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
// IN/OUT (arrival/departure): airport at pickup = IN, airport at dropoff = OUT.
function isAirportPoint(s) { return /aeroport|airport|\bLIS\b|\bOPO\b|\bFAO\b|\bFNC\b|\bPDL\b/i.test(s || ''); }
function serviceIO(v) {
    const a = isAirportPoint(v.serviceStartPoint), b = isAirportPoint(v.serviceTargetPoint);
    if (a && !b) return { cls: 'io-in',  text: 'IN'  };
    if (b && !a) return { cls: 'io-out', text: 'OUT' };
    return null;
}
function toMin(t) { const p = (t||'00:00').split(':'); return parseInt(p[0]||0)*60+parseInt(p[1]||0); }

function pairInOut(data) {
    const items = data.map(v => ({ v, io: serviceIO(v), min: toMin(v.serviceStartTime), paired: false, pairRole: null, pairDiff: 0 }));
    const used  = new Set();
    // Pair each OUT with the closest IN within 30 min
    items.forEach((a, i) => {
        if (a.io?.text !== 'OUT' || used.has(i)) return;
        const j = items.findIndex((b, k) => k !== i && !used.has(k) && b.io?.text === 'IN' && Math.abs(b.min - a.min) <= 30);
        if (j === -1) return;
        const diff = Math.abs(items[j].min - a.min);
        a.paired = true; a.pairRole = 'out'; a.pairDiff = diff;
        items[j].paired = true; items[j].pairRole = 'in'; items[j].pairDiff = diff;
        used.add(i); used.add(j);
    });
    // Sort by time, OUT always above its paired IN
    const sorted = [...items].sort((a, b) => a.min - b.min);
    const result = [], placed = new Set();
    sorted.forEach(a => {
        if (placed.has(a.v.ServiceID)) return;
        result.push(a); placed.add(a.v.ServiceID);
        if (a.pairRole === 'out') {
            const partner = items.find(b => b.pairRole === 'in' && b.paired && !placed.has(b.v.ServiceID) && b.pairDiff === a.pairDiff);
            if (partner) { result.push(partner); placed.add(partner.v.ServiceID); }
        }
    });
    return result;
}

function renderList(data) {
    const el = document.getElementById('rideList'); if (!el) return; el.innerHTML = '';
    if (data.length === 0) {
        el.innerHTML = "<div class='empty-state'><i class='bi bi-calendar-x'></i><p>" + T.no_services + "</p></div>";
        return;
    }
    const sorted = pairInOut(data);
    sorted.forEach(({ v, pairRole, pairDiff }) => {
        const status = localTripStatus[String(v.ServiceID)] ?? parseInt(v.status_id) ?? 0;
        const isDone = status === 4;
        const stype  = parseInt(v.serviceType) || 0;
        const isPriv = stype === 1;
        const badgeCls  = isPriv ? 'badge-private' : 'badge-shared';
        const badgeText = isPriv ? T.private : T.shared;
        const dotCls    = 'dot-' + (isDone ? 4 : (status >= 1 ? status : 0));
        const io        = serviceIO(v);
        const ioBadge   = io ? `<span class="io-badge ${io.cls}">${io.text}</span>` : '';

        let extraBadges = '';
        if (v.partner_id && v.partner_id > 0) {
            if (v.AgencyName) extraBadges += `<span class="ride-badge" style="background:rgba(59,130,246,.12);color:#3b82f6;border:1px solid rgba(59,130,246,.2);"><i class="bi bi-building-fill"></i> ${v.AgencyName}</span>`;
            extraBadges += `<span class="ride-badge ${v.has_key == 1 ? 'sb-green' : 'sb-red'} ride-badge"><i class="bi bi-key-fill"></i> ${v.has_key == 1 ? T.with_key : T.no_key}</span>`;
        }

        const pairBorder = pairRole === 'in' ? 'border-left:3px solid #22c55e;' : pairRole === 'out' ? 'border-left:3px solid #f59e0b;' : '';
        el.innerHTML += `
<div class="ride-card open-modal${isDone ? ' is-done' : ''}" style="${pairBorder}"
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
                ${ioBadge}
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
                    const lbl = s.type === 'pickup' ? T.pickup : T.dropoff;
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
                <div class="rt-point"><div class="rt-label">${T.from}</div>${v.serviceStartPoint}</div>
                <div class="rt-point"><div class="rt-label">${T.to}</div>${v.serviceTargetPoint}</div>
               </div>`
        }
    </div>
    ${v.total_price > 0 ? `<div class="price-badge"><i class="bi bi-cash-coin"></i>${parseFloat(v.total_price).toFixed(2)}€</div>` : ''}
</div>`;
    });

    document.querySelectorAll('.open-modal').forEach(card => {
        card.addEventListener('click', () => {
            const v = viagens.find(x => String(x.ServiceID) === card.dataset.id);
            const d = card.dataset; currentRideData = d;
            const m = document.getElementById('detailsModal');
            m.querySelector('#modalIdDisplay').textContent = d.id;
            m.querySelector('#modalADT').textContent      = d.paxadt;
            m.querySelector('#modalCHD').textContent      = d.paxchd;
            m.querySelector('#modalClient').textContent   = d.client || 'Client';

            // Route: multi-stop for aggregate masters, simple for others
            const routeCard = document.getElementById('modalRouteCard');
            if (v && v.is_aggregate_master == 1 && Array.isArray(v.stops) && v.stops.length > 0) {
                routeCard.innerHTML = v.stops.map(s => {
                    const isPickup = s.type === 'pickup';
                    const icon     = isPickup ? 'bi-geo-alt-fill' : 'bi-flag-fill';
                    const cls      = isPickup ? 'ri-pickup' : 'ri-dropoff';
                    const lbl      = isPickup ? T.pickup : T.dropoff;
                    const sub      = [s.time ? s.time.substring(0,5) : '', s.client || ''].filter(Boolean).join(' · ');
                    const flightHtml = (isPickup && s.flight && s.flight.trim())
                        ? `<a href="https://www.flightradar24.com/data/flights/${s.flight.replace(/\s/g,'')}" target="_blank"
                              style="display:inline-flex;align-items:center;gap:4px;font-size:.7rem;color:var(--accent);font-weight:600;margin-top:3px;text-decoration:none;">
                              <i class="bi bi-airplane-fill"></i>${s.flight}</a>`
                        : '';
                    return `<div class="route-point">
                        <div class="route-indicator ${cls}"><i class="bi ${icon}"></i></div>
                        <div>
                            <div class="route-loc-label">${lbl}${sub ? ' · <span style="font-weight:600;color:var(--text-2)">' + sub + '</span>' : ''}</div>
                            <div class="route-loc-text">${s.location || '—'}</div>
                            ${flightHtml}
                        </div>
                    </div>`;
                }).join('');
                // Set start/end for Waze from first pickup / last dropoff
                const firstPickup = v.stops.find(s => s.type === 'pickup');
                const lastDropoff = [...v.stops].reverse().find(s => s.type === 'dropoff');
                currentRideData = { ...d, start: firstPickup?.location || d.start, end: lastDropoff?.location || d.end };

                // Multi-stop flow: hide stepper + main btn; show multi-stop section
                currentMultiStopRide = v;
                document.getElementById('statusStepper').style.display    = 'none';
                document.getElementById('btnDynamicAction').style.display = 'none';
                document.getElementById('multiStopSection').style.display = 'block';
                initStopStateFromTimestamps(String(v.ServiceID), v.stops);
                updateMultiStopUI(String(v.ServiceID), v.stops);

                // Client header: split into one column per client
                document.getElementById('singleClientBlock').style.display = 'none';
                document.getElementById('multiClientSection').style.display = 'block';
                buildMultiClient(v);
            } else {
                routeCard.innerHTML = `
                    <div class="route-point">
                        <div class="route-indicator ri-pickup"><i class="bi bi-geo-alt-fill"></i></div>
                        <div><div class="route-loc-label">${T.pickup}</div><div class="route-loc-text">${d.start}</div></div>
                    </div>
                    <div class="route-point">
                        <div class="route-indicator ri-dropoff"><i class="bi bi-flag-fill"></i></div>
                        <div><div class="route-loc-label">${T.dropoff}</div><div class="route-loc-text">${d.end}</div></div>
                    </div>`;

                // Single-stop flow: show stepper + main btn; hide multi-stop section
                currentMultiStopRide = null;
                document.getElementById('statusStepper').style.display    = '';
                document.getElementById('btnDynamicAction').style.display = '';
                document.getElementById('multiStopSection').style.display = 'none';
                document.getElementById('msNavBtn').style.display         = 'none';
                document.getElementById('msArrivedBtn').style.display     = 'none';
                document.getElementById('msWithClientBtn').style.display  = 'none';
                document.getElementById('msNextBtn').style.display        = 'none';

                // Client header: single client
                document.getElementById('singleClientBlock').style.display = 'block';
                document.getElementById('multiClientSection').style.display = 'none';
            }

            const wa = document.getElementById('waUtilBtn'); wa.style.display = 'none';
            if (d.clientnumber && d.clientnumber.replace(/[^0-9]/g,'').length > 7) {
                wa.href = `https://wa.me/${d.clientnumber.replace(/[^0-9]/g,'')}`;
                wa.style.display = 'inline-flex';
            }

            const waAloj = document.getElementById('whatsappAlojamento');
            if (d.partnerid && d.partnerid > 0 && d.agencyphone) {
                const ph = '351' + String(d.agencyphone).replace(/[^0-9]/g,'');
                waAloj.href = 'https://wa.me/' + ph + '?text=' + encodeURIComponent(T.wa_leaving + d.client + T.wa_dest + d.end);
                waAloj.style.display = 'flex';
            } else { waAloj.style.display = 'none'; }

            const bc = document.getElementById('modalBadgesContainer'); bc.innerHTML = '';
            if (d.price && parseFloat(d.price) > 0) bc.innerHTML += `<span class="strip-badge sb-green"><i class="bi bi-currency-euro"></i>${parseFloat(d.price).toFixed(2)}</span>`;
            if (d.partnerid && d.partnerid > 0) {
                if (d.agencyname) bc.innerHTML += `<span class="strip-badge sb-blue"><i class="bi bi-building"></i>${d.agencyname}</span>`;
                bc.innerHTML += `<span class="strip-badge ${d.haskey==1?'sb-green':'sb-red'}"><i class="bi bi-key-fill"></i>${d.haskey==1?T.with_key:T.no_key}</span>`;
            }

            const tl = document.getElementById('trackFlightLink');
            // For aggregate masters, flight tracking is per-stop (shown inline above)
            if (!v?.is_aggregate_master && d.flight && d.flight.trim()) {
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

            // Notes: office → driver banner + driver's own observations
            const adminBanner = document.getElementById('adminNoteBanner');
            const adminText   = document.getElementById('adminNoteText');
            if (v && v.admin_note && String(v.admin_note).trim()) {
                adminText.textContent = v.admin_note;
                adminBanner.style.display = 'flex';
            } else {
                adminBanner.style.display = 'none';
            }
            const dNote = (v && v.driver_note) ? String(v.driver_note) : '';
            document.getElementById('driverNoteInput').value = dNote;
            refreshDriverNoteInline(dNote);
            setObsAccordion(false);

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

// ── Scroll hint: show fade+chevron while the sheet has content below the fold ──
(function () {
    const modalEl = document.getElementById('detailsModal');
    const mc      = modalEl.querySelector('.modal-content');
    const hint    = document.getElementById('scrollHint');
    function update() {
        const scrollable = mc.scrollHeight > mc.clientHeight + 8;
        const atBottom   = mc.scrollTop + mc.clientHeight >= mc.scrollHeight - 12;
        hint.classList.toggle('hidden', !scrollable || atBottom);
    }
    mc.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
    // Recalculate after the sheet opens (content height is final by then).
    modalEl.addEventListener('shown.bs.modal', () => { mc.scrollTop = 0; update(); });
    // Buttons inside can change height (e.g. multi-stop) — re-check shortly after open.
    modalEl.addEventListener('shown.bs.modal', () => setTimeout(update, 250));
})();

// ── Airport sign ──────────────────────────────────────────────────────────
const airportOverlay = document.getElementById('airportOverlay');
const nameEl = document.getElementById('airportClientName');
let currentFontSize = 15;
async function openAirportSign(name, flight) {
    const rawName = (name && String(name).trim()) ? String(name) : 'CLIENT';
    nameEl.innerHTML = rawName.trim().split(/\s+/).map(w => `<span class="name-part">${w}</span>`).join(' ');
    document.getElementById('airportFlight').textContent = flight || '';
    currentFontSize = (rawName.trim().split(/\s+/).length <= 2) ? 18 : 12;
    nameEl.style.fontSize = currentFontSize + 'vw';
    airportOverlay.style.display = 'flex';
    if (document.documentElement.requestFullscreen) document.documentElement.requestFullscreen();
    if ('wakeLock' in navigator) { try { wakeLock = await navigator.wakeLock.request('screen'); } catch(e) {} }
}
document.getElementById('btnAirportMode').onclick = () => openAirportSign(currentRideData?.client, currentRideData?.flight);
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
    try {
        stream = await navigator.mediaDevices.getUserMedia({ video: {
            facingMode: currentFacingMode,
            width:  { ideal: 4096, min: 1280 },
            height: { ideal: 3072, min: 720 },
            aspectRatio: { ideal: 4/3 },
        }});
        video.srcObject = stream;
        video.onloadedmetadata = async () => {
            const track = stream.getVideoTracks()[0];
            const caps = track.getCapabilities?.() ?? {};
            const adv = {};
            if (caps.focusMode?.includes('continuous'))       adv.focusMode       = 'continuous';
            if (caps.exposureMode?.includes('continuous'))    adv.exposureMode    = 'continuous';
            if (caps.whiteBalanceMode?.includes('continuous')) adv.whiteBalanceMode = 'continuous';
            if (Object.keys(adv).length) await track.applyConstraints({ advanced: [adv] }).catch(() => {});
            updateCameraUI('capture');
        };
    }
    catch(e) { srToast('error', e.message, 'Camera'); closeCameraOverlay(); }
    if ('geolocation' in navigator) locationWatcher = navigator.geolocation.watchPosition(p => { currentLat = p.coords.latitude; currentLng = p.coords.longitude; });
}
document.getElementById('uploadNoShow').onclick  = () => { currentMode = 'noshow';  document.getElementById('cameraInstruction').textContent = T.photo_noshow;  startCamera(); };
document.getElementById('uploadVoucher').onclick = () => { currentMode = 'voucher'; document.getElementById('cameraInstruction').textContent = T.photo_voucher; startCamera(); };

// Gallery button inside camera overlay — works for both voucher and no-show modes
document.getElementById('cameraGalleryInput').addEventListener('change', function() {
    const file = this.files[0]; if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        closeCameraOverlay();
        submitPhotoBase64(e.target.result);
    };
    reader.readAsDataURL(file);
    this.value = '';
});

function submitPhotoBase64(base64) {
    if (!currentRideData?.id) { srToast('warning', T.select_ride); return; }
    const url = currentMode === 'voucher' ? '/SRMT/public/admin/upload-voucher.php' : '/SRMT/public/admin/upload-no-show.php';
    fetch(url, { method: 'POST', body: JSON.stringify({ trip_id: currentRideData.id, image_data: base64, lat: currentLat, lng: currentLng }) })
        .then(r => r.json()).then(d => {
            if (d.success) { srToast('success', d.message); if (currentMode === 'noshow') updateStatusBackend(currentRideData.id, 4); }
            else srToast('error', d.message || 'Erro');
        });
}
document.getElementById('btnRotateCamera').onclick = () => { currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment'; startCamera(); };
document.getElementById('btnCapture').onclick = async () => {
    const track = stream?.getVideoTracks()[0];
    if (track && typeof ImageCapture !== 'undefined') {
        try {
            const ic   = new ImageCapture(track);
            const blob = await ic.takePhoto();
            const bmp  = await createImageBitmap(blob);
            canvas.width  = bmp.width;
            canvas.height = bmp.height;
            canvas.getContext('2d').drawImage(bmp, 0, 0);
            updateCameraUI('review');
            return;
        } catch(e) { /* fallback below */ }
    }
    // Fallback: canvas crop
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
    const img = canvas.toDataURL('image/jpeg', 0.96);
    const url = currentMode === 'voucher' ? '/SRMT/public/admin/upload-voucher.php' : '/SRMT/public/admin/upload-no-show.php';
    fetch(url, { method: 'POST', body: JSON.stringify({ trip_id: currentRideData.id, image_data: img, lat: currentLat, lng: currentLng }) })
        .then(r => r.json()).then(d => {
            if (d.success) { srToast('success', d.message); closeCameraOverlay(); if (currentMode === 'noshow') updateStatusBackend(currentRideData.id, 4); }
            else { btnSend.disabled = false; }
        });
};
// ── Trip finish modal ─────────────────────────────────────────────────────
function showFinishModal(rideId) {
    const d = currentRideData;
    if (!d) return;

    // client — dataset key is 'client' from data-client
    const v = viagens.find(x => String(x.ServiceID) === String(rideId));
    const clientName = v?.NomeCliente || d.client || '—';
    const startPt    = d.start || v?.serviceStartPoint  || '—';
    const endPt      = d.end   || v?.serviceTargetPoint || '—';

    document.getElementById('finishModalClient').textContent = clientName;
    document.getElementById('finishModalStart').textContent  = startPt;
    document.getElementById('finishModalEnd').textContent    = endPt;

    const adt = parseInt(d.paxadt) || 0, chd = parseInt(d.paxchd) || 0;
    document.getElementById('finishModalPax').textContent = adt + chd + ' pax' + (chd ? ` (${chd} CHD)` : '');

    const price = parseFloat(d.total_price || v?.total_price || 0);
    const pw = document.getElementById('finishModalPriceWrap');
    if (price > 0) { document.getElementById('finishModalPrice').textContent = price.toFixed(2) + '€'; pw.style.display = 'flex'; }
    else pw.style.display = 'none';

    // duration
    const startTs = parseInt(sessionStorage.getItem('tripStart_' + rideId) || 0);
    const durWrap = document.getElementById('finishModalDurationWrap');
    if (startTs) {
        const mins = Math.round((Date.now() - startTs) / 60000);
        const h = Math.floor(mins / 60), m = mins % 60;
        document.getElementById('finishModalDuration').textContent =
            h > 0 ? `${h}h ${m}min de serviço` : `${m} min de serviço`;
        durWrap.style.display = 'flex';
    } else { durWrap.style.display = 'none'; }

    document.getElementById('finishModalObs').value = document.getElementById('driverNoteInput')?.value || '';

    // Close Bootstrap details modal first so it doesn't trap focus/touch
    const bsModal = bootstrap.Modal.getInstance(document.getElementById('detailsModal'));
    if (bsModal) bsModal.hide();

    // Show after Bootstrap has finished hiding (300ms transition)
    setTimeout(() => {
        document.getElementById('tripFinishOverlay').style.display = 'flex';
        setTimeout(() => document.getElementById('finishModalObs').focus(), 350);
    }, 320);

    document.getElementById('btnFinishConfirm').onclick = () => {
        const obs = document.getElementById('finishModalObs').value.trim();
        const noteEl = document.getElementById('driverNoteInput');
        if (noteEl && obs) noteEl.value = obs;
        closeFinishModal();
        stopLiveTracking();
        updateStatusBackend(rideId, 4);
        sessionStorage.removeItem('tripStart_' + rideId);
    };
}
function closeFinishModal() { document.getElementById('tripFinishOverlay').style.display = 'none'; }
document.getElementById('tripFinishOverlay').addEventListener('click', function(e) { if (e.target === this) closeFinishModal(); });

// ── Profile photo: take (camera) or pick (gallery) ─────────────────────────
(function () {
    const input = document.getElementById('profilePhotoInput');
    const img   = document.getElementById('currentProfilePhoto');
    const ph    = document.getElementById('currentProfilePlaceholder');
    const save  = document.getElementById('btnSavePhoto');
    const take  = document.getElementById('btnTakePhoto');
    const pick  = document.getElementById('btnPickPhoto');
    if (!input) return;
    take?.addEventListener('click', () => { input.setAttribute('capture', 'user'); input.click(); });
    pick?.addEventListener('click', () => { input.removeAttribute('capture'); input.click(); });
    input.onchange = e => {
        const [f] = e.target.files;
        if (!f) return;
        if (img) { img.src = URL.createObjectURL(f); img.style.display = ''; }
        if (ph)  ph.style.display = 'none';
        if (save) save.disabled = false;
    };
})();
</script>
</body>
</html>
