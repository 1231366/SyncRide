<?php
use App\Http\View;
View::layout('layouts.superadmin', ['title' => 'Super Admin — SyncRide', 'active' => 'dashboard']);
?>

<div class="max-w-7xl mx-auto px-4 md:px-6 pt-8">

    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-extrabold">Platform Overview</h1>
        <p class="text-slate-400 mt-1 text-sm">All companies and their activity across SyncRide.</p>
    </div>

    <!-- Global KPIs -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <?php
        $kpis = [
            ['icon' => 'building-2',  'label' => 'Companies',     'value' => $totalCompanies, 'color' => 'violet'],
            ['icon' => 'route',       'label' => 'Total Rides',    'value' => $totalRides,     'color' => 'blue'],
            ['icon' => 'users',       'label' => 'Total Drivers',  'value' => $totalDrivers,   'color' => 'emerald'],
            ['icon' => 'handshake',   'label' => 'Total Partners', 'value' => $totalPartners,  'color' => 'violet'],
        ];
        foreach ($kpis as $k): ?>
        <div class="glass rounded-2xl p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider"><?= View::e($k['label']) ?></span>
                <div class="w-9 h-9 rounded-xl bg-<?= $k['color'] ?>-500/15 flex items-center justify-center">
                    <i data-lucide="<?= $k['icon'] ?>" class="w-5 h-5 text-<?= $k['color'] ?>-400"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-white"><?= number_format((int) $k['value']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Company cards -->
    <div class="mb-4 flex items-center justify-between">
        <h2 class="text-base font-bold text-white">Companies</h2>
        <a href="/SRMT/public/superadmin/companies.php"
           class="flex items-center gap-2 px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-semibold transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> Manage
        </a>
    </div>

    <?php if (empty($stats)): ?>
    <div class="glass rounded-2xl p-12 text-center">
        <i data-lucide="building-2" class="w-12 h-12 text-slate-500 mx-auto mb-4"></i>
        <p class="text-slate-400">No companies yet. <a href="/SRMT/public/superadmin/companies.php" class="text-violet-400 hover:underline">Create one</a>.</p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
        <?php foreach ($stats as $company): ?>
        <div class="glass rounded-2xl p-6">
            <div class="flex items-start justify-between mb-4">
                <div>
                    <div class="text-base font-bold text-white"><?= View::e($company['name']) ?></div>
                    <div class="text-xs text-slate-500 font-mono mt-0.5"><?= View::e($company['slug']) ?></div>
                </div>
                <span class="text-xs bg-violet-500/15 text-violet-400 px-2.5 py-1 rounded-full font-medium">
                    #<?= (int) $company['id'] ?>
                </span>
            </div>

            <div class="grid grid-cols-3 gap-3 mb-4">
                <?php
                $metrics = [
                    ['label' => 'Admins',   'val' => $company['admins'],   'icon' => 'shield-check'],
                    ['label' => 'Drivers',  'val' => $company['drivers'],  'icon' => 'car'],
                    ['label' => 'Partners', 'val' => $company['partners'], 'icon' => 'handshake'],
                ];
                foreach ($metrics as $m): ?>
                <div class="bg-white/5 rounded-xl p-3 text-center">
                    <i data-lucide="<?= $m['icon'] ?>" class="w-4 h-4 text-slate-400 mx-auto mb-1"></i>
                    <div class="text-lg font-bold text-white"><?= (int) $m['val'] ?></div>
                    <div class="text-xs text-slate-500"><?= $m['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="flex items-center justify-between text-sm border-t border-white/10 pt-4">
                <div>
                    <span class="text-slate-400">Total rides:</span>
                    <span class="text-white font-semibold ml-1"><?= number_format((int) $company['total_rides']) ?></span>
                </div>
                <div>
                    <span class="text-slate-400">Today:</span>
                    <span class="<?= (int) $company['rides_today'] > 0 ? 'text-emerald-400' : 'text-slate-500' ?> font-semibold ml-1">
                        <?= (int) $company['rides_today'] ?>
                    </span>
                </div>
                <a href="/SRMT/public/superadmin/companies.php?edit=<?= (int) $company['id'] ?>"
                   class="text-violet-400 hover:text-violet-300 transition-colors">
                    <i data-lucide="pencil" class="w-4 h-4"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
