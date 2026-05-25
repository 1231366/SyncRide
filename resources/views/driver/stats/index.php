<?php
use App\Http\View;

/**
 * @var int    $totalCount
 * @var int    $lastMonthCount
 * @var int    $yearTotal
 * @var string $bestMonth
 * @var int[]  $monthly        12-element array (0=Jan)
 * @var int[]  $availableYears
 * @var int    $selectedYear
 */
View::layout('layouts.driver', [
    'title'        => 'Stats — SyncRide',
    'active'       => 'stats',
    'extraScripts' => '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const data    = ' . json_encode(array_values($monthly)) . ';
    const labels  = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
    const years   = ' . json_encode(array_values($availableYears)) . ';
    const selYear = ' . $selectedYear . ';

    const sel = document.getElementById("year-selector");
    years.forEach(y => {
        const o = new Option(y, y);
        if (y == selYear) o.selected = true;
        sel.add(o);
    });
    sel.addEventListener("change", () => {
        const u = new URL(window.location);
        u.searchParams.set("year", sel.value);
        window.location.href = u.toString();
    });

    const ctx  = document.getElementById("monthlyChart").getContext("2d");
    const grad = ctx.createLinearGradient(0, 0, 0, 300);
    grad.addColorStop(0, "rgba(79,70,229,.5)");
    grad.addColorStop(1, "rgba(79,70,229,0)");

    new Chart(ctx, {
        type: "bar",
        data: {
            labels,
            datasets: [{ label: "Rides", data, backgroundColor: grad, borderColor: "#4f46e5", borderWidth: 2, borderRadius: 6, maxBarThickness: 30 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, grid: { color: "rgba(200,200,200,.1)" } },
                x: { grid: { display: false } }
            },
            plugins: { legend: { display: false } }
        }
    });
})();
</script>',
]);
?>

<style>
    .stat-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        padding: 15px;
        display: flex; align-items: center; gap: 15px;
        box-shadow: var(--shadow-sm);
        height: 100%;
    }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
    .bg-indigo-soft { background: rgba(79,70,229,.1); color: var(--primary-accent); }
    .bg-emerald-soft { background: rgba(16,185,129,.1); color: #10b981; }
    .bg-amber-soft   { background: rgba(245,158,11,.1);  color: #f59e0b; }
    .bg-pink-soft    { background: rgba(236,72,153,.1);  color: #ec4899; }
    .stat-info h3 { font-family: var(--font-display); font-weight: 700; font-size: 1.4rem; margin: 0; line-height: 1; color: var(--text-main); }
    .stat-info p  { margin: 3px 0 0 0; font-size: .75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .5px; }

    .chart-card { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px; box-shadow: var(--shadow-sm); margin-bottom: 20px; }
    .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
    .chart-title  { font-family: var(--font-display); font-weight: 700; font-size: 1.1rem; color: var(--text-main); margin: 0; }
    .year-select  { border: 1px solid var(--border-color); background-color: var(--bg-body); color: var(--text-main); border-radius: 8px; padding: 4px 10px; font-size: .9rem; outline: none; font-weight: 600; }
</style>

<h4 class="fw-bold mb-3">Performance</h4>

<div class="row g-3 mb-4">
    <div class="col-6">
        <div class="stat-card">
            <div class="stat-icon bg-indigo-soft"><i class="bi bi-archive"></i></div>
            <div class="stat-info"><h3><?= $totalCount ?></h3><p>All Time</p></div>
        </div>
    </div>
    <div class="col-6">
        <div class="stat-card">
            <div class="stat-icon bg-emerald-soft"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-info"><h3><?= $lastMonthCount ?></h3><p>Last 30 Days</p></div>
        </div>
    </div>
    <div class="col-6">
        <div class="stat-card">
            <div class="stat-icon bg-amber-soft"><i class="bi bi-calendar3"></i></div>
            <div class="stat-info"><h3><?= $yearTotal ?></h3><p>Year <?= $selectedYear ?></p></div>
        </div>
    </div>
    <div class="col-6">
        <div class="stat-card">
            <div class="stat-icon bg-pink-soft"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-info"><h3><?= View::e($bestMonth) ?></h3><p>Best Month</p></div>
        </div>
    </div>
</div>

<div class="chart-card">
    <div class="chart-header">
        <h5 class="chart-title">Monthly Evolution</h5>
        <select id="year-selector" class="year-select"></select>
    </div>
    <div style="height:300px;position:relative;">
        <canvas id="monthlyChart"></canvas>
    </div>
</div>
