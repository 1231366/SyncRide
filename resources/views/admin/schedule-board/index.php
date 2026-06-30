<?php
/** @var array<App\Models\User> $drivers */
use App\Http\View;

ob_start(); // ── head ──────────────────────────────────────────────
?>
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css' rel='stylesheet' />
<style>
/* ══════════════════════════════════════════════════════════
   SCHEDULE BOARD
══════════════════════════════════════════════════════════ */

/* ── Outer wrap ──────────────────────────────────────────── */
.sb-wrap {
    display: flex; gap: 14px;
    height: calc(100dvh - 190px);
    min-height: 500px;
    padding: 0 16px 16px;
}

/* ── Utility: glass card ─────────────────────────────────── */
.sb-glass {
    background: rgba(255,255,255,0.68);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(0,0,0,0.07);
}
[data-theme="dark"] .sb-glass {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.08);
}

/* ═══════════════════════════════════════════════════════════
   STAGED PANEL (desktop left sidebar)
═══════════════════════════════════════════════════════════ */
.sb-staged {
    width: 240px; flex-shrink: 0;
    display: flex; flex-direction: column;
    border-radius: 20px; overflow: hidden;
    background: rgba(255,255,255,0.68);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(0,0,0,0.07);
}
[data-theme="dark"] .sb-staged {
    background: rgba(255,255,255,0.04);
    border-color: rgba(255,255,255,0.08);
}
.sb-staged-hdr {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px 10px;
    font-size: 10px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase;
    border-bottom: 1px solid rgba(0,0,0,0.06);
}
[data-theme="dark"] .sb-staged-hdr { border-bottom-color: rgba(255,255,255,0.06); }
.sb-staged-count {
    min-width: 22px; height: 22px; padding: 0 7px;
    border-radius: 99px; background: #2563eb; color: #fff;
    font-size: 11px; font-weight: 800;
    display: inline-flex; align-items: center; justify-content: center;
}
.sb-staged-list {
    flex: 1; overflow-y: auto; padding: 10px;
    display: flex; flex-direction: column; gap: 8px;
    scrollbar-width: thin; scrollbar-color: rgba(0,0,0,0.1) transparent;
}
.sb-staged-list::-webkit-scrollbar { width: 3px; }
.sb-staged-list::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.12); border-radius: 99px; }

/* ── Staged trip card ────────────────────────────────────── */
.sb-card {
    border-radius: 14px; padding: 11px 13px;
    cursor: grab; user-select: none;
    background: #fff;
    border: 1px solid rgba(0,0,0,0.07);
    border-left: 3px solid #2563eb;
    transition: transform .15s, box-shadow .15s;
}
[data-theme="dark"] .sb-card {
    background: rgba(255,255,255,0.07);
    border-color: rgba(255,255,255,0.08);
    border-left-color: #3b82f6;
}
.sb-card:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(0,0,0,0.09); }
.sb-card:active { cursor: grabbing; transform: scale(1.02); }
.sb-card-time   { font-size: 10px; font-weight: 800; color: #2563eb; margin-bottom: 3px; }
[data-theme="dark"] .sb-card-time { color: #60a5fa; }
.sb-card-client { font-size: 12.5px; font-weight: 800; line-height: 1.3; margin-bottom: 4px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sb-card-route  { font-size: 10px; color: #64748b; font-weight: 600;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px; }
[data-theme="dark"] .sb-card-route { color: #94a3b8; }
.sb-card-meta   { display: flex; gap: 5px; flex-wrap: wrap; margin-top: 6px; }
.sb-tag { font-size: 9px; font-weight: 700; padding: 2px 8px; border-radius: 99px;
    background: rgba(37,99,235,.09); color: #2563eb; border: 1px solid rgba(37,99,235,.15); }
.sb-tag.flight { background: rgba(16,185,129,.08); color: #10b981; border-color: rgba(16,185,129,.15); }
.sb-tag.shared { background: rgba(139,92,246,.08); color: #8b5cf6; border-color: rgba(139,92,246,.15); }

/* ═══════════════════════════════════════════════════════════
   CALENDAR AREA
═══════════════════════════════════════════════════════════ */
.sb-calendar-wrap {
    flex: 1; min-width: 0;
    display: flex; flex-direction: column; gap: 10px;
}

/* ── Nav row ─────────────────────────────────────────────── */
.sb-nav-row {
    display: flex; align-items: center; gap: 8px; flex-shrink: 0;
    border-radius: 18px; padding: 10px 14px;
    background: rgba(255,255,255,0.68);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(0,0,0,0.07);
}
[data-theme="dark"] .sb-nav-row {
    background: rgba(255,255,255,0.05);
    border-color: rgba(255,255,255,0.08);
}
.sb-btn {
    height: 40px; border-radius: 99px; padding: 0 16px;
    font-size: 11px; font-weight: 700;
    border: 1px solid rgba(0,0,0,0.10);
    background: rgba(0,0,0,0.05); color: inherit;
    cursor: pointer; transition: all .13s;
    display: inline-flex; align-items: center; justify-content: center; gap: 5px;
    touch-action: manipulation; white-space: nowrap;
}
[data-theme="dark"] .sb-btn { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.10); }
.sb-btn:hover { background: rgba(0,0,0,0.10); }
[data-theme="dark"] .sb-btn:hover { background: rgba(255,255,255,0.12); }
.sb-btn.active { background: #2563eb; color: #fff; border-color: #2563eb; }
.sb-btn-icon { width: 40px; padding: 0; font-size: 17px; font-weight: 900; }
.sb-title { font-size: 13px; font-weight: 800; flex: 1; min-width: 0; }
.sb-view-group { display: flex; gap: 4px; flex-shrink: 0; }

/* ── Driver filter row ───────────────────────────────────── */
.sb-driver-row {
    display: flex; gap: 6px; align-items: center; flex-shrink: 0;
    overflow-x: auto; scrollbar-width: none; padding: 0 4px;
    -webkit-overflow-scrolling: touch;
}
.sb-driver-row::-webkit-scrollbar { display: none; }
.sb-driver-pill {
    display: inline-flex; align-items: center; gap: 5px;
    cursor: pointer; padding: 5px 11px 5px 8px;
    border-radius: 99px; font-size: 10px; font-weight: 700;
    border: 1.5px solid transparent; transition: opacity .15s;
    background: rgba(255,255,255,0.65); white-space: nowrap; flex-shrink: 0;
    -webkit-tap-highlight-color: transparent;
}
[data-theme="dark"] .sb-driver-pill { background: rgba(255,255,255,0.06); }
.sb-driver-pill .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.sb-driver-pill.dimmed { opacity: .28; }

/* ── FullCalendar container ──────────────────────────────── */
.sb-calendar {
    flex: 1; min-height: 0; border-radius: 18px; overflow: hidden;
    background: rgba(255,255,255,0.65);
    backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(0,0,0,0.07);
}
[data-theme="dark"] .sb-calendar { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); }
.sb-calendar .fc { height: 100%; font-family: inherit; }
.sb-calendar .fc-theme-standard td,
.sb-calendar .fc-theme-standard th,
.sb-calendar .fc-theme-standard .fc-scrollgrid { border-color: rgba(0,0,0,0.06); }
[data-theme="dark"] .sb-calendar .fc-theme-standard td,
[data-theme="dark"] .sb-calendar .fc-theme-standard th,
[data-theme="dark"] .sb-calendar .fc-theme-standard .fc-scrollgrid { border-color: rgba(255,255,255,0.06); }
.sb-calendar .fc-col-header-cell { padding: 10px 0; font-size: 11px; font-weight: 800; letter-spacing: .02em; }
.sb-calendar .fc-timegrid-slot { height: 40px; }
.sb-calendar .fc-timegrid-slot-label { font-size: 10px; font-weight: 700; color: #94a3b8; padding: 0 8px; }
.sb-calendar .fc-timegrid-slot-minor { border-top-style: none; }
.sb-calendar .fc-event { border-radius: 9px !important; font-size: 10px !important; cursor: pointer; border: none !important; box-shadow: 0 2px 8px rgba(0,0,0,0.15) !important; }
.sb-calendar .fc-event-title { font-weight: 800 !important; }
.sb-calendar .fc-toolbar { display: none; }
.sb-calendar .fc-col-header { background: rgba(0,0,0,0.02); }
[data-theme="dark"] .sb-calendar .fc-col-header { background: rgba(255,255,255,0.02); }
.sb-calendar .fc-highlight { background: rgba(37,99,235,.12) !important; border-radius: 8px; }
.sb-calendar .fc-now-indicator-line { border-color: #ef4444; }
.sb-calendar .fc-now-indicator-arrow { border-top-color: #ef4444; }

/* ═══════════════════════════════════════════════════════════
   MOBILE — float badge (unassigned count)
═══════════════════════════════════════════════════════════ */
.sb-float-btn {
    position: fixed;
    bottom: calc(88px + var(--safe-bottom, 0px));
    right: 18px;
    z-index: 900;
    background: #2563eb; color: #fff;
    border: none; border-radius: 99px;
    padding: 11px 18px 11px 14px;
    font-size: 12px; font-weight: 800;
    box-shadow: 0 8px 24px rgba(37,99,235,.5);
    cursor: pointer;
    display: none; align-items: center; gap: 8px;
    touch-action: manipulation;
    transition: transform .15s, box-shadow .15s;
}
.sb-float-btn:active { transform: scale(.95); }
.sb-float-badge-n {
    background: rgba(255,255,255,.25); color: #fff;
    border-radius: 99px; min-width: 22px; height: 22px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 900; padding: 0 6px;
}

/* ═══════════════════════════════════════════════════════════
   MOBILE — bottom sheet (staged rides)
═══════════════════════════════════════════════════════════ */
.sb-sheet-overlay {
    position: fixed; inset: 0; z-index: 3000;
    background: rgba(0,0,0,0.42); backdrop-filter: blur(5px);
    display: flex; align-items: flex-end;
    visibility: hidden; opacity: 0; transition: all .28s;
}
.sb-sheet-overlay.open { visibility: visible; opacity: 1; }
.sb-sheet {
    width: 100%; max-height: 76dvh;
    border-radius: 26px 26px 0 0;
    background: #f1f5f9;
    display: flex; flex-direction: column;
    transform: translateY(100%);
    transition: transform .35s cubic-bezier(.34,1.08,.64,1);
    overflow: hidden;
}
[data-theme="dark"] .sb-sheet { background: #0d1220; }
.sb-sheet-overlay.open .sb-sheet { transform: translateY(0); }
.sb-sheet-knob {
    width: 36px; height: 4px; border-radius: 99px;
    background: rgba(0,0,0,0.14); margin: 14px auto 0; flex-shrink: 0; cursor: pointer;
}
[data-theme="dark"] .sb-sheet-knob { background: rgba(255,255,255,0.15); }
.sb-sheet-hdr {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 20px 8px; flex-shrink: 0;
}
.sb-sheet-title { font-size: 17px; font-weight: 800; }
.sb-sheet-title span { color: #2563eb; }
.sb-sheet-x {
    width: 32px; height: 32px; border-radius: 99px;
    background: rgba(0,0,0,0.07); border: none; cursor: pointer;
    font-size: 16px; font-weight: 700; color: inherit;
    display: flex; align-items: center; justify-content: center;
}
[data-theme="dark"] .sb-sheet-x { background: rgba(255,255,255,0.08); }
.sb-sheet-list {
    flex: 1; overflow-y: auto; padding: 4px 14px 32px;
    display: flex; flex-direction: column; gap: 8px;
    -webkit-overflow-scrolling: touch;
}
/* Mobile tappable ride card in sheet */
.sb-sheet-card {
    border-radius: 16px; padding: 14px 16px;
    background: #fff;
    border: 1px solid rgba(0,0,0,0.07);
    border-left: 3px solid #2563eb;
    cursor: pointer; user-select: none;
    -webkit-tap-highlight-color: transparent;
    transition: background .1s;
    display: flex; flex-direction: column; gap: 3px;
}
[data-theme="dark"] .sb-sheet-card { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.09); border-left-color: #3b82f6; }
.sb-sheet-card:active { background: rgba(37,99,235,.06); }
.sb-sheet-card-time   { font-size: 11px; font-weight: 800; color: #2563eb; }
[data-theme="dark"] .sb-sheet-card-time { color: #60a5fa; }
.sb-sheet-card-client { font-size: 15px; font-weight: 800; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sb-sheet-card-route  { font-size: 11px; color: #64748b; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
[data-theme="dark"] .sb-sheet-card-route { color: #94a3b8; }
.sb-sheet-card-assign {
    margin-top: 8px; display: flex; align-items: center; justify-content: space-between;
}
.sb-sheet-card-assign-btn {
    font-size: 11px; font-weight: 800; color: #2563eb;
    background: rgba(37,99,235,.10); border: 1px solid rgba(37,99,235,.15);
    border-radius: 99px; padding: 5px 14px;
}

/* ═══════════════════════════════════════════════════════════
   MODALS
═══════════════════════════════════════════════════════════ */
.sb-modal-overlay {
    position: fixed; inset: 0; z-index: 4000;
    background: rgba(0,0,0,0.42); backdrop-filter: blur(6px);
    display: none; align-items: center; justify-content: center;
}
.sb-modal-overlay.open { display: flex; }
.sb-modal {
    width: 92%; max-width: 400px; border-radius: 24px; padding: 24px;
    animation: sbIn .25s cubic-bezier(.34,1.56,.64,1);
}
[data-theme="light"] .sb-modal { background: #fff; box-shadow: 0 24px 64px rgba(0,0,0,.14); border: 1px solid rgba(0,0,0,.08); }
[data-theme="dark"]  .sb-modal { background: #0f172a; border: 1px solid rgba(255,255,255,.10); }
@keyframes sbIn { from { transform: scale(.88) translateY(12px); opacity:0; } to { transform:none; opacity:1; } }
.sb-modal-title { font-size: 16px; font-weight: 800; margin-bottom: 4px; }
.sb-modal-sub   { font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 18px; }
.sb-select {
    width: 100%; border-radius: 12px; padding: 12px 14px;
    font-size: 14px; font-weight: 600;
    border: 1px solid rgba(0,0,0,.12);
    background: rgba(0,0,0,.04); color: inherit; margin-bottom: 14px;
    -webkit-appearance: none;
}
[data-theme="dark"] .sb-select { background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.12); }
.sb-modal-actions { display: flex; gap: 8px; }
.sb-modal-confirm {
    flex: 1; height: 46px; border-radius: 12px;
    background: #2563eb; color: #fff; border: none;
    font-weight: 800; font-size: 13px; cursor: pointer;
    touch-action: manipulation;
}
.sb-modal-confirm:hover { opacity: .9; }
.sb-modal-cancel {
    height: 46px; border-radius: 12px; padding: 0 18px;
    background: rgba(0,0,0,.06); border: 1px solid rgba(0,0,0,.08);
    font-weight: 700; font-size: 13px; cursor: pointer; color: inherit;
    touch-action: manipulation;
}
[data-theme="dark"] .sb-modal-cancel { background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.10); }
.sb-detail-row { display: flex; gap: 10px; margin-bottom: 9px; font-size: 12px; }
.sb-detail-label { color: #94a3b8; font-weight: 700; width: 72px; flex-shrink: 0; font-size: 11px; padding-top: 1px; }
.sb-detail-val   { font-weight: 600; flex: 1; min-width: 0; }
.sb-unassign-btn {
    display: block; width: 100%; margin-top: 10px; height: 40px;
    border-radius: 10px; border: 1px solid rgba(239,68,68,.3);
    background: rgba(239,68,68,.07); color: #ef4444;
    font-size: 12px; font-weight: 700; cursor: pointer; touch-action: manipulation;
}

/* ═══════════════════════════════════════════════════════════
   MOBILE OVERRIDES
═══════════════════════════════════════════════════════════ */
@media (max-width: 767px) {
    .sb-wrap {
        flex-direction: column;
        height: auto; min-height: unset;
        padding: 8px 12px 12px; gap: 8px;
    }
    /* Hide desktop staged panel */
    .sb-staged { display: none !important; }

    /* Nav row: 2-row on mobile (controls top, date below) */
    .sb-nav-row { padding: 8px 10px; border-radius: 16px; gap: 5px; flex-wrap: wrap; }
    .sb-btn { height: 36px; padding: 0 11px; font-size: 10.5px; }
    .sb-btn-icon { width: 36px; padding: 0; font-size: 16px; }
    /* Title: push to second full-width row */
    .sb-title {
        order: 10; flex: 0 0 100%;
        font-size: 11px; font-weight: 700; text-align: center;
        color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .sb-view-group { margin-left: auto; gap: 3px; }
    .sb-view-group .sb-btn { padding: 0 9px; font-size: 9.5px; }

    /* Driver pills: smaller */
    .sb-driver-pill { font-size: 9px; padding: 4px 9px 4px 7px; }
    .sb-driver-pill .dot { width: 7px; height: 7px; }

    /* Calendar: fill remaining screen (no page header, 2-row nav, pills, bottom nav) */
    .sb-calendar {
        border-radius: 16px;
        height: calc(100dvh - 260px) !important;
        min-height: 360px;
        flex: none;
    }
    .sb-calendar .fc-timegrid-slot { height: 46px !important; }
    .sb-calendar .fc-col-header-cell { padding: 8px 0; font-size: 10px; }
    .sb-calendar .fc-timegrid-slot-label { font-size: 9px; padding: 0 4px; }
    .sb-calendar .fc-event { border-radius: 7px !important; }

    /* Show float btn */
    .sb-float-btn { display: flex; }
}
</style>
<?php $sbHead = ob_get_clean(); ?>

<?php ob_start(); // ── scripts ───────────────────────────────────── ?>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
<script>
const DRIVERS = <?= json_encode(array_map(static fn($d) => ['id' => $d->id, 'name' => $d->name], $drivers), JSON_UNESCAPED_UNICODE) ?>;
const PALETTE  = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16','#f97316','#14b8a6'];
// Cor determinística por driver_id — TEM de ser idêntica à fórmula do servidor
// (ScheduleBoardController::events) para legenda e grelha coincidirem sempre.
const driverColor = {};
DRIVERS.forEach((d) => { driverColor[d.id] = PALETTE[d.id % PALETTE.length]; });

// Cockpit — estado operacional ao vivo (status_id → cor + etiqueta). A ordem é a
// progressão real de uma viagem: por fazer → a caminho → no local → com cliente →
// em viagem → concluída.
// Real status_id sequence: 0 → 1 → 2 → 5 → 3 → 4
const SB_STATUS = {
    0: { label: <?= json_encode(t('board.st_pending')) ?>,     color: '#94a3b8' },
    1: { label: <?= json_encode(t('board.st_on_the_way')) ?>,  color: '#3b82f6' },
    2: { label: <?= json_encode(t('board.st_at_pickup')) ?>,   color: '#06b6d4' },
    5: { label: <?= json_encode(t('board.st_with_client')) ?>, color: '#8b5cf6' },
    3: { label: <?= json_encode(t('board.st_on_trip')) ?>,     color: '#f59e0b' },
    4: { label: <?= json_encode(t('board.st_completed')) ?>,   color: '#10b981' },
};
const sbStatus = id => SB_STATUS[id] || SB_STATUS[0];

const IS_MOBILE = () => window.innerWidth < 768;

let calendar, pendingDrop = null, dimmedDrivers = new Set();
let COLOR_MODE = 'driver'; // 'driver' | 'status'

document.addEventListener('DOMContentLoaded', () => {
    initCalendar();
    loadStaged();
    buildLegend();
    buildStatusLegend();
});

/* ── Calendar ──────────────────────────────────────────────── */
function initCalendar() {
    const el = document.getElementById('sbCalendar');
    calendar = new FullCalendar.Calendar(el, {
        headerToolbar: false,
        initialView: IS_MOBILE() ? 'timeGridDay' : 'timeGridWeek',
        editable: true,
        droppable: !IS_MOBILE(),
        eventDurationEditable: false,
        allDaySlot: false,
        nowIndicator: true,
        slotMinTime: '05:00:00',
        slotMaxTime: '23:59:00',
        slotLabelInterval: '01:00',
        scrollTime: '07:00:00',
        locale: '<?= isset($_SESSION['lang']) ? htmlspecialchars($_SESSION['lang']) : 'en' ?>',
        firstDay: 1,
        height: '100%',
        dayHeaderFormat: { weekday: 'short', day: 'numeric' },
        events: fetchEvents,

        eventDrop: async function(info) {
            const e  = info.event;
            const ok = await saveUpdate(e.id, e.startStr.slice(0,10), e.startStr.slice(11,16), null);
            if (!ok) info.revert();
        },

        drop: function(info) {
            const card   = info.draggedEl;
            const date   = info.dateStr.slice(0,10);
            const time   = info.dateStr.length >= 16 ? info.dateStr.slice(11,16) : '08:00';
            openAssignModal(card.dataset.rideId, date, time, card);
        },

        eventContent: function(arg) {
            const p = arg.event.extendedProps;
            const driver = p.driver_name
                ? `<div style="margin-top:2px;font-size:9px;opacity:.85;font-weight:700;letter-spacing:.02em;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escHtml(p.driver_name)}</div>`
                : `<div style="margin-top:2px;font-size:9px;opacity:.55;font-style:italic">No driver</div>`;
            // No modo cockpit mostra-se a etiqueta de estado em vez do condutor.
            const bottom = COLOR_MODE === 'status'
                ? `<div style="margin-top:2px;font-size:9px;opacity:.9;font-weight:800;letter-spacing:.02em">● ${escHtml(sbStatus(p.status_id).label)}</div>`
                : driver;
            return { html:
                `<div style="padding:4px 7px;line-height:1.4;height:100%;display:flex;flex-direction:column">
                    <div style="font-weight:800;font-size:10.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escHtml(arg.event.title)}</div>
                    <div style="font-size:9px;opacity:.8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escHtml(p.pickup || '')}</div>
                    ${bottom}
                </div>`
            };
        },

        eventClick: function(info) { openDetailModal(info.event); },

        eventDidMount: function(info) {
            const p = info.event.extendedProps;
            // Cor consoante o modo: por condutor (default) ou por estado (cockpit).
            const color = COLOR_MODE === 'status'
                ? sbStatus(p.status_id).color
                : (p.driver_color || (p.driver_id ? driverColor[p.driver_id] : '#64748b'));
            info.el.style.backgroundColor = color;
            info.el.style.borderColor     = color;
            if (p.driver_id && dimmedDrivers.has(p.driver_id)) info.el.style.opacity = '.22';
        },
    });
    calendar.render();
    updateTitle();

    if (IS_MOBILE()) {
        document.querySelectorAll('.sb-view-btn').forEach(b =>
            b.classList.toggle('active', b.dataset.view === 'timeGridDay'));
    }
}

async function fetchEvents(info, successCb, failureCb) {
    try {
        const res = await fetch(`/SRMT/public/admin/api-schedule-board.php?start=${info.startStr.slice(0,10)}&end=${info.endStr.slice(0,10)}`);
        successCb(await res.json());
        updateTitle();
    } catch(e) { failureCb(e); }
}

function updateTitle() {
    const v      = calendar.view;
    const locale = '<?= isset($_SESSION['lang']) && $_SESSION['lang'] === 'pt' ? 'pt-PT' : 'en-GB' ?>';
    if (IS_MOBILE() && v.type === 'timeGridDay') {
        document.getElementById('sbTitle').textContent =
            v.currentStart.toLocaleDateString(locale, { weekday:'short', day:'numeric', month:'short', year:'numeric' });
    } else {
        const end = new Date(v.currentEnd - 1);
        const fmt = d => d.toLocaleDateString(locale, { day:'numeric', month:'short' });
        document.getElementById('sbTitle').textContent = fmt(v.currentStart) + ' — ' + fmt(end) + ' ' + v.currentStart.getFullYear();
    }
}

function prevWeek()  { calendar.prev();  updateTitle(); }
function nextWeek()  { calendar.next();  updateTitle(); }
function goToday()   { calendar.today(); updateTitle(); }
function setView(v)  {
    calendar.changeView(v);
    document.querySelectorAll('.sb-view-btn').forEach(b => b.classList.toggle('active', b.dataset.view === v));
    updateTitle();
}

/* ── Staged trips ──────────────────────────────────────────── */
async function loadStaged() {
    const res   = await fetch('/SRMT/public/admin/api-schedule-staged.php');
    const trips = await res.json();
    const n     = trips.length;

    document.getElementById('sbStagedCount').textContent = n;
    document.getElementById('sbFloatCount').textContent  = n;
    document.getElementById('sbSheetCount').textContent  = n;

    const floatBtn = document.getElementById('sbFloatBadge');
    floatBtn.style.display = (IS_MOBILE() && n > 0) ? 'flex' : 'none';

    const empty = `<div style="text-align:center;padding:32px 8px;font-size:11px;color:#94a3b8;font-weight:600">All assigned ✓</div>`;

    if (n === 0) {
        document.getElementById('sbStagedList').innerHTML = empty;
        document.getElementById('sbSheetList').innerHTML  = empty;
        return;
    }

    /* Desktop cards (draggable) */
    const desktopHtml = trips.map(t => `
        <div class="sb-card"
             data-ride-id="${t.id}" data-date="${t.date}" data-time="${t.time}"
             data-client="${escHtml(t.client)}"
             title="Drag to schedule">
            <div class="sb-card-time">${fmtDate(t.date)} · ${t.time}</div>
            <div class="sb-card-client">${escHtml(t.client)}</div>
            <div class="sb-card-route">↑ ${escHtml(t.pickup)}</div>
            <div class="sb-card-route">↓ ${escHtml(t.dropoff)}</div>
            <div class="sb-card-meta">
                <span class="sb-tag">${t.pax} pax</span>
                ${t.flight ? `<span class="sb-tag flight">${escHtml(t.flight)}</span>` : ''}
                ${t.type == 1 ? '<span class="sb-tag">Private</span>' : '<span class="sb-tag shared">Shared</span>'}
            </div>
        </div>`).join('');
    document.getElementById('sbStagedList').innerHTML = desktopHtml;

    /* Mobile sheet cards (tap to assign) */
    const mobileHtml = trips.map(t => `
        <div class="sb-sheet-card"
             onclick="openAssignModal('${t.id}','${t.date}','${t.time}',null)">
            <div class="sb-sheet-card-time">${fmtDate(t.date)} · ${t.time}</div>
            <div class="sb-sheet-card-client">${escHtml(t.client)}</div>
            <div class="sb-sheet-card-route">↑ ${escHtml(t.pickup)}</div>
            <div class="sb-sheet-card-route">↓ ${escHtml(t.dropoff)}</div>
            <div class="sb-sheet-card-assign">
                <div style="display:flex;gap:5px;flex-wrap:wrap">
                    <span class="sb-tag">${t.pax} pax</span>
                    ${t.flight ? `<span class="sb-tag flight">${escHtml(t.flight)}</span>` : ''}
                </div>
                <span class="sb-sheet-card-assign-btn"><?= t('board.assign_title') ?> →</span>
            </div>
        </div>`).join('');
    document.getElementById('sbSheetList').innerHTML = mobileHtml;

    /* Init drag only on desktop */
    if (!IS_MOBILE()) {
        new FullCalendar.Draggable(document.getElementById('sbStagedList'), {
            itemSelector: '.sb-card',
            eventData: el => ({ id: 'staged_' + el.dataset.rideId, title: el.dataset.client, duration: '01:00', create: false }),
        });
    }
}

/* ── Driver legend ─────────────────────────────────────────── */
function buildLegend() {
    document.getElementById('sbLegend').innerHTML = DRIVERS.map(d => `
        <span class="sb-driver-pill" data-driver-id="${d.id}" onclick="toggleDriver(${d.id})"
              style="border-color:${driverColor[d.id]}30">
            <span class="dot" style="background:${driverColor[d.id]}"></span>
            ${escHtml(d.name)}
        </span>`).join('');
}

function toggleDriver(id) {
    dimmedDrivers.has(id) ? dimmedDrivers.delete(id) : dimmedDrivers.add(id);
    document.querySelectorAll('.sb-driver-pill').forEach(p =>
        p.classList.toggle('dimmed', dimmedDrivers.has(Number(p.dataset.driverId))));
    calendar.refetchEvents();
}

/* ── Cockpit: legenda de estados + alternância de cor ──────── */
function buildStatusLegend() {
    document.getElementById('sbStatusLegend').innerHTML = Object.keys(SB_STATUS).map(k => {
        const s = SB_STATUS[k];
        return `<span class="sb-driver-pill" style="border-color:${s.color}30;cursor:default">
            <span class="dot" style="background:${s.color}"></span>${escHtml(s.label)}</span>`;
    }).join('');
}

function setColorMode(mode) {
    COLOR_MODE = (mode === 'status') ? 'status' : 'driver';
    document.querySelectorAll('.sb-colormode-btn').forEach(b =>
        b.classList.toggle('active', b.dataset.mode === COLOR_MODE));
    document.getElementById('sbLegend').style.display       = COLOR_MODE === 'driver' ? '' : 'none';
    document.getElementById('sbStatusLegend').style.display = COLOR_MODE === 'status' ? '' : 'none';
    calendar.refetchEvents(); // re-corre eventDidMount/eventContent com o novo modo
}

/* ── Bottom sheet (mobile) ─────────────────────────────────── */
function openStagedSheet()  { document.getElementById('sbSheetOverlay').classList.add('open'); }
function closeStagedSheet() { document.getElementById('sbSheetOverlay').classList.remove('open'); }

/* ── Assign modal ──────────────────────────────────────────── */
function openAssignModal(rideId, date, time, cardEl) {
    pendingDrop = { rideId, date, time, cardEl };
    const sel = document.getElementById('assignDriverSel');
    sel.innerHTML = '<option value=""><?= t('board.no_driver') ?></option>'
        + DRIVERS.map(d => `<option value="${d.id}">${escHtml(d.name)}</option>`).join('');
    document.getElementById('assignDateLabel').textContent = fmtDate(date) + ' · ' + time;
    openOverlay('sbAssignOverlay');
}

async function confirmAssign() {
    if (!pendingDrop) return;
    const { rideId, date, time, cardEl } = pendingDrop;
    const driverId = document.getElementById('assignDriverSel').value || null;
    const ok = await saveUpdate(rideId, date, time, driverId);
    if (ok) {
        closeOverlay('sbAssignOverlay');
        if (cardEl) cardEl.remove();
        closeStagedSheet();
        loadStaged();
        calendar.refetchEvents();
    }
    pendingDrop = null;
}

function cancelAssign() { pendingDrop = null; closeOverlay('sbAssignOverlay'); }

/* ── Detail modal ──────────────────────────────────────────── */
function openDetailModal(event) {
    const p = event.extendedProps;
    document.getElementById('detailClient').textContent  = event.title;
    document.getElementById('detailPickup').textContent  = p.pickup  || '—';
    document.getElementById('detailDropoff').textContent = p.dropoff || '—';
    document.getElementById('detailFlight').value        = p.flight  || '';
    document.getElementById('detailPax').textContent     = (p.pax_adt||0) + 'A ' + (p.pax_chd||0) + 'C ' + (p.pax_bby||0) + 'B';
    document.getElementById('detailTime').textContent    = event.startStr?.slice(0,16).replace('T',' ') || '';

    // Estado operacional ao vivo (badge colorido).
    const st = sbStatus(p.status_id);
    document.getElementById('detailStatus').innerHTML =
        `<span style="display:inline-flex;align-items:center;gap:5px;font-weight:800;color:${st.color}">
            <span style="width:8px;height:8px;border-radius:50%;background:${st.color}"></span>${escHtml(st.label)}</span>`;

    // Atalho para a edição completa em Viagens: abre o separador "Todas" já com a
    // pesquisa pelo nome do cliente (a pesquisa é server-side e cobre cliente/voo/grupo).
    document.getElementById('detailOpenRidesBtn').href =
        '/SRMT/public/admin/rides.php?tab=all&q=' + encodeURIComponent(p.client || '');

    const sel = document.getElementById('detailDriverSel');
    sel.innerHTML = '<option value=""><?= t('board.no_driver') ?></option>'
        + DRIVERS.map(d => `<option value="${d.id}" ${d.id == p.driver_id ? 'selected' : ''}>${escHtml(d.name)}</option>`).join('');

    document.getElementById('detailSaveBtn').onclick = async () => {
        const flight = document.getElementById('detailFlight').value.trim();
        const ok = await saveUpdate(event.id, event.startStr.slice(0,10), event.startStr.slice(11,16), sel.value || null, flight);
        if (ok) { closeOverlay('sbDetailOverlay'); calendar.refetchEvents(); }
    };

    const unassignBtn = document.getElementById('detailUnassignBtn');
    unassignBtn.style.display = p.driver_id ? 'block' : 'none';
    unassignBtn.onclick = async () => {
        const ok = await saveUpdate(event.id, event.startStr.slice(0,10), event.startStr.slice(11,16), 'unassign');
        if (ok) { closeOverlay('sbDetailOverlay'); calendar.refetchEvents(); loadStaged(); }
    };

    openOverlay('sbDetailOverlay');
}

/* ── API ───────────────────────────────────────────────────── */
async function saveUpdate(rideId, date, time, driverId, flight) {
    try {
        const body = new URLSearchParams({ ride_id: rideId, date, time });
        if (driverId !== null)        body.append('driver_id', driverId);
        if (flight !== undefined)     body.append('flight', flight);
        const res  = await fetch('/SRMT/public/admin/schedule-board-update.php', { method: 'POST', body });
        return (await res.json()).success;
    } catch { return false; }
}

/* ── Helpers ───────────────────────────────────────────────── */
function openOverlay(id)  { document.getElementById(id).classList.add('open'); }
function closeOverlay(id) { document.getElementById(id).classList.remove('open'); }
function escHtml(s) { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }
function fmtDate(s) {
    const d = new Date(s + 'T00:00:00');
    return d.toLocaleDateString('<?= isset($_SESSION['lang']) && $_SESSION['lang'] === 'pt' ? 'pt-PT' : 'en-GB' ?>', { weekday:'short', day:'numeric', month:'short' });
}
</script>
<?php $sbScripts = ob_get_clean(); ?>

<?php
View::layout('layouts.admin', [
    'title'        => t('board.title') . ' — SyncRide OS',
    'active'       => 'board',
    'extraHead'    => $sbHead,
    'extraScripts' => $sbScripts,
]);
?>

<!-- Page header (desktop only) -->
<div class="hidden md:block px-4 mt-5 mb-3">
    <h1 class="text-xl font-black"><?= t('board.title') ?></h1>
    <p class="text-[10px] text-zinc-500 font-semibold mt-0.5"><?= t('board.subtitle') ?></p>
</div>

<div class="sb-wrap">

    <!-- Desktop: staged sidebar -->
    <div class="sb-staged">
        <div class="sb-staged-hdr">
            <span><?= t('board.unassigned') ?></span>
            <span class="sb-staged-count" id="sbStagedCount">…</span>
        </div>
        <div class="sb-staged-list" id="sbStagedList">
            <div style="text-align:center;padding:32px 8px;font-size:11px;color:#94a3b8;font-weight:600"><?= t('board.loading') ?></div>
        </div>
    </div>

    <!-- Calendar area -->
    <div class="sb-calendar-wrap">

        <!-- Nav row -->
        <div class="sb-nav-row">
            <button class="sb-btn sb-btn-icon" onclick="prevWeek()">‹</button>
            <button class="sb-btn sb-btn-icon" onclick="nextWeek()">›</button>
            <button class="sb-btn" onclick="goToday()"><?= t('board.today') ?></button>
            <span class="sb-title" id="sbTitle"></span>
            <div class="sb-view-group">
                <button class="sb-btn sb-view-btn active" data-view="timeGridWeek" onclick="setView('timeGridWeek')"><?= t('board.week') ?></button>
                <button class="sb-btn sb-view-btn" data-view="timeGridDay"   onclick="setView('timeGridDay')"><?= t('board.day') ?></button>
                <button class="sb-btn sb-view-btn" data-view="dayGridMonth"  onclick="setView('dayGridMonth')"><?= t('board.month') ?></button>
            </div>
            <!-- Cockpit: alternar cor por condutor / por estado operacional -->
            <div class="sb-view-group" style="margin-left:8px">
                <span style="font-size:10px;font-weight:700;color:#94a3b8;align-self:center;padding:0 4px"><?= t('board.color_by') ?>:</span>
                <button class="sb-btn sb-colormode-btn active" data-mode="driver" onclick="setColorMode('driver')"><?= t('board.by_driver') ?></button>
                <button class="sb-btn sb-colormode-btn" data-mode="status" onclick="setColorMode('status')"><?= t('board.by_status') ?></button>
            </div>
        </div>

        <!-- Driver filter (modo condutor) / Status legend (modo cockpit) -->
        <div class="sb-driver-row" id="sbLegend"></div>
        <div class="sb-driver-row" id="sbStatusLegend" style="display:none"></div>

        <!-- FullCalendar -->
        <div class="sb-calendar" id="sbCalendar"></div>

    </div>
</div>

<!-- Mobile: floating unassigned badge -->
<button class="sb-float-btn" id="sbFloatBadge" onclick="openStagedSheet()" style="display:none">
    <i data-lucide="users" style="width:16px;height:16px;flex-shrink:0"></i>
    <span class="sb-float-badge-n" id="sbFloatCount">0</span>
    <?= t('board.unassigned') ?>
</button>

<!-- Mobile: bottom sheet with unassigned rides -->
<div class="sb-sheet-overlay" id="sbSheetOverlay" onclick="if(event.target===this)closeStagedSheet()">
    <div class="sb-sheet">
        <div class="sb-sheet-knob" onclick="closeStagedSheet()"></div>
        <div class="sb-sheet-hdr">
            <span class="sb-sheet-title"><?= t('board.unassigned') ?> <span id="sbSheetCount">0</span></span>
            <button class="sb-sheet-x" onclick="closeStagedSheet()">✕</button>
        </div>
        <div class="sb-sheet-list" id="sbSheetList"></div>
    </div>
</div>

<!-- Assign driver modal -->
<div class="sb-modal-overlay" id="sbAssignOverlay" onclick="if(event.target===this)cancelAssign()">
    <div class="sb-modal">
        <div class="sb-modal-title"><?= t('board.assign_title') ?></div>
        <div class="sb-modal-sub" id="assignDateLabel"></div>
        <select class="sb-select" id="assignDriverSel"></select>
        <div class="sb-modal-actions">
            <button class="sb-modal-cancel" onclick="cancelAssign()"><?= t('board.cancel') ?></button>
            <button class="sb-modal-confirm" onclick="confirmAssign()"><?= t('board.confirm') ?></button>
        </div>
    </div>
</div>

<!-- Event detail modal -->
<div class="sb-modal-overlay" id="sbDetailOverlay" onclick="if(event.target===this)closeOverlay('sbDetailOverlay')">
    <div class="sb-modal">
        <div class="sb-modal-title" id="detailClient"></div>
        <div class="sb-modal-sub" id="detailTime"></div>
        <div class="sb-detail-row"><span class="sb-detail-label"><?= t('board.status') ?></span><span class="sb-detail-val" id="detailStatus"></span></div>
        <div class="sb-detail-row"><span class="sb-detail-label"><?= t('board.pickup') ?></span><span class="sb-detail-val" id="detailPickup"></span></div>
        <div class="sb-detail-row"><span class="sb-detail-label"><?= t('board.dropoff') ?></span><span class="sb-detail-val" id="detailDropoff"></span></div>
        <div class="sb-detail-row"><span class="sb-detail-label"><?= t('board.pax') ?></span><span class="sb-detail-val" id="detailPax"></span></div>
        <div style="margin-top:12px;margin-bottom:6px;font-size:11px;font-weight:700;color:#94a3b8"><?= t('board.flight') ?></div>
        <input class="sb-select" id="detailFlight" placeholder="—" style="text-transform:uppercase">
        <div style="margin-top:12px;margin-bottom:6px;font-size:11px;font-weight:700;color:#94a3b8"><?= t('board.driver') ?></div>
        <select class="sb-select" id="detailDriverSel"></select>
        <div class="sb-modal-actions">
            <button class="sb-modal-cancel" onclick="closeOverlay('sbDetailOverlay')"><?= t('board.cancel') ?></button>
            <button class="sb-modal-confirm" id="detailSaveBtn"><?= t('board.save') ?></button>
        </div>
        <a class="sb-unassign-btn" id="detailOpenRidesBtn" style="display:block;text-align:center;text-decoration:none;color:#3b82f6;border-color:rgba(59,130,246,.3);background:rgba(59,130,246,.08)" href="#"><?= t('board.open_in_rides') ?></a>
        <button class="sb-unassign-btn" id="detailUnassignBtn"><?= t('board.unassign') ?></button>
    </div>
</div>
