<?php
/**
 * Super-admin layout — global tenant management.
 * Variables: string $title, string $content, string $active, string $extraScripts
 */
use App\Http\View;

$title        = $title        ?? 'SyncRide — Super Admin';
$active       = $active       ?? '';
$extraScripts = $extraScripts ?? '';

$userName       = isset($_SESSION['name']) ? explode(' ', (string) $_SESSION['name'])[0] : 'SA';
$initial        = mb_strtoupper(mb_substr($userName, 0, 1, 'UTF-8'));
$svgAvatar      = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><circle cx="20" cy="20" r="20" fill="#7c3aed"/><text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="white" font-size="17" font-weight="bold" font-family="system-ui">' . htmlspecialchars($initial) . '</text></svg>';
$avatarFallback = 'data:image/svg+xml;base64,' . base64_encode($svgAvatar);

$navClass = static function (string $id) use ($active): string {
    return $id === $active ? 'sr-nav-active' : '';
};
?><!DOCTYPE html>
<html lang="en" translate="no" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" id="themeColor" content="#020617">
<title><?= View::e($title) ?></title>
<meta name="csrf-token" content="<?= \App\Support\Session::csrfToken() ?>">

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root { --safe-bottom: env(safe-area-inset-bottom, 0px); }

    html, body { height: 100%; overflow: hidden; }
    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        margin: 0;
        -webkit-font-smoothing: antialiased;
        background-color: #020617;
        color: #f1f5f9;
    }
    #app-container { height: 100%; overflow-y: auto; -webkit-overflow-scrolling: touch; }

    /* ── Page background ─────────────────────────────────── */
    .bg-main {
        background: radial-gradient(circle at 50% -10%, #2e1065 0%, #020617 70%);
        background-color: #020617;
        height: 100%;
    }
    [data-theme="light"] .bg-main {
        background: radial-gradient(circle at 50% -10%, #ede9fe 0%, #f1f5f9 65%);
        background-color: #f1f5f9;
    }
    [data-theme="light"] body { background-color: #f1f5f9; color: #0f172a; }

    /* ── Glass ───────────────────────────────────────────── */
    .glass {
        background: rgba(255,255,255,0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.10);
    }
    [data-theme="light"] .glass {
        background: rgba(255,255,255,0.62);
        border: 1px solid rgba(0,0,0,0.08);
    }

    /* ── Bottom nav pill ─────────────────────────────────── */
    .nav-bottom {
        position: fixed;
        bottom: 0; left: 50%;
        transform: translateX(-50%);
        width: calc(100% - 24px);
        max-width: 480px;
        height: 66px;
        margin-bottom: calc(10px + var(--safe-bottom));
        background: rgba(10,14,30,0.95);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
        border: 1px solid rgba(255,255,255,0.09);
        border-radius: 26px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.5), 0 2px 8px rgba(0,0,0,0.3);
        display: flex; align-items: stretch;
        z-index: 1000;
        overflow: hidden;
    }
    [data-theme="light"] .nav-bottom {
        background: rgba(255,255,255,0.90);
        border: 1px solid rgba(0,0,0,0.07);
        box-shadow: 0 8px 32px rgba(0,0,0,0.10), 0 2px 8px rgba(0,0,0,0.06);
    }
    .nav-bottom a, .nav-bottom button {
        flex: 1; display: flex; flex-direction: column;
        align-items: center; justify-content: center; gap: 3px;
        font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
        color: #475569;
        background: none; border: none; cursor: pointer;
        text-decoration: none;
        transition: color .15s;
        padding: 0;
    }
    .nav-bottom a i, .nav-bottom button i { width: 20px; height: 20px; display: block; }
    .nav-bottom a:hover, .nav-bottom button:hover { color: #94a3b8; }
    [data-theme="light"] .nav-bottom a,
    [data-theme="light"] .nav-bottom button { color: #94a3b8; }
    [data-theme="light"] .nav-bottom a:hover,
    [data-theme="light"] .nav-bottom button:hover { color: #64748b; }
    .nav-bottom a.sr-nav-active { color: #a78bfa; }
    [data-theme="light"] .nav-bottom a.sr-nav-active { color: #7c3aed; }

    /* ── Full menu overlay ───────────────────────────────── */
    .menu-backdrop {
        background: rgba(2,6,23,0.99);
        backdrop-filter: blur(24px);
        -webkit-backdrop-filter: blur(24px);
    }
    [data-theme="light"] .menu-backdrop {
        background: rgba(248,250,252,0.98);
    }
    .menu-content { color: #f1f5f9; }
    [data-theme="light"] .menu-content { color: #0f172a; }
    .menu-hr { border-color: #1e293b; }
    [data-theme="light"] .menu-hr { border-color: #e2e8f0; }
    .menu-link { color: #f1f5f9 !important; text-decoration: none; background: none; border: none; width: 100%; }
    [data-theme="light"] .menu-link { color: #0f172a !important; }
    .menu-link.menu-active { color: #a78bfa !important; }
    [data-theme="light"] .menu-link.menu-active { color: #7c3aed !important; }
    .menu-link-danger { color: #dc2626 !important; }

    /* ── Shared modal ────────────────────────────────────── */
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
    .modal-os {
        background: rgba(10,12,20,0.97); border: 1px solid rgba(255,255,255,0.12);
    }
    [data-theme="light"] .modal-os {
        background: rgba(255,255,255,0.96); border: 1px solid rgba(0,0,0,0.10);
        box-shadow: 0 24px 64px rgba(0,0,0,0.14);
    }
    .modal-overlay {
        position: fixed; inset: 0; backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);
        background: rgba(0,0,0,0.6);
        visibility: hidden; opacity: 0; z-index: 3999; transition: all 0.3s;
    }
    .modal-overlay.active { visibility: visible; opacity: 1; }
    [data-theme="light"] .modal-overlay { background: rgba(0,0,0,0.22); }

    /* ── Light-mode overrides for slate classes in views ─── */
    [data-theme="light"] .text-white      { color: #0f172a !important; }
    [data-theme="light"] .text-slate-100  { color: #0f172a !important; }
    [data-theme="light"] .text-slate-300  { color: #334155 !important; }
    [data-theme="light"] .text-slate-400  { color: #64748b !important; }
    [data-theme="light"] .text-slate-500  { color: #94a3b8 !important; }
    [data-theme="light"] .bg-slate-900    { background: rgba(255,255,255,0.85) !important; }
    [data-theme="light"] .bg-slate-800    { background: rgba(0,0,0,0.06) !important; }
    [data-theme="light"] .border-slate-700 { border-color: rgba(0,0,0,0.10) !important; }
    [data-theme="light"] .border-slate-800 { border-color: rgba(0,0,0,0.08) !important; }
    [data-theme="light"] .bg-slate-800\/60 { background: rgba(0,0,0,0.04) !important; }
    [data-theme="light"] .bg-black\/70    {
        background: rgba(0,0,0,0.22) !important;
        backdrop-filter: blur(4px);
    }
    [data-theme="light"] input.bg-slate-800,
    [data-theme="light"] select.bg-slate-800 {
        background: rgba(0,0,0,0.04) !important;
        color: #0f172a !important;
        border-color: rgba(0,0,0,0.12) !important;
    }
    [data-theme="light"] input.bg-slate-800::placeholder { color: #94a3b8 !important; }

    .no-scrollbar::-webkit-scrollbar { display: none; }
</style>
</head>
<body>
<div class="bg-main">
<div id="app-container">
    <div style="padding-bottom: calc(66px + var(--safe-bottom) + 24px)">

        <header class="px-6 pt-10 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-violet-700 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                    <?= View::e($initial) ?>
                </div>
                <div>
                    <h2 class="text-[15px] font-extrabold leading-tight">Hi, <?= View::e($userName) ?></h2>
                    <p class="text-[8px] text-slate-500 font-black tracking-widest uppercase italic">Super Admin</p>
                </div>
            </div>
            <button onclick="toggleMenu()" class="glass w-10 h-10 rounded-full flex items-center justify-center active:scale-90 transition-transform border-0 cursor-pointer">
                <i data-lucide="menu" class="w-4 h-4"></i>
            </button>
        </header>

        <?= $content ?>

    </div>
</div>
</div>

<!-- Bottom nav -->
<nav class="nav-bottom">
    <a href="/SRMT/public/superadmin/"              class="<?= $navClass('dashboard') ?>"><i data-lucide="layout-grid"></i>Dashboard</a>
    <a href="/SRMT/public/superadmin/companies.php" class="<?= $navClass('companies') ?>"><i data-lucide="building-2"></i>Companies</a>
    <button onclick="toggleMenu()"><i data-lucide="grid-2x2"></i>More</button>
</nav>

<!-- Full-screen overlay menu -->
<div id="fullMenu" class="fixed inset-0 z-[2000] hidden">
    <div class="menu-backdrop absolute inset-0" onclick="toggleMenu()"></div>
    <div class="menu-content relative h-full flex flex-col p-10 overflow-y-auto no-scrollbar">
        <div class="flex justify-between items-center mb-12">
            <h2 class="text-3xl font-black italic tracking-tighter">SyncRide <span class="text-violet-500">SA</span></h2>
            <button onclick="toggleMenu()" class="glass w-12 h-12 rounded-full flex items-center justify-center border-0 cursor-pointer">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <nav class="grid grid-cols-1 gap-5 text-xl font-bold">
            <a href="/SRMT/public/superadmin/"              class="menu-link flex items-center gap-4 <?= $active==='dashboard' ? 'menu-active' : '' ?>"><i data-lucide="layout-grid"></i> Dashboard</a>
            <a href="/SRMT/public/superadmin/companies.php" class="menu-link flex items-center gap-4 <?= $active==='companies' ? 'menu-active' : '' ?>"><i data-lucide="building-2"></i> Companies</a>
            <hr class="menu-hr border-t">
            <button onclick="toggleTheme()" class="menu-link flex items-center gap-4 text-left cursor-pointer"><i data-lucide="sun-moon"></i> Toggle Theme</button>
            <a href="#" onclick="event.preventDefault();toggleMenu();openChangePassword();" class="menu-link flex items-center gap-4"><i data-lucide="key-round"></i> Change Password</a>
            <a href="/SRMT/public/auth/logout.php" class="menu-link menu-link-danger flex items-center gap-4"><i data-lucide="log-out"></i> Logout</a>
        </nav>
    </div>
</div>

<?php include __DIR__ . '/_csrf.php'; ?>
<?php include __DIR__ . '/_change_password.php'; ?>

<script>
    lucide.createIcons();

    (function () {
        var saved = localStorage.getItem('sr-sa-theme') || 'dark';
        applyTheme(saved, false);
    })();

    function applyTheme(t, save) {
        document.documentElement.dataset.theme = t;
        var mc = document.getElementById('themeColor');
        if (mc) mc.content = t === 'dark' ? '#020617' : '#f1f5f9';
        if (save) localStorage.setItem('sr-sa-theme', t);
    }

    function toggleTheme() {
        var current = document.documentElement.dataset.theme || 'dark';
        applyTheme(current === 'dark' ? 'light' : 'dark', true);
    }

    function toggleMenu() {
        document.getElementById('fullMenu').classList.toggle('hidden');
    }
</script>
<?= $extraScripts ?>
</body>
</html>
