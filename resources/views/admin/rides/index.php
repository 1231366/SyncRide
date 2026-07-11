<?php
/** @var array<App\Models\User>         $drivers */
/** @var int                            $pendingRequestsCount */
/** @var int                            $todayCount */
/** @var int                            $unassignedCount */
/** @var array<array<string,mixed>>     $activePartners */
/** @var string|null                    $flash */

use App\Http\View;

ob_start();
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<style>
    :root {
        --safe-top: env(safe-area-inset-top, 0px);
        --safe-bottom: env(safe-area-inset-bottom, 20px);
    }
    .glass-strong {
        background: rgba(255,255,255,0.92);
        backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);
        border: 1px solid rgba(0,0,0,0.1);
    }
    [data-theme="dark"] .glass-strong {
        background: rgba(20,20,20,0.92);
        border: 1px solid rgba(255,255,255,0.1);
    }
    /* Icon button */
    .icon-btn {
        width: 40px; height: 40px; border-radius: 999px;
        display: inline-flex; align-items: center; justify-content: center;
        background: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.08);
        color: #475569; transition: all .2s;
    }
    .icon-btn:hover { background: rgba(0,0,0,0.1); }
    .icon-btn:active { transform: scale(0.92); }
    [data-theme="dark"] .icon-btn { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #fff; }
    [data-theme="dark"] .icon-btn:hover { background: rgba(255,255,255,0.1); }
    /* Labels */
    .eyebrow { font-size: 10px; font-weight: 800; color: #94a3b8; letter-spacing: 0.2em; text-transform: uppercase; font-style: italic; }
    /* Filter pills */
    .pill {
        display: inline-flex; align-items: center; gap: 6px;
        padding: 7px 14px; border-radius: 999px;
        background: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.08);
        color: #64748b; font-size: 11px; font-weight: 700;
        cursor: pointer; transition: all .2s; white-space: nowrap; text-decoration: none;
    }
    .pill:hover { background: rgba(0,0,0,0.10); color: #0f172a; }
    .pill.active { background: #2563eb; color: #fff; border-color: #2563eb; }
    [data-theme="dark"] .pill { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); color: #a1a1aa; }
    [data-theme="dark"] .pill:hover { background: rgba(255,255,255,0.08); color: #fff; }
    [data-theme="dark"] .pill.active { background: #fff; color: #000; border-color: #fff; }
    .pill.warning-pill { color: #fbbf24; border-color: rgba(251,191,36,0.3); background: rgba(251,191,36,0.08); }
    .pill.warning-pill.active { background: #fbbf24; color: #000; }
    .pill-count {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 18px; height: 18px; padding: 0 6px; border-radius: 999px;
        font-size: 10px; font-weight: 800;
        background: rgba(0,0,0,0.08); color: inherit; margin-left: 2px;
    }
    [data-theme="dark"] .pill-count { background: rgba(255,255,255,0.08); }
    .pill.active .pill-count { background: rgba(255,255,255,0.25); color: #fff; }
    [data-theme="dark"] .pill.active .pill-count { background: rgba(0,0,0,0.12); color: #000; }
    .pill.warning-pill .pill-count.has-pending {
        background: #fbbf24; color: #000;
        box-shadow: 0 0 0 0 rgba(251,191,36,0.6);
        animation: pulseBadge 1.8s ease-out infinite;
    }
    .pill.warning-pill.active .pill-count.has-pending { background: #000; color: #fbbf24; animation: none; }
    @keyframes pulseBadge {
        0%   { box-shadow: 0 0 0 0 rgba(251,191,36,0.55); }
        70%  { box-shadow: 0 0 0 6px rgba(251,191,36,0); }
        100% { box-shadow: 0 0 0 0 rgba(251,191,36,0); }
    }
    /* Buttons */
    .btn-primary-modern {
        background: #2563eb; color: #fff; border: none; border-radius: 12px;
        padding: 10px 18px; font-weight: 700; font-size: 12px;
        display: inline-flex; align-items: center; gap: 6px; transition: all .2s;
    }
    .btn-primary-modern:hover { background: #1d4ed8; color: #fff; }
    .btn-primary-modern:active { transform: scale(0.96); }
    .btn-ghost {
        background: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.1);
        color: #475569; border-radius: 12px; padding: 8px 12px;
        font-weight: 600; font-size: 12px; transition: all .2s;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-ghost:hover { background: rgba(0,0,0,0.1); color: #0f172a; }
    [data-theme="dark"] .btn-ghost { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.1); color: #d4d4d8; }
    [data-theme="dark"] .btn-ghost:hover { background: rgba(255,255,255,0.08); color: #fff; }
    /* Search */
    .search-wrap { position: relative; flex: 1; min-width: 0; }
    .search-wrap input {
        width: 100%; background: rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.08);
        color: #0f172a; padding: 10px 14px 10px 38px; border-radius: 14px;
        font-size: 13px; outline: none; font-family: inherit;
    }
    .search-wrap input::placeholder { color: #94a3b8; }
    .search-wrap input:focus { border-color: rgba(37,99,235,0.4); }
    .search-wrap .search-icon { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
    [data-theme="dark"] .search-wrap input { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); color: #fff; }
    [data-theme="dark"] .search-wrap input:focus { border-color: rgba(255,255,255,0.2); }
    /* Ride cards — compact */
    #tabelaViagens { display: block; width: 100%; }
    #tabelaViagens thead { display: none; }
    #tabelaViagens tbody { display: block; }
    #tabelaViagens tbody tr {
        display: block; position: relative;
        background: rgba(255,255,255,0.65); border: 1px solid rgba(0,0,0,0.08);
        border-radius: 16px; margin-bottom: 7px; padding: 10px 14px;
        backdrop-filter: blur(20px); transition: background .15s, border-color .15s, transform .1s;
        cursor: pointer; -webkit-tap-highlight-color: transparent;
    }
    #tabelaViagens tbody tr:hover { background: rgba(255,255,255,0.82); border-color: rgba(0,0,0,0.13); }
    #tabelaViagens tbody tr:active { transform: scale(0.985); }
    [data-theme="dark"] #tabelaViagens tbody tr { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.08); }
    [data-theme="dark"] #tabelaViagens tbody tr:hover { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.15); }
    /* Past/completed rides: muted */
    #tabelaViagens tbody tr.ride-past { opacity: 0.45; }
    /* A decorrer: motorista já começou mas ainda não terminou */
    #tabelaViagens tbody tr.ride-active {
        border-color: rgba(34,197,94,0.4) !important;
        box-shadow: 0 0 0 1px rgba(34,197,94,0.12), 0 2px 12px rgba(34,197,94,0.08) !important;
    }
    #tabelaViagens tbody tr.ride-active::before {
        content: 'A DECORRER';
        position: absolute; top: -9px; left: 14px;
        font-size: 8px; font-weight: 800; letter-spacing: 0.12em;
        color: #16a34a; background: rgba(34,197,94,0.1);
        border: 1px solid rgba(34,197,94,0.3);
        padding: 1px 7px; border-radius: 999px;
        animation: pulse-green 1.4s ease-in-out infinite;
    }
    @keyframes pulse-green {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.35; }
    }
    /* Overdue: hora passou mas ainda não começou */
    #tabelaViagens tbody tr.ride-overdue {
        border-color: rgba(234,88,12,0.35) !important;
        background: rgba(255,237,213,0.5) !important;
    }
    [data-theme="dark"] #tabelaViagens tbody tr.ride-overdue {
        border-color: rgba(234,88,12,0.3) !important;
        background: rgba(124,45,18,0.15) !important;
    }
    #tabelaViagens tbody tr.ride-overdue::before {
        content: 'POR INICIAR';
        position: absolute; top: -9px; left: 14px;
        font-size: 8px; font-weight: 800; letter-spacing: 0.12em;
        color: #ea580c; background: rgba(234,88,12,0.1);
        border: 1px solid rgba(234,88,12,0.3);
        padding: 1px 7px; border-radius: 999px;
    }
    /* Pill "X concluídas" acima — vive dentro da .rides-sticky */
    #completedStackedPill {
        display: none; align-items: center; justify-content: center; gap: 5px;
        margin: -2px auto 6px; padding: 5px 13px; border-radius: 999px;
        background: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.08);
        font-size: 11px; font-weight: 700; color: #64748b;
        cursor: pointer; width: fit-content;
        transition: background .15s;
    }
    #completedStackedPill:hover { background: rgba(0,0,0,0.09); }
    [data-theme="dark"] #completedStackedPill { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.08); color: #a1a1aa; }
    #tabelaViagens tbody td { display: block; border: none !important; padding: 0 !important; background: transparent !important; color: #0f172a; }
    [data-theme="dark"] #tabelaViagens tbody td { color: #fff; }
    #tabelaViagens tbody td.col-selection { display: none; position: absolute; top: 12px; left: 12px; }
    .selection-active #tabelaViagens tbody td.col-selection { display: block; }
    .selection-active #tabelaViagens tbody tr { padding-left: 40px; }
    /* ID (#2) — inline before time */
    #tabelaViagens tbody td:nth-child(2) { display: inline-block !important; font-size: 9px; font-weight: 700; color: #94a3b8; letter-spacing: 0.08em; text-transform: uppercase; font-family: monospace; margin-bottom: 0; vertical-align: middle; margin-right: 5px; }
    [data-theme="dark"] #tabelaViagens tbody td:nth-child(2) { color: #52525b; }
    /* Time (#3) — inline with ID, then force new line via block after */
    #tabelaViagens tbody td:nth-child(3) { display: inline-block !important; font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 4px; vertical-align: middle; }
    #tabelaViagens tbody td:nth-child(3)::after { content: ''; display: block; }
    [data-theme="dark"] #tabelaViagens tbody td:nth-child(3) { color: #fff; }
    /* Driver + cliente (#4) */
    #tabelaViagens tbody td:nth-child(4) { font-size: 11px; color: #475569; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    [data-theme="dark"] #tabelaViagens tbody td:nth-child(4) { color: #a1a1aa; }
    .card-client { font-size: 10px; color: #94a3b8; font-weight: 600; }
    .card-client::before { content: '·'; margin-right: 4px; }
    [data-theme="dark"] .card-client { color: #52525b; }
    /* Pickup (#5) — inline, truncate, green dot */
    #tabelaViagens tbody td:nth-child(5) { display: inline-block !important; width: auto !important; font-size: 11px; color: #334155; padding-left: 16px !important; position: relative; line-height: 1.4; margin-bottom: 2px; max-width: 44%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; vertical-align: middle; }
    /* Hide delegation/grouping badges inside pickup cell */
    #tabelaViagens tbody td:nth-child(5) > div { display: none; }
    /* → separator */
    #tabelaViagens tbody td:nth-child(5)::after { content: ' \2192'; color: #94a3b8; }
    /* Dropoff (#6) — inline, truncate, red dot */
    #tabelaViagens tbody td:nth-child(6) { display: inline-block !important; width: auto !important; font-size: 11px; color: #334155; padding-left: 16px !important; position: relative; line-height: 1.4; margin-bottom: 4px; max-width: 44%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; vertical-align: middle; }
    [data-theme="dark"] #tabelaViagens tbody td:nth-child(5),
    [data-theme="dark"] #tabelaViagens tbody td:nth-child(6) { color: #d4d4d8; }
    #tabelaViagens tbody td:nth-child(5):before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: #10b981; position: absolute; left: 3px; top: 5px; box-shadow: 0 0 0 2px rgba(16,185,129,0.15); }
    #tabelaViagens tbody td:nth-child(6):before { content: ""; width: 7px; height: 7px; border-radius: 50%; background: #ef4444; position: absolute; left: 3px; top: 5px; box-shadow: 0 0 0 2px rgba(239,68,68,0.15); }
    /* Type badge (#7) — absolute top-right, no onClick (use drawer) */
    #tabelaViagens tbody td:nth-child(7) { position: absolute; top: 10px; right: 12px; display: inline-block !important; width: auto !important; font-size: 9px; padding: 2px 8px; border-radius: 999px; font-weight: 800; background: rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.10); color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 0; }
    #tabelaViagens tbody td:nth-child(7) span { pointer-events: none; }
    [data-theme="dark"] #tabelaViagens tbody td:nth-child(7) { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #a1a1aa; }
    /* Key/chave (#8) — hidden (available in drawer) */
    #tabelaViagens tbody td:nth-child(8) { display: none !important; }
    /* Actions (last) — hidden from card, content used in drawer */
    #tabelaViagens tbody td:last-child { display: none !important; }
    @media (min-width: 992px) {
        #tabelaViagens tbody { display: grid; grid-template-columns: repeat(2,1fr); gap: 7px; }
        #tabelaViagens tbody tr { margin-bottom: 0; }
    }
    /* Ride drawer */
    #rideDrawerBackdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.28); backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px); z-index: 1050; }
    #rideDrawer {
        position: fixed; bottom: 0; left: 0; right: 0; z-index: 1060;
        transform: translateY(100%); transition: transform .3s cubic-bezier(.32,.72,0,1);
        background: rgba(255,255,255,0.97); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);
        border-radius: 24px 24px 0 0; border-top: 1px solid rgba(0,0,0,0.08);
        padding: 16px 20px calc(20px + env(safe-area-inset-bottom, 0px));
        max-height: 70vh; overflow-y: auto; box-shadow: 0 -20px 60px rgba(0,0,0,0.12);
    }
    [data-theme="dark"] #rideDrawer { background: rgba(15,15,25,0.97); border-top-color: rgba(255,255,255,0.08); box-shadow: 0 -20px 60px rgba(0,0,0,0.5); }
    #rideDrawer.open { transform: translateY(0); }
    .drawer-handle { width: 36px; height: 4px; background: rgba(0,0,0,0.12); border-radius: 2px; margin: 0 auto 14px; }
    [data-theme="dark"] .drawer-handle { background: rgba(255,255,255,0.12); }
    .drawer-ride-meta { margin-bottom: 14px; padding-bottom: 12px; border-bottom: 1px solid rgba(0,0,0,0.07); }
    [data-theme="dark"] .drawer-ride-meta { border-bottom-color: rgba(255,255,255,0.07); }
    .drawer-time { font-size: 17px; font-weight: 800; color: #0f172a; }
    [data-theme="dark"] .drawer-time { color: #fff; }
    .drawer-id { font-size: 10px; font-weight: 700; color: #94a3b8; font-family: monospace; letter-spacing: 0.08em; }
    .drawer-route { font-size: 12px; color: #475569; margin-top: 5px; display: flex; align-items: center; gap: 6px; }
    [data-theme="dark"] .drawer-route { color: #a1a1aa; }
    .drawer-driver { font-size: 11px; color: #94a3b8; margin-top: 4px; }
    .drawer-actions { display: grid; gap: 8px; }
    .drawer-actions .btn { width: 100% !important; height: auto !important; padding: 11px 16px !important; border-radius: 14px !important; font-size: 13px !important; font-weight: 700 !important; display: flex !important; align-items: center !important; gap: 10px !important; justify-content: flex-start !important; background: rgba(0,0,0,0.04) !important; border: 1px solid rgba(0,0,0,0.08) !important; color: #0f172a !important; }
    [data-theme="dark"] .drawer-actions .btn { background: rgba(255,255,255,0.05) !important; border-color: rgba(255,255,255,0.08) !important; color: #fff !important; }
    .drawer-actions .btn:hover { background: rgba(0,0,0,0.08) !important; }
    [data-theme="dark"] .drawer-actions .btn:hover { background: rgba(255,255,255,0.09) !important; }
    .drawer-actions .btn-primary  { color: #2563eb !important; border-color: rgba(37,99,235,0.25) !important; background: rgba(37,99,235,0.06) !important; }
    .drawer-actions .btn-warning  { color: #d97706 !important; border-color: rgba(217,119,6,0.25) !important; background: rgba(217,119,6,0.06) !important; }
    .drawer-actions .btn-danger   { color: #dc2626 !important; border-color: rgba(220,38,38,0.25) !important; background: rgba(220,38,38,0.06) !important; }
    .drawer-actions .btn-info     { color: #0891b2 !important; border-color: rgba(8,145,178,0.25) !important; background: rgba(8,145,178,0.06) !important; }
    .drawer-actions .btn-secondary{ color: #64748b !important; border-color: rgba(100,116,139,0.2) !important; }
    .drawer-actions .btn-success  { color: #16a34a !important; border-color: rgba(22,163,74,0.25) !important; background: rgba(22,163,74,0.06) !important; }
    .drawer-actions .btn-outline-warning { color: #d97706 !important; border-color: rgba(217,119,6,0.3) !important; background: rgba(217,119,6,0.06) !important; }
    .drawer-actions .btn-outline-info { color: #0891b2 !important; border-color: rgba(8,145,178,0.3) !important; background: rgba(8,145,178,0.06) !important; }
    .drawer-actions .btn i { font-size: 15px; flex-shrink: 0; }
    #tabelaViagens .btn,
    #tabelaViagens button:not(.btn-circle-mobile):not([data-bs-dismiss]):not(.btn-close) {
        width: 32px; height: 32px; padding: 0;
        display: inline-flex; align-items: center; justify-content: center;
        border-radius: 999px; font-size: 12px;
        background: rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.1);
        color: #475569; transition: all .15s;
    }
    #tabelaViagens .btn:hover { background: rgba(0,0,0,0.12); color: #0f172a; }
    [data-theme="dark"] #tabelaViagens .btn,
    [data-theme="dark"] #tabelaViagens button:not(.btn-circle-mobile):not([data-bs-dismiss]):not(.btn-close) {
        background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.1); color: #d4d4d8;
    }
    [data-theme="dark"] #tabelaViagens .btn:hover { background: rgba(255,255,255,0.12); color: #fff; }
    #tabelaViagens .btn-primary, #tabelaViagens .btn-info { color: #2563eb !important; border-color: rgba(37,99,235,0.3) !important; }
    [data-theme="dark"] #tabelaViagens .btn-primary, [data-theme="dark"] #tabelaViagens .btn-info { color: #60a5fa !important; border-color: rgba(96,165,250,0.3) !important; }
    #tabelaViagens .btn-danger { color: #dc2626 !important; border-color: rgba(220,38,38,0.3) !important; }
    [data-theme="dark"] #tabelaViagens .btn-danger { color: #f87171 !important; border-color: rgba(248,113,113,0.3) !important; }
    #tabelaViagens .btn-success { color: #16a34a !important; border-color: rgba(22,163,74,0.3) !important; }
    [data-theme="dark"] #tabelaViagens .btn-success { color: #34d399 !important; border-color: rgba(52,211,153,0.3) !important; }
    #tabelaViagens .btn-warning { color: #d97706 !important; border-color: rgba(217,119,6,0.3) !important; }
    [data-theme="dark"] #tabelaViagens .btn-warning { color: #fbbf24 !important; border-color: rgba(251,191,36,0.3) !important; }
    #tabelaViagens .btn-secondary { color: #94a3b8 !important; }
    .agency-badge {
        font-size: 9px; background-color: rgba(99,102,241,0.12);
        color: #6366f1; border: 1px solid rgba(99,102,241,0.25);
        padding: 2px 8px; border-radius: 999px;
        display: inline-flex; align-items: center; gap: 4px;
        font-weight: 700; margin-right: 5px;
    }
    [data-theme="dark"] .agency-badge { color: #a5b4fc; }
    .req-pending {
        background-color: rgba(245,158,11,0.12); color: #d97706;
        border: 1px solid rgba(245,158,11,0.3);
        padding: 4px 12px; border-radius: 999px;
        font-size: 10px; font-weight: 700;
        display: inline-flex; align-items: center; gap: 4px;
    }
    [data-theme="dark"] .req-pending { color: #fbbf24; }
    /* Toolbar / dropdown */
    .rides-toolbar { position: relative; z-index: 20; }
    .rides-toolbar .dropdown { position: static; }
    .rides-toolbar .dropdown-menu { z-index: 1080; margin-top: 6px; padding: 6px; min-width: 180px; }
    .rides-toolbar .dropdown-menu .dropdown-item { border-radius: 10px; padding: 8px 12px; font-size: 13px; font-weight: 600; color: #0f172a !important; transition: background .15s; }
    .rides-toolbar .dropdown-menu .dropdown-item:hover,
    .rides-toolbar .dropdown-menu .dropdown-item:focus { background: rgba(0,0,0,0.06); color: #0f172a !important; }
    [data-theme="dark"] .rides-toolbar .dropdown-menu .dropdown-item { color: #fff !important; }
    [data-theme="dark"] .rides-toolbar .dropdown-menu .dropdown-item:hover,
    [data-theme="dark"] .rides-toolbar .dropdown-menu .dropdown-item:focus { background: rgba(255,255,255,0.08); color: #fff !important; }
    #tabelaViagens, #tabelaViagens_wrapper { position: relative; z-index: 1; }
    /* Processing indicator */
    .dataTables_processing {
        position: absolute !important; top: 24px !important; left: 50% !important;
        transform: translateX(-50%) !important; width: auto !important; margin: 0 !important;
        padding: 10px 18px !important;
        background: rgba(255,255,255,0.92) !important; backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(0,0,0,0.1) !important; border-radius: 999px !important;
        color: #475569 !important; font-size: 0 !important;
        box-shadow: 0 8px 30px rgba(0,0,0,0.12); z-index: 10;
    }
    [data-theme="dark"] .dataTables_processing {
        background: rgba(20,20,20,0.92) !important; border-color: rgba(255,255,255,0.1) !important;
        box-shadow: 0 8px 30px rgba(0,0,0,0.4);
    }
    .dataTables_processing::before {
        content: ""; display: inline-block; width: 14px; height: 14px; border-radius: 50%;
        border: 2px solid rgba(0,0,0,0.1); border-top-color: #2563eb;
        animation: spinModern 0.7s linear infinite; vertical-align: middle;
    }
    [data-theme="dark"] .dataTables_processing::before { border-color: rgba(255,255,255,0.15); border-top-color: #60a5fa; }
    .dataTables_processing::after { content: "<?= t('rides.loading') ?>"; font-size: 12px; font-weight: 700; color: #475569; margin-left: 10px; letter-spacing: 0.02em; vertical-align: middle; }
    [data-theme="dark"] .dataTables_processing::after { color: #e4e4e7; }
    @keyframes spinModern { to { transform: rotate(360deg); } }
    /* Skeleton */
    .rides-skeleton { display: grid; gap: 10px; margin-top: 4px; }
    @media (min-width: 992px) { .rides-skeleton { grid-template-columns: repeat(2,1fr); } }
    .rides-skeleton .sk-card {
        height: 78px; border-radius: 16px;
        background: linear-gradient(110deg, rgba(0,0,0,0.04) 8%, rgba(0,0,0,0.07) 18%, rgba(0,0,0,0.04) 33%);
        background-size: 200% 100%; border: 1px solid rgba(0,0,0,0.06);
        animation: shimmer 1.4s linear infinite;
    }
    [data-theme="dark"] .rides-skeleton .sk-card {
        background: linear-gradient(110deg, rgba(255,255,255,0.04) 8%, rgba(255,255,255,0.08) 18%, rgba(255,255,255,0.04) 33%);
        border-color: rgba(255,255,255,0.06);
    }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
    /* No modo timeline (hoje), esconder paginação — tudo carregado de uma vez */
    .today-mode .dataTables_paginate { display: none !important; }
    /* DataTable search */
    .dataTables_filter, .dataTables_length, .dataTables_info { display: none !important; }
    #filter-container .dataTables_filter { display: block !important; margin: 0; padding: 0; }
    #filter-container .dataTables_filter label { display: block; margin: 0; color: transparent; font-size: 0; }
    #filter-container .dataTables_filter input {
        width: 100%; background: rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.08);
        color: #0f172a; padding: 10px 14px 10px 38px; border-radius: 14px;
        font-size: 13px; outline: none; font-family: inherit;
    }
    #filter-container .dataTables_filter input::placeholder { color: #94a3b8; }
    #filter-container .dataTables_filter input:focus { border-color: rgba(37,99,235,0.4); }
    [data-theme="dark"] #filter-container .dataTables_filter input { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); color: #fff; }
    [data-theme="dark"] #filter-container .dataTables_filter input:focus { border-color: rgba(255,255,255,0.2); }
    /* Pagination */
    .dataTables_paginate { padding: 20px 0 4px 0; }
    .dataTables_paginate .pagination { margin: 0; padding: 0; gap: 4px; display: flex; justify-content: center; flex-wrap: wrap; list-style: none; }
    .dataTables_paginate .page-item { list-style: none; }
    .dataTables_paginate .page-link {
        background: rgba(0,0,0,0.04) !important; border: 1px solid rgba(0,0,0,0.08) !important;
        color: #475569 !important; padding: 0 !important;
        min-width: 34px; height: 34px; line-height: 32px;
        border-radius: 999px !important; font-size: 12px; font-weight: 700;
        text-align: center; box-shadow: none !important; transition: all .15s; outline: none !important;
        display: inline-flex; align-items: center; justify-content: center;
    }
    .dataTables_paginate .page-link:hover { background: rgba(0,0,0,0.08) !important; color: #0f172a !important; border-color: rgba(0,0,0,0.15) !important; }
    .dataTables_paginate .page-link:focus { box-shadow: none !important; }
    .dataTables_paginate .page-item.active .page-link { background: #2563eb !important; border-color: #2563eb !important; color: #fff !important; }
    .dataTables_paginate .page-item.disabled .page-link { opacity: 0.3; }
    .dataTables_paginate .page-item.previous .page-link, .dataTables_paginate .page-item.next .page-link { font-size: 14px; }
    [data-theme="dark"] .dataTables_paginate .page-link { background: rgba(255,255,255,0.04) !important; border-color: rgba(255,255,255,0.08) !important; color: #d4d4d8 !important; }
    [data-theme="dark"] .dataTables_paginate .page-link:hover { background: rgba(255,255,255,0.09) !important; color: #fff !important; border-color: rgba(255,255,255,0.15) !important; }
    [data-theme="dark"] .dataTables_paginate .page-item.disabled .page-link { color: #71717a !important; background: transparent !important; border-color: rgba(255,255,255,0.04) !important; }
    table.dataTable.no-footer { border-bottom: none; }
    /* Modal */
    .modal-content {
        background: rgba(255,255,255,0.96); backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);
        border: 1px solid rgba(0,0,0,0.1); border-radius: 28px; color: #0f172a;
        box-shadow: 0 24px 64px rgba(0,0,0,0.12);
    }
    [data-theme="dark"] .modal-content { background: rgba(20,20,20,0.95); border-color: rgba(255,255,255,0.1); color: #fff; box-shadow: none; }
    .modal-header, .modal-footer { border-color: rgba(0,0,0,0.08); }
    [data-theme="dark"] .modal-header, [data-theme="dark"] .modal-footer { border-color: rgba(255,255,255,0.08); }
    .modal-title { color: #0f172a; font-weight: 800; }
    [data-theme="dark"] .modal-title { color: #fff; }
    .btn-close { opacity: 0.5; }
    .btn-close:hover { opacity: 1; }
    [data-theme="dark"] .btn-close { filter: invert(1) brightness(2); opacity: 0.6; }
    /* Form controls */
    .form-control-custom, .form-select-custom {
        background-color: rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.1);
        color: #0f172a; border-radius: 14px; padding: 8px 12px; font-size: 13px;
        width: 100%; outline: none;
    }
    .form-control-custom:focus, .form-select-custom:focus {
        border-color: rgba(37,99,235,0.5); box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        background-color: rgba(0,0,0,0.02); color: #0f172a;
    }
    .form-control-custom::placeholder { color: #94a3b8; }
    .form-control-custom:disabled, .form-select-custom:disabled { opacity: 0.6; }
    .form-select-custom option { background: #fff; color: #0f172a; }
    [data-theme="dark"] .form-control-custom, [data-theme="dark"] .form-select-custom { background-color: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.1); color: #fff; }
    [data-theme="dark"] .form-control-custom:focus, [data-theme="dark"] .form-select-custom:focus { border-color: rgba(96,165,250,0.6); box-shadow: 0 0 0 3px rgba(37,99,235,0.15); background-color: rgba(255,255,255,0.06); color: #fff; }
    [data-theme="dark"] .form-select-custom option { background: #18181b; color: #fff; }
    .form-label.small, .form-label { color: #64748b !important; font-size: 10px !important; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; }
    [data-theme="dark"] .form-label.small, [data-theme="dark"] .form-label { color: #a1a1aa !important; }
    [data-theme="light"] #editTripTypeDisplay { color: #0f172a !important; }
    [data-theme="light"] .spinner-border.text-light { border-color: rgba(0,0,0,0.15) !important; border-right-color: transparent !important; }
    [data-theme="dark"] input[type="date"]::-webkit-calendar-picker-indicator,
    [data-theme="dark"] input[type="time"]::-webkit-calendar-picker-indicator,
    [data-theme="dark"] input[type="datetime-local"]::-webkit-calendar-picker-indicator { filter: invert(1); opacity: 0.6; }
    /* Btn modern */
    .btn-modern {
        background: linear-gradient(135deg,#2563eb,#1d4ed8); color: #fff; border: none; border-radius: 14px;
        padding: 12px 18px; font-weight: 800; font-size: 13px;
        transition: all .2s; box-shadow: 0 8px 20px rgba(37,99,235,0.25);
    }
    .btn-modern:hover { background: linear-gradient(135deg,#1d4ed8,#1e3a8a); color: #fff; }
    .btn-modern:active { transform: scale(0.97); }
    /* Logs */
    .logs-timeline { border-left: 2px solid rgba(0,0,0,0.1); margin-left: 10px; padding-left: 25px; }
    [data-theme="dark"] .logs-timeline { border-left-color: rgba(255,255,255,0.1); }
    .logs-item { position: relative; margin-bottom: 25px; }
    .logs-dot { width: 14px; height: 14px; background: #f1f5f9; border: 3px solid rgba(0,0,0,0.15); border-radius: 50%; position: absolute; left: -33px; top: 5px; z-index: 1; }
    [data-theme="dark"] .logs-dot { background: #18181b; border-color: rgba(255,255,255,0.15); }
    .logs-item.completed .logs-dot { border-color: #10b981; background: #10b981; }
    .logs-title { font-weight: 700; color: #0f172a; margin-bottom: 2px; font-size: 14px; }
    [data-theme="dark"] .logs-title { color: #fff; }
    .logs-date { font-size: 12px; color: #71717a; }
    @media (max-width: 576px) { .modal-body { padding: 1rem !important; } .modal-title { font-size: 1rem; } .form-control-custom, .form-select-custom { padding: 10px 12px; font-size: 13px; } }
    @media (max-width: 991px) {
        /* ── Tab pills: single horizontally-scrollable row ── */
        #ride-tabs {
            flex-wrap: nowrap !important;
            overflow-x: auto;
            padding-bottom: 4px;
        }
        #ride-tabs::-webkit-scrollbar { display: none; }

        /* ── Toolbar: search on its own full-width row (rendered first),
              action buttons below in a tight row ── */
        #filter-container {
            flex: 0 0 100%;
            order: -1;
        }
        /* Push + Novo to the far right of the tools row */
        .rides-toolbar .btn-primary-modern { margin-left: auto; }
        /* Slightly smaller icon buttons to give breathing room */
        .rides-toolbar .icon-btn { width: 36px; height: 36px; }

        /* ── Pagination: horizontal scroll if many pages ── */
        .p-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .dataTables_paginate {
            padding: 16px 0 8px 0;
            /* Clear the floating bottom nav pill so the last page row isn't hidden behind it */
            margin-bottom: calc(84px + var(--safe-bottom));
        }
    }
    .page-title { font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; }
    [data-theme="dark"] .page-title { color: #fff; }
    .page-subtitle { font-size: 11px; color: #94a3b8; font-weight: 600; }
    .btn-circle-mobile { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; }
    .edit-type-row { border-top: 1px solid rgba(0,0,0,0.08); }
    [data-theme="dark"] .edit-type-row { border-top: 1px solid rgba(255,255,255,0.08); }
    /* Tabs scroll wrapper — fade right edge to hint at more pills */
    .ride-tabs-wrap { position: relative; }
    @media (max-width: 991px) {
        .ride-tabs-wrap::after {
            content: '';
            position: absolute;
            right: 0; top: 0; bottom: 4px;
            width: 40px;
            background: linear-gradient(to right, transparent, rgba(241,245,249,0.98));
            pointer-events: none;
            z-index: 2;
        }
        [data-theme="dark"] .ride-tabs-wrap::after {
            background: linear-gradient(to right, transparent, rgba(2,6,23,0.98));
        }
    }
    /* ── Stops modal ─────────────────────────────────────── */
    #stopsModal .modal-content { border-radius: 20px !important; }
    #stopsModal .modal-header  { border-bottom: 1px solid rgba(0,0,0,0.07) !important; flex-wrap: wrap; gap: 8px; }
    [data-theme="dark"] #stopsModal .modal-header { border-color: rgba(255,255,255,0.07) !important; }
    #stopsModal .modal-body    { padding: 1rem 1rem 1.25rem !important; }
    /* Stop cards */
    .stop-item {
        background: rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.08);
        border-radius: 14px; padding: 12px; min-height: 52px; touch-action: pan-y;
        transition: background .15s;
    }
    [data-theme="dark"] .stop-item {
        background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08);
    }
    .stop-drag-handle {
        cursor: grab; width: 24px; min-height: 44px; touch-action: none;
        color: #cbd5e1; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    [data-theme="dark"] .stop-drag-handle { color: #52525b; }
    .stop-type-badge {
        width: 28px; height: 28px; border-radius: 8px; font-size: 11px; font-weight: 900;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .stop-type-badge.pickup  { background: rgba(239,68,68,.12);  color: #ef4444; }
    .stop-type-badge.dropoff { background: rgba(34,197,94,.12);  color: #22c55e; }
    .stop-loc  { font-weight: 700; font-size: 13px; margin-bottom: 1px; }
    .stop-meta { font-size: 11px; color: #94a3b8; margin-bottom: 0; }
    .stop-ref  { font-size: 10px; color: #94a3b8; padding-top: 2px; flex-shrink: 0; }
    /* Stop edit fields */
    .stop-edit .stop-field {
        background: rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.12);
        color: inherit; border-radius: 8px; padding: 5px 9px; font-size: 12px; font-weight: 600;
        outline: none;
    }
    .stop-edit .stop-field:focus { border-color: rgba(37,99,235,0.5); }
    [data-theme="dark"] .stop-edit .stop-field {
        background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.12); color: #fff;
    }
    /* Header action buttons */
    .stops-action-btn {
        border-radius: 10px; font-weight: 800; font-size: 11px; min-height: 36px;
        padding: 0 12px; border: 1px solid transparent; cursor: pointer; display: inline-flex;
        align-items: center; gap: 5px; transition: background .15s;
    }
    .stops-action-btn.edit  { background: rgba(37,99,235,.1);   color: #2563eb; border-color: rgba(37,99,235,.25); }
    .stops-action-btn.save  { background: rgba(34,197,94,.1);   color: #16a34a; border-color: rgba(34,197,94,.25); }
    .stops-action-btn.split { background: rgba(251,191,36,.1);  color: #d97706; border-color: rgba(251,191,36,.25); }
    [data-theme="dark"] .stops-action-btn.edit  { color: #60a5fa; border-color: rgba(96,165,250,.3); }
    [data-theme="dark"] .stops-action-btn.save  { color: #4ade80; border-color: rgba(74,222,128,.3); }
    [data-theme="dark"] .stops-action-btn.split { color: #fbbf24; border-color: rgba(251,191,36,.3); }
    @media (max-width: 576px) {
        #stopsModal .modal-header .stops-header-actions { width: 100%; }
        .stops-action-btn { flex: 1; justify-content: center; }
    }
    /* Touch-action on all toolbar/action buttons */
    .icon-btn, .btn-ghost, .btn-primary-modern, .pill {
        touch-action: manipulation;
    }
    /* ── Sticky filters: tabs + toolbar stay pinned while the list scrolls.
          The page title above scrolls away; they pin right under the app header
          (--sr-header-h is published by the layout). Scroll context: #app-container. ── */
    .rides-sticky {
        position: sticky;
        top: var(--sr-header-h, 0px);
        z-index: 30;
        margin: 0 -24px;                 /* full-bleed over the main's px-6 padding */
        padding: 8px 24px 2px;
        background: rgba(241,245,249,0.82);
        backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px);
        transition: padding .22s ease;
    }
    [data-theme="dark"] .rides-sticky { background: rgba(2,6,23,0.82); }
    .rides-sticky .ride-tabs-wrap,
    .rides-sticky .rides-toolbar { transition: margin .22s ease; }
    /* ── Scrolled: ultra-compact sticky ───────────────────────────── */
    #app-container.scrolled .rides-sticky {
        padding: 3px 24px 2px;
    }
    #app-container.scrolled .rides-sticky .ride-tabs-wrap { margin-bottom: 2px !important; }
    /* Tab pills — smaller */
    #app-container.scrolled .rides-sticky .pill {
        padding: 3px 9px; font-size: 10px;
    }
    #app-container.scrolled .rides-sticky .pill-count {
        min-width: 13px; height: 13px; font-size: 8px; padding: 0 3px;
    }
    #app-container.scrolled .rides-sticky .pill i { display: none; }
    /* Toolbar: strip the glass card, make it a flat thin bar */
    #app-container.scrolled .rides-sticky .rides-toolbar {
        background: transparent !important;
        border: none !important;
        backdrop-filter: none !important; -webkit-backdrop-filter: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
        margin-bottom: 3px !important;
        gap: 5px !important;
        box-shadow: none !important;
    }
    /* Search input compact */
    #app-container.scrolled .rides-sticky .search-wrap input {
        padding: 5px 10px 5px 30px !important;
        font-size: 11px !important;
        border-radius: 9px !important;
    }
    #app-container.scrolled .rides-sticky .search-wrap .search-icon { left: 9px; width: 13px; height: 13px; }
    /* Icon buttons compact */
    #app-container.scrolled .rides-sticky .icon-btn {
        width: 28px !important; height: 28px !important;
    }
    #app-container.scrolled .rides-sticky .icon-btn svg { width: 13px !important; height: 13px !important; }
    /* + Novo compact */
    #app-container.scrolled .rides-sticky .btn-primary-modern {
        padding: 5px 11px !important; font-size: 10px !important; border-radius: 9px !important;
    }
    /* ── Timeline: próxima viagem destaca-se ────────────────────────── */
    #tabelaViagens tbody tr.ride-current {
        border-color: rgba(37,99,235,0.4) !important;
        box-shadow: 0 0 0 1px rgba(37,99,235,0.12), 0 2px 12px rgba(37,99,235,0.08);
    }
    #tabelaViagens tbody tr.ride-current::before {
        content: 'PRÓXIMA';
        position: absolute; top: -9px; left: 14px;
        font-size: 8px; font-weight: 800; letter-spacing: 0.15em;
        color: #2563eb; background: rgba(37,99,235,0.08);
        border: 1px solid rgba(37,99,235,0.2);
        padding: 1px 7px; border-radius: 999px;
    }
    [data-theme="dark"] #tabelaViagens tbody tr.ride-current {
        border-color: rgba(96,165,250,0.4) !important;
        box-shadow: 0 0 0 1px rgba(96,165,250,0.12), 0 2px 12px rgba(96,165,250,0.08);
    }
    [data-theme="dark"] #tabelaViagens tbody tr.ride-current::before {
        color: #60a5fa; background: rgba(96,165,250,0.08); border-color: rgba(96,165,250,0.2);
    }

    /* ── Autocomplete de localizações (OSM / Photon) ─────────────── */
    .place-field { position: relative; }
    .place-suggestions {
        position: absolute; z-index: 1080; left: 0; right: 0; top: 100%;
        margin-top: 4px; background: #fff; border: 1px solid rgba(0,0,0,.1);
        border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,.18);
        overflow: hidden; display: none; max-height: 240px; overflow-y: auto;
    }
    [data-theme="dark"] .place-suggestions { background: #1e293b; border-color: rgba(255,255,255,.1); }
    .place-suggestions.open { display: block; }
    .place-item {
        padding: 9px 12px; font-size: 12.5px; cursor: pointer; line-height: 1.3;
        border-bottom: 1px solid rgba(0,0,0,.05); color: #1e293b;
    }
    [data-theme="dark"] .place-item { color: #e2e8f0; border-bottom-color: rgba(255,255,255,.05); }
    .place-item:last-child { border-bottom: 0; }
    .place-item:hover, .place-item.active { background: rgba(37,99,235,.1); }
    .place-item .pi-main { font-weight: 700; }
    .place-item .pi-sub  { font-size: 11px; opacity: .65; }
    .place-loading { padding: 9px 12px; font-size: 11px; opacity: .6; }
</style>
<?php $ridesHead = ob_get_clean(); ?>
<?php
ob_start();
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    var SR_RIDES = {
        oldest: "<?= t('rides.oldest_first') ?>",
        newest: "<?= t('rides.newest_first') ?>",
        deleteQ: "<?= t('rides.delete_ride') ?>",
        aboutTo: "<?= t('rides.about_to_delete') ?>",
        cancel: "<?= t('rides.cancel') ?>",
        save: "<?= t('rides.save') ?>",
        loading: "<?= t('rides.loading') ?>",
        noRides: "<?= t('rides.no_rides') ?>"
    };

    let tabelaViagens;
    let selectionMode = false;
    let selectedIds = new Map(); // id → row data, persists across pages
    let _timelineScrolled = false; // auto-scroll to current ride only once per tab load

    $(document).ready(function () {
        const urlParams = new URLSearchParams(window.location.search);
        const currentStatus = urlParams.get('tab') || 'today';

        $('#ride-tabs a[data-status="' + currentStatus + '"]').addClass('active');
        $('#ride-tabs a:not([data-status="' + currentStatus + '"])').removeClass('active');

        tabelaViagens = $('#tabelaViagens').DataTable({
            processing: true, serverSide: true,
            ajax: {
                url: 'rides-data.php?status=' + currentStatus, type: 'GET', cache: false,
                data: function(d) {
                    if (window._dateFrom) d.date_from = window._dateFrom;
                    if (window._dateTo)   d.date_to   = window._dateTo;
                }
            },
            columns: [
                { data: 'raw_id', className: 'col-selection', orderable: false,
                  render: function(data, type, row) {
                      const id = row.raw_id ? row.raw_id : row.id.replace('#','');
                      return '<div class="mobile-checkbox-container"><input type="checkbox" class="form-check-input ride-checkbox" value="' + id + '"></div>';
                  }},
                { data: 'id' },
                { data: 'data_hora' },
                { data: 'condutor', render: function(data, type, row) {
                    if (row.status_pedido === 'pendente') return '<span class="req-pending"><i class="bi bi-shop me-1"></i> ' + (row.partner_name || 'Agency') + '</span>';
                    let html = '';
                    if (row.partner_name && row.partner_name.trim() !== '') html += '<span class="agency-badge"><i class="bi bi-shop"></i> ' + row.partner_name + '</span> ';
                    if (data) html += '<span>' + data + '</span>'; else html += '<span class="text-zinc-500 fst-italic">No Driver</span>';
                    return html;
                }},
                { data: 'recolha' },
                { data: 'entrega' },
                { data: 'tipo' },
                { data: 'chave', orderable: false },
                { data: 'acoes', orderable: false, render: function(data, type, row) {
                    if (row.status_pedido === 'pendente') {
                        const tripId = row.raw_id ? row.raw_id : row.id.replace('#','');
                        return '<div class="d-flex gap-2 justify-content-end"><button class="btn btn-success btn-sm" onclick="handleRequest(' + tripId + ',\'approve\')"><i class="bi bi-check-lg"></i></button><button class="btn btn-danger btn-sm" onclick="handleRequest(' + tripId + ',\'reject\')"><i class="bi bi-x-lg"></i></button></div>';
                    }
                    return '<div class="d-flex gap-1 justify-content-end align-items-center">' + data + '</div>';
                }},
                { data: 'client_name',  visible: false, searchable: true, defaultContent: '' },
                { data: 'flight_number', visible: false, searchable: true, defaultContent: '' }
            ],
            language: { search: '', searchPlaceholder: "<?= t('rides.search') ?>", lengthMenu: '', info: '', paginate: { next: '→', previous: '←' }, zeroRecords: SR_RIDES.noRides },
            order: [[2,'asc']], pageLength: currentStatus === 'today' ? 500 : 10, dom: 'frt<"p-wrap"p>',
            createdRow: function(row, data) {
                row.dataset.rideId = String(data.raw_id || '');
                if (data.is_completed) {
                    row.classList.add('ride-past');
                } else if (data.raw_status > 0) {
                    // Motorista já iniciou mas ainda não terminou → a decorrer
                    row.classList.add('ride-active');
                } else {
                    // status = 0: ainda não começou
                    const [datePart, timePart] = (data.data_hora || '').split(' ');
                    const [y, mo, d] = (datePart || '').split('-').map(Number);
                    const [h, mi] = (timePart || '0:0').split(':').map(Number);
                    const dt = new Date(y, mo - 1, d, h, mi);
                    const oneHourAgo = new Date(Date.now() - 60 * 60 * 1000);
                    if (!isNaN(dt.getTime()) && dt < oneHourAgo) {
                        row.classList.add('ride-overdue');
                    }
                }
                // Injetar nome do cliente na card (após o condutor, td 4)
                if (data.client_name) {
                    const driverCell = row.cells[3];
                    if (driverCell) {
                        const el = document.createElement('span');
                        el.className = 'card-client';
                        el.textContent = data.client_name;
                        driverCell.appendChild(el);
                    }
                }
            }
        });

        // Aplicar today-mode no load inicial se arrancar no tab Hoje
        if (currentStatus === 'today') {
            setTimeout(function() {
                document.getElementById('tabelaViagens_wrapper')?.classList.add('today-mode');
            }, 0);
        }

        // Open actions drawer on card tap (not in selection mode, not on interactive elements)
        $('#tabelaViagens tbody').on('click', 'tr', function(e) {
            if (selectionMode) return;
            if ($(e.target).is('input')) return;
            const rowData = tabelaViagens.row(this).data();
            if (rowData) openRideDrawer(rowData, this);
        });

        $('#tabelaViagens_filter').appendTo('#filter-container');
        $('#tabelaViagens_filter input').addClass('form-control-search');

        // Deep-link vindo do Quadro: ?q=<cliente> pré-preenche a pesquisa (e, no
        // separador "Todas", ordena do mais recente para o mais antigo).
        const _deepQ = urlParams.get('q');
        if (_deepQ) {
            if (currentStatus === 'all') tabelaViagens.order([2, 'desc']);
            $('#tabelaViagens_filter input').val(_deepQ);
            tabelaViagens.search(_deepQ).draw();
        }

        $('#ride-tabs a').on('click', function(e) {
            e.preventDefault();
            $('#ride-tabs a').removeClass('active');
            $(this).addClass('active');
            const status = $(this).data('status');
            const url = new URL(window.location);
            url.searchParams.set('tab', status);
            window.history.replaceState({}, '', url);
            tabelaViagens.search('');
            _timelineScrolled = false; // permitir auto-scroll na próxima carga
            // Hoje: carregar tudo para o timeline funcionar sem paginação
            tabelaViagens.page.len(status === 'today' ? 500 : 10);
            document.getElementById('tabelaViagens_wrapper')?.classList.toggle('today-mode', status === 'today');
            // "Todas" mostra histórico — mais recentes primeiro. Restantes: ordem cronológica.
            tabelaViagens.order(status === 'all' ? [2, 'desc'] : [2, 'asc']);
            tabelaViagens.ajax.url('rides-data.php?status=' + status).load();
            disableSelectionMode();
        });

        $('#selectAll').on('change', function() {
            $('.ride-checkbox').prop('checked', $(this).prop('checked'));
            updateBulkButton();
        });
        $(document).on('change', '.ride-checkbox', function() { updateBulkButton(); });

        $('#tabelaViagens').on('draw.dt', function() {
            lucide.createIcons();
            const skel = document.getElementById('ridesSkeleton');
            if (skel) skel.style.display = 'none';
            $('#tabelaViagens').show();
            // Restore checkbox state for rows visible on this page
            if (selectionMode) {
                $('.ride-checkbox').each(function() {
                    const id = $(this).val();
                    if (selectedIds.has(id)) $(this).prop('checked', true);
                });
            }
            // NOTE: badge is NOT refreshed here — that fired an extra HTTP request on
            // every sort/page/filter. It's refreshed once on load and after approve/reject.

            // ── Timeline: marcar próxima viagem e ancorar no scroll ───────
            const activeTab = $('#ride-tabs a.active').data('status');
            if (activeTab === 'today') {
                document.querySelectorAll('#tabelaViagens tbody tr.ride-current').forEach(function(r) {
                    r.classList.remove('ride-current');
                });
                const rows = Array.from(document.querySelectorAll('#tabelaViagens tbody tr'));
                // PRÓXIMA = primeira que está pendente E não está atrasada NEM em curso
                const firstCurrent = rows.find(function(r) {
                    return !r.classList.contains('ride-past')
                        && !r.classList.contains('ride-overdue')
                        && !r.classList.contains('ride-active');
                });
                if (firstCurrent) firstCurrent.classList.add('ride-current');

                // Pill "X concluídas" — atualizar ANTES de calcular o offset do scroll
                const doneCount = rows.filter(function(r) { return r.classList.contains('ride-past'); }).length;
                const pill = document.getElementById('completedStackedPill');
                const pillText = document.getElementById('completedStackedText');
                if (pill) {
                    if (doneCount > 0) {
                        pill.style.display = 'flex';
                        if (pillText) pillText.textContent = doneCount + ' concluída' + (doneCount !== 1 ? 's' : '') + ' acima';
                    } else {
                        pill.style.display = 'none';
                    }
                }

                // Scroll instantâneo ao primeiro carregamento (após pill estar visível → offsetHeight correto)
                if (firstCurrent && !_timelineScrolled) {
                    _timelineScrolled = true;
                    const container = document.getElementById('app-container');
                    const sticky    = document.querySelector('.rides-sticky');
                    const headerH   = parseInt(getComputedStyle(document.documentElement).getPropertyValue('--sr-header-h')) || 81;
                    const stickyH   = sticky ? sticky.offsetHeight : 0;
                    const cRect     = container.getBoundingClientRect();
                    const rRect     = firstCurrent.getBoundingClientRect();
                    const rowTop    = rRect.top - cRect.top + container.scrollTop;
                    const target    = Math.max(0, rowTop - headerH - stickyH - 12);
                    container.scrollTop = target;
                }
            }
        });

        refreshPendingBadge(); // initial badge load (once)

        $('#tabelaViagens').on('preXhr.dt', function() {
            const skel = document.getElementById('ridesSkeleton');
            if (skel) skel.style.display = 'grid';
            $('#tabelaViagens').hide();
        });

        $('#tabelaViagens').on('xhr.dt', function(e, settings, json) {
            if (!json) return;
            const status = $('#ride-tabs a.active').data('status');
            const n = json.recordsFiltered ?? json.recordsTotal ?? 0;
            if (status === 'today')    { const b = document.getElementById('todayBadge');    if (b) b.textContent = n; }
            if (status === 'tomorrow') { const b = document.getElementById('tomorrowBadge'); if (b) b.textContent = n; }
            if (status === 'pending')  { const b = document.getElementById('pendingBadge');  if (b) b.textContent = n; }
        });
    });

    // ── Date-range filter ──────────────────────────────────────────
    function applyDateFilter() {
        window._dateFrom = document.getElementById('dateFrom').value || '';
        window._dateTo   = document.getElementById('dateTo').value   || '';
        const hasRange   = !!(window._dateFrom || window._dateTo);
        const btn = document.getElementById('dateFilterBtn');
        if (btn) btn.classList.toggle('active', hasRange);

        // Um intervalo de datas só faz sentido sobre TODO o histórico. Se o utilizador
        // estiver no separador "Hoje" (ou outro de dia único), saltamos para "Todas" e
        // ordenamos do mais recente para o mais antigo — é assim que se procuram
        // serviços passados (ex.: reclamações).
        if (hasRange) {
            const active = $('#ride-tabs a.active').data('status');
            if (active !== 'all') {
                $('#ride-tabs a').removeClass('active');
                $('#ride-tabs a[data-status="all"]').addClass('active');
                const url = new URL(window.location);
                url.searchParams.set('tab', 'all');
                window.history.replaceState({}, '', url);
                tabelaViagens.search('');
                tabelaViagens.order([2, 'desc']);
                tabelaViagens.ajax.url('rides-data.php?status=all').load();
                return;
            }
        }
        tabelaViagens.ajax.reload();
    }
    function clearDateFilter() {
        document.getElementById('dateFrom').value = '';
        document.getElementById('dateTo').value   = '';
        window._dateFrom = ''; window._dateTo = '';
        const btn = document.getElementById('dateFilterBtn');
        if (btn) btn.classList.remove('active');
        tabelaViagens.ajax.reload();
    }

    function refreshPendingBadge() {
        $.getJSON('rides-data.php?status=requests', function(res) {
            const n = res?.recordsTotal ?? 0;
            const badge = document.getElementById('pendingRequestsBadge');
            if (!badge) return;
            badge.textContent = n;
            badge.classList.toggle('has-pending', n > 0);
        }).fail(function() {});
    }

    function toggleSelectionMode() {
        selectionMode = !selectionMode;
        const t = document.getElementById('tabelaViagens');
        if (selectionMode) {
            t.parentElement.classList.add('selection-active');
            document.body.classList.add('selection-active');
        } else { disableSelectionMode(); }
    }
    function disableSelectionMode() {
        selectionMode = false;
        selectedIds.clear();
        const t = document.getElementById('tabelaViagens');
        t.parentElement.classList.remove('selection-active');
        document.body.classList.remove('selection-active');
        $('.ride-checkbox').prop('checked', false);
        $('#selectAll').prop('checked', false);
        updateBulkButton();
    }
    function updateBulkButton() {
        // Sync current-page checkboxes into selectedIds
        $('.ride-checkbox').each(function() {
            const id  = $(this).val();
            const tr  = $(this).closest('tr');
            const row = tabelaViagens.row(tr).data();
            if ($(this).prop('checked')) { if (row) selectedIds.set(id, row); }
            else { selectedIds.delete(id); }
        });
        const selected = selectedIds.size;
        $('#selectedCount').text(selected);
        $('#aggCount').text(selected);
        $('#btnBulkDelete').toggle(selected > 0);
        $('#btnBulkAggregate').toggle(selected >= 2);
    }
    function aggregateSelected() {
        const rows = Array.from(selectedIds.values());
        if (rows.length < 2) { toastr.warning('<?= View::e(t('rides.aggregate_min')) ?>'); return; }

        // Só Shared (raw_type === 0) podem ser agrupados
        const nonShared = rows.filter(r => (r.raw_type ?? -1) !== 0);
        if (nonShared.length > 0) {
            toastr.warning('<?= View::e(t('rides.aggregate_shared_only')) ?>');
            return;
        }

        const ids = rows.map(r => r.raw_id ?? r.id.replace('#',''));
        const fd = new FormData(); fd.append('ids_bulk', JSON.stringify(ids));
        fetch('/SRMT/public/admin/ride-aggregate.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) { toastr.success('<?= View::e(t('rides.aggregate_done')) ?> (#' + d.master_id + ')'); disableSelectionMode(); tabelaViagens.ajax.reload(null, false); }
                else { toastr.error(d.error || 'Erro'); }
            })
            .catch(() => toastr.error('Falha de rede'));
    }

    // ── Modal de paragens ──────────────────────────────────────────
    let _stopsMasterId = 0;
    let _stopsSortable = null;
    let _stopsEditMode = false;

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function openStopsModal(masterId, stopCount) {
        _stopsMasterId = masterId;
        _stopsEditMode = false;
        _updateEditToggleUI();
        document.getElementById('stopsModalSub').textContent = '#' + masterId + ' · ' + stopCount + ' <?= t('rides.stops_badge') ?>';
        const list = document.getElementById('stopsList');
        list.innerHTML = '';
        document.getElementById('stopsLoading').classList.remove('d-none');

        fetch('/SRMT/public/admin/ride-stops.php?ride_id=' + masterId)
            .then(r => r.json())
            .then(d => {
                document.getElementById('stopsLoading').classList.add('d-none');
                if (!d.success) { toastr.error(d.error || 'Erro'); return; }
                renderStops(d.stops);
            }).catch(() => { document.getElementById('stopsLoading').classList.add('d-none'); toastr.error('Falha de rede'); });

        new bootstrap.Modal(document.getElementById('stopsModal')).show();
    }

    function _initSortable() {
        if (_stopsSortable) { _stopsSortable.destroy(); _stopsSortable = null; }
        const list = document.getElementById('stopsList');
        _stopsSortable = Sortable.create(list, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'opacity-30',
            touchStartThreshold: 4,
            onEnd() {
                // Auto-save order immediately after every drop
                const ids = Array.from(list.querySelectorAll('.stop-item')).map(el => parseInt(el.dataset.id));
                const fd  = new FormData();
                fd.append('master_id', _stopsMasterId);
                fd.append('ordered_ids', JSON.stringify(ids));
                fetch('/SRMT/public/admin/ride-stops-reorder.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(d => { if (!d.success) toastr.error(d.error || 'Erro ao guardar ordem'); })
                    .catch(() => toastr.error('Falha de rede'));
            },
        });
    }

    function renderStops(stops) {
        const list = document.getElementById('stopsList');
        list.innerHTML = '';
        if (!stops || stops.length === 0) {
            list.innerHTML = '<p class="text-zinc-500 text-sm text-center py-4"><?= t('rides.stops_empty') ?></p>';
            return;
        }
        stops.forEach(s => {
            const isPickup = s.stop_type === 'pickup';
            const div = document.createElement('div');
            div.className = 'stop-item';
            div.dataset.id   = s.id;
            div.dataset.type = s.stop_type;

            // ── View mode ──────────────────────────────────────────────
            const view = document.createElement('div');
            view.className = 'stop-view d-flex align-items-start gap-3';
            view.innerHTML = `
                <span class="drag-handle stop-drag-handle">
                    <i class="bi bi-grip-vertical" style="font-size:15px;pointer-events:none"></i>
                </span>
                <span class="stop-type-badge ${isPickup ? 'pickup' : 'dropoff'}">${isPickup ? 'P' : 'D'}</span>
                <div class="flex-1 min-w-0">
                    <p class="stop-loc">${escHtml(s.location || '')}</p>
                    <p class="stop-meta">
                        ${s.scheduled_time ? '<span>' + escHtml(s.scheduled_time.substring(0,5)) + '</span> · ' : ''}
                        ${s.client_name   ? escHtml(s.client_name) : ''}
                        ${s.pax_total     ? ' · ' + s.pax_total + ' pax' : ''}
                        ${s.reference_no  ? ' · ' + escHtml(s.reference_no) : ''}
                    </p>
                </div>
                <span class="stop-ref">#${escHtml(String(s.source_service_id || ''))}</span>
            `;

            // ── Edit mode ──────────────────────────────────────────────
            const edit = document.createElement('div');
            edit.className = 'stop-edit d-none';
            edit.innerHTML = `
                <div class="d-flex align-items-center gap-2 mb-2">
                    <select class="stop-field flex-shrink-0" data-f="stop_type" style="width:auto">
                        <option value="pickup"  ${isPickup ? 'selected' : ''}>P – Recolha</option>
                        <option value="dropoff" ${!isPickup ? 'selected' : ''}>D – Entrega</option>
                    </select>
                    <input class="stop-field flex-1" data-f="location" value="${escHtml(s.location || '')}" placeholder="Local" style="min-width:0">
                </div>
                <div class="d-flex gap-2">
                    <input class="stop-field flex-shrink-0" data-f="scheduled_time" type="time" value="${escHtml(s.scheduled_time ? s.scheduled_time.substring(0,5) : '')}" style="width:90px">
                    <input class="stop-field flex-1" data-f="client_name" value="${escHtml(s.client_name || '')}" placeholder="Cliente" style="min-width:0">
                    <input class="stop-field flex-shrink-0" data-f="pax_total" type="number" min="0" max="99" value="${s.pax_total || ''}" placeholder="Pax" style="width:56px">
                </div>
            `;

            div.appendChild(view);
            div.appendChild(edit);
            list.appendChild(div);
        });

        _applyEditMode(); // shows/hides view vs edit content
        _initSortable();  // always-on drag, auto-saves on drop
    }

    function _updateEditToggleUI() {
        const btn  = document.getElementById('btnStopsEditToggle');
        const save = document.getElementById('btnSaveStops');
        if (!btn) return;
        if (_stopsEditMode) {
            btn.innerHTML  = '<i class="bi bi-eye me-1"></i><?= t('rides.stops_view_mode') ?>';
            btn.style.background = 'rgba(139,92,246,.15)';
            btn.style.color      = '#a78bfa';
            btn.style.borderColor= 'rgba(139,92,246,.3)';
            save.style.display = '';
        } else {
            btn.innerHTML  = '<i class="bi bi-pencil me-1"></i><?= t('rides.stops_edit_mode') ?>';
            btn.style.background = 'rgba(6,182,212,.15)';
            btn.style.color      = '#06b6d4';
            btn.style.borderColor= 'rgba(6,182,212,.3)';
            save.style.display = 'none';
        }
    }

    function _applyEditMode() {
        document.querySelectorAll('#stopsList .stop-item').forEach(el => {
            el.querySelector('.stop-view').classList.toggle('d-none', _stopsEditMode);
            el.querySelector('.stop-edit').classList.toggle('d-none', !_stopsEditMode);
        });
        // Sortable stays alive regardless of edit mode — only init once per render
        if (!_stopsSortable) _initSortable();
    }

    function toggleStopsEditMode() {
        _stopsEditMode = !_stopsEditMode;
        _updateEditToggleUI();
        _applyEditMode();
    }

    function saveStopsAll() {
        const items = document.querySelectorAll('#stopsList .stop-item');
        const stops = Array.from(items).map(el => {
            const get = f => el.querySelector(`.stop-field[data-f="${f}"]`)?.value ?? '';
            return {
                id:             parseInt(el.dataset.id),
                stop_type:      get('stop_type') || el.dataset.type,
                location:       get('location'),
                scheduled_time: get('scheduled_time') || null,
                client_name:    get('client_name')    || null,
                pax_total:      get('pax_total')      || null,
            };
        });
        const fd = new FormData();
        fd.append('master_id', _stopsMasterId);
        fd.append('stops', JSON.stringify(stops));
        fetch('/SRMT/public/admin/ride-stops-save.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    toastr.success('<?= t('rides.stops_order_saved') ?>');
                    // Re-render in view mode with updated data
                    _stopsEditMode = false;
                    _updateEditToggleUI();
                    // Reload stops to reflect changes
                    fetch('/SRMT/public/admin/ride-stops.php?ride_id=' + _stopsMasterId)
                        .then(r => r.json()).then(d2 => { if (d2.success) renderStops(d2.stops); });
                } else { toastr.error(d.error || 'Erro'); }
            }).catch(() => toastr.error('Falha de rede'));
    }

    function disaggregateAll() {
        if (!confirm('<?= View::e(t('rides.stops_split_confirm')) ?>')) return;
        const fd = new FormData(); fd.append('ride_id', _stopsMasterId);
        fetch('/SRMT/public/admin/ride-disaggregate.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    bootstrap.Modal.getInstance(document.getElementById('stopsModal'))?.hide();
                    toastr.success('<?= View::e(t('rides.disaggregate_done')) ?>');
                    tabelaViagens.ajax.reload(null, false);
                } else { toastr.error(d.error || 'Erro'); }
            }).catch(() => toastr.error('Falha de rede'));
    }
    function bulkDelete() {
        const ids = [];
        $('.ride-checkbox:checked').each(function() { ids.push($(this).val()); });
        if (confirm('Delete ' + ids.length + ' selected ride(s)?')) {
            const currentStatus = $('#ride-tabs a.active').data('status') || 'today';
            const form = document.createElement('form');
            form.method = 'POST'; form.action = 'delete-ride.php';
            const inputIds = document.createElement('input'); inputIds.type = 'hidden'; inputIds.name = 'ids_bulk'; inputIds.value = JSON.stringify(ids); form.appendChild(inputIds);
            const inputTab = document.createElement('input'); inputTab.type = 'hidden'; inputTab.name = 'from_tab'; inputTab.value = currentStatus; form.appendChild(inputTab);
            document.body.appendChild(form); form.submit();
        }
    }
    function sortRides(col, dir) { tabelaViagens.order([col, dir]).draw(); }
    function setViagemId(id) { document.getElementById('viagemId_assign').value = id; }
    function changeTripType(tripId, currentType) {
        document.getElementById('tripId_changeType').value = tripId;
        if (currentType == 1) document.getElementById('private').checked = true;
        else document.getElementById('shared').checked = true;
        new bootstrap.Modal(document.getElementById('changeTripTypeModal')).show();
    }
    function handleRequest(id, action) {
        if (!confirm(action === 'approve' ? 'Approve this request?' : 'Reject this request?')) return;
        $.post('/SRMT/public/api/request-handle.php', { id: id, action: action }, function(res) {
            if (res.success) { toastr.success('Done!'); tabelaViagens.ajax.reload(); refreshPendingBadge(); }
            else { toastr.error('Error'); }
        });
    }
    function editTravel(id, dataHora, condutor, recolha, entrega, paxADT, paxCHD, paxBBY, flightNumber, clientName, clientNumber, serviceType, totalPrice, valorMotorista, driverNoteB64, adminNoteB64, legCode) {
        disableEdit();
        const b64dec = b => { try { return b ? decodeURIComponent(escape(atob(b))) : ''; } catch (e) { return ''; } };
        const driverNote = b64dec(driverNoteB64), adminNote = b64dec(adminNoteB64);
        const dnWrap = document.getElementById('editDriverNoteWrap');
        if (driverNote.trim()) { document.getElementById('editDriverNoteText').textContent = driverNote; dnWrap.style.display = 'block'; }
        else { dnWrap.style.display = 'none'; }
        document.getElementById('editAdminNote').value = adminNote;
        document.getElementById('editTripId').value = id;
        document.getElementById('editDataHora').value = dataHora.replace(' ','T');
        document.getElementById('editCondutor').value = condutor;
        document.getElementById('editRecolha').value = recolha;
        document.getElementById('editEntrega').value = entrega;
        document.getElementById('editLegCode').value = (legCode === 'IN' || legCode === 'OT') ? legCode : '';
        document.getElementById('editpaxADT').value = paxADT;
        document.getElementById('editpaxCHD').value = paxCHD;
        document.getElementById('editPaxBBY').value = paxBBY;
        document.getElementById('editflightNumber').value = flightNumber;
        document.getElementById('editclientName').value = clientName;
        document.getElementById('editclientNumber').value = clientNumber;
        document.getElementById('editTotalPrice').value = totalPrice;
        document.getElementById('editValorMotorista').value = (valorMotorista === undefined || valorMotorista === null) ? '' : valorMotorista;
        document.getElementById('editTripTypeDisplay').value = serviceType == 1 ? 'Private' : 'Shared';
        document.getElementById('btnChangeTypeEdit').onclick = function() {
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            changeTripType(id, serviceType);
        };
    }
    function openChangeDriverFromEdit() {
        const id = document.getElementById('editTripId').value;
        if (!id) return;
        bootstrap.Modal.getInstance(document.getElementById('editModal'))?.hide();
        setViagemId(id);
        new bootstrap.Modal(document.getElementById('atribuirCondutorModal')).show();
    }
    function enableEdit() {
        document.querySelectorAll('#editTripForm input').forEach(input => { if (input.id !== 'editCondutor' && input.id !== 'editTripTypeDisplay' && input.id !== 'editTotalPrice') input.disabled = false; });
        document.getElementById('editLegCode').disabled = false;
        document.getElementById('editAdminNote').disabled = false;
        document.getElementById('saveChangesBtn').style.display = 'inline-block';
        const btn = document.getElementById('enableEditBtn');
        btn.textContent = 'Cancel'; btn.setAttribute('onclick','disableEdit()');
    }
    function disableEdit() {
        document.querySelectorAll('#editTripForm input').forEach(input => input.disabled = true);
        document.getElementById('editLegCode').disabled = true;
        document.getElementById('editAdminNote').disabled = true;
        document.getElementById('saveChangesBtn').style.display = 'none';
        const btn = document.getElementById('enableEditBtn');
        btn.textContent = "<?= t('rides.edit_details') ?>"; btn.setAttribute('onclick','enableEdit()');
    }
    let _deleteTripId = null;
    function setDeleteTrip(id, name) {
        document.getElementById('deleteTripName').textContent = name;
        _deleteTripId = id;
    }
    function doDeleteTrip() {
        if (!_deleteTripId) return;
        const currentStatus = $('#ride-tabs a.active').data('status') || 'today';
        const f = document.createElement('form');
        f.method = 'POST'; f.action = 'delete-ride.php';
        const i1 = document.createElement('input'); i1.type='hidden'; i1.name='id';       i1.value=_deleteTripId;  f.appendChild(i1);
        const i2 = document.createElement('input'); i2.type='hidden'; i2.name='from_tab';  i2.value=currentStatus;  f.appendChild(i2);
        document.body.appendChild(f); f.submit(); // CSRF token injected by layout
    }
    function viewTripLogs(id) {
        const modal = new bootstrap.Modal(document.getElementById('modalLogs'));
        const content = document.getElementById('logsContent');
        content.innerHTML = '<div class="text-center py-3"><div class="spinner-border text-light" role="status"></div></div>';
        modal.show();
        fetch('ride-logs.php?id=' + id).then(r => r.json()).then(res => {
            if (res.success) {
                const d = res.data;
                const fmt = t => {
                    if (!t) return '<span class="text-zinc-500 fst-italic small">Pending</span>';
                    const dd = new Date(t.replace(' ','T'));
                    return dd.toLocaleString('en-GB', { day:'2-digit', month:'2-digit', hour:'2-digit', minute:'2-digit' });
                };
                const esc = s => (s == null ? '' : String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])));

                let steps;
                if (d.is_aggregate_master === 1 && Array.isArray(d.stops) && d.stops.length) {
                    // Multi-stop: one "Arrived" + "Departed" pair per stop, in admin order
                    steps = [];
                    d.stops.forEach((s, i) => {
                        const isPickup = s.stop_type === 'pickup';
                        const who      = s.client_name ? ' · ' + esc(s.client_name) : '';
                        const place    = s.location ? ' — ' + esc(s.location) : '';
                        steps.push({ label: (isPickup ? 'Recolha ' : 'Entrega ') + (i + 1) + who + place, time: s.ts_arrived,  sub: 'Chegou' });
                        steps.push({ label: (isPickup ? 'Saída recolha ' : 'Saída entrega ') + (i + 1), time: s.ts_departed, sub: 'Saiu' });
                    });
                } else {
                    steps = [
                        { label: 'Started Pickup',       time: d.ts_start_pickup },
                        { label: 'Arrived at Pickup',    time: d.ts_arrived_pickup },
                        { label: 'With Client',          time: d.ts_with_client },
                        { label: 'Trip Started',         time: d.ts_start_trip },
                        { label: 'Trip Completed',       time: d.ts_completed }
                    ];
                }
                let html = '<div class="logs-timeline">';
                steps.forEach(step => {
                    const done = step.time != null;
                    html += '<div class="logs-item ' + (done ? 'completed' : 'pending') + '"><div class="logs-dot"></div><div class="logs-content"><div class="logs-title">' + step.label + '</div><div class="logs-date">' + fmt(step.time) + '</div></div></div>';
                });
                content.innerHTML = html + '</div>';
            } else {
                content.innerHTML = '<p class="text-center" style="color:#f87171">Error loading logs.</p>';
            }
        });
    }

    // ── Ride actions drawer ───────────────────────────────────────
    function openRideDrawer(row, tr) {
        const timeStr  = (row.data_hora || '').split(' ').slice(1).join(' ') || row.data_hora || '';
        const dateStr  = (row.data_hora || '').split(' ')[0] || '';
        const driverTxt = (tr ? tr.cells[3]?.innerText : '') || '';

        // Header
        document.getElementById('drawerTime').textContent   = timeStr || row.data_hora;
        document.getElementById('drawerDate').textContent   = dateStr;
        document.getElementById('drawerId').textContent     = row.id || ('#' + row.raw_id);
        document.getElementById('drawerPickup').textContent  = row.recolha ? row.recolha.replace(/<[^>]*>/g,'').split('\n')[0].trim() : '';
        document.getElementById('drawerDropoff').textContent = row.entrega ? row.entrega.replace(/<[^>]*>/g,'').trim() : '';
        document.getElementById('drawerDriver').textContent  = driverTxt;

        // Actions: inject server-generated HTML, unwrap the flex container so grid layout works
        const actionsEl = document.getElementById('drawerActions');
        const tmpWrap = document.createElement('div');
        tmpWrap.innerHTML = row.acoes || '';
        const innerFlex = tmpWrap.querySelector('.d-flex') || tmpWrap;
        actionsEl.innerHTML = innerFlex.innerHTML;

        // Admin can complete the service if the driver forgot — but only for rides that
        // are already in progress (status > 0) OR whose scheduled time is in the past.
        // Never allow concluding a future, not-yet-started ride.
        const rawStatus = parseInt(row.raw_status);
        let rideInPast = false;
        const dh = (row.data_hora || '').trim();
        if (dh) {
            const [dp, tp] = dh.split(' ');
            const rideDt = new Date(dp + 'T' + (tp || '00:00'));
            if (!isNaN(rideDt.getTime())) rideInPast = rideDt < new Date();
        }
        if (rawStatus !== 4 && (rawStatus > 0 || rideInPast)) {
            const cBtn = document.createElement('button');
            cBtn.type = 'button';
            cBtn.className = 'btn';
            cBtn.style.cssText = 'color:#16a34a !important;border-color:rgba(34,197,94,0.3) !important;background:rgba(34,197,94,0.08) !important;';
            cBtn.innerHTML = '<i class="bi bi-check-circle-fill"></i><span><?= t('rides.conclude') ?></span>';
            cBtn.addEventListener('click', function () { concludeRide(row.raw_id || row.id); });
            actionsEl.prepend(cBtn);
        }

        // Add text labels; remove assign-driver (agora no modal editar) e change-type
        actionsEl.querySelectorAll('button, a').forEach(function(btn) {
            const oc = btn.getAttribute('onclick') || '';
            // Remover assign driver (move-se para o modal de edição)
            if (oc.includes('atribuirCondutorModal')) { btn.remove(); return; }
            const icon = btn.querySelector('i');
            if (!icon) return;
            const cls = btn.className;
            let label = '';
            if (oc.includes('viewTripLogs'))                                   label = '<?= t('rides.trip_history') ?>';
            else if (cls.includes('btn-warning') && cls.includes('text-dark')) label = '<?= t('rides.view_details') ?>';
            else if (cls.includes('btn-danger'))                               label = '<?= t('rides.delete') ?>';
            else if (cls.includes('btn-secondary'))                            label = '<?= t('rides.delegate_btn') ?>';
            else if (cls.includes('btn-outline-warning'))                      label = '<?= t('rides.recall_btn') ?>';
            else if (cls.includes('btn-outline-info'))                         label = '<?= t('rides.stops_open_btn') ?>';
            else if (cls.includes('btn-success'))                              label = '<?= t('rides.approve') ?>';
            else if (cls.includes('btn-danger'))                               label = '<?= t('rides.delete') ?>';
            if (label) {
                const span = document.createElement('span');
                span.textContent = label;
                btn.appendChild(span);
            }
            btn.addEventListener('click', function() { setTimeout(closeRideDrawer, 80); }, { once: true });
        });

        document.getElementById('rideDrawerBackdrop').style.display = 'block';
        requestAnimationFrame(function() {
            document.getElementById('rideDrawer').classList.add('open');
        });
    }
    function closeRideDrawer() {
        document.getElementById('rideDrawer').classList.remove('open');
        setTimeout(function() {
            document.getElementById('rideDrawerBackdrop').style.display = 'none';
        }, 300);
    }
    // Admin marks a service completed (status_id = 4) — same backend path as the driver.
    function concludeRide(id) {
        if (!id) return;
        if (!confirm('<?= t('rides.conclude_confirm') ?>')) return;
        const fd = new FormData(); fd.append('ride_id', id); fd.append('status', 4);
        fetch('/SRMT/public/api/status-update.php', { method: 'POST', body: fd })
            .then(r => r.json()).then(d => {
                if (d && d.success) { toastr.success('OK'); closeRideDrawer(); tabelaViagens.ajax.reload(null, false); }
                else { toastr.error((d && d.error) || 'Erro'); }
            }).catch(() => toastr.error('Erro'));
    }

    toastr.options = { closeButton: true, progressBar: true, positionClass: 'toast-top-right', timeOut: '3000' };
    const __flash = "<?= View::e($flash ?? '') ?>";
    if (__flash) {
        const msg = __flash === 'ride_created'  ? 'Ride created!'
                  : __flash === 'rideUpdated'   ? 'Ride updated!'
                  : __flash === 'ride_deleted'  ? 'Ride deleted!'
                  : __flash === 'TypeChanged'   ? 'Type changed!'
                  : __flash === 'viagemAtribuida' ? 'Driver assigned!'
                  : '';
        if (msg) toastr.success(msg);
        const url = new URL(window.location);
        url.searchParams.delete('success');
        window.history.replaceState({}, '', url);
    }

    // ── Recall / Return ───────────────────────────────────────────
    // isDelegatedOut=true  → caller is the original owner (recalls the trip)
    // isDelegatedOut=false → caller is the receiver (returns the trip)
    // isDelegatedOut=null  → infer from active tab (legacy path)
    async function recallTrip(rideId, isDelegatedOut = null) {
        if (isDelegatedOut === null) {
            isDelegatedOut = $('#ride-tabs a.active').data('status') === 'delegated';
        }
        const msg = isDelegatedOut
            ? '<?= t('rides.recall_success') ?>'
            : '<?= t('rides.return_success') ?>';
        try {
            const res  = await fetch('/SRMT/public/admin/ride-recall.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ride_id=' + rideId,
            });
            const data = await res.json();
            if (data.success) {
                toastr.success(msg);
                tabelaViagens.ajax.reload(null, false);
            } else {
                toastr.error(data.error || 'Failed.');
            }
        } catch (e) {
            toastr.error('Network error.');
        }
    }

    // ── Delegation ────────────────────────────────────────────────
    function openDelegateModal(rideId) {
        <?php if (!empty($activePartners)): ?>
        document.getElementById('delegateRideId').value = rideId;
        document.getElementById('delegateTargetCompany').value = '';
        new bootstrap.Modal(document.getElementById('delegateModal')).show();
        <?php else: ?>
        toastr.warning('<?= t('rides.delegate_no_partners') ?>');
        <?php endif; ?>
    }

    async function confirmDelegate() {
        const rideId   = document.getElementById('delegateRideId').value;
        const targetId = document.getElementById('delegateTargetCompany').value;
        if (!targetId) { toastr.error('<?= t('rides.delegate_select_partner') ?>'); return; }

        const btn = document.getElementById('confirmDelegateBtn');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> <?= t('rides.delegating') ?>';

        try {
            const res  = await fetch('/SRMT/public/admin/ride-delegate.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'ride_id=' + rideId + '&target_company_id=' + targetId,
            });
            const data = await res.json();
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('delegateModal')).hide();
                toastr.success('<?= t('rides.delegate_success') ?>');
                tabelaViagens.ajax.reload(null, false);
            } else {
                toastr.error(data.error || 'Failed to delegate.');
            }
        } catch (e) {
            toastr.error('Network error.');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send me-1"></i> <?= t('rides.delegate_btn') ?>';
        }
    }

    if (new URLSearchParams(window.location.search).get('new') === '1') {
        new bootstrap.Modal(document.getElementById('modalCriarViagem')).show();
    }

    // ── Autocomplete de localizações (OSM via Photon, grátis, sem chave) ──────
    // Type-ahead com sugestões reais do OpenStreetMap. Enviesado para a zona de
    // Lisboa para que hotéis/locais locais apareçam primeiro. O valor escolhido
    // fica em texto legível (igual ao que o motorista precisa de ler).
    (function () {
        // Photon só aceita lang en/de/fr; os nomes do OSM já vêm no idioma local
        // (PT em Portugal), por isso não enviamos 'lang' para evitar 400.
        const BIAS = { lat: 38.7223, lon: -9.1393 }; // Lisboa
        const esc  = s => { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; };

        function label(props) {
            const main = props.name || [props.street, props.housenumber].filter(Boolean).join(' ') || props.city || '';
            const subParts = [props.postcode, props.city, props.state, props.country]
                .filter(Boolean).filter(v => v !== main);
            return { main, sub: [...new Set(subParts)].join(', ') };
        }
        function value(props) {
            const l = label(props);
            return l.sub && props.city && props.city !== l.main ? `${l.main}, ${props.city}` : l.main;
        }

        function attach(input) {
            const box = input.parentElement.querySelector('.place-suggestions');
            if (!box) return;
            let timer = null, ctrl = null, items = [], active = -1;

            const close = () => { box.classList.remove('open'); box.innerHTML = ''; items = []; active = -1; };
            const render = feats => {
                items = feats;
                if (!feats.length) { close(); return; }
                box.innerHTML = feats.map((f, i) => {
                    const l = label(f.properties);
                    return `<div class="place-item" data-i="${i}">
                        <div class="pi-main">${esc(l.main)}</div>
                        ${l.sub ? `<div class="pi-sub">${esc(l.sub)}</div>` : ''}</div>`;
                }).join('');
                box.classList.add('open');
                active = -1;
            };
            const pick = i => {
                const f = items[i]; if (!f) return;
                input.value = value(f.properties);
                close(); input.focus();
            };

            input.addEventListener('input', () => {
                const q = input.value.trim();
                clearTimeout(timer);
                if (q.length < 3) { close(); return; }
                timer = setTimeout(async () => {
                    if (ctrl) ctrl.abort();
                    ctrl = new AbortController();
                    box.innerHTML = '<div class="place-loading">…</div>'; box.classList.add('open');
                    try {
                        const url = `https://photon.komoot.io/api/?q=${encodeURIComponent(q)}`
                                  + `&limit=6&lat=${BIAS.lat}&lon=${BIAS.lon}`;
                        const res = await fetch(url, { signal: ctrl.signal });
                        const data = await res.json();
                        render((data.features || []).filter(f => f.properties));
                    } catch (e) { if (e.name !== 'AbortError') close(); }
                }, 300);
            });

            input.addEventListener('keydown', e => {
                if (!box.classList.contains('open') || !items.length) return;
                if (e.key === 'ArrowDown')      { e.preventDefault(); active = (active + 1) % items.length; }
                else if (e.key === 'ArrowUp')   { e.preventDefault(); active = (active - 1 + items.length) % items.length; }
                else if (e.key === 'Enter')     { if (active >= 0) { e.preventDefault(); pick(active); } return; }
                else if (e.key === 'Escape')    { close(); return; }
                else return;
                box.querySelectorAll('.place-item').forEach((el, i) => el.classList.toggle('active', i === active));
            });

            box.addEventListener('mousedown', e => {
                const it = e.target.closest('.place-item');
                if (it) { e.preventDefault(); pick(parseInt(it.dataset.i, 10)); }
            });
            input.addEventListener('blur', () => setTimeout(close, 150));
        }

        document.querySelectorAll('.js-place').forEach(attach);
    })();
</script>
<?php $ridesScripts = ob_get_clean(); ?>
<?php
View::layout('layouts.admin', [
    'title'        => 'Rides — SyncRide OS',
    'active'       => 'rides',
    'extraHead'    => $ridesHead,
    'extraScripts' => $ridesScripts,
]);
?>

<main class="px-6 mt-8">

    <div class="mb-6">
        <h1 class="page-title"><?= t('rides.title') ?></h1>
        <p class="page-subtitle mt-1"><?= t('rides.subtitle') ?></p>
    </div>

    <div class="rides-sticky">
    <div class="ride-tabs-wrap mb-3">
    <div class="flex flex-wrap gap-2 no-scrollbar pb-2" id="ride-tabs">
        <a class="pill active" data-bs-toggle="tab" href="#today" data-status="today">
            <?= t('rides.today') ?>
            <span id="todayBadge" class="pill-count" data-tab="today"><?= $todayCount ?></span>
        </a>
        <a class="pill" data-bs-toggle="tab" href="#tomorrow" data-status="tomorrow">
            <?= t('rides.tomorrow') ?>
            <span id="tomorrowBadge" class="pill-count" data-tab="tomorrow"><?= $tomorrowCount ?? 0 ?></span>
        </a>
        <a class="pill" data-bs-toggle="tab" href="#pending" data-status="pending">
            <?= t('rides.unassigned') ?>
            <span id="pendingBadge" class="pill-count" data-tab="pending"><?= $unassignedCount ?></span>
        </a>
        <a class="pill" data-bs-toggle="tab" href="#assigned" data-status="assigned"><?= t('rides.assigned') ?></a>
        <a class="pill" data-bs-toggle="tab" href="#all" data-status="all"><?= t('rides.all') ?></a>
        <a class="pill warning-pill" data-bs-toggle="tab" href="#requests" data-status="requests">
            <i class="bi bi-bell-fill"></i> <?= t('rides.requests') ?>
            <span id="pendingRequestsBadge" class="pill-count<?= $pendingRequestsCount > 0 ? ' has-pending' : '' ?>"><?= $pendingRequestsCount ?></span>
        </a>
        <?php if (!empty($activePartners)): ?>
        <a class="pill" data-bs-toggle="tab" href="#delegated" data-status="delegated">
            <i class="bi bi-send-fill me-1"></i> <?= t('rides.delegated_tab') ?>
        </a>
        <?php endif; ?>
    </div>
    </div><!-- /.ride-tabs-wrap -->

    <div class="glass rounded-[22px] p-3 mb-4 flex items-center gap-2 flex-wrap rides-toolbar">
        <div id="filter-container" class="search-wrap">
            <i data-lucide="search" class="search-icon w-4 h-4"></i>
        </div>
        <button id="btnBulkDelete" class="btn-ghost" style="display:none;color:#f87171;border-color:rgba(248,113,113,0.3);background:rgba(248,113,113,0.08);" onclick="bulkDelete()">
            <i class="bi bi-trash3-fill"></i> Delete (<span id="selectedCount">0</span>)
        </button>
        <button id="btnBulkAggregate" class="btn-ghost" style="display:none;color:#06b6d4;border-color:rgba(6,182,212,0.3);background:rgba(6,182,212,0.08);" onclick="aggregateSelected()">
            <i class="bi bi-link-45deg"></i> <?= t('rides.aggregate_btn') ?> (<span id="aggCount">0</span>)
        </button>
        <button id="toggleSelectionMode" class="icon-btn" title="<?= t('rides.selection_mode') ?>" onclick="toggleSelectionMode()">
            <i data-lucide="check-square" class="w-4 h-4"></i>
        </button>
        <div class="dropdown">
            <button class="icon-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i data-lucide="arrow-down-wide-narrow" class="w-4 h-4"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end glass-strong border-0 shadow-lg" style="border-radius:18px;">
                <li><a class="dropdown-item text-white" href="#" onclick="sortRides(2,'asc')"><?= t('rides.oldest_first') ?></a></li>
                <li><a class="dropdown-item text-white" href="#" onclick="sortRides(2,'desc')"><?= t('rides.newest_first') ?></a></li>
            </ul>
        </div>
        <!-- Date-range filter -->
        <div class="dropdown">
            <button class="icon-btn" id="dateFilterBtn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="<?= t('rides.filter_date') ?>">
                <i data-lucide="calendar-range" class="w-4 h-4"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end glass-strong border-0 shadow-lg p-3" style="border-radius:18px;min-width:230px;">
                <label class="small fw-bold d-block mb-1"><?= t('rides.date_from') ?></label>
                <input type="date" id="dateFrom" class="form-control-custom mb-2" onclick="event.stopPropagation()">
                <label class="small fw-bold d-block mb-1"><?= t('rides.date_to') ?></label>
                <input type="date" id="dateTo" class="form-control-custom mb-3" onclick="event.stopPropagation()">
                <div class="d-flex gap-2">
                    <button class="btn-ghost flex-fill" onclick="clearDateFilter()"><?= t('rides.clear') ?></button>
                    <button class="btn-primary-modern flex-fill" onclick="applyDateFilter()"><?= t('rides.apply') ?></button>
                </div>
            </div>
        </div>
        <button class="btn-primary-modern" data-bs-toggle="modal" data-bs-target="#modalCriarViagem">
            <i data-lucide="plus" class="w-4 h-4"></i> <?= t('rides.new') ?>
        </button>
    </div>
    <div id="completedStackedPill" onclick="document.getElementById('app-container').scrollTo({top:0,behavior:'smooth'})">
        <i class="bi bi-chevron-up"></i> <span id="completedStackedText"></span>
    </div>
    </div><!-- /.rides-sticky -->

    <div id="ridesSkeleton" class="rides-skeleton">
        <div class="sk-card"></div><div class="sk-card"></div>
        <div class="sk-card"></div><div class="sk-card"></div>
    </div>

    <table id="tabelaViagens" class="table" style="width:100%;display:none;">
        <thead>
            <tr>
                <th class="col-selection"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                <th><?= t('rides.col_id') ?></th><th><?= t('rides.col_datetime') ?></th><th><?= t('rides.col_driver') ?></th>
                <th><?= t('rides.col_pickup') ?></th><th><?= t('rides.col_dropoff') ?></th><th><?= t('rides.col_type') ?></th><th><?= t('rides.col_key') ?></th><th><?= t('rides.col_actions') ?></th>
                <th><?= t('rides.col_client') ?></th><th><?= t('rides.col_flight') ?></th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

</main>

<!-- Actions Drawer -->
<div id="rideDrawerBackdrop" onclick="closeRideDrawer()"></div>
<div id="rideDrawer">
    <div class="drawer-handle"></div>
    <div class="drawer-ride-meta">
        <div class="d-flex align-items-baseline gap-2">
            <span class="drawer-time" id="drawerTime"></span>
            <span class="drawer-date text-zinc-400" id="drawerDate" style="font-size:11px"></span>
            <span class="drawer-id ms-auto" id="drawerId"></span>
        </div>
        <div class="drawer-route">
            <span style="color:#10b981">●</span>
            <span id="drawerPickup" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></span>
            <span style="color:#94a3b8">→</span>
            <span style="color:#ef4444">●</span>
            <span id="drawerDropoff" style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"></span>
        </div>
        <div class="drawer-driver" id="drawerDriver"></div>
    </div>
    <div class="drawer-actions" id="drawerActions"></div>
</div>

<!-- Create Ride Modal -->
<div class="modal fade" id="modalCriarViagem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title"><?= t('rides.new_ride') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-3 pt-2 pb-3">
                <form action="ride-add.php" method="POST">
                    <div class="row mb-2">
                        <div class="col-6"><label class="form-label small"><?= t('rides.date') ?></label><input type="date" class="form-control-custom" name="serviceDate" required /></div>
                        <div class="col-6"><label class="form-label small"><?= t('rides.time') ?></label><input type="time" class="form-control-custom" name="serviceStartTime" required /></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><label class="form-label small"><?= t('rides.adults') ?></label><input type="number" class="form-control-custom" name="paxADT" required placeholder="0" /></div>
                        <div class="col-4"><label class="form-label small"><?= t('rides.children') ?></label><input type="number" class="form-control-custom" name="paxCHD" required placeholder="0" /></div>
                        <div class="col-4"><label class="form-label small"><?= t('rides.babies') ?></label><input type="number" class="form-control-custom" name="paxBBY" min="0" placeholder="0" /></div>
                    </div>
                    <div class="mb-2 place-field"><label class="form-label small"><?= t('rides.pickup') ?></label><input type="text" class="form-control-custom js-place" autocomplete="off" name="serviceStartPoint" required placeholder="Address or Hotel" /><div class="place-suggestions"></div></div>
                    <div class="mb-2 place-field"><label class="form-label small"><?= t('rides.dropoff') ?></label><input type="text" class="form-control-custom js-place" autocomplete="off" name="serviceTargetPoint" required placeholder="Final destination" /><div class="place-suggestions"></div></div>
                    <div class="row mb-2">
                        <div class="col-6">
                            <label class="form-label small"><?= t('rides.driver') ?></label>
                            <select class="form-select-custom" name="driver">
                                <option value="later"><?= t('rides.later') ?></option>
                                <?php foreach ($drivers as $d): ?>
                                    <option value="<?= $d->id ?>"><?= View::e($d->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Type</label>
                            <select class="form-select-custom" name="serviceType">
                                <option value="1"><?= t('rides.private') ?></option>
                                <option value="0"><?= t('rides.shared') ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><label class="form-label small"><?= t('rides.col_flight') ?></label><input type="text" class="form-control-custom" name="FlightNumber" placeholder="e.g. TP1234" /></div>
                        <div class="col-6"><label class="form-label small"><?= t('rides.client') ?></label><input type="text" class="form-control-custom" name="NomeCliente" placeholder="Name" /></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><label class="form-label small"><?= t('rides.client_number') ?></label><input type="text" class="form-control-custom" name="ClientNumber" placeholder="+351..." /></div>
                        <div class="col-6">
                            <label class="form-label small" style="color:#34d399 !important;"><i class="bi bi-cash-coin"></i> <?= t('rides.amount') ?></label>
                            <input type="number" step="0.01" class="form-control-custom" name="totalPrice" placeholder="e.g. 45.50">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6">
                            <label class="form-label small" style="color:#38bdf8 !important;"><i class="bi bi-person-badge"></i> <?= t('rides.driver_amount') ?></label>
                            <input type="number" step="0.01" class="form-control-custom" name="valorMotorista" placeholder="e.g. 11.00">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small" style="color:#38bdf8 !important;"><i class="bi bi-megaphone"></i> <?= t('rides.admin_note') ?></label>
                        <textarea class="form-control-custom" name="adminNote" rows="2" placeholder="<?= t('rides.admin_note_ph') ?>"></textarea>
                    </div>
                    <button type="submit" class="btn-modern w-100 mt-1"><?= t('rides.create_ride') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="changeTripTypeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0"><h5 class="modal-title"><?= t('rides.change_type') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="update-trip-type.php" method="POST">
                <div class="modal-body text-center">
                    <input type="hidden" id="tripId_changeType" name="tripId">
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="tripType" id="private" value="1" autocomplete="off">
                        <label class="btn btn-outline-light" for="private"><?= t('rides.private') ?></label>
                        <input type="radio" class="btn-check" name="tripType" id="shared" value="0" autocomplete="off">
                        <label class="btn btn-outline-warning" for="shared"><?= t('rides.shared') ?></label>
                    </div>
                </div>
                <div class="modal-footer border-top-0"><button type="submit" class="btn-modern w-100"><?= t('rides.save') ?></button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="atribuirCondutorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0"><h5 class="modal-title"><?= t('rides.assign_driver') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <form action="assign-driver.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" id="viagemId_assign" name="viagemId">
                    <select name="condutorId" class="form-select-custom text-center fw-bold">
                        <?php foreach ($drivers as $d): ?>
                            <option value="<?= $d->id ?>"><?= View::e($d->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label class="form-label small mt-3 d-block"><i class="bi bi-person-badge"></i> <?= t('rides.pay_basis') ?></label>
                    <select name="payBasis" class="form-select-custom text-center">
                        <option value=""><?= t('rides.pay_basis_auto') ?></option>
                        <option value="company_vehicle"><?= t('rides.pay_basis_company') ?></option>
                        <option value="own_vehicle"><?= t('rides.pay_basis_own') ?></option>
                    </select>
                    <p class="text-zinc-500 small mt-2 mb-0"><?= t('rides.pay_basis_hint') ?></p>
                </div>
                <div class="modal-footer border-top-0"><button type="submit" class="btn-modern w-100"><?= t('rides.confirm') ?></button></div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteTripModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center p-5">
                <div style="color:#f87171" class="mb-3"><i class="bi bi-trash3-fill" style="font-size:3rem;"></i></div>
                <h5 class="fw-bold mb-2"><?= t('rides.delete_ride') ?></h5>
                <p class="text-zinc-400 mb-4"><?= t('rides.about_to_delete') ?> <strong id="deleteTripName"></strong>.</p>
                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn-ghost px-4" data-bs-dismiss="modal"><?= t('rides.cancel') ?></button>
                    <a href="#" id="confirmDeleteTripBtn" onclick="event.preventDefault();doDeleteTrip();" class="btn-modern px-4" style="background:linear-gradient(135deg,#ef4444,#b91c1c);box-shadow:0 8px 20px rgba(239,68,68,0.25);"><?= t('rides.delete') ?></a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-2"><h5 class="modal-title"><?= t('rides.view_details') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body px-3 py-2">
                <form id="editTripForm" action="ride-update.php" method="POST">
                    <input type="hidden" name="edit_trip_id" id="editTripId">
                    <!-- Linha 1: Data/Hora + Condutor -->
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="small">Date / Time</label><input type="datetime-local" class="form-control-custom" id="editDataHora" name="edit_departure_datetime" disabled></div>
                        <div class="col-6">
                            <label class="small"><?= t('rides.driver') ?></label>
                            <div class="d-flex align-items-center gap-1">
                                <input type="text" class="form-control-custom flex-1" id="editCondutor" name="edit_driverName" disabled style="min-width:0">
                                <button type="button" class="btn-ghost flex-shrink-0" id="btnChangeDriverEdit" onclick="openChangeDriverFromEdit()" title="<?= t('rides.assign_driver') ?>" style="padding:6px 9px;border-radius:10px;">
                                    <i class="bi bi-person-check-fill"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Linha 2: Origem + Destino -->
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="small"><?= t('rides.pickup') ?></label><input type="text" class="form-control-custom" id="editRecolha" name="edit_origin" disabled></div>
                        <div class="col-6"><label class="small"><?= t('rides.dropoff') ?></label><input type="text" class="form-control-custom" id="editEntrega" name="edit_destination" disabled></div>
                        <div class="col-6"><label class="small"><?= t('rides.direction') ?></label>
                            <select class="form-control-custom" id="editLegCode" name="edit_leg_code" disabled>
                                <option value=""><?= t('rides.direction_auto') ?></option>
                                <option value="IN">IN</option>
                                <option value="OT">OUT</option>
                            </select>
                        </div>
                    </div>
                    <!-- Linha 3: ADT + CHD + BBY + Voo -->
                    <div class="row g-2 mb-2">
                        <div class="col-3"><label class="small"><?= t('rides.adt') ?></label><input type="number" class="form-control-custom" id="editpaxADT" name="edit_paxADT" disabled></div>
                        <div class="col-3"><label class="small"><?= t('rides.chd') ?></label><input type="number" class="form-control-custom" id="editpaxCHD" name="edit_paxCHD" disabled></div>
                        <div class="col-3"><label class="small"><?= t('rides.babies') ?></label><input type="number" class="form-control-custom" id="editPaxBBY" name="edit_paxBBY" min="0" disabled></div>
                        <div class="col-3"><label class="small"><?= t('rides.col_flight') ?></label><input type="text" class="form-control-custom" id="editflightNumber" name="edit_flightNumber" disabled></div>
                    </div>
                    <!-- Linha 4: Cliente + Nr Cliente -->
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="small"><?= t('rides.client') ?></label><input type="text" class="form-control-custom" id="editclientName" name="edit_clientName" disabled></div>
                        <div class="col-6"><label class="small"><?= t('rides.client_number') ?></label><input type="text" class="form-control-custom" id="editclientNumber" name="edit_clientNumber" disabled></div>
                    </div>
                    <!-- Linha 5: Valor cliente + Valor motorista -->
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="small" style="color:#34d399 !important;"><?= t('rides.amount') ?></label><input type="number" step="0.01" class="form-control-custom" id="editTotalPrice" name="edit_totalPrice" disabled></div>
                        <div class="col-6"><label class="small" style="color:#38bdf8 !important;"><?= t('rides.driver_amount') ?></label><input type="number" step="0.01" class="form-control-custom" id="editValorMotorista" name="edit_valorMotorista" disabled></div>
                    </div>
                    <!-- Linha 6: Tipo -->
                    <div class="d-flex justify-content-between align-items-center edit-type-row pt-2">
                        <div><span class="text-zinc-500 small me-2">Type:</span><input type="text" class="d-inline-block border-0 bg-transparent fw-bold text-white" style="width:90px;" id="editTripTypeDisplay" disabled></div>
                        <button type="button" id="btnChangeTypeEdit" class="btn-ghost"><i class="bi bi-shuffle"></i> <?= t('rides.change') ?></button>
                    </div>
                    <!-- Nota do condutor (read-only) -->
                    <div id="editDriverNoteWrap" class="mt-2" style="display:none;">
                        <label class="small" style="color:#fbbf24 !important;"><i class="bi bi-chat-left-text"></i> <?= t('rides.driver_note') ?></label>
                        <div id="editDriverNoteText" class="form-control-custom" style="white-space:pre-wrap;opacity:.9;"></div>
                    </div>
                    <!-- Nota do escritório para o condutor -->
                    <div class="mt-2">
                        <label class="small" style="color:#38bdf8 !important;"><i class="bi bi-megaphone"></i> <?= t('rides.admin_note') ?></label>
                        <textarea class="form-control-custom" id="editAdminNote" name="edit_adminNote" rows="2" placeholder="<?= t('rides.admin_note_ph') ?>" disabled></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-2">
                <button type="button" class="btn-ghost w-100" style="color:#fbbf24;border-color:rgba(251,191,36,0.3);background:rgba(251,191,36,0.08);padding:10px;" id="enableEditBtn" onclick="enableEdit()"><?= t('rides.edit_details') ?></button>
                <button type="submit" class="btn-modern w-100" form="editTripForm" id="saveChangesBtn" style="display:none;"><?= t('rides.save') ?></button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLogs" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0"><h5 class="modal-title"><?= t('rides.trip_history') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body pt-4">
                <div id="logsContent"><div class="text-center py-3"><div class="spinner-border text-light" role="status"></div></div></div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($activePartners)): ?>
<div class="modal fade" id="delegateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0">
                <h5 class="modal-title"><i class="bi bi-send me-2 text-warning"></i><?= t('rides.delegate_title') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-zinc-400 small mb-3"><?= t('rides.delegate_desc') ?></p>
                <input type="hidden" id="delegateRideId" value="">
                <select id="delegateTargetCompany" class="form-control-custom mb-3">
                    <option value=""><?= t('rides.delegate_select') ?></option>
                    <?php foreach ($activePartners as $ap): ?>
                    <option value="<?= (int) $ap['partner_id'] ?>"><?= htmlspecialchars((string) $ap['partner_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn-ghost" data-bs-dismiss="modal"><?= t('rides.delegate_cancel') ?></button>
                <button type="button" class="btn-modern" id="confirmDelegateBtn" onclick="confirmDelegate()"
                    style="background:linear-gradient(135deg,#f97316,#ea580c);box-shadow:0 8px 20px rgba(249,115,22,0.25);">
                    <i class="bi bi-send me-1"></i> <?= t('rides.delegate_btn') ?>
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal: paragens da viagem multi-paragem -->
<div class="modal fade" id="stopsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header px-4 py-3">
                <div class="d-flex align-items-center gap-2 flex-1 min-w-0">
                    <i class="bi bi-signpost-2 text-primary flex-shrink-0"></i>
                    <h5 class="modal-title mb-0"><?= t('rides.stops_modal_title') ?></h5>
                    <span id="stopsModalSub" class="text-zinc-400 ms-1 flex-shrink-0" style="font-size:11px"></span>
                </div>
                <div class="stops-header-actions d-flex gap-2 align-items-center ms-2">
                    <button id="btnStopsEditToggle" class="stops-action-btn edit" onclick="toggleStopsEditMode()" style="touch-action:manipulation">
                        <i class="bi bi-pencil"></i><?= t('rides.stops_edit_mode') ?>
                    </button>
                    <button id="btnSaveStops" class="stops-action-btn save" onclick="saveStopsAll()" style="display:none;touch-action:manipulation">
                        <i class="bi bi-floppy"></i><?= t('rides.stops_save_order') ?>
                    </button>
                    <button id="btnDisaggregateAll" class="stops-action-btn split" onclick="disaggregateAll()" style="touch-action:manipulation">
                        <i class="bi bi-scissors"></i><?= t('rides.stops_split_all') ?>
                    </button>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body">
                <div id="stopsLoading" class="text-center py-4 text-zinc-400 small d-none">
                    <div class="spinner-border spinner-border-sm me-2"></div><?= t('rides.loading') ?>
                </div>
                <div id="stopsList" class="d-flex flex-column gap-2"></div>
            </div>
        </div>
    </div>
</div>
