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
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet"/>
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
    /* Ride cards */
    #tabelaViagens { display: block; width: 100%; }
    #tabelaViagens thead { display: none; }
    #tabelaViagens tbody { display: block; }
    #tabelaViagens tbody tr {
        display: block; position: relative;
        background: rgba(255,255,255,0.65); border: 1px solid rgba(0,0,0,0.08);
        border-radius: 22px; margin-bottom: 10px; padding: 16px 18px;
        backdrop-filter: blur(20px); transition: all .2s;
    }
    #tabelaViagens tbody tr:hover { background: rgba(255,255,255,0.82); border-color: rgba(0,0,0,0.13); }
    [data-theme="dark"] #tabelaViagens tbody tr { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.08); }
    [data-theme="dark"] #tabelaViagens tbody tr:hover { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.15); }
    #tabelaViagens tbody td { display: block; border: none !important; padding: 0 !important; background: transparent !important; color: #0f172a; }
    [data-theme="dark"] #tabelaViagens tbody td { color: #fff; }
    #tabelaViagens tbody td.col-selection { display: none; position: absolute; top: 16px; left: 14px; }
    .selection-active #tabelaViagens tbody td.col-selection { display: block; }
    .selection-active #tabelaViagens tbody tr { padding-left: 46px; }
    #tabelaViagens tbody td:nth-child(2) { font-size: 10px; font-weight: 800; color: #94a3b8; letter-spacing: 0.1em; text-transform: uppercase; margin-bottom: 2px; font-family: monospace; }
    [data-theme="dark"] #tabelaViagens tbody td:nth-child(2) { color: #71717a; }
    #tabelaViagens tbody td:nth-child(3) { font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
    [data-theme="dark"] #tabelaViagens tbody td:nth-child(3) { color: #fff; }
    #tabelaViagens tbody td:nth-child(4) { font-size: 12px; color: #475569; margin-bottom: 10px; }
    [data-theme="dark"] #tabelaViagens tbody td:nth-child(4) { color: #d4d4d8; }
    #tabelaViagens tbody td:nth-child(5),
    #tabelaViagens tbody td:nth-child(6) { font-size: 12px; color: #334155; padding-left: 20px !important; position: relative; line-height: 1.4; margin-bottom: 6px; }
    [data-theme="dark"] #tabelaViagens tbody td:nth-child(5),
    [data-theme="dark"] #tabelaViagens tbody td:nth-child(6) { color: #e4e4e7; }
    #tabelaViagens tbody td:nth-child(5):before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: #10b981; position: absolute; left: 4px; top: 6px; box-shadow: 0 0 0 3px rgba(16,185,129,0.15); }
    #tabelaViagens tbody td:nth-child(6):before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: #ef4444; position: absolute; left: 4px; top: 6px; box-shadow: 0 0 0 3px rgba(239,68,68,0.15); }
    #tabelaViagens tbody td:nth-child(7) { display: inline-block; width: auto !important; font-size: 9px; padding: 3px 10px; border-radius: 999px; font-weight: 800; background: rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.10); color: #64748b; text-transform: uppercase; letter-spacing: 0.1em; margin-top: 4px; }
    [data-theme="dark"] #tabelaViagens tbody td:nth-child(7) { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: #a1a1aa; }
    #tabelaViagens tbody td:nth-child(8) { position: absolute; bottom: 16px; right: 60px; width: auto !important; }
    #tabelaViagens tbody td:last-child { position: absolute; top: 14px; right: 14px; display: flex; gap: 6px; width: auto !important; }
    @media (min-width: 992px) {
        #tabelaViagens tbody { display: grid; grid-template-columns: repeat(2,1fr); gap: 10px; }
        #tabelaViagens tbody tr { margin-bottom: 0; }
    }
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
        height: 130px; border-radius: 22px;
        background: linear-gradient(110deg, rgba(0,0,0,0.04) 8%, rgba(0,0,0,0.07) 18%, rgba(0,0,0,0.04) 33%);
        background-size: 200% 100%; border: 1px solid rgba(0,0,0,0.06);
        animation: shimmer 1.4s linear infinite;
    }
    [data-theme="dark"] .rides-skeleton .sk-card {
        background: linear-gradient(110deg, rgba(255,255,255,0.04) 8%, rgba(255,255,255,0.08) 18%, rgba(255,255,255,0.04) 33%);
        border-color: rgba(255,255,255,0.06);
    }
    @keyframes shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
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
    .page-title { font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: -0.02em; }
    [data-theme="dark"] .page-title { color: #fff; }
    .page-subtitle { font-size: 11px; color: #94a3b8; font-weight: 600; }
    .btn-circle-mobile { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 999px; }
    .edit-type-row { border-top: 1px solid rgba(0,0,0,0.08); }
    [data-theme="dark"] .edit-type-row { border-top: 1px solid rgba(255,255,255,0.08); }
</style>
<?php $ridesHead = ob_get_clean(); ?>
<?php
ob_start();
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
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
                    if (row.chave && row.chave.includes('bi-key-fill')) html += ' <span>' + row.chave + '</span>';
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
            order: [[2,'asc']], pageLength: 10, dom: 'frt<"p-wrap"p>'
        });

        $('#tabelaViagens_filter').appendTo('#filter-container');
        $('#tabelaViagens_filter input').addClass('form-control-search');

        $('#ride-tabs a').on('click', function(e) {
            e.preventDefault();
            $('#ride-tabs a').removeClass('active');
            $(this).addClass('active');
            const status = $(this).data('status');
            const url = new URL(window.location);
            url.searchParams.set('tab', status);
            window.history.replaceState({}, '', url);
            tabelaViagens.search('');
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
            // NOTE: badge is NOT refreshed here — that fired an extra HTTP request on
            // every sort/page/filter. It's refreshed once on load and after approve/reject.
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
        const btn = document.getElementById('dateFilterBtn');
        if (btn) btn.classList.toggle('active', !!(window._dateFrom || window._dateTo));
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
    function editTravel(id, dataHora, condutor, recolha, entrega, paxADT, paxCHD, paxBBY, flightNumber, clientName, clientNumber, serviceType, totalPrice) {
        disableEdit();
        document.getElementById('editTripId').value = id;
        document.getElementById('editDataHora').value = dataHora.replace(' ','T');
        document.getElementById('editCondutor').value = condutor;
        document.getElementById('editRecolha').value = recolha;
        document.getElementById('editEntrega').value = entrega;
        document.getElementById('editpaxADT').value = paxADT;
        document.getElementById('editpaxCHD').value = paxCHD;
        document.getElementById('editPaxBBY').value = paxBBY;
        document.getElementById('editflightNumber').value = flightNumber;
        document.getElementById('editclientName').value = clientName;
        document.getElementById('editclientNumber').value = clientNumber;
        document.getElementById('editTotalPrice').value = totalPrice;
        document.getElementById('editTripTypeDisplay').value = serviceType == 1 ? 'Private' : 'Shared';
        document.getElementById('btnChangeTypeEdit').onclick = function() {
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            changeTripType(id, serviceType);
        };
    }
    function enableEdit() {
        document.querySelectorAll('#editTripForm input').forEach(input => { if (input.id !== 'editCondutor' && input.id !== 'editTripTypeDisplay') input.disabled = false; });
        document.getElementById('saveChangesBtn').style.display = 'inline-block';
        const btn = document.getElementById('enableEditBtn');
        btn.textContent = 'Cancel'; btn.setAttribute('onclick','disableEdit()');
    }
    function disableEdit() {
        document.querySelectorAll('#editTripForm input').forEach(input => input.disabled = true);
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
                const steps = [
                    { label: 'Started Pickup',       time: d.ts_start_pickup },
                    { label: 'Arrived at Pickup',    time: d.ts_arrived_pickup },
                    { label: 'With Client',          time: d.ts_with_client },
                    { label: 'Trip Started',         time: d.ts_start_trip },
                    { label: 'Trip Completed',       time: d.ts_completed }
                ];
                let html = '<div class="logs-timeline">';
                steps.forEach(step => {
                    const done = step.time !== null;
                    html += '<div class="logs-item ' + (done ? 'completed' : 'pending') + '"><div class="logs-dot"></div><div class="logs-content"><div class="logs-title">' + step.label + '</div><div class="logs-date">' + fmt(step.time) + '</div></div></div>';
                });
                content.innerHTML = html + '</div>';
            } else {
                content.innerHTML = '<p class="text-center" style="color:#f87171">Error loading logs.</p>';
            }
        });
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
    async function recallTrip(rideId) {
        const isDelegatedOut = $('#ride-tabs a.active').data('status') === 'delegated';
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
                ridesTable.ajax.reload(null, false);
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
                ridesTable.ajax.reload(null, false);
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

    <div class="flex flex-wrap gap-2 no-scrollbar pb-2 mb-3" id="ride-tabs">
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

    <div class="glass rounded-[22px] p-3 mb-4 flex items-center gap-2 flex-wrap rides-toolbar">
        <div id="filter-container" class="search-wrap">
            <i data-lucide="search" class="search-icon w-4 h-4"></i>
        </div>
        <button id="btnBulkDelete" class="btn-ghost" style="display:none;color:#f87171;border-color:rgba(248,113,113,0.3);background:rgba(248,113,113,0.08);" onclick="bulkDelete()">
            <i class="bi bi-trash3-fill"></i> Delete (<span id="selectedCount">0</span>)
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
                    <div class="mb-2"><label class="form-label small"><?= t('rides.pickup') ?></label><input type="text" class="form-control-custom" name="serviceStartPoint" required placeholder="Address or Hotel" /></div>
                    <div class="mb-2"><label class="form-label small"><?= t('rides.dropoff') ?></label><input type="text" class="form-control-custom" name="serviceTargetPoint" required placeholder="Final destination" /></div>
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
            <div class="modal-header border-bottom-0"><h5 class="modal-title"><?= t('rides.edit_details') ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body p-4">
                <form id="editTripForm" action="ride-update.php" method="POST">
                    <input type="hidden" name="edit_trip_id" id="editTripId">
                    <div class="row mb-3">
                        <div class="col-6"><label class="small">Date / Time</label><input type="datetime-local" class="form-control-custom" id="editDataHora" name="edit_departure_datetime" disabled></div>
                        <div class="col-6"><label class="small"><?= t('rides.driver') ?></label><input type="text" class="form-control-custom" id="editCondutor" name="edit_driverName" disabled></div>
                    </div>
                    <div class="mb-3"><label class="small"><?= t('rides.pickup') ?></label><input type="text" class="form-control-custom" id="editRecolha" name="edit_origin" disabled></div>
                    <div class="mb-3"><label class="small"><?= t('rides.dropoff') ?></label><input type="text" class="form-control-custom" id="editEntrega" name="edit_destination" disabled></div>
                    <div class="row mb-3">
                        <div class="col-4"><label class="small"><?= t('rides.adt') ?></label><input type="number" class="form-control-custom" id="editpaxADT" name="edit_paxADT" disabled></div>
                        <div class="col-4"><label class="small"><?= t('rides.chd') ?></label><input type="number" class="form-control-custom" id="editpaxCHD" name="edit_paxCHD" disabled></div>
                        <div class="col-4"><label class="small"><?= t('rides.col_flight') ?></label><input type="text" class="form-control-custom" id="editflightNumber" name="edit_flightNumber" disabled></div>
                    </div>
                    <div class="mb-3"><label class="small"><?= t('rides.babies') ?></label><input type="number" class="form-control-custom" id="editPaxBBY" name="edit_paxBBY" min="0" disabled></div>
                    <div class="row mb-3">
                        <div class="col-md-6"><label class="small"><?= t('rides.client') ?></label><input type="text" class="form-control-custom" id="editclientName" name="edit_clientName" disabled></div>
                        <div class="col-md-6"><label class="small"><?= t('rides.client_number') ?></label><input type="text" class="form-control-custom" id="editclientNumber" name="edit_clientNumber" disabled></div>
                    </div>
                    <div class="mb-3"><label class="form-label small" style="color:#34d399 !important;"><?= t('rides.amount') ?></label><input type="number" step="0.01" class="form-control-custom" id="editTotalPrice" name="edit_totalPrice" disabled></div>
                    <div class="mt-4 pt-3 d-flex justify-content-between align-items-center edit-type-row">
                        <div><span class="text-zinc-500 small me-2">Type:</span><input type="text" class="d-inline-block border-0 bg-transparent fw-bold text-white" style="width:100px;" id="editTripTypeDisplay" disabled></div>
                        <button type="button" id="btnChangeTypeEdit" class="btn-ghost"><i class="bi bi-shuffle"></i> <?= t('rides.change') ?></button>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn-ghost w-100" style="color:#fbbf24;border-color:rgba(251,191,36,0.3);background:rgba(251,191,36,0.08);padding:12px;" id="enableEditBtn" onclick="enableEdit()"><?= t('rides.edit_details') ?></button>
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
