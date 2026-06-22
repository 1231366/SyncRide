<?php
/**
 * Admin layout — SyncRide OS.
 *
 * Variables:
 *   string $title          window title
 *   string $content        rendered child template
 *   string $active         active nav id
 *   string $extraHead      HTML for <head>
 *   string $extraScripts   HTML before </body>
 */

use App\Http\View;

$title        = $title        ?? 'SyncRide OS';
$active       = $active       ?? '';
$extraHead    = $extraHead    ?? '';
$extraScripts = $extraScripts ?? '';

$userName  = isset($_SESSION['name']) ? explode(' ', (string) $_SESSION['name'])[0] : 'Admin';
$rawPhoto  = $_SESSION['profile_photo_path'] ?? null;
if ($rawPhoto !== null && $rawPhoto !== '') {
    $rawPhoto = str_replace('Includes/dist/pages/', '', $rawPhoto);
    $userPhoto = str_starts_with($rawPhoto, '/') || str_starts_with($rawPhoto, 'http')
        ? $rawPhoto
        : '/SRMT/public/' . $rawPhoto;
} else {
    $userPhoto = '';
}
$initial        = mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'));
$svgAvatar      = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><circle cx="20" cy="20" r="20" fill="#2563eb"/><text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="white" font-size="17" font-weight="bold" font-family="system-ui">' . htmlspecialchars($initial) . '</text></svg>';
$avatarFallback = 'data:image/svg+xml;base64,' . base64_encode($svgAvatar);

$navClass = static function (string $id) use ($active): string {
    return $id === $active ? 'sr-nav-active' : '';
};
?><!DOCTYPE html>
<html lang="en" translate="no" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" id="themeColor" content="#f1f5f9">
<title><?= View::e($title) ?></title>
<meta name="csrf-token" content="<?= \App\Support\Session::csrfToken() ?>">

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root { --safe-bottom: env(safe-area-inset-bottom, 0px); }

    html, body { height: 100%; overflow: hidden; }
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        margin: 0;
        -webkit-font-smoothing: antialiased;
        background-color: #f1f5f9;
        color: #0f172a;
    }
    .bg-main { height: 100%; }
    #app-container { height: 100%; overflow-y: auto; -webkit-overflow-scrolling: touch; }

    /* ── Page background ────────────────────────────────────── */
    .bg-main {
        background: radial-gradient(circle at 50% -10%, #bfdbfe 0%, #f1f5f9 65%);
        background-attachment: fixed;
        min-height: 100vh;
    }
    [data-theme="dark"] .bg-main {
        background: radial-gradient(circle at 50% -10%, #1e3a8a 0%, #020617 70%);
        background-color: #020617;
    }
    [data-theme="dark"] body { background-color: #020617; color: #f1f5f9; }

    /* ── Glass ──────────────────────────────────────────────── */
    .glass {
        background: rgba(255,255,255,0.62);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(0,0,0,0.08);
    }
    [data-theme="dark"] .glass {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.10);
    }

    /* ── Bottom nav — pill flush to bottom ──────────────────── */
    .nav-bottom {
        position: fixed;
        bottom: 0; left: 50%;
        transform: translateX(-50%);
        width: calc(100% - 24px);
        max-width: 480px;
        height: 66px;
        margin-bottom: calc(10px + var(--safe-bottom));
        background: rgba(255,255,255,0.90);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(0,0,0,0.07);
        border-radius: 26px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.10), 0 2px 8px rgba(0,0,0,0.06);
        display: flex; align-items: stretch;
        z-index: 1000;
        overflow: hidden;
    }
    [data-theme="dark"] .nav-bottom {
        background: rgba(10,14,30,0.95);
        border: 1px solid rgba(255,255,255,0.09);
        box-shadow: 0 8px 32px rgba(0,0,0,0.5), 0 2px 8px rgba(0,0,0,0.3);
    }
    .nav-bottom a,
    .nav-bottom button {
        flex: 1; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: 3px;
        font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        color: #94a3b8;
        background: none; border: none; cursor: pointer;
        text-decoration: none;
        transition: color .15s;
        padding: 0;
    }
    .nav-bottom a i, .nav-bottom button i { width: 20px; height: 20px; display: block; }
    .nav-bottom a:hover, .nav-bottom button:hover { color: #64748b; }
    [data-theme="dark"] .nav-bottom a,
    [data-theme="dark"] .nav-bottom button { color: #475569; }
    [data-theme="dark"] .nav-bottom a:hover,
    [data-theme="dark"] .nav-bottom button:hover { color: #94a3b8; }
    .nav-bottom a.sr-nav-active { color: #2563eb; }
    [data-theme="dark"] .nav-bottom a.sr-nav-active { color: #60a5fa; }

    /* ── Full menu overlay ──────────────────────────────────── */
    .menu-backdrop {
        background: rgba(248,250,252,0.98);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
    }
    [data-theme="dark"] .menu-backdrop {
        background: rgba(2,6,23,0.99);
    }
    .menu-content { color: #0f172a; }
    [data-theme="dark"] .menu-content { color: #f1f5f9; }
    .menu-hr { border-color: #e2e8f0; }
    [data-theme="dark"] .menu-hr { border-color: #1e293b; }
    .menu-link {
        color: #0f172a !important; text-decoration: none;
    }
    [data-theme="dark"] .menu-link { color: #f1f5f9 !important; }
    .menu-link.menu-active { color: #2563eb !important; }
    [data-theme="dark"] .menu-link.menu-active { color: #60a5fa !important; }
    .menu-link-danger { color: #dc2626 !important; }

    /* ── Shared modal ─────────────────────────────────────────── */
    .modal-os {
        position: fixed; top: 50%; left: 50%;
        transform: translate(-50%,-50%) scale(0.9);
        width: 90%; max-width: 460px;
        visibility: hidden; opacity: 0;
        backdrop-filter: blur(30px); -webkit-backdrop-filter: blur(30px);
        border-radius: 28px; z-index: 4000;
        transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1);
        padding: 24px; max-height: 85vh; overflow-y: auto;
    }
    .modal-os.active { visibility: visible; opacity: 1; transform: translate(-50%,-50%) scale(1); }
    [data-theme="dark"] .modal-os {
        background: rgba(10,12,20,0.97); border: 1px solid rgba(255,255,255,0.12);
    }
    [data-theme="light"] .modal-os {
        background: rgba(255,255,255,0.96); border: 1px solid rgba(0,0,0,0.10);
        box-shadow: 0 24px 64px rgba(0,0,0,0.14);
    }
    .modal-overlay {
        position: fixed; inset: 0; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
        visibility: hidden; opacity: 0; z-index: 3999; transition: all 0.3s;
    }
    .modal-overlay.active { visibility: visible; opacity: 1; }
    [data-theme="dark"]  .modal-overlay { background: rgba(0,0,0,0.6); }
    [data-theme="light"] .modal-overlay { background: rgba(0,0,0,0.22); }

    /* ── Light-mode overrides for hardcoded dark Tailwind classes ── */
    [data-theme="light"] .text-white      { color: #0f172a !important; }
    [data-theme="light"] .text-zinc-200,
    [data-theme="light"] .text-zinc-300   { color: #475569 !important; }
    [data-theme="light"] .text-zinc-400   { color: #64748b !important; }
    [data-theme="light"] .text-zinc-800   { color: #334155 !important; }
    [data-theme="light"] .bg-white\/5     { background: rgba(0,0,0,0.04) !important; }
    [data-theme="light"] .bg-white\/8     { background: rgba(0,0,0,0.05) !important; }
    [data-theme="light"] .bg-white\/10    { background: rgba(0,0,0,0.06) !important; }
    [data-theme="light"] .border-white\/10  { border-color: rgba(0,0,0,0.08) !important; }
    [data-theme="light"] .border-white\/15  { border-color: rgba(0,0,0,0.10) !important; }
    [data-theme="light"] .action-circle {
        background: rgba(0,0,0,0.05) !important; border-color: rgba(0,0,0,0.08) !important;
    }
    [data-theme="light"] .text-indigo-100 { color: #4338ca !important; }
    [data-theme="light"] .text-indigo-400 { color: #6366f1 !important; }
    /* modal-os form inputs */
    [data-theme="light"] .modal-os .text-white { color: #0f172a !important; }
    [data-theme="light"] .modal-os input,
    [data-theme="light"] .modal-os select,
    [data-theme="light"] .modal-os textarea {
        color: #0f172a !important; background: rgba(0,0,0,0.04) !important;
        border-color: rgba(0,0,0,0.12) !important;
    }

.no-scrollbar::-webkit-scrollbar { display: none; }
</style>
<?= $extraHead ?>
</head>
<body>
<div class="bg-main">
<div id="app-container">
    <div style="padding-bottom: calc(66px + var(--safe-bottom) + 24px)">

        <header class="px-6 pt-10 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <img src="<?= View::e($userPhoto) ?>" onerror="this.onerror=null;this.src='<?= $avatarFallback ?>'" class="w-10 h-10 rounded-full border-2 border-blue-500/20 object-cover" alt="">
                <div>
                    <h2 class="text-[15px] font-extrabold leading-tight">Hi, <?= View::e($userName) ?></h2>
                    <p class="text-[8px] text-zinc-500 font-black tracking-widest uppercase italic"><?= t('nav.system_admin') ?></p>
                </div>
            </div>
            <button onclick="toggleMenu()" class="glass w-10 h-10 rounded-full flex items-center justify-center active:scale-90 transition-transform border-0">
                <i data-lucide="menu" class="w-4 h-4"></i>
            </button>
        </header>

        <?= $content ?>

    </div>
</div>
</div>

<!-- Bottom nav — flush, 5 items + More -->
<nav class="nav-bottom">
    <a href="/SRMT/public/admin/"              class="<?= $navClass('dashboard') ?>"><i data-lucide="home"></i><?= t('nav.home') ?></a>
    <a href="/SRMT/public/admin/rides.php"     class="<?= $navClass('rides') ?>"    ><i data-lucide="calendar"></i><?= t('nav.rides') ?></a>
    <a href="/SRMT/public/admin/live-map.php"  class="<?= $navClass('live-map') ?>" ><i data-lucide="locate-fixed"></i><?= t('nav.live') ?></a>
    <a href="/SRMT/public/admin/financial.php" class="<?= $navClass('financial') ?>"><i data-lucide="wallet"></i><?= t('nav.cash') ?></a>
    <button onclick="toggleMenu()"><i data-lucide="grid-2x2"></i><?= t('nav.more') ?></button>
</nav>

<!-- Full-screen overlay menu -->
<div id="fullMenu" class="fixed inset-0 z-[2000] hidden">
    <div class="menu-backdrop absolute inset-0" onclick="toggleMenu()"></div>
    <div class="menu-content relative h-full flex flex-col p-10 overflow-y-auto no-scrollbar">
        <div class="flex justify-between items-center mb-12">
            <h2 class="text-3xl font-black italic tracking-tighter">SyncRide <span class="text-blue-600">OS</span></h2>
            <button onclick="toggleMenu()" class="glass w-12 h-12 rounded-full flex items-center justify-center border-0">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <nav class="grid grid-cols-1 gap-5 text-xl font-bold">
            <a href="/SRMT/public/admin/"                class="menu-link flex items-center gap-4 <?= $active==='dashboard' ? 'menu-active' : '' ?>"><i data-lucide="layout-grid"></i> <?= t('nav.dashboard') ?></a>
            <a href="/SRMT/public/admin/rides.php"       class="menu-link flex items-center gap-4 <?= $active==='rides'     ? 'menu-active' : '' ?>"><i data-lucide="navigation"></i> <?= t('nav.rides') ?></a>
            <a href="/SRMT/public/admin/import.php"      class="menu-link flex items-center gap-4 <?= $active==='import'    ? 'menu-active' : '' ?>"><i data-lucide="file-spreadsheet"></i> <?= t('nav.import') ?></a>
            <a href="/SRMT/public/admin/live-map.php"    class="menu-link flex items-center gap-4 <?= $active==='live-map'  ? 'menu-active' : '' ?>"><i data-lucide="map"></i> <?= t('nav.live_map') ?></a>
            <hr class="menu-hr border-t">
            <a href="/SRMT/public/admin/users.php"       class="menu-link flex items-center gap-4 <?= $active==='users'     ? 'menu-active' : '' ?>"><i data-lucide="users"></i> <?= t('nav.team') ?></a>
            <a href="/SRMT/public/admin/fleet.php"       class="menu-link flex items-center gap-4 <?= $active==='fleet'     ? 'menu-active' : '' ?>"><i data-lucide="truck"></i> <?= t('nav.fleet') ?></a>
            <a href="/SRMT/public/admin/financial.php"   class="menu-link flex items-center gap-4 <?= $active==='financial' ? 'menu-active' : '' ?>"><i data-lucide="banknote"></i> <?= t('nav.financial') ?></a>
            <a href="/SRMT/public/admin/pricing.php"    class="menu-link flex items-center gap-4 <?= $active==='pricing'   ? 'menu-active' : '' ?>"><i data-lucide="tag"></i> <?= t('nav.pricing') ?></a>
            <hr class="menu-hr border-t">
            <a href="/SRMT/public/admin/schedule-board.php" class="menu-link flex items-center gap-4 <?= $active==='board'    ? 'menu-active' : '' ?>"><i data-lucide="layout-grid"></i> <?= t('nav.board') ?></a>
            <a href="/SRMT/public/admin/driver-stats.php" class="menu-link flex items-center gap-4 <?= $active==='stats'    ? 'menu-active' : '' ?>"><i data-lucide="bar-chart-3"></i> <?= t('nav.stats') ?></a>
            <a href="/SRMT/public/admin/no-shows.php"    class="menu-link flex items-center gap-4 <?= $active==='no-shows'  ? 'menu-active' : '' ?>"><i data-lucide="alert-triangle"></i> <?= t('nav.noshows') ?></a>
            <a href="/SRMT/public/admin/storage.php"     class="menu-link flex items-center gap-4 <?= $active==='storage'   ? 'menu-active' : '' ?>"><i data-lucide="database"></i> <?= t('nav.storage') ?></a>
            <a href="/SRMT/public/admin/partnerships.php" class="menu-link flex items-center gap-4 <?= $active==='partnerships' ? 'menu-active' : '' ?>"><i data-lucide="handshake"></i> <?= t('nav.partnerships') ?></a>
            <a href="/SRMT/public/admin/settings.php"    class="menu-link flex items-center gap-4 <?= $active==='settings'  ? 'menu-active' : '' ?>"><i data-lucide="settings-2"></i> <?= t('nav.settings') ?></a>
            <hr class="menu-hr border-t">
            <a href="#" onclick="event.preventDefault();toggleMenu();openChangePassword();" class="menu-link flex items-center gap-4"><i data-lucide="key-round"></i> <?= t('pwd.title') ?></a>
            <a href="/SRMT/public/auth/logout.php"       class="menu-link menu-link-danger flex items-center gap-4"><i data-lucide="log-out"></i> <?= t('nav.logout') ?></a>
        </nav>
    </div>
</div>

<?php include __DIR__ . '/_change_password.php'; ?>
<?php include __DIR__ . '/_csrf.php'; ?>

<script>
    lucide.createIcons();

    /* ── Theme ────────────────────────────────────────────────── */
    (function () {
        var saved = localStorage.getItem('sr-theme') || 'light';
        applyTheme(saved, false);
    })();

    function applyTheme(t, save) {
        document.documentElement.dataset.theme = t;
        var mc = document.getElementById('themeColor');
        if (mc) mc.content = t === 'dark' ? '#020617' : '#f1f5f9';
        if (save) localStorage.setItem('sr-theme', t);
    }

    /* ── Menu ─────────────────────────────────────────────────── */
    function toggleMenu() {
        document.getElementById('fullMenu').classList.toggle('hidden');
    }

    /* iOS fix: Bootstrap modals live inside #app-container (overflow + backdrop-filter),
       which traps them in a stacking context so the backdrop covers the modal. Moving
       the modal to <body> on open puts it and the backdrop on the same layer. */
    document.addEventListener('show.bs.modal', function (e) {
        if (e.target && e.target.parentElement !== document.body) {
            document.body.appendChild(e.target);
        }
    });
</script>
<?= $extraScripts ?>
</body>
</html>
