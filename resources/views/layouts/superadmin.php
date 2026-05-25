<?php
/**
 * Super-admin layout — global tenant management.
 * Variables: string $title, string $content, string $active, string $extraScripts
 */
use App\Http\View;

$title        = $title        ?? 'SyncRide — Super Admin';
$active       = $active       ?? '';
$extraScripts = $extraScripts ?? '';

$navItem = static function(string $id, string $href, string $icon, string $label) use ($active): string {
    $cls = $id === $active
        ? 'flex items-center gap-3 px-4 py-3 rounded-xl bg-indigo-600 text-white font-semibold'
        : 'flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-white transition-colors';
    return "<a href=\"{$href}\" class=\"{$cls}\"><i data-lucide=\"{$icon}\" class=\"w-5 h-5\"></i><span>{$label}</span></a>";
};
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= View::e($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex">

<!-- Sidebar -->
<aside class="w-64 min-h-screen bg-slate-900 border-r border-slate-800 flex flex-col fixed top-0 left-0 z-40">
    <div class="px-6 py-5 border-b border-slate-800">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-xl bg-indigo-600 flex items-center justify-center">
                <i data-lucide="building-2" class="w-5 h-5 text-white"></i>
            </div>
            <div>
                <div class="text-sm font-bold text-white">SyncRide</div>
                <div class="text-xs text-indigo-400 font-medium">Super Admin</div>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-3 py-4 space-y-1">
        <?= $navItem('dashboard',  '/SRMT/public/superadmin/',           'layout-dashboard', 'Dashboard') ?>
        <?= $navItem('companies',  '/SRMT/public/superadmin/companies.php', 'building-2',   'Companies') ?>
    </nav>

    <div class="px-3 py-4 border-t border-slate-800 space-y-1">
        <div class="flex items-center gap-3 px-4 py-3">
            <div class="w-8 h-8 rounded-full bg-indigo-700 flex items-center justify-center text-xs font-bold text-white">
                <?= strtoupper(substr((string) ($_SESSION['name'] ?? 'SA'), 0, 2)) ?>
            </div>
            <div class="min-w-0">
                <div class="text-sm font-medium text-white truncate"><?= View::e((string) ($_SESSION['name'] ?? 'Super Admin')) ?></div>
                <div class="text-xs text-slate-500 truncate"><?= View::e((string) ($_SESSION['email'] ?? '')) ?></div>
            </div>
        </div>
        <a href="/SRMT/public/auth/logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-red-900/40 hover:text-red-400 transition-colors">
            <i data-lucide="log-out" class="w-5 h-5"></i><span>Logout</span>
        </a>
    </div>
</aside>

<!-- Main content -->
<main class="flex-1 ml-64 p-8 min-h-screen">
    <?= $content ?>
</main>

<script>lucide.createIcons();</script>
<?= $extraScripts ?>
</body>
</html>
