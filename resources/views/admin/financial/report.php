<?php
/** @var string $from @var string $to @var ?string $supplier @var string $driverName */
/** @var array{rows:array<array<string,mixed>>,totals:array<string,mixed>,by_supplier:array<string,array<string,mixed>>,by_driver:array<string,array<string,mixed>>} $report */
/** @var float $totalExpenses @var float $netProfit @var array<App\Models\Expense> $expenses */
/** @var array<string> $categoryLabels @var array<float> $categoryValues */
/** @var array<string,array<string,mixed>> $byDay @var array<int,int> $byHour */
/** @var array{private:array{count:int,revenue:float},shared:array{count:int,revenue:float}} $byType */
/** @var array{count:int,revenue:float,driver_cost:float,margin:float} $prevTotals */
/** @var int $periodDays @var float $marginPct @var float $netMarginPct @var float $avgTicket @var float $avgPerDay */

use App\Http\View;

$tot = $report['totals'];
$topDrivers = array_slice($report['by_driver'], 0, 15, true);
$genDate = date('Y-m-d H:i');
$filterSup = ($supplier !== null && $supplier !== '') ? $supplier : t('fin.all_suppliers');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= t('fin.report_title') ?> – SyncRide OS</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');

  @page { size: A4 portrait; margin: 14mm 12mm; }

  * { box-sizing: border-box; }

  body {
    font-family: 'Inter', system-ui, sans-serif;
    font-size: 11px;
    color: #0f172a;
    background: #fff;
    margin: 0;
    padding: 0;
  }

  /* layout helpers */
  .rpt-page  { max-width: 780px; margin: 0 auto; padding: 24px 20px; }
  .rpt-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .rpt-grid3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
  .rpt-grid5 { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; }

  /* header strip */
  .rpt-header { display: flex; justify-content: space-between; align-items: flex-start;
                padding-bottom: 14px; border-bottom: 2px solid #0f172a; margin-bottom: 18px; }
  .rpt-logo   { font-size: 18px; font-weight: 900; letter-spacing: -.04em; }
  .rpt-logo span { color: #10b981; }
  .rpt-meta   { text-align: right; font-size: 9px; color: #64748b; font-weight: 600; line-height: 1.6; }

  /* KPI card */
  .rpt-kpi { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
             padding: 10px 12px; }
  .rpt-kpi-label { font-size: 8px; font-weight: 800; text-transform: uppercase;
                   letter-spacing: .1em; color: #94a3b8; margin-bottom: 3px; }
  .rpt-kpi-value { font-size: 18px; font-weight: 900; line-height: 1.1; }
  .rpt-kpi-sub   { font-size: 9px; color: #64748b; font-weight: 600; margin-top: 2px; }
  .c-green  { color: #10b981; }
  .c-violet { color: #7c3aed; }
  .c-blue   { color: #2563eb; }
  .c-amber  { color: #d97706; }

  /* section */
  .rpt-sec       { margin-bottom: 18px; }
  .rpt-sec-title { font-size: 9px; font-weight: 800; text-transform: uppercase;
                   letter-spacing: .1em; color: #64748b; margin-bottom: 8px;
                   padding-bottom: 4px; border-bottom: 1px solid #e2e8f0; }
  /* chart card */
  .rpt-chart { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; }
  .rpt-chart-title { font-size: 8px; font-weight: 800; text-transform: uppercase;
                     letter-spacing: .1em; color: #94a3b8; margin-bottom: 8px; }

  /* table */
  .rpt-table { width: 100%; border-collapse: collapse; }
  .rpt-table th { font-size: 8px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em;
                  color: #64748b; padding: 4px 6px; background: #f1f5f9; }
  .rpt-table td { padding: 5px 6px; border-bottom: 1px solid #f1f5f9; font-size: 10px; }
  .rpt-table tr:last-child td { border-bottom: none; }
  .rpt-table .tfoot td { font-weight: 900; background: #f8fafc; border-top: 2px solid #e2e8f0; }
  .text-right { text-align: right; }
  .text-center { text-align: center; }

  /* print directives */
  @media print {
    body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    .no-print { display: none !important; }
    .rpt-page  { padding: 0; max-width: 100%; }
    .page-break-before { break-before: page; }
    .page-break-after  { break-after:  page; }
    .avoid-break { break-inside: avoid; }
  }

  /* print btn (hidden on print) */
  .rpt-print-btn {
    position: fixed; bottom: 24px; right: 24px;
    background: #0f172a; color: #fff; border: none;
    padding: 12px 22px; border-radius: 50px;
    font-size: 13px; font-weight: 800; cursor: pointer;
    box-shadow: 0 8px 24px rgba(0,0,0,.25);
    display: flex; align-items: center; gap: 8px;
  }
</style>
</head>
<body>

<div class="rpt-page">

    <!-- ── Header ──────────────────────────────────────────────────── -->
    <div class="rpt-header">
        <div>
            <p class="rpt-logo">Sync<span>Ride</span> OS</p>
            <p style="font-size:10px;font-weight:700;color:#64748b;margin-top:2px"><?= t('fin.report_title') ?></p>
        </div>
        <div class="rpt-meta">
            <div><?= t('fin.period') ?>: <strong><?= View::e($from) ?> → <?= View::e($to) ?></strong></div>
            <div><?= t('fin.supplier') ?>: <?= View::e($filterSup) ?></div>
            <div><?= t('fin.driver') ?>: <?= View::e($driverName) ?></div>
            <div><?= t('fin.report_gen') ?>: <?= View::e($genDate) ?></div>
            <div><?= t('fin.period_days') ?>: <?= $periodDays ?> <?= t('fin.days') ?></div>
        </div>
    </div>

    <!-- ── KPI summary ─────────────────────────────────────────────── -->
    <div class="rpt-sec">
        <p class="rpt-sec-title"><?= t('fin.summary') ?></p>
        <div class="rpt-grid5 avoid-break">
            <div class="rpt-kpi">
                <p class="rpt-kpi-label"><?= t('fin.revenue') ?></p>
                <p class="rpt-kpi-value c-green">€<?= number_format((float)$tot['revenue'], 2) ?></p>
                <p class="rpt-kpi-sub">€<?= number_format($avgPerDay, 0) ?>/<?= t('fin.day_abbr') ?></p>
            </div>
            <div class="rpt-kpi">
                <p class="rpt-kpi-label"><?= t('fin.margin') ?></p>
                <p class="rpt-kpi-value c-violet">€<?= number_format((float)$tot['margin'], 2) ?></p>
                <p class="rpt-kpi-sub"><?= $marginPct ?>%</p>
            </div>
            <div class="rpt-kpi">
                <p class="rpt-kpi-label"><?= t('fin.driver_cost') ?></p>
                <p class="rpt-kpi-value c-blue">€<?= number_format((float)$tot['driver_cost'], 2) ?></p>
                <p class="rpt-kpi-sub"><?= (int)$tot['count'] ?> <?= t('fin.services') ?></p>
            </div>
            <div class="rpt-kpi">
                <p class="rpt-kpi-label"><?= t('fin.expenses') ?></p>
                <p class="rpt-kpi-value" style="color:#ef4444">€<?= number_format($totalExpenses, 2) ?></p>
                <p class="rpt-kpi-sub"><?= count($expenses) ?> <?= t('fin.entries') ?></p>
            </div>
            <div class="rpt-kpi">
                <p class="rpt-kpi-label"><?= t('fin.net') ?></p>
                <p class="rpt-kpi-value <?= $netProfit >= 0 ? 'c-blue' : '' ?>" style="<?= $netProfit < 0 ? 'color:#ef4444' : '' ?>">€<?= number_format($netProfit, 2) ?></p>
                <p class="rpt-kpi-sub"><?= $netMarginPct ?>% <?= t('fin.of_revenue') ?></p>
            </div>
        </div>

        <div class="rpt-grid3 avoid-break" style="margin-top:10px">
            <div class="rpt-kpi" style="text-align:center">
                <p class="rpt-kpi-label"><?= t('fin.avg_ticket') ?></p>
                <p class="rpt-kpi-value" style="font-size:15px">€<?= number_format($avgTicket, 2) ?></p>
            </div>
            <div class="rpt-kpi" style="text-align:center">
                <p class="rpt-kpi-label"><?= t('fin.private') ?></p>
                <p class="rpt-kpi-value c-blue" style="font-size:15px">
                    €<?= number_format($byType['private']['revenue'], 2) ?>
                    <span style="font-size:11px;font-weight:700;color:#94a3b8"> (<?= $byType['private']['count'] ?>)</span>
                </p>
            </div>
            <div class="rpt-kpi" style="text-align:center">
                <p class="rpt-kpi-label"><?= t('fin.shared') ?></p>
                <p class="rpt-kpi-value c-violet" style="font-size:15px">
                    €<?= number_format($byType['shared']['revenue'], 2) ?>
                    <span style="font-size:11px;font-weight:700;color:#94a3b8"> (<?= $byType['shared']['count'] ?>)</span>
                </p>
            </div>
        </div>
    </div>

    <!-- ── Charts ──────────────────────────────────────────────────── -->
    <div class="rpt-sec avoid-break">
        <p class="rpt-sec-title"><?= t('fin.insights') ?></p>
        <div class="rpt-grid2">
            <div class="rpt-chart">
                <p class="rpt-chart-title"><?= t('fin.daily_revenue') ?></p>
                <div id="rptDaily"></div>
            </div>
            <div class="rpt-chart">
                <p class="rpt-chart-title"><?= t('fin.peak_hours') ?></p>
                <div id="rptHours"></div>
            </div>
        </div>
    </div>

    <!-- ── By supplier ─────────────────────────────────────────────── -->
    <?php if ($report['by_supplier'] !== []): ?>
    <div class="rpt-sec avoid-break">
        <p class="rpt-sec-title"><?= t('fin.by_supplier_t') ?></p>
        <table class="rpt-table">
            <thead><tr>
                <th class="text-left"><?= t('fin.supplier') ?></th>
                <th class="text-center">#</th>
                <th class="text-right"><?= t('fin.revenue') ?></th>
                <th class="text-right"><?= t('fin.driver_cost') ?></th>
                <th class="text-right"><?= t('fin.margin') ?></th>
                <th class="text-right"><?= t('fin.margin_pct') ?></th>
            </tr></thead>
            <tbody>
                <?php foreach ($report['by_supplier'] as $name => $v):
                    $mp = $v['revenue'] > 0 ? round($v['margin'] / $v['revenue'] * 100, 1) : 0; ?>
                <tr>
                    <td style="font-weight:700"><?= View::e((string)$name) ?></td>
                    <td class="text-center" style="color:#64748b"><?= (int)$v['count'] ?></td>
                    <td class="text-right c-green">€<?= number_format((float)$v['revenue'], 2) ?></td>
                    <td class="text-right c-blue">€<?= number_format((float)$v['driver_cost'], 2) ?></td>
                    <td class="text-right c-violet">€<?= number_format((float)$v['margin'], 2) ?></td>
                    <td class="text-right" style="font-weight:800;color:<?= $mp >= 0 ? '#10b981' : '#ef4444' ?>"><?= $mp ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="tfoot">
                <td colspan="2" style="font-size:9px;text-transform:uppercase;letter-spacing:.07em">Total</td>
                <td class="text-right c-green">€<?= number_format((float)$tot['revenue'], 2) ?></td>
                <td class="text-right c-blue">€<?= number_format((float)$tot['driver_cost'], 2) ?></td>
                <td class="text-right c-violet">€<?= number_format((float)$tot['margin'], 2) ?></td>
                <td class="text-right c-violet"><?= $marginPct ?>%</td>
            </tr></tfoot>
        </table>
    </div>
    <?php endif; ?>

    <!-- ── By driver ───────────────────────────────────────────────── -->
    <?php if ($topDrivers !== []): ?>
    <div class="rpt-sec avoid-break">
        <p class="rpt-sec-title"><?= t('fin.driver_ranking') ?></p>
        <table class="rpt-table">
            <thead><tr>
                <th class="text-left">#</th>
                <th class="text-left"><?= t('fin.driver') ?></th>
                <th class="text-center"><?= t('fin.services') ?></th>
                <th class="text-right"><?= t('fin.revenue') ?></th>
                <th class="text-right"><?= t('fin.driver_cost') ?></th>
                <th class="text-right"><?= t('fin.margin') ?></th>
                <th class="text-right"><?= t('fin.margin_pct') ?></th>
            </tr></thead>
            <tbody>
                <?php $rank = 1; foreach ($topDrivers as $name => $v):
                    $mp = $v['revenue'] > 0 ? round($v['margin'] / $v['revenue'] * 100, 1) : 0; ?>
                <tr>
                    <td style="color:#94a3b8;font-weight:800"><?= $rank++ ?></td>
                    <td style="font-weight:700"><?= View::e((string)$name) ?></td>
                    <td class="text-center" style="color:#64748b"><?= (int)$v['count'] ?></td>
                    <td class="text-right c-green">€<?= number_format((float)$v['revenue'], 2) ?></td>
                    <td class="text-right c-blue">€<?= number_format((float)$v['driver_cost'], 2) ?></td>
                    <td class="text-right c-violet">€<?= number_format((float)$v['margin'], 2) ?></td>
                    <td class="text-right" style="font-weight:800;color:<?= $mp >= 0 ? '#10b981' : '#ef4444' ?>"><?= $mp ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- ── Daily breakdown table ───────────────────────────────────── -->
    <?php if ($byDay !== []): ?>
    <div class="rpt-sec page-break-before">
        <p class="rpt-sec-title"><?= t('fin.daily_revenue') ?></p>
        <table class="rpt-table">
            <thead><tr>
                <th class="text-left"><?= t('import.col_date') ?></th>
                <th class="text-center">#</th>
                <th class="text-right"><?= t('fin.revenue') ?></th>
                <th class="text-right"><?= t('fin.driver_cost') ?></th>
                <th class="text-right"><?= t('fin.margin') ?></th>
                <th class="text-right"><?= t('fin.margin_pct') ?></th>
            </tr></thead>
            <tbody>
                <?php foreach ($byDay as $d => $v):
                    $mp = $v['revenue'] > 0 ? round($v['margin'] / $v['revenue'] * 100, 1) : 0; ?>
                <tr>
                    <td style="font-weight:700"><?= View::e($d) ?></td>
                    <td class="text-center" style="color:#64748b"><?= (int)$v['count'] ?></td>
                    <td class="text-right c-green">€<?= number_format((float)$v['revenue'], 2) ?></td>
                    <td class="text-right c-blue">€<?= number_format((float)$v['driver_cost'], 2) ?></td>
                    <td class="text-right c-violet">€<?= number_format((float)$v['margin'], 2) ?></td>
                    <td class="text-right" style="font-weight:800;color:<?= $mp >= 0 ? '#10b981' : '#ef4444' ?>"><?= $mp ?>%</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="tfoot">
                <td colspan="2" style="font-size:9px;text-transform:uppercase;letter-spacing:.07em">Total</td>
                <td class="text-right c-green">€<?= number_format((float)$tot['revenue'], 2) ?></td>
                <td class="text-right c-blue">€<?= number_format((float)$tot['driver_cost'], 2) ?></td>
                <td class="text-right c-violet">€<?= number_format((float)$tot['margin'], 2) ?></td>
                <td class="text-right c-violet"><?= $marginPct ?>%</td>
            </tr></tfoot>
        </table>
    </div>
    <?php endif; ?>

    <!-- ── Service detail table ─────────────────────────────────────── -->
    <?php if ($report['rows'] !== []): ?>
    <div class="rpt-sec page-break-before">
        <p class="rpt-sec-title"><?= t('fin.services') ?> (<?= (int)$tot['count'] ?>)</p>
        <table class="rpt-table">
            <thead><tr>
                <th class="text-left"><?= t('import.col_date') ?></th>
                <th class="text-left"><?= t('import.col_client') ?></th>
                <th class="text-left"><?= t('fin.driver') ?></th>
                <th class="text-left"><?= t('fin.type') ?></th>
                <th class="text-right"><?= t('fin.revenue') ?></th>
                <th class="text-right"><?= t('fin.driver_cost') ?></th>
                <th class="text-right"><?= t('fin.margin') ?></th>
            </tr></thead>
            <tbody>
                <?php foreach ($report['rows'] as $r):
                    $margin = (float)$r['margin'];
                    $type = ((int)$r['serviceType']) === 1 ? t('fin.private') : t('fin.shared'); ?>
                <tr>
                    <td style="white-space:nowrap"><?= View::e((string)$r['serviceDate']) ?> <span style="color:#94a3b8"><?= View::e(substr((string)$r['serviceStartTime'], 0, 5)) ?></span></td>
                    <td style="font-weight:600"><?= View::e((string)($r['NomeCliente'] ?? '')) ?></td>
                    <td style="color:#64748b"><?= View::e((string)($r['driver_name'] ?? '—')) ?></td>
                    <td style="color:#64748b"><?= View::e($type) ?></td>
                    <td class="text-right c-green"><?= $r['total_price'] !== null ? '€' . number_format((float)$r['total_price'], 2) : '—' ?></td>
                    <td class="text-right c-blue"><?= $r['valor_motorista'] !== null ? '€' . number_format((float)$r['valor_motorista'], 2) : '—' ?></td>
                    <td class="text-right" style="font-weight:800;color:<?= $margin >= 0 ? '#10b981' : '#ef4444' ?>">€<?= number_format($margin, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="tfoot">
                <td colspan="4" style="font-size:9px;text-transform:uppercase;letter-spacing:.07em">Total</td>
                <td class="text-right c-green">€<?= number_format((float)$tot['revenue'], 2) ?></td>
                <td class="text-right c-blue">€<?= number_format((float)$tot['driver_cost'], 2) ?></td>
                <td class="text-right c-violet">€<?= number_format((float)$tot['margin'], 2) ?></td>
            </tr></tfoot>
        </table>
    </div>
    <?php endif; ?>

    <!-- ── Expenses ─────────────────────────────────────────────────── -->
    <?php if ($expenses !== []): ?>
    <div class="rpt-sec avoid-break">
        <p class="rpt-sec-title"><?= t('fin.expenses') ?> (€<?= number_format($totalExpenses, 2) ?>)</p>
        <table class="rpt-table">
            <thead><tr>
                <th class="text-left"><?= t('fin.date') ?></th>
                <th class="text-left"><?= t('fin.category') ?></th>
                <th class="text-left"><?= t('fin.description') ?></th>
                <th class="text-right"><?= t('fin.amount') ?></th>
            </tr></thead>
            <tbody>
                <?php foreach ($expenses as $e): ?>
                <tr>
                    <td><?= View::e($e->date) ?></td>
                    <td style="color:#64748b"><?= View::e($e->category) ?></td>
                    <td><?= View::e($e->description) ?></td>
                    <td class="text-right" style="color:#ef4444;font-weight:700">€<?= number_format($e->amount, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot><tr class="tfoot">
                <td colspan="3" style="font-size:9px;text-transform:uppercase;letter-spacing:.07em">Total <?= t('fin.expenses') ?></td>
                <td class="text-right" style="color:#ef4444">€<?= number_format($totalExpenses, 2) ?></td>
            </tr></tfoot>
        </table>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div style="text-align:center;padding-top:14px;border-top:1px solid #e2e8f0;color:#94a3b8;font-size:9px;font-weight:600">
        SyncRide OS · <?= t('fin.report_gen') ?> <?= View::e($genDate) ?>
    </div>

</div><!-- /rpt-page -->

<!-- Print button (hidden when printing) -->
<button class="rpt-print-btn no-print" onclick="window.print()">
    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
    PDF / Print
</button>

<script>
(function () {
    const tc = '#64748b';
    const gc = 'rgba(0,0,0,0.07)';

    const dayDates   = <?= json_encode(array_keys($byDay), JSON_UNESCAPED_UNICODE) ?>;
    const dayRevs    = <?= json_encode(array_values(array_map(fn($v) => round($v['revenue'], 2), $byDay))) ?>;
    const dayMargins = <?= json_encode(array_values(array_map(fn($v) => round($v['margin'],  2), $byDay))) ?>;
    const hourData   = <?= json_encode(array_values($byHour)) ?>;
    const hourCats   = Array.from({length: 24}, (_, i) => i + 'h');

    const charts = [];

    if (dayDates.length > 0) {
        charts.push(new ApexCharts(document.querySelector('#rptDaily'), {
            chart: { type: 'area', height: 160, background: 'transparent', toolbar: { show: false }, animations: { enabled: false } },
            series: [
                { name: '<?= t('fin.revenue') ?>', data: dayRevs    },
                { name: '<?= t('fin.margin') ?>',  data: dayMargins },
            ],
            xaxis: { categories: dayDates, labels: { style: { colors: tc, fontSize: '8px' }, rotate: dayDates.length > 14 ? -45 : 0 }, tickAmount: Math.min(8, dayDates.length) },
            yaxis: { labels: { style: { colors: tc, fontSize: '8px' }, formatter: v => '€' + v.toFixed(0) } },
            colors: ['#10b981', '#7c3aed'],
            fill:   { type: 'gradient', gradient: { shade: 'light', type: 'vertical', opacityFrom: 0.25, opacityTo: 0.0 } },
            stroke: { curve: 'smooth', width: [2, 1.5], dashArray: [0, 4] },
            dataLabels: { enabled: false },
            grid: { borderColor: gc, strokeDashArray: 3 },
            legend: { labels: { colors: tc }, fontSize: '9px', position: 'top' },
            tooltip: { enabled: false },
        }));
    }

    charts.push(new ApexCharts(document.querySelector('#rptHours'), {
        chart: { type: 'bar', height: 160, background: 'transparent', toolbar: { show: false }, animations: { enabled: false } },
        plotOptions: { bar: { borderRadius: 3, columnWidth: '70%' } },
        series: [{ name: '<?= t('fin.services') ?>', data: hourData }],
        xaxis: { categories: hourCats, labels: { style: { colors: tc, fontSize: '7px' } } },
        yaxis: { labels: { style: { colors: tc, fontSize: '8px' } } },
        colors: ['#f59e0b'],
        grid: { borderColor: gc, strokeDashArray: 3 },
        dataLabels: { enabled: false },
        tooltip: { enabled: false },
    }));

    Promise.all(charts.map(c => c.render())).then(() => {
        setTimeout(() => window.print(), 800);
    });
})();
</script>
</body>
</html>
