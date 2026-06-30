<?php
use App\Http\View;
use App\Models\Service;

/**
 * @var string              $selectedDate  'Y-m-d'
 * @var Service[]           $rides         rides for $selectedDate
 * @var array<string,int>   $ridesPerDay   date → ride count for the month
 */
View::layout('layouts.driver', [
    'title'  => 'Agenda — SyncRide',
    'active' => 'agenda',
]);

$sel       = new DateTimeImmutable($selectedDate);
$today     = date('Y-m-d');
$monthStart = $sel->modify('first day of this month');
$prevMonthDate = $sel->modify('-1 month')->format('Y-m-d');
$nextMonthDate = $sel->modify('+1 month')->format('Y-m-d');
$monthLabel    = $sel->format('F Y');
$daysInMonth   = (int) $sel->format('t');
$firstDow      = (int) $monthStart->format('N'); // 1=Mon … 7=Sun

$dayNames = ($_SESSION['admin_lang'] ?? 'en') === 'pt'
    ? ['Seg','Ter','Qua','Qui','Sex','Sáb','Dom']
    : ['Mo','Tu','We','Th','Fr','Sa','Su'];
?>
<style>
    /* ── Calendar ──────────────────────────────────────────────────────── */
    .cal-wrap {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        overflow: hidden;
        margin-bottom: 20px;
        box-shadow: var(--shadow-sm);
    }
    .cal-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 20px 12px;
        border-bottom: 1px solid var(--border-color);
    }
    .cal-month-label {
        font-family: var(--font-display); font-weight: 700;
        font-size: 1rem; color: var(--text-main);
    }
    .cal-nav {
        width: 32px; height: 32px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        border: 1px solid var(--border-color); color: var(--text-muted);
        background: transparent; text-decoration: none; font-size: .9rem;
        transition: background .15s, color .15s;
    }
    .cal-nav:hover { background: var(--bg-raised); color: var(--text-main); }

    .cal-grid {
        display: grid; grid-template-columns: repeat(7, 1fr);
        padding: 8px 10px 12px;
        gap: 2px;
    }
    .cal-dow {
        text-align: center; font-size: .65rem; font-weight: 700;
        text-transform: uppercase; letter-spacing: .5px;
        color: var(--text-faint, var(--text-muted)); padding: 6px 0;
    }
    .cal-day {
        display: flex; flex-direction: column; align-items: center;
        padding: 4px 0 5px; border-radius: 10px;
        text-decoration: none; transition: background .15s;
        min-height: 46px;
    }
    .cal-day:hover:not(.cal-empty):not(.cal-selected) { background: var(--bg-raised); }
    .cal-day-num {
        width: 30px; height: 30px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: .85rem; font-weight: 500; color: var(--text-main);
        transition: background .15s, color .15s;
    }
    .cal-day.cal-today .cal-day-num {
        border: 2px solid var(--primary-accent);
        color: var(--primary-accent); font-weight: 700;
    }
    .cal-day.cal-selected .cal-day-num {
        background: var(--primary-accent); color: #fff; font-weight: 700;
    }
    .cal-day.cal-today.cal-selected .cal-day-num {
        background: var(--primary-accent); border: none; color: #fff;
    }
    .cal-empty { cursor: default; }
    .cal-dots {
        display: flex; gap: 3px; margin-top: 3px; min-height: 6px;
        align-items: center; justify-content: center;
    }
    .cal-dot {
        width: 5px; height: 5px; border-radius: 50%;
        background: var(--primary-accent);
    }
    .cal-day.cal-selected .cal-dot { background: #fff; opacity: .8; }

    /* ── Day label ─────────────────────────────────────────────────────── */
    .day-label {
        display: flex; align-items: center; gap: 8px;
        margin-bottom: 14px;
    }
    .day-label-date {
        font-family: var(--font-display); font-size: 1.1rem;
        font-weight: 700; color: var(--text-main);
    }
    .day-label-count {
        font-size: .72rem; font-weight: 600; padding: 3px 9px;
        border-radius: 20px; background: var(--accent-soft, rgba(37,99,235,.1));
        color: var(--primary-accent);
    }

    /* ── Ride cards ────────────────────────────────────────────────────── */
    .ride-card {
        background: var(--bg-card); border: 1px solid var(--border-color);
        border-radius: var(--radius-md); margin-bottom: 12px;
        padding: 16px; box-shadow: var(--shadow-sm);
        border-left: 3px solid var(--primary-accent);
    }
    .ride-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .ride-time   { font-family: var(--font-display); font-size: 1.2rem; font-weight: 800; color: var(--text-main); }
    .ride-badge  { font-size: .68rem; font-weight: 600; padding: 3px 9px; border-radius: 20px; text-transform: uppercase; }
    .badge-private { background: rgba(37,99,235,.1); color: var(--primary-accent); border: 1px solid rgba(37,99,235,.2); }
    .badge-shared  { background: rgba(217,119,6,.1);  color: #d97706; border: 1px solid rgba(217,119,6,.2); }

    .route-block {
        background: var(--bg-raised); border-radius: 10px;
        padding: 10px 12px; margin-bottom: 10px;
    }
    .route-row { display: flex; align-items: flex-start; gap: 10px; }
    .route-row + .route-row { margin-top: 8px; padding-top: 8px; border-top: 1px dashed var(--border-color); }
    .route-icon {
        width: 22px; height: 22px; border-radius: 50%; flex-shrink: 0;
        display: flex; align-items: center; justify-content: center; font-size: .65rem; margin-top: 1px;
    }
    .ri-from { background: rgba(22,163,74,.15); color: #16a34a; }
    .ri-to   { background: rgba(220,38,38,.15); color: #dc2626; }
    .route-loc-label { font-size: .6rem; text-transform: uppercase; letter-spacing: .4px; color: var(--text-faint, var(--text-muted)); }
    .route-loc-text  { font-size: .88rem; font-weight: 600; color: var(--text-main); line-height: 1.3; }

    .ride-meta { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; }
    .ride-meta-pax { font-size: .78rem; color: var(--text-muted); }
    .ride-meta-client { font-size: .82rem; font-weight: 700; color: var(--primary-accent); }

    .empty-state { text-align: center; padding: 40px 20px; color: var(--text-muted); opacity: .6; }
    .empty-state i { font-size: 2rem; display: block; margin-bottom: 10px; }
    .empty-state p { font-size: .88rem; }
</style>

<!-- Calendar -->
<div class="cal-wrap">
    <div class="cal-header">
        <a href="?date=<?= $prevMonthDate ?>" class="cal-nav"><i class="bi bi-chevron-left"></i></a>
        <span class="cal-month-label"><?= $monthLabel ?></span>
        <a href="?date=<?= $nextMonthDate ?>" class="cal-nav"><i class="bi bi-chevron-right"></i></a>
    </div>

    <div class="cal-grid">
        <?php foreach ($dayNames as $dn): ?>
            <div class="cal-dow"><?= $dn ?></div>
        <?php endforeach; ?>

        <?php
        // Empty cells before first day (Monday-based)
        for ($e = 1; $e < $firstDow; $e++):
        ?>
            <div class="cal-day cal-empty"></div>
        <?php endfor; ?>

        <?php for ($d = 1; $d <= $daysInMonth; $d++):
            $dateStr  = $monthStart->setDate((int)$monthStart->format('Y'), (int)$monthStart->format('m'), $d)->format('Y-m-d');
            $isToday  = $dateStr === $today;
            $isSel    = $dateStr === $selectedDate;
            $count    = $ridesPerDay[$dateStr] ?? 0;
            $cls      = 'cal-day';
            if ($isToday) $cls .= ' cal-today';
            if ($isSel)   $cls .= ' cal-selected';
        ?>
            <a href="?date=<?= $dateStr ?>" class="<?= $cls ?>">
                <div class="cal-day-num"><?= $d ?></div>
                <?php if ($count > 0): ?>
                <div class="cal-dots">
                    <?php for ($dot = 0; $dot < min($count, 3); $dot++): ?>
                        <div class="cal-dot"></div>
                    <?php endfor; ?>
                </div>
                <?php else: ?>
                <div class="cal-dots"></div>
                <?php endif; ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

<!-- Rides for selected day -->
<?php
$monthNames = ($_SESSION['admin_lang'] ?? 'en') === 'pt'
    ? ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez']
    : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$labelDate  = $sel->format('d') . ' ' . $monthNames[(int)$sel->format('n') - 1];
$rideCount  = count($rides);
?>
<div class="day-label">
    <span class="day-label-date"><?= View::e($labelDate) ?></span>
    <?php if ($rideCount > 0): ?>
        <span class="day-label-count"><?= $rideCount ?> <?= $rideCount !== 1 ? t('drv.rides') : t('drv.ride') ?></span>
    <?php endif; ?>
</div>

<?php if ($rides !== []): ?>
    <?php foreach ($rides as $ride): ?>
    <div class="ride-card">
        <div class="ride-header">
            <div class="ride-time"><?= View::e(substr($ride->startTime, 0, 5)) ?></div>
            <div style="display:flex;gap:5px;align-items:center;flex-wrap:wrap">
                <?php if ($ride->companyName !== null): ?>
                <span class="ride-badge" style="background:rgba(99,102,241,.10);color:#6366f1;border:1px solid rgba(99,102,241,.2)">
                    <i class="bi bi-building" style="font-size:.65rem"></i> <?= View::e($ride->companyName) ?>
                </span>
                <?php endif; ?>
                <span class="ride-badge <?= $ride->isShared() ? 'badge-shared' : 'badge-private' ?>">
                    <?= $ride->isShared() ? t('drv.shared') : t('drv.private') ?>
                </span>
            </div>
        </div>

        <div class="route-block">
            <div class="route-row">
                <div class="route-icon ri-from"><i class="bi bi-geo-alt-fill"></i></div>
                <div>
                    <div class="route-loc-label"><?= t('drv.pickup') ?></div>
                    <div class="route-loc-text"><?= View::e($ride->pickupAddress) ?></div>
                </div>
            </div>
            <div class="route-row">
                <div class="route-icon ri-to"><i class="bi bi-flag-fill"></i></div>
                <div>
                    <div class="route-loc-label"><?= t('drv.dropoff') ?></div>
                    <div class="route-loc-text"><?= View::e($ride->dropoffAddress) ?></div>
                </div>
            </div>
        </div>

        <div class="ride-meta">
            <span class="ride-meta-pax">
                <i class="bi bi-people-fill me-1"></i><?= $ride->paxAdults ?>A
                <?php if ($ride->paxChildren > 0): ?> + <?= $ride->paxChildren ?>C<?php endif; ?>
                <?php if (($ride->paxBabies ?? 0) > 0): ?> + <?= $ride->paxBabies ?>B<?php endif; ?>
            </span>
            <?php if ($ride->clientName !== null): ?>
                <span class="ride-meta-client"><?= View::e($ride->clientName) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="empty-state">
        <i class="bi bi-sun"></i>
        <p><?= t('drv.free_day') ?></p>
    </div>
<?php endif; ?>
