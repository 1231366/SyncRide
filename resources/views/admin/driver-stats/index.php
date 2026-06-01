<?php
use App\Http\View;

/** @var array $drivers @var array $partners @var int|null $driverId @var int|null $partnerId */
/** @var string $startDate @var string $endDate @var string $mode @var string $subjectName */
/** @var array $box1 @var array $box2 @var array $box3 @var array $box4 */
/** @var array<int,int> $chartData @var string $tableTitle @var array $tableRows @var array $leaderboard */

$monthLabels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

View::layout('layouts.admin', [
    'title'  => 'Stats — SyncRide OS',
    'active' => 'stats',
    'extraHead' => '
        <style>
            .filter-input {
                outline: none; border-radius: 12px; padding: 8px 12px;
                font-size: 11px; font-weight: 600; font-family: inherit;
                transition: border-color .2s;
            }
            [data-theme="dark"]  .filter-input { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #f1f5f9; }
            [data-theme="light"] .filter-input { background: rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.12); color: #0f172a; }
            .rank-badge {
                width: 22px; height: 22px; border-radius: 6px;
                display: flex; align-items: center; justify-content: center;
                font-size: 10px; font-weight: 800;
            }
            .rank-1 { background: #fbbf24; color: #000; }
            .rank-2 { background: #94a3b8; color: #000; }
            .rank-3 { background: #cd7f32; color: #000; }
            [data-theme="dark"]  .rank-n { background: rgba(255,255,255,0.08); color: #a1a1aa; }
            [data-theme="light"] .rank-n { background: rgba(0,0,0,0.07); color: #64748b; }
        </style>
    ',
    'extraScripts' => '
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        <script>
            new ApexCharts(document.querySelector("#mainChart"), {
                series: [{ name: "Rides", data: ' . json_encode($chartData) . ' }],
                chart: { height: 200, type: "area", toolbar: { show: false } },
                colors: ["#3b82f6"],
                stroke: { curve: "smooth", width: 2 },
                fill: { type: "gradient", gradient: { opacityFrom: 0.3, opacityTo: 0 } },
                xaxis: {
                    categories: ' . json_encode($monthLabels) . ',
                    labels: { style: { colors: "#71717a", fontSize: "9px" } }
                },
                yaxis: { labels: { show: false } },
                grid: { show: false },
                dataLabels: { enabled: false },
                tooltip: { theme: document.documentElement.dataset.theme === "dark" ? "dark" : "light" }
            }).render();
        </script>
    ',
]);
?>

<main class="px-6 mt-6">

    <!-- Filter bar -->
    <form method="GET" class="space-y-3 mb-6">
        <div class="grid grid-cols-2 gap-2">
            <select name="driver_id" class="filter-input"
                    onchange="if(this.value){this.form.partner_id.value='';}this.form.submit();">
                <option value=""><?= t('stats.drivers') ?></option>
                <?php foreach ($drivers as $d): ?>
                    <option value="<?= (int) $d->id ?>"
                        <?= ((int) $d->id === $driverId) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $d->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="partner_id" class="filter-input"
                    onchange="if(this.value){this.form.driver_id.value='';}this.form.submit();">
                <option value=""><?= t('stats.partners') ?></option>
                <?php foreach ($partners as $p): ?>
                    <option value="<?= (int) $p->id ?>"
                        <?= ((int) $p->id === $partnerId) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) $p->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2 items-center glass p-2 rounded-2xl">
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"
                   class="bg-transparent text-[10px] font-bold outline-none flex-1 text-white">
            <i data-lucide="arrow-right" class="w-3 h-3 text-zinc-600 flex-shrink-0"></i>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"
                   class="bg-transparent text-[10px] font-bold outline-none flex-1 text-white">
            <button type="submit"
                    class="w-8 h-8 bg-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                <i data-lucide="search" class="w-4 h-4 text-white"></i>
            </button>
        </div>
    </form>

    <!-- Subject context (only when a driver/partner is selected) -->
    <?php if ($mode !== 'overview'): ?>
        <div class="mb-4">
            <h1 class="text-[20px] font-extrabold tracking-tight"><?= htmlspecialchars($subjectName) ?></h1>
            <p class="text-[10px] text-zinc-500 font-semibold uppercase tracking-widest mt-0.5">
                <?= $mode === 'driver' ? t('stats.driver_perf') : t('stats.partner_stats') ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- KPI cards -->
    <section class="grid grid-cols-2 gap-3 mb-4">
        <?php foreach ([$box1, $box2, $box3, $box4] as $box): ?>
            <div class="glass p-4 rounded-[22px]">
                <div class="flex justify-between items-start mb-2">
                    <i data-lucide="<?= htmlspecialchars($box['icon']) ?>"
                       class="w-4 h-4 <?= htmlspecialchars($box['color']) ?>"></i>
                    <span class="text-[7px] font-black text-zinc-600 uppercase italic">
                        <?= htmlspecialchars($box['lbl']) ?>
                    </span>
                </div>
                <h3 class="text-2xl font-black"><?= htmlspecialchars((string) $box['val']) ?></h3>
            </div>
        <?php endforeach; ?>
    </section>

    <!-- Monthly chart -->
    <section class="mb-6">
        <div class="glass rounded-[28px] p-5">
            <h3 class="text-[10px] font-black text-white uppercase tracking-widest mb-4 italic">
                <?= t('stats.monthly_trend') ?>
            </h3>
            <div id="mainChart"></div>
        </div>
    </section>

    <!-- Table / leaderboard -->
    <section class="mb-8">
        <h3 class="text-[10px] font-black uppercase tracking-[0.2em] text-zinc-500 italic mb-4 px-1">
            <?= htmlspecialchars($tableTitle) ?>
        </h3>

        <?php if ($mode === 'overview' && !empty($leaderboard)): ?>
            <!-- Driver leaderboard -->
            <p class="text-[9px] font-black uppercase text-zinc-600 tracking-widest mb-2 px-1"><?= t('stats.drivers') ?></p>
            <div class="space-y-2 mb-4">
                <?php foreach ($leaderboard['drivers'] as $k => $d): ?>
                    <a href="?driver_id=<?= (int) $d['id'] ?>&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>"
                       class="glass p-3.5 rounded-2xl flex items-center justify-between block no-underline">
                        <div class="flex items-center gap-3">
                            <div class="rank-badge <?= $k < 3 ? 'rank-' . ($k + 1) : 'rank-n' ?>">
                                <?= $k + 1 ?>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-white"><?= htmlspecialchars($d['name']) ?></h4>
                                <p class="text-[9px] text-amber-500 font-bold">
                                    <i data-lucide="star" class="w-2.5 h-2.5 inline mr-1"></i>
                                    <?= $d['avg_rating'] !== null ? number_format($d['avg_rating'], 1) : 'N/A' ?>
                                </p>
                            </div>
                        </div>
                        <span class="text-xs font-black text-blue-500"><?= (int) $d['trips_period'] ?> <?= t('stats.rides') ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Partner leaderboard -->
            <?php if (!empty($leaderboard['partners'])): ?>
                <p class="text-[9px] font-black uppercase text-zinc-600 tracking-widest mb-2 px-1"><?= t('stats.partners') ?></p>
                <div class="space-y-2">
                    <?php foreach ($leaderboard['partners'] as $k => $p): ?>
                        <a href="?partner_id=<?= (int) $p['id'] ?>&start_date=<?= htmlspecialchars($startDate) ?>&end_date=<?= htmlspecialchars($endDate) ?>"
                           class="glass p-3.5 rounded-2xl flex items-center justify-between block no-underline">
                            <div class="flex items-center gap-3">
                                <div class="rank-badge <?= $k < 3 ? 'rank-' . ($k + 1) : 'rank-n' ?>">
                                    <?= $k + 1 ?>
                                </div>
                                <h4 class="text-xs font-bold text-white"><?= htmlspecialchars($p['name']) ?></h4>
                            </div>
                            <span class="text-xs font-black text-purple-400"><?= (int) $p['trips_period'] ?> <?= t('stats.rides') ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Driver/partner recent ride list -->
            <div class="space-y-2">
                <?php foreach ($tableRows as $row): ?>
                    <div class="glass p-3.5 rounded-2xl flex items-center justify-between">
                        <div>
                            <h4 class="text-[10px] font-bold text-white">
                                <?= htmlspecialchars(date('d M', strtotime((string) $row['serviceDate']))) ?>
                                &bull;
                                <?= htmlspecialchars(substr((string) $row['serviceStartTime'], 0, 5)) ?>
                            </h4>
                            <p class="text-[9px] text-zinc-500 truncate w-48">
                                <?= htmlspecialchars((string) $row['serviceStartPoint']) ?>
                                &rarr;
                                <?= htmlspecialchars((string) $row['serviceTargetPoint']) ?>
                            </p>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-zinc-700"></i>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($tableRows)): ?>
                    <p class="text-center text-zinc-600 text-xs py-8"><?= t('stats.no_rides') ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

</main>
