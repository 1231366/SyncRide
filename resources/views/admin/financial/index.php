<?php
/** @var string $from @var string $to @var ?string $supplier @var ?int $driverId */
/** @var array<string> $suppliers @var array<App\Models\User> $drivers */
/** @var array{rows:array<array<string,mixed>>,totals:array<string,mixed>,by_supplier:array<string,array<string,mixed>>,by_driver:array<string,array<string,mixed>>} $report */
/** @var float $totalExpenses @var float $netProfit @var array<App\Models\Expense> $expenses */
/** @var array<string> $categoryLabels @var array<float> $categoryValues @var ?string $flash */
/** @var array<string,array<string,mixed>> $byDay @var array<int,int> $byHour */
/** @var array{private:array{count:int,revenue:float},shared:array{count:int,revenue:float}} $byType */
/** @var array{count:int,revenue:float,driver_cost:float,margin:float} $prevTotals */
/** @var string $prevFrom @var string $prevTo @var int $periodDays */
/** @var float $marginPct @var float $prevMarginPct @var float $netMarginPct */
/** @var float $avgTicket @var float $avgPerDay */
/** @var ?float $pctRevenue @var ?float $pctMargin @var ?float $pctCount @var ?float $diffMarginPct */
/** @var bool $isCurrentPeriod @var ?float $projRevenue @var ?float $projMargin */

use App\Http\View;

// Compute top-10 drivers for chart
$topDrivers = array_slice($report['by_driver'], 0, 10, true);
$flashMessages = ['created' => t('fin.expense_logged'), 'deleted' => t('fin.expense_removed')];

$pctBadge = static function (?float $v, bool $invertColors = false): string {
    if ($v === null) return '';
    $up   = $v >= 0;
    $color = ($up xor $invertColors) ? '#10b981' : '#ef4444';
    $arrow = $up ? '↑' : '↓';
    return "<span style='font-size:10px;font-weight:800;color:{$color}'>{$arrow} " . abs($v) . "%</span>";
};
$ppBadge = static function (?float $v): string {
    if ($v === null) return '';
    $color = $v >= 0 ? '#10b981' : '#ef4444';
    $arrow = $v >= 0 ? '↑' : '↓';
    return "<span style='font-size:10px;font-weight:800;color:{$color}'>{$arrow} " . abs($v) . "pp</span>";
};

ob_start(); // ── extraHead ──────────────────────────────────────────
?>
<style>
/* ── Financial page ───────────────────────────────────────────── */
.fin-kpi {
    border-radius: 20px; padding: 16px 18px;
    background: rgba(255,255,255,0.65);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(0,0,0,0.07);
    display: flex; flex-direction: column; gap: 2px;
}
[data-theme="dark"] .fin-kpi { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.08); }
.fin-kpi-label { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .12em; color: #94a3b8; margin-bottom: 4px; }
.fin-kpi-value { font-size: 24px; font-weight: 900; line-height: 1; margin-bottom: 5px; }
.fin-kpi-sub   { font-size: 10px; color: #94a3b8; font-weight: 600; display: flex; align-items: center; gap: 5px; }
.fin-chart-card {
    border-radius: 20px; padding: 18px;
    background: rgba(255,255,255,0.65);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(0,0,0,0.07);
}
[data-theme="dark"] .fin-chart-card { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.08); }
.fin-chart-title { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .1em; color: #94a3b8; margin-bottom: 12px; }
/* Prediction banner */
.fin-proj {
    border-radius: 18px; padding: 14px 18px;
    background: rgba(37,99,235,.08);
    border: 1px solid rgba(37,99,235,.18);
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
}
[data-theme="dark"] .fin-proj { background: rgba(59,130,246,.10); border-color: rgba(59,130,246,.25); }
/* Alert banner */
.fin-alert {
    border-radius: 18px; padding: 14px 18px;
    background: rgba(239,68,68,.07);
    border: 1px solid rgba(239,68,68,.20);
    display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
}
/* Sub-table */
.fin-sub-table th { font-size: 9px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; padding: 4px 8px; }
.fin-sub-table td { font-size: 11px; padding: 7px 8px; border-top: 1px solid rgba(0,0,0,0.05); }
[data-theme="dark"] .fin-sub-table td { border-top-color: rgba(255,255,255,0.05); }
/* Expense form inputs */
[data-theme="light"] .fin-input { background: rgba(0,0,0,0.04) !important; border-color: rgba(0,0,0,0.10) !important; color: #0f172a !important; }
</style>
<?php $finHead = ob_get_clean(); ?>

<?php ob_start(); // ── extraScripts ────────────────────────────────── ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const isDark = document.documentElement.dataset.theme === 'dark';
    const tc  = isDark ? '#94a3b8' : '#64748b';
    const gc  = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const tip = isDark ? 'dark' : 'light';
    const fmt = v => '€' + parseFloat(v).toFixed(2);

    // ── 1. Daily Revenue + Margin ─────────────────────────────────────
    const dayDates   = <?= json_encode(array_keys($byDay), JSON_UNESCAPED_UNICODE) ?>;
    const dayRevs    = <?= json_encode(array_values(array_map(fn($v) => round($v['revenue'], 2), $byDay))) ?>;
    const dayMargins = <?= json_encode(array_values(array_map(fn($v) => round($v['margin'],  2), $byDay))) ?>;
    const dayCosts   = <?= json_encode(array_values(array_map(fn($v) => round($v['driver_cost'], 2), $byDay))) ?>;

    if (dayDates.length > 0) {
        new ApexCharts(document.querySelector('#finChartDaily'), {
            chart: { type: 'area', height: 260, background: 'transparent', toolbar: { show: false }, animations: { speed: 400 } },
            series: [
                { name: '<?= t('fin.revenue') ?>',      data: dayRevs    },
                { name: '<?= t('fin.margin') ?>',       data: dayMargins },
                { name: '<?= t('fin.driver_cost') ?>', data: dayCosts   },
            ],
            xaxis: { categories: dayDates, labels: { style: { colors: tc, fontSize: '9px', fontWeight: 600 }, rotate: dayDates.length > 14 ? -45 : 0 }, tickAmount: Math.min(10, dayDates.length) },
            yaxis: { labels: { style: { colors: tc, fontSize: '9px' }, formatter: v => '€' + v.toFixed(0) } },
            colors: ['#10b981', '#8b5cf6', '#0ea5e9'],
            fill: { type: 'gradient', gradient: { shade: 'dark', type: 'vertical', opacityFrom: 0.3, opacityTo: 0.0 } },
            stroke: { curve: 'smooth', width: [2.5, 2, 1.5], dashArray: [0, 0, 4] },
            dataLabels: { enabled: false },
            grid: { borderColor: gc, strokeDashArray: 3 },
            tooltip: { theme: tip, y: { formatter: fmt } },
            legend: { labels: { colors: tc }, fontSize: '10px', position: 'top' },
        }).render();
    } else {
        document.querySelector('#finChartDaily').innerHTML = '<p style="text-align:center;padding:48px;font-size:11px;color:#94a3b8;font-weight:600"><?= t('fin.no_services') ?></p>';
    }

    // ── 2. Private vs Shared donut ────────────────────────────────────
    const privRev   = <?= round($byType['private']['revenue'], 2) ?>;
    const sharedRev = <?= round($byType['shared']['revenue'],  2) ?>;
    const privCt    = <?= $byType['private']['count'] ?>;
    const sharedCt  = <?= $byType['shared']['count'] ?>;

    if (privRev + sharedRev > 0) {
        new ApexCharts(document.querySelector('#finChartType'), {
            chart: { type: 'donut', height: 260, background: 'transparent' },
            series: [privRev, sharedRev],
            labels: ['<?= t('fin.private') ?>', '<?= t('fin.shared') ?>'],
            colors: ['#3b82f6', '#6366f1'],
            stroke: { width: 0 },
            plotOptions: { pie: { donut: { size: '72%', labels: { show: true,
                name:  { color: tc, fontSize: '12px', fontWeight: 800 },
                value: { color: tc, fontSize: '16px', fontWeight: 900, formatter: fmt },
                total: { show: true, label: 'Total', color: tc, fontSize: '10px', formatter: () => fmt(privRev + sharedRev) }
            } } } },
            legend: { position: 'bottom', labels: { colors: tc }, fontSize: '11px' },
            dataLabels: { enabled: false },
            tooltip: { theme: tip, y: { formatter: fmt } },
        }).render();
    } else {
        document.querySelector('#finChartType').innerHTML = '<p style="text-align:center;padding:48px;font-size:11px;color:#94a3b8;font-weight:600">—</p>';
    }

    // ── 3. Driver ranking (horizontal bar) ────────────────────────────
    const driverNames   = <?= json_encode(array_reverse(array_keys($topDrivers)), JSON_UNESCAPED_UNICODE) ?>;
    const driverRevs    = <?= json_encode(array_reverse(array_values(array_map(fn($v) => round($v['revenue'], 2), $topDrivers)))) ?>;
    const driverMargins = <?= json_encode(array_reverse(array_values(array_map(fn($v) => round($v['margin'],  2), $topDrivers)))) ?>;

    if (driverNames.length > 0) {
        new ApexCharts(document.querySelector('#finChartDrivers'), {
            chart: { type: 'bar', height: Math.max(200, driverNames.length * 48), background: 'transparent', toolbar: { show: false } },
            plotOptions: { bar: { horizontal: true, borderRadius: 6, barHeight: '55%', dataLabels: { position: 'top' } } },
            series: [
                { name: '<?= t('fin.revenue') ?>', data: driverRevs    },
                { name: '<?= t('fin.margin') ?>',  data: driverMargins },
            ],
            xaxis: { labels: { style: { colors: tc, fontSize: '9px' }, formatter: v => '€' + v } },
            yaxis: { labels: { style: { colors: tc, fontSize: '10px', fontWeight: 700 } } },
            colors: ['#10b981', '#8b5cf6'],
            grid: { borderColor: gc, strokeDashArray: 3 },
            dataLabels: { enabled: false },
            legend: { labels: { colors: tc }, fontSize: '10px' },
            tooltip: { theme: tip, y: { formatter: fmt } },
        }).render();
    }

    // ── 4. Peak hours bar ─────────────────────────────────────────────
    const hourData = <?= json_encode(array_values($byHour)) ?>;
    const hourCats = Array.from({length: 24}, (_, i) => i + 'h');
    new ApexCharts(document.querySelector('#finChartHours'), {
        chart: { type: 'bar', height: 210, background: 'transparent', toolbar: { show: false } },
        plotOptions: { bar: { borderRadius: 5, columnWidth: '68%' } },
        series: [{ name: '<?= t('fin.services') ?>', data: hourData }],
        xaxis: { categories: hourCats, labels: { style: { colors: tc, fontSize: '8.5px' } } },
        yaxis: { labels: { style: { colors: tc, fontSize: '9px' } } },
        colors: ['#f59e0b'],
        grid: { borderColor: gc, strokeDashArray: 3 },
        dataLabels: { enabled: false },
        tooltip: { theme: tip },
    }).render();

    // ── 5. Expense categories donut ───────────────────────────────────
    const expLabels = <?= json_encode($categoryLabels, JSON_UNESCAPED_UNICODE) ?>;
    const expValues = <?= json_encode(array_map('floatval', $categoryValues)) ?>;
    if (expLabels.length > 0) {
        new ApexCharts(document.querySelector('#finChartExpenses'), {
            chart: { type: 'donut', height: 220, background: 'transparent' },
            series: expValues, labels: expLabels,
            colors: ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899'],
            stroke: { width: 0 },
            plotOptions: { pie: { donut: { size: '68%' } } },
            legend: { labels: { colors: tc }, position: 'bottom', fontSize: '11px' },
            dataLabels: { enabled: false },
            tooltip: { theme: tip, y: { formatter: fmt } },
        }).render();
    }

    <?php if ($flash !== null && isset($flashMessages[$flash])): ?>
    if (typeof toastr !== 'undefined') toastr.success("<?= View::e($flashMessages[$flash]) ?>");
    <?php elseif ($flash === 'recalculated'): ?>
    if (typeof toastr !== 'undefined') toastr.success("<?= View::e(t('fin.recalc_done')) ?>".replace('%n', "<?= (int)($_GET['n'] ?? 0) ?>"));
    <?php endif; ?>
});

function openExpenseModal() {
    document.getElementById('modalOverlay').classList.add('active');
    document.getElementById('expenseModal').classList.add('active');
}
function closeModal() {
    document.getElementById('modalOverlay').classList.remove('active');
    document.getElementById('expenseModal').classList.remove('active');
}
</script>
<?php $finScripts = ob_get_clean(); ?>

<?php
View::layout('layouts.admin', [
    'title'        => t('fin.title') . ' — SyncRide OS',
    'active'       => 'financial',
    'extraHead'    => $finHead,
    'extraScripts' => $finScripts,
]);
?>

<?php $tot = $report['totals']; ?>

<section class="px-4 md:px-6 mt-6 max-w-7xl mx-auto">

    <!-- ── Page header ─────────────────────────────────────────────── -->
    <div class="flex items-start justify-between mb-5 flex-wrap gap-3">
        <div>
            <h1 class="text-xl font-black"><?= t('fin.title') ?></h1>
            <p class="text-[10px] text-zinc-500 font-semibold mt-0.5">
                <?= View::e($from) ?> → <?= View::e($to) ?>
                <?php if ($prevTotals['revenue'] > 0): ?>
                    <span class="opacity-60 ml-1">· <?= t('fin.prev_period') ?>: <?= View::e($prevFrom) ?> → <?= View::e($prevTo) ?></span>
                <?php endif; ?>
            </p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <a href="/SRMT/public/admin/financial-report.php?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&supplier=<?= urlencode((string)$supplier) ?>&driver=<?= (int)$driverId ?>"
               target="_blank"
               class="glass rounded-xl px-4 text-xs font-bold flex items-center gap-2 text-violet-500" style="min-height:40px;touch-action:manipulation">
                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> <?= t('fin.pdf_report') ?>
            </a>
            <a href="/SRMT/public/admin/financial-export.php?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&supplier=<?= urlencode((string)$supplier) ?>&driver=<?= (int)$driverId ?>"
               class="glass rounded-xl px-4 text-xs font-bold flex items-center gap-2 text-emerald-500" style="min-height:40px">
                <i data-lucide="download" class="w-3.5 h-3.5"></i> CSV
            </a>
            <form method="POST" action="/SRMT/public/admin/financial-recalc.php" class="inline"
                  onsubmit="return confirm('<?= View::e(t('fin.recalc_confirm')) ?>')">
                <input type="hidden" name="from" value="<?= View::e($from) ?>">
                <input type="hidden" name="to"   value="<?= View::e($to) ?>">
                <button type="submit" class="glass rounded-xl px-4 text-xs font-bold flex items-center gap-2 text-sky-500" style="min-height:40px" title="<?= View::e(t('fin.recalc_hint')) ?>">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> <?= t('fin.recalc') ?>
                </button>
            </form>
        </div>
    </div>

    <!-- ── Filter form ─────────────────────────────────────────────── -->
    <div class="glass rounded-2xl p-4 mb-5">
        <form method="GET" class="flex flex-col gap-3">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div class="flex flex-col gap-1">
                    <span class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.from') ?></span>
                    <input type="date" name="from" value="<?= View::e($from) ?>"
                           class="fin-input bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-xs w-full" style="min-height:38px">
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.to') ?></span>
                    <input type="date" name="to" value="<?= View::e($to) ?>"
                           class="fin-input bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-xs w-full" style="min-height:38px">
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.supplier') ?></span>
                    <select name="supplier" class="fin-input bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-xs w-full" style="min-height:38px">
                        <option value=""><?= t('fin.all_suppliers') ?></option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= View::e($s) ?>" <?= $supplier === $s ? 'selected' : '' ?>><?= View::e($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.driver') ?></span>
                    <select name="driver" class="fin-input bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-xs w-full" style="min-height:38px">
                        <option value="0"><?= t('fin.all_drivers') ?></option>
                        <?php foreach ($drivers as $d): ?>
                            <option value="<?= (int)$d->id ?>" <?= $driverId === (int)$d->id ? 'selected' : '' ?>><?= View::e($d->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="glass rounded-xl px-5 text-xs font-bold flex items-center gap-2 text-blue-500" style="min-height:38px;touch-action:manipulation">
                    <i data-lucide="filter" class="w-3.5 h-3.5"></i> <?= t('fin.apply') ?>
                </button>
            </div>
        </form>
    </div>

    <!-- ── KPI cards ───────────────────────────────────────────────── -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">

        <!-- Revenue -->
        <div class="fin-kpi">
            <p class="fin-kpi-label"><?= t('fin.revenue') ?></p>
            <h3 class="fin-kpi-value" style="color:#10b981">€<?= number_format((float)$tot['revenue'], 2) ?></h3>
            <p class="fin-kpi-sub">
                <?= $pctBadge($pctRevenue) ?>
                <span><?= t('fin.vs_prev') ?></span>
            </p>
        </div>

        <!-- Margin % -->
        <div class="fin-kpi">
            <p class="fin-kpi-label"><?= t('fin.margin_pct') ?></p>
            <h3 class="fin-kpi-value" style="color:#8b5cf6"><?= $marginPct ?>%</h3>
            <p class="fin-kpi-sub">
                <?= $ppBadge($diffMarginPct) ?>
                <span><?= t('fin.vs_prev') ?></span>
            </p>
        </div>

        <!-- Net profit -->
        <div class="fin-kpi">
            <p class="fin-kpi-label"><?= t('fin.net') ?></p>
            <h3 class="fin-kpi-value <?= $netProfit >= 0 ? '' : 'text-red-500' ?>" style="<?= $netProfit >= 0 ? 'color:#3b82f6' : '' ?>">
                €<?= number_format($netProfit, 2) ?>
            </h3>
            <p class="fin-kpi-sub" style="color:#94a3b8"><?= $netMarginPct ?>% <?= t('fin.of_revenue') ?></p>
        </div>

        <!-- Avg ticket -->
        <div class="fin-kpi">
            <p class="fin-kpi-label"><?= t('fin.avg_ticket') ?></p>
            <h3 class="fin-kpi-value">€<?= number_format($avgTicket, 2) ?></h3>
            <p class="fin-kpi-sub" style="color:#94a3b8">€<?= number_format($avgPerDay, 0) ?> / <?= t('fin.day_abbr') ?></p>
        </div>

        <!-- Services count -->
        <div class="fin-kpi">
            <p class="fin-kpi-label"><?= t('fin.services') ?></p>
            <h3 class="fin-kpi-value"><?= (int)$tot['count'] ?></h3>
            <p class="fin-kpi-sub">
                <?= $pctBadge($pctCount) ?>
                <span><?= t('fin.vs_prev') ?></span>
            </p>
        </div>

    </div>

    <!-- ── Phase 3: Projection banner ──────────────────────────────── -->
    <?php if ($isCurrentPeriod && $projRevenue !== null): ?>
    <div class="fin-proj mb-4">
        <i data-lucide="trending-up" class="w-5 h-5 flex-shrink-0" style="color:#3b82f6"></i>
        <div class="flex-1 min-w-0">
            <p class="text-[11px] font-black" style="color:#3b82f6"><?= t('fin.at_current_rate') ?></p>
            <p class="text-[11px] font-semibold mt-0.5">
                <?= t('fin.proj_revenue') ?>: <strong>€<?= number_format($projRevenue, 2) ?></strong>
                &nbsp;·&nbsp;
                <?= t('fin.proj_margin') ?>: <strong>€<?= number_format($projMargin, 2) ?></strong>
                <?php if ($projRevenue > 0): ?>
                    <span style="color:#94a3b8">(<?= round($projMargin / $projRevenue * 100, 1) ?>%)</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="text-right flex-shrink-0">
            <p class="text-[9px] text-zinc-400 font-semibold uppercase tracking-wider"><?= $periodDays ?> <?= t('fin.days') ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Phase 3: Margin alert ────────────────────────────────────── -->
    <?php if ($diffMarginPct !== null && $diffMarginPct < -3): ?>
    <div class="fin-alert mb-4">
        <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 text-red-500"></i>
        <div>
            <p class="text-[11px] font-black text-red-500"><?= t('fin.margin_alert') ?></p>
            <p class="text-[11px] font-semibold mt-0.5">
                <?= t('fin.margin_dropped') ?> <?= abs($diffMarginPct) ?>pp
                (<?= $prevMarginPct ?>% → <?= $marginPct ?>%) <?= t('fin.vs_prev') ?>
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Charts row 1: Daily + Type ──────────────────────────────── -->
    <div class="grid md:grid-cols-3 gap-3 mb-3">
        <div class="fin-chart-card md:col-span-2">
            <p class="fin-chart-title"><?= t('fin.daily_revenue') ?></p>
            <div id="finChartDaily"></div>
        </div>
        <div class="fin-chart-card">
            <p class="fin-chart-title"><?= t('fin.ride_split') ?></p>
            <div class="flex gap-4 text-[10px] font-bold mb-2">
                <span style="color:#3b82f6">▋ <?= t('fin.private') ?>: <?= $byType['private']['count'] ?></span>
                <span style="color:#6366f1">▋ <?= t('fin.shared') ?>: <?= $byType['shared']['count'] ?></span>
            </div>
            <div id="finChartType"></div>
        </div>
    </div>

    <!-- ── Charts row 2: Drivers + Hours ───────────────────────────── -->
    <div class="grid md:grid-cols-2 gap-3 mb-5">
        <div class="fin-chart-card">
            <p class="fin-chart-title"><?= t('fin.driver_ranking') ?></p>
            <?php if ($topDrivers !== []): ?>
                <div id="finChartDrivers"></div>
            <?php else: ?>
                <p class="text-center py-10 text-[11px] text-zinc-500"><?= t('fin.no_services') ?></p>
            <?php endif; ?>
        </div>
        <div class="fin-chart-card">
            <p class="fin-chart-title"><?= t('fin.peak_hours') ?></p>
            <div id="finChartHours"></div>
        </div>
    </div>

    <!-- ── Sub-totals tables ────────────────────────────────────────── -->
    <div class="grid md:grid-cols-2 gap-3 mb-5">
        <?php
        $subCard = static function (string $title, array $bucket): void {
            echo '<div class="fin-chart-card">'
               . '<p class="fin-chart-title">' . View::e($title) . '</p>';
            if ($bucket === []) {
                echo '<p class="text-xs text-zinc-500 py-4 text-center">—</p></div>';
                return;
            }
            echo '<div class="overflow-x-auto">'
               . '<table class="w-full fin-sub-table">'
               . '<thead><tr>'
               . '<th class="text-left"></th>'
               . '<th class="text-center">#</th>'
               . '<th class="text-right">' . t('fin.revenue') . '</th>'
               . '<th class="text-right">' . t('fin.margin') . '</th>'
               . '<th class="text-right">' . t('fin.margin_pct') . '</th>'
               . '</tr></thead><tbody>';
            foreach ($bucket as $name => $v) {
                $mp = $v['revenue'] > 0 ? round($v['margin'] / $v['revenue'] * 100, 1) : 0;
                $mc = $mp >= 0 ? 'color:#10b981' : 'color:#ef4444';
                echo '<tr>'
                   . '<td class="font-bold">' . View::e((string)$name) . '</td>'
                   . '<td class="text-center text-zinc-400">' . (int)$v['count'] . '</td>'
                   . '<td class="text-right" style="color:#10b981">€' . number_format((float)$v['revenue'], 2) . '</td>'
                   . '<td class="text-right ' . ($v['margin'] >= 0 ? '' : 'text-red-400') . '" style="' . ($v['margin'] >= 0 ? 'color:#10b981' : '') . '">€' . number_format((float)$v['margin'], 2) . '</td>'
                   . '<td class="text-right font-bold" style="' . $mc . '">' . $mp . '%</td>'
                   . '</tr>';
            }
            echo '</tbody></table></div></div>';
        };
        $subCard(t('fin.by_supplier_t'), $report['by_supplier']);
        $subCard(t('fin.by_driver_t'),   $report['by_driver']);
        ?>
    </div>

    <!-- ── Service detail table ─────────────────────────────────────── -->
    <div class="fin-chart-card mb-5 p-0 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/5">
            <p class="fin-chart-title mb-0"><?= t('fin.services') ?> · <?= (int)$tot['count'] ?></p>
            <span class="text-[10px] text-zinc-500 font-semibold">
                <?= t('fin.driver_cost') ?>: <span style="color:#0ea5e9">€<?= number_format((float)$tot['driver_cost'], 2) ?></span>
            </span>
        </div>
        <div class="overflow-x-auto" style="max-height:440px">
            <table class="w-full text-[11px]">
                <thead class="bg-white/5 text-zinc-400 sticky top-0">
                    <tr class="text-left">
                        <th class="px-3 py-2 font-black"><?= t('import.col_date') ?></th>
                        <th class="px-3 py-2 font-black"><?= t('import.col_client') ?></th>
                        <th class="px-3 py-2 font-black hidden md:table-cell"><?= t('import.col_route') ?></th>
                        <th class="px-3 py-2 font-black hidden sm:table-cell"><?= t('import.col_supplier') ?></th>
                        <th class="px-3 py-2 font-black"><?= t('fin.driver') ?></th>
                        <th class="px-3 py-2 font-black text-right" style="color:#10b981"><?= t('fin.revenue') ?></th>
                        <th class="px-3 py-2 font-black text-right hidden sm:table-cell" style="color:#0ea5e9"><?= t('fin.driver_cost') ?></th>
                        <th class="px-3 py-2 font-black text-right" style="color:#8b5cf6"><?= t('fin.margin') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report['rows'] as $r):
                        $margin = (float)$r['margin'];
                    ?>
                    <tr class="border-t border-white/5 hover:bg-white/3 transition-colors">
                        <td class="px-3 py-2 whitespace-nowrap">
                            <?= View::e((string)$r['serviceDate']) ?>
                            <span class="text-zinc-500 ml-1"><?= View::e(substr((string)$r['serviceStartTime'], 0, 5)) ?></span>
                        </td>
                        <td class="px-3 py-2 font-semibold"><?= View::e((string)($r['NomeCliente'] ?? '')) ?></td>
                        <td class="px-3 py-2 text-zinc-400 hidden md:table-cell" style="max-width:180px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            <?= View::e((string)$r['serviceStartPoint']) ?> → <?= View::e((string)$r['serviceTargetPoint']) ?>
                        </td>
                        <td class="px-3 py-2 text-zinc-400 hidden sm:table-cell"><?= View::e((string)($r['supplier'] ?? '—')) ?></td>
                        <td class="px-3 py-2"><?= View::e((string)($r['driver_name'] ?? '—')) ?></td>
                        <td class="px-3 py-2 text-right font-bold" style="color:#10b981">
                            <?= $r['total_price'] !== null ? '€' . number_format((float)$r['total_price'], 2) : '—' ?>
                        </td>
                        <td class="px-3 py-2 text-right hidden sm:table-cell" style="color:#0ea5e9">
                            <?= $r['valor_motorista'] !== null ? '€' . number_format((float)$r['valor_motorista'], 2) : '—' ?>
                        </td>
                        <td class="px-3 py-2 text-right font-bold" style="color:<?= $margin >= 0 ? '#10b981' : '#ef4444' ?>">
                            €<?= number_format($margin, 2) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ($report['rows'] === []): ?>
                        <tr><td colspan="8" class="px-3 py-8 text-center text-zinc-500 text-xs"><?= t('fin.no_services') ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ── Expenses section ─────────────────────────────────────────── -->
    <div class="grid md:grid-cols-2 gap-4 mb-8">

        <!-- Expense list -->
        <div>
            <div class="flex justify-between items-center mb-3">
                <div>
                    <h3 class="text-sm font-black uppercase tracking-widest text-zinc-400"><?= t('fin.expenses') ?></h3>
                    <p class="text-[10px] text-zinc-500 mt-0.5">Total: <span class="font-black text-red-400">€<?= number_format($totalExpenses, 2) ?></span></p>
                </div>
                <button onclick="openExpenseModal()" class="glass rounded-full px-4 py-1.5 text-xs font-bold flex items-center gap-2" style="touch-action:manipulation">
                    <i data-lucide="plus" class="w-3.5 h-3.5 text-blue-500"></i> <?= t('fin.new') ?>
                </button>
            </div>
            <div class="space-y-2">
                <?php foreach ($expenses as $expense): ?>
                <div class="glass p-3 rounded-2xl flex items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <h4 class="text-sm font-bold truncate"><?= View::e($expense->description) ?></h4>
                        <p class="text-[10px] text-zinc-500 mt-0.5">
                            <?= View::e($expense->category) ?> · <?= View::e($expense->date) ?>
                            <?php if ($expense->filePath !== null): ?>
                                · <a href="<?= View::e($expense->filePath) ?>" target="_blank" class="text-blue-400"><?= t('fin.proof') ?></a>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span class="text-sm font-black text-red-400">€<?= number_format($expense->amount, 2) ?></span>
                        <a href="/SRMT/public/admin/save-expense.php?action=delete&id=<?= (int)$expense->id ?>"
                           onclick="return confirm('Delete?')"
                           class="w-9 h-9 glass rounded-full flex items-center justify-center text-red-500/50 hover:text-red-500 transition-colors" style="touch-action:manipulation">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if ($expenses === []): ?>
                    <div class="glass p-6 rounded-2xl text-center text-zinc-500 text-xs"><?= t('fin.no_expenses') ?></div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Expense donut -->
        <?php if ($categoryLabels !== []): ?>
        <div class="fin-chart-card self-start">
            <p class="fin-chart-title"><?= t('fin.by_category') ?></p>
            <div id="finChartExpenses"></div>
        </div>
        <?php endif; ?>

    </div>

</section>

<!-- Expense modal -->
<div class="modal-overlay" id="modalOverlay" onclick="closeModal()"></div>
<div class="modal-os" id="expenseModal">
    <div class="flex justify-between items-start mb-5">
        <h3 class="text-lg font-black"><?= t('fin.new_expense') ?></h3>
        <button onclick="closeModal()" class="text-zinc-400"><i data-lucide="x-circle" class="w-5 h-5"></i></button>
    </div>
    <form action="/SRMT/public/admin/save-expense.php" method="POST" enctype="multipart/form-data" class="space-y-4">
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.category') ?></label>
            <select name="category" required class="fin-input w-full mt-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm">
                <option value=""><?= t('fin.choose') ?></option>
                <option value="Fuel"><?= t('fin.fuel') ?></option>
                <option value="Maintenance"><?= t('fin.maintenance') ?></option>
                <option value="Insurance"><?= t('fin.insurance') ?></option>
                <option value="Tolls"><?= t('fin.tolls') ?></option>
                <option value="Parking"><?= t('fin.parking') ?></option>
                <option value="Other"><?= t('fin.other') ?></option>
            </select>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.description') ?></label>
            <input type="text" name="description" required class="fin-input w-full mt-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.amount') ?></label>
                <input type="number" name="amount" step="0.01" min="0.01" required class="fin-input w-full mt-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm">
            </div>
            <div>
                <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.date') ?></label>
                <input type="date" name="date" required class="fin-input w-full mt-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm">
            </div>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.proof') ?></label>
            <input type="file" name="proof" accept="image/*,application/pdf" class="w-full mt-1 text-xs text-zinc-400">
        </div>
        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 rounded-xl py-3 font-bold text-sm text-white transition-colors"><?= t('fin.save') ?></button>
    </form>
</div>
