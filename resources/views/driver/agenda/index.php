<?php
use App\Http\View;
use App\Models\Service;

/** @var string $selectedDate  @var Service[] $rides */
View::layout('layouts.driver', [
    'title'  => 'Agenda — SyncRide',
    'active' => 'agenda',
]);

$monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$d          = new DateTimeImmutable($selectedDate);
$label      = $d->format('d') . ' ' . $monthNames[(int)$d->format('n') - 1] . ', ' . $d->format('Y');
?>

<style>
    .date-selector-card {
        background-color: var(--bg-card);
        padding: 20px;
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border-color);
        margin-bottom: 25px;
        text-align: center;
    }
    .date-input-styled {
        border: 2px solid var(--border-color);
        background-color: var(--bg-body);
        color: var(--text-main);
        border-radius: 12px;
        padding: 12px 20px;
        font-size: 1.1rem;
        font-family: var(--font-display);
        font-weight: 600;
        outline: none;
        width: 100%;
        text-align: center;
        transition: border-color 0.2s;
    }
    .date-input-styled:focus { border-color: var(--primary-accent); }

    .ride-card {
        background-color: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        margin-bottom: 15px;
        padding: 16px;
        box-shadow: var(--shadow-sm);
    }
    .ride-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .ride-time   { font-family: var(--font-display); font-size: 1.25rem; font-weight: 700; color: var(--text-main); }
    .ride-badge  { font-size: 0.7rem; font-weight: 600; padding: 4px 10px; border-radius: 50px; text-transform: uppercase; }
    .badge-private { background: rgba(79,70,229,.1); color: var(--primary-accent); border: 1px solid rgba(79,70,229,.2); }
    .badge-shared  { background: rgba(245,158,11,.1); color: #d97706;             border: 1px solid rgba(245,158,11,.2); }

    .card-timeline { position: relative; padding-left: 20px; border-left: 2px dashed var(--border-color); margin-left: 6px; }
    .ct-point { position: relative; margin-bottom: 15px; }
    .ct-point:last-child { margin-bottom: 0; }
    .ct-dot { width: 12px; height: 12px; border-radius: 50%; position: absolute; left: -27px; top: 4px; border: 2px solid var(--bg-card); }
    .dot-pickup  { background-color: #10b981; }
    .dot-dropoff { background-color: #ef4444; }
    .ct-text { font-size: .95rem; color: var(--text-main); line-height: 1.3; }

    .card-footer-info { margin-top: 15px; padding-top: 12px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
</style>

<h4 class="fw-bold mb-3">Agenda</h4>

<div class="date-selector-card">
    <label class="d-block text-muted small mb-2 fw-bold text-uppercase">Select Day</label>
    <form method="GET" action="">
        <input type="date" name="date" class="date-input-styled"
               value="<?= View::e($selectedDate) ?>"
               onchange="this.form.submit()">
    </form>
    <div class="mt-2 text-muted small"><?= View::e($label) ?></div>
</div>

<?php if ($rides !== []): ?>
    <?php foreach ($rides as $ride): ?>
    <div class="ride-card">
        <div class="ride-header">
            <div class="ride-time"><?= View::e(substr($ride->startTime, 0, 5)) ?></div>
            <span class="ride-badge <?= $ride->isShared() ? 'badge-shared' : 'badge-private' ?>">
                <?= $ride->isShared() ? 'Shared' : 'Private' ?>
            </span>
        </div>

        <div class="card-timeline">
            <div class="ct-point">
                <div class="ct-dot dot-pickup"></div>
                <span class="ct-text"><?= View::e($ride->pickupAddress) ?></span>
            </div>
            <div class="ct-point">
                <div class="ct-dot dot-dropoff"></div>
                <span class="ct-text"><?= View::e($ride->dropoffAddress) ?></span>
            </div>
        </div>

        <div class="card-footer-info">
            <small class="text-muted fw-medium">
                <i class="bi bi-people-fill me-1"></i>
                <?= $ride->paxAdults ?> ADT, <?= $ride->paxChildren ?> CHD
            </small>
            <?php if ($ride->clientName !== null): ?>
            <small class="fw-bold" style="color:var(--primary-accent);"><?= View::e($ride->clientName) ?></small>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="text-center py-5 text-muted opacity-50">
        <i class="bi bi-calendar-x fs-1"></i>
        <p class="mt-3 fw-medium">Free day — no services scheduled.</p>
    </div>
<?php endif; ?>
