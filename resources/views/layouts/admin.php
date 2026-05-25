<?php
/**
 * Admin layout — SyncRide OS.
 *
 * Variables provided by callers (every key is optional):
 *   string $title          window title
 *   string $content        rendered child template (always set by View)
 *   string $active         id of the active nav-item (e.g. 'rides', 'fleet')
 *   array  $extraHead      raw HTML appended inside <head>
 *   array  $extraScripts   raw HTML appended right before </body>
 */

use App\Http\View;

/** @var string $content */
$title        = $title        ?? 'SyncRide OS';
$active       = $active       ?? '';
$extraHead    = $extraHead    ?? '';
$extraScripts = $extraScripts ?? '';

$userName  = isset($_SESSION['name'])  ? explode(' ', (string) $_SESSION['name'])[0] : 'Admin';
$userPhoto = $_SESSION['profile_photo_path']
    ?? '/SRMT/public/uploads/profiles/default.png';

/** Returns the CSS classes for a nav item depending on whether it is active. */
$navClass = static function (string $id) use ($active): string {
    return $id === $active ? 'text-blue-500' : 'text-zinc-500';
};
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#000000">
    <title><?= View::e($title) ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root { --safe-bottom: env(safe-area-inset-bottom, 20px); }
        html, body { height: 100%; overflow: hidden; background-color: #000; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: #fff; margin: 0; -webkit-font-smoothing: antialiased; }
        #app-container { height: 100%; overflow-y: auto; -webkit-overflow-scrolling: touch; }
        .bg-main { background: radial-gradient(circle at 50% -10%, #1e40af 0%, #000 75%); background-attachment: fixed; min-height: 100vh; }
        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .nav-float {
            position: fixed; bottom: calc(16px + var(--safe-bottom)); left: 50%; transform: translateX(-50%);
            width: calc(100% - 32px); max-width: 400px; height: 72px;
            background: rgba(18, 18, 18, 0.95); backdrop-filter: blur(25px);
            border-radius: 26px; border: 1px solid rgba(255, 255, 255, 0.1);
            display: flex; justify-content: space-around; align-items: center; z-index: 1000;
        }
        .nav-float a { flex: 1; display: flex; }
        .nav-float .nav-extra { display: none !important; }
        @media (min-width: 992px) {
            .nav-float { max-width: 720px; height: 78px; border-radius: 32px; }
            .nav-float .nav-extra { display: flex !important; }
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>
    <?= $extraHead ?>
</head>
<body class="bg-main">
    <div id="app-container">
        <div class="pb-32">
            <header class="px-6 pt-10 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <img src="<?= View::e($userPhoto) ?>" class="w-10 h-10 rounded-full border-2 border-blue-500/20 object-cover" alt="">
                    <div>
                        <h2 class="text-[15px] font-extrabold leading-tight">Hi, <?= View::e($userName) ?></h2>
                        <p class="text-[8px] text-zinc-500 font-black tracking-widest uppercase italic">System Admin</p>
                    </div>
                </div>
                <button onclick="toggleMenu()" class="w-10 h-10 glass rounded-full flex items-center justify-center active:scale-90 transition-transform">
                    <i data-lucide="menu" class="w-4 h-4 text-white"></i>
                </button>
            </header>

            <?= $content ?>
        </div>
    </div>

    <nav class="nav-float">
        <a href="/SRMT/public/admin/"            class="flex-col items-center gap-1 <?= $navClass('dashboard') ?>"><i data-lucide="home"        class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Home</span></a>
        <a href="/SRMT/public/admin/rides.php"   class="flex-col items-center gap-1 <?= $navClass('rides') ?>"    ><i data-lucide="calendar"    class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Rides</span></a>
        <a href="/SRMT/public/admin/live-map.php" class="flex-col items-center gap-1 <?= $navClass('live-map') ?>"><i data-lucide="locate-fixed" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Live</span></a>
        <a href="/SRMT/public/admin/financial.php" class="flex-col items-center gap-1 <?= $navClass('financial') ?>"><i data-lucide="wallet"   class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Cash</span></a>
        <a href="/SRMT/public/admin/fleet.php"   class="nav-extra flex-col items-center gap-1 <?= $navClass('fleet') ?>"     ><i data-lucide="truck"       class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Fleet</span></a>
        <a href="/SRMT/public/admin/users.php"   class="nav-extra flex-col items-center gap-1 <?= $navClass('users') ?>"     ><i data-lucide="users"       class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Team</span></a>
        <a href="/SRMT/public/admin/driver-stats.php" class="nav-extra flex-col items-center gap-1 <?= $navClass('stats') ?>"><i data-lucide="bar-chart-3" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Stats</span></a>
        <a href="/SRMT/public/admin/no-shows.php" class="nav-extra flex-col items-center gap-1 <?= $navClass('no-shows') ?>" ><i data-lucide="alert-triangle" class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">No Show</span></a>
        <a href="/SRMT/public/admin/storage.php"   class="nav-extra flex-col items-center gap-1 <?= $navClass('storage') ?>"  ><i data-lucide="database"    class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Storage</span></a>
        <a href="/SRMT/public/admin/settings.php" class="nav-extra flex-col items-center gap-1 <?= $navClass('settings') ?>"><i data-lucide="settings-2"  class="w-5 h-5"></i><span class="text-[7px] font-black uppercase">Settings</span></a>
    </nav>

    <div id="fullMenu" class="fixed inset-0 z-[2000] hidden">
        <div class="absolute inset-0 bg-black/98 backdrop-blur-2xl" onclick="toggleMenu()"></div>
        <div class="relative h-full flex flex-col p-10 text-white overflow-y-auto no-scrollbar">
            <div class="flex justify-between items-center mb-12">
                <h2 class="text-3xl font-black italic tracking-tighter">SyncRide <span class="text-blue-600">OS</span></h2>
                <button onclick="toggleMenu()" class="w-12 h-12 glass rounded-full flex items-center justify-center"><i data-lucide="x"></i></button>
            </div>
            <nav class="grid grid-cols-1 gap-6 text-xl font-bold">
                <a href="/SRMT/public/admin/"               class="flex items-center gap-4 <?= $active === 'dashboard' ? 'text-blue-500' : '' ?>"><i data-lucide="layout-grid"></i> Dashboard</a>
                <a href="/SRMT/public/admin/rides.php"      class="flex items-center gap-4"><i data-lucide="navigation"></i> Rides</a>
                <a href="/SRMT/public/admin/live-map.php"   class="flex items-center gap-4"><i data-lucide="map"></i> Live Map</a>
                <hr class="border-zinc-800">
                <a href="/SRMT/public/admin/users.php"      class="flex items-center gap-4"><i data-lucide="users"></i> Team</a>
                <a href="/SRMT/public/admin/fleet.php"      class="flex items-center gap-4"><i data-lucide="truck"></i> Fleet</a>
                <a href="/SRMT/public/admin/financial.php"  class="flex items-center gap-4"><i data-lucide="banknote"></i> Financial</a>
                <hr class="border-zinc-800">
                <a href="/SRMT/public/admin/driver-stats.php" class="flex items-center gap-4"><i data-lucide="bar-chart-3"></i> Stats</a>
                <a href="/SRMT/public/admin/no-shows.php"   class="flex items-center gap-4"><i data-lucide="alert-triangle"></i> No-shows</a>
                <a href="/SRMT/public/admin/storage.php"    class="flex items-center gap-4"><i data-lucide="database"></i> Storage</a>
                <a href="/SRMT/public/admin/settings.php"  class="flex items-center gap-4 <?= $active === 'settings' ? 'text-blue-500' : '' ?>"><i data-lucide="settings-2"></i> Settings</a>
                <hr class="border-zinc-800">
                <a href="/SRMT/public/auth/logout.php"      class="flex items-center gap-4 text-red-500 mt-4"><i data-lucide="log-out"></i> Logout</a>
            </nav>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function toggleMenu() { document.getElementById('fullMenu').classList.toggle('hidden'); }
    </script>
    <?= $extraScripts ?>
</body>
</html>
