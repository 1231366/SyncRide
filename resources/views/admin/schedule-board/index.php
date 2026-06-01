<?php
/** @var array<App\Models\User> $drivers */
use App\Http\View;

ob_start();
?>
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css' rel='stylesheet' />
<style>
/* ── Layout ─────────────────────────────────────────────────── */
.sb-wrap {
    display: flex; gap: 14px; height: calc(100vh - 200px); min-height: 500px;
    padding: 0 16px 16px;
}
/* ── Staged panel ───────────────────────────────────────────── */
.sb-staged {
    width: 200px; flex-shrink: 0;
    display: flex; flex-direction: column; gap: 10px;
}
.sb-staged-header {
    display: flex; align-items: center; justify-content: space-between;
    font-size: 10px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase;
    padding: 10px 12px; border-radius: 16px;
    background: rgba(255,255,255,0.6); backdrop-filter: blur(20px);
    border: 1px solid rgba(0,0,0,0.07);
}
[data-theme="dark"] .sb-staged-header {
    background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.08);
}
.sb-staged-count {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 20px; height: 20px; padding: 0 6px; border-radius: 999px;
    background: #2563eb; color: #fff; font-size: 10px; font-weight: 800;
}
.sb-staged-list {
    flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;
    padding-right: 2px;
}
.sb-staged-list::-webkit-scrollbar { width: 3px; }
.sb-staged-list::-webkit-scrollbar-track { background: transparent; }
.sb-staged-list::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.15); border-radius: 99px; }
/* ── Trip card (staged) ─────────────────────────────────────── */
.sb-card {
    border-radius: 14px; padding: 10px 12px; cursor: grab;
    background: rgba(255,255,255,0.7); backdrop-filter: blur(20px);
    border: 1px solid rgba(0,0,0,0.08);
    transition: transform .15s, box-shadow .15s;
    user-select: none;
}
[data-theme="dark"] .sb-card {
    background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.10);
}
.sb-card:active { cursor: grabbing; transform: scale(1.03); box-shadow: 0 12px 32px rgba(0,0,0,0.18); }
.sb-card-date  { font-size: 9px; font-weight: 800; color: #94a3b8; letter-spacing: .05em; text-transform: uppercase; margin-bottom: 3px; }
.sb-card-client { font-size: 12px; font-weight: 800; line-height: 1.3; margin-bottom: 5px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sb-card-route  { font-size: 10px; color: #64748b; font-weight: 600; margin-bottom: 4px;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sb-card-meta   { display: flex; gap: 6px; flex-wrap: wrap; }
.sb-tag { font-size: 9px; font-weight: 700; padding: 2px 7px; border-radius: 99px;
    background: rgba(37,99,235,.10); color: #2563eb; border: 1px solid rgba(37,99,235,.15); }
.sb-tag.flight { background: rgba(16,185,129,.08); color: #10b981; border-color: rgba(16,185,129,.15); }
/* ── Calendar panel ─────────────────────────────────────────── */
.sb-calendar-wrap {
    flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 10px;
}
.sb-toolbar {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    padding: 10px 14px; border-radius: 16px;
    background: rgba(255,255,255,0.6); backdrop-filter: blur(20px);
    border: 1px solid rgba(0,0,0,0.07);
}
[data-theme="dark"] .sb-toolbar { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.08); }
.sb-title { font-size: 13px; font-weight: 800; flex: 1; }
.sb-btn {
    height: 32px; border-radius: 99px; padding: 0 14px;
    font-size: 11px; font-weight: 700; border: 1px solid rgba(0,0,0,0.10);
    background: rgba(0,0,0,0.05); color: inherit; cursor: pointer;
    transition: all .15s; display: inline-flex; align-items: center; gap: 6px;
}
[data-theme="dark"] .sb-btn { background: rgba(255,255,255,0.07); border-color: rgba(255,255,255,0.10); }
.sb-btn:hover { background: rgba(0,0,0,0.10); }
.sb-btn.active { background: #2563eb; color: #fff; border-color: #2563eb; }
.sb-btn-icon { width: 32px; padding: 0; justify-content: center; }
/* Driver legend pills */
.sb-legend { display: flex; gap: 6px; flex-wrap: wrap; align-items: center; }
.sb-driver-pill {
    display: inline-flex; align-items: center; gap: 5px; cursor: pointer;
    padding: 3px 10px 3px 7px; border-radius: 99px; font-size: 10px; font-weight: 700;
    border: 1.5px solid transparent; transition: opacity .15s;
    background: rgba(0,0,0,0.04);
}
.sb-driver-pill .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.sb-driver-pill.dimmed { opacity: .3; }
/* ── FullCalendar overrides ──────────────────────────────────── */
.sb-calendar {
    flex: 1; min-height: 0; border-radius: 16px; overflow: hidden;
    background: rgba(255,255,255,0.6); backdrop-filter: blur(20px);
    border: 1px solid rgba(0,0,0,0.07);
}
[data-theme="dark"] .sb-calendar { background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.08); }
.sb-calendar .fc { height: 100%; font-family: inherit; }
.sb-calendar .fc-theme-standard td, .sb-calendar .fc-theme-standard th,
.sb-calendar .fc-theme-standard .fc-scrollgrid { border-color: rgba(0,0,0,0.06); }
[data-theme="dark"] .sb-calendar .fc-theme-standard td,
[data-theme="dark"] .sb-calendar .fc-theme-standard th,
[data-theme="dark"] .sb-calendar .fc-theme-standard .fc-scrollgrid { border-color: rgba(255,255,255,0.06); }
.sb-calendar .fc-col-header-cell { padding: 8px 0; font-size: 11px; font-weight: 800; }
.sb-calendar .fc-timegrid-slot { height: 36px; }
.sb-calendar .fc-timegrid-slot-label { font-size: 10px; font-weight: 700; color: #94a3b8; }
.sb-calendar .fc-event { border-radius: 8px !important; font-size: 10px !important; font-weight: 700 !important; cursor: pointer; }
.sb-calendar .fc-event-title { font-weight: 700 !important; }
.sb-calendar .fc-toolbar { display: none; }
.sb-calendar .fc-daygrid-day-number { font-size: 11px; font-weight: 700; }
/* Highlight drop target */
.sb-calendar .fc-highlight { background: rgba(37,99,235,.12) !important; border-radius: 8px; }
/* ── Modals ──────────────────────────────────────────────────── */
.sb-modal-overlay {
    position: fixed; inset: 0; z-index: 3999;
    background: rgba(0,0,0,0.4); backdrop-filter: blur(4px);
    display: none; align-items: center; justify-content: center;
}
.sb-modal-overlay.open { display: flex; }
.sb-modal {
    width: 90%; max-width: 400px; border-radius: 24px; padding: 24px;
    animation: sbModalIn .25s cubic-bezier(.34,1.56,.64,1);
}
[data-theme="light"] .sb-modal { background: #fff; box-shadow: 0 24px 64px rgba(0,0,0,.14); border: 1px solid rgba(0,0,0,.08); }
[data-theme="dark"]  .sb-modal { background: #0f172a; border: 1px solid rgba(255,255,255,.10); }
@keyframes sbModalIn { from { transform: scale(.88) translateY(10px); opacity: 0; } to { transform: none; opacity: 1; } }
.sb-modal-title { font-size: 15px; font-weight: 800; margin-bottom: 4px; }
.sb-modal-sub   { font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 16px; }
.sb-select {
    width: 100%; border-radius: 12px; padding: 10px 14px;
    font-size: 13px; font-weight: 600; border: 1px solid rgba(0,0,0,.12);
    background: rgba(0,0,0,.04); color: inherit; margin-bottom: 14px;
}
[data-theme="dark"] .sb-select { background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.12); }
.sb-modal-actions { display: flex; gap: 8px; }
.sb-modal-confirm {
    flex: 1; height: 44px; border-radius: 12px;
    background: #2563eb; color: #fff; border: none; font-weight: 700; font-size: 13px; cursor: pointer;
    transition: opacity .15s;
}
.sb-modal-confirm:hover { opacity: .9; }
.sb-modal-cancel {
    height: 44px; border-radius: 12px; padding: 0 18px;
    background: rgba(0,0,0,.06); border: 1px solid rgba(0,0,0,.08);
    font-weight: 700; font-size: 13px; cursor: pointer; color: inherit;
}
[data-theme="dark"] .sb-modal-cancel { background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.10); }
/* Detail modal extras */
.sb-detail-row { display: flex; gap: 8px; margin-bottom: 8px; font-size: 12px; }
.sb-detail-label { color: #94a3b8; font-weight: 700; width: 68px; flex-shrink: 0; font-size: 11px; }
.sb-detail-val { font-weight: 600; flex: 1; }
.sb-unassign-btn {
    display: block; width: 100%; margin-top: 8px; height: 36px;
    border-radius: 10px; border: 1px solid rgba(239,68,68,.3);
    background: rgba(239,68,68,.06); color: #ef4444;
    font-size: 12px; font-weight: 700; cursor: pointer;
}
</style>
<?php $sbHead = ob_get_clean(); ?>

<?php ob_start(); ?>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
<script>
const DRIVERS = <?= json_encode(array_map(static fn($d) => ['id' => $d->id, 'name' => $d->name], $drivers), JSON_UNESCAPED_UNICODE) ?>;
const PALETTE = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#84cc16','#f97316','#14b8a6'];
const driverColor = {};
DRIVERS.forEach((d, i) => { driverColor[d.id] = PALETTE[i % PALETTE.length]; });

let calendar, pendingDrop = null, dimmedDrivers = new Set();

document.addEventListener('DOMContentLoaded', () => {
    initCalendar();
    loadStaged();
    buildLegend();
});

// ── Calendar ──────────────────────────────────────────────────
function initCalendar() {
    const el = document.getElementById('sbCalendar');
    calendar = new FullCalendar.Calendar(el, {
        plugins: [],
        headerToolbar: false,
        initialView: 'timeGridWeek',
        editable: true,
        droppable: true,
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

        // Drag existing event to new slot → reschedule
        eventDrop: async function(info) {
            const e    = info.event;
            const date = e.startStr.slice(0,10);
            const time = e.startStr.slice(11,16);
            const ok   = await saveUpdate(e.id, date, time, null);
            if (!ok) info.revert();
        },

        // External card dropped → ask driver
        drop: function(info) {
            const card   = info.draggedEl;
            const rideId = card.dataset.rideId;
            const date   = info.dateStr.slice(0,10);
            const time   = info.dateStr.length >= 16 ? info.dateStr.slice(11,16) : '08:00';
            openAssignModal(rideId, date, time, card);
        },

        eventContent: function(arg) {
            const p = arg.event.extendedProps;
            const driverLabel = p.driver_name
                ? `<div style="opacity:.8;margin-top:2px;font-size:9px">👤 ${p.driver_name}</div>`
                : `<div style="opacity:.6;margin-top:2px;font-size:9px">No driver</div>`;
            return { html:
                `<div style="padding:3px 6px;line-height:1.4">
                    <div style="font-weight:800;font-size:10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${arg.event.title}</div>
                    <div style="opacity:.85;font-size:9px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        📍 ${p.pickup || ''}
                    </div>
                    ${driverLabel}
                </div>`
            };
        },

        eventClick: function(info) { openDetailModal(info.event); },

        // Dim events for non-selected drivers
        eventDidMount: function(info) {
            const dId = info.event.extendedProps.driver_id;
            if (dId && dimmedDrivers.has(dId)) {
                info.el.style.opacity = '.25';
            }
        },
    });
    calendar.render();
    updateTitle();
}

async function fetchEvents(info, successCb, failureCb) {
    try {
        const res = await fetch(`/SRMT/public/admin/api-schedule-board.php?start=${info.startStr.slice(0,10)}&end=${info.endStr.slice(0,10)}`);
        successCb(await res.json());
        updateTitle();
    } catch(e) { failureCb(e); }
}

function updateTitle() {
    const view = calendar.view;
    const start = view.currentStart;
    const end   = new Date(view.currentEnd - 1);
    const fmt   = d => d.toLocaleDateString('<?= isset($_SESSION['lang']) && $_SESSION['lang'] === 'pt' ? 'pt-PT' : 'en-GB' ?>', { day:'numeric', month:'short' });
    document.getElementById('sbTitle').textContent = fmt(start) + ' — ' + fmt(end) + ' ' + start.getFullYear();
}

function prevWeek()  { calendar.prev();  updateTitle(); }
function nextWeek()  { calendar.next();  updateTitle(); }
function goToday()   { calendar.today(); updateTitle(); }
function setView(v)  {
    calendar.changeView(v);
    document.querySelectorAll('.sb-view-btn').forEach(b => b.classList.toggle('active', b.dataset.view === v));
    updateTitle();
}

// ── Staged trips ──────────────────────────────────────────────
async function loadStaged() {
    const res   = await fetch('/SRMT/public/admin/api-schedule-staged.php');
    const trips = await res.json();
    document.getElementById('sbStagedCount').textContent = trips.length;

    const list = document.getElementById('sbStagedList');
    if (trips.length === 0) {
        list.innerHTML = '<div style="text-align:center;padding:24px 8px;font-size:11px;color:#94a3b8;font-weight:600">All assigned ✓</div>';
        return;
    }

    list.innerHTML = trips.map(t => `
        <div class="sb-card"
             data-ride-id="${t.id}"
             data-date="${t.date}"
             data-time="${t.time}"
             data-client="${escHtml(t.client)}"
             data-pickup="${escHtml(t.pickup)}"
             data-dropoff="${escHtml(t.dropoff)}"
             title="Drag to calendar to schedule">
            <div class="sb-card-date">${fmtDate(t.date)} · ${t.time}</div>
            <div class="sb-card-client">${escHtml(t.client)}</div>
            <div class="sb-card-route">📍 ${escHtml(t.pickup)}</div>
            <div class="sb-card-route" style="color:#94a3b8">🏁 ${escHtml(t.dropoff)}</div>
            <div class="sb-card-meta">
                <span class="sb-tag">${t.pax} pax</span>
                ${t.flight ? `<span class="sb-tag flight">✈ ${escHtml(t.flight)}</span>` : ''}
                ${t.type == 1 ? '<span class="sb-tag">Private</span>' : '<span class="sb-tag">Shared</span>'}
            </div>
        </div>
    `).join('');

    // Make staged cards draggable onto FullCalendar
    new FullCalendar.Draggable(list, {
        itemSelector: '.sb-card',
        eventData: el => ({
            id:       'staged_' + el.dataset.rideId,
            title:    el.dataset.client,
            duration: '01:00',
            create:   false,
        }),
    });
}

// ── Driver legend ─────────────────────────────────────────────
function buildLegend() {
    const wrap = document.getElementById('sbLegend');
    wrap.innerHTML = DRIVERS.map(d => `
        <span class="sb-driver-pill" data-driver-id="${d.id}" onclick="toggleDriver(${d.id})" style="border-color:${driverColor[d.id]}20">
            <span class="dot" style="background:${driverColor[d.id]}"></span>
            ${escHtml(d.name)}
        </span>
    `).join('');
}

function toggleDriver(id) {
    if (dimmedDrivers.has(id)) dimmedDrivers.delete(id);
    else dimmedDrivers.add(id);
    document.querySelectorAll('.sb-driver-pill').forEach(p => {
        p.classList.toggle('dimmed', dimmedDrivers.has(Number(p.dataset.driverId)));
    });
    calendar.refetchEvents();
}

// ── Assign modal (after dropping from staged) ─────────────────
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
        cardEl?.remove();
        loadStaged();
        calendar.refetchEvents();
    }
    pendingDrop = null;
}

function cancelAssign() {
    pendingDrop = null;
    closeOverlay('sbAssignOverlay');
}

// ── Detail modal (click existing event) ──────────────────────
function openDetailModal(event) {
    const p = event.extendedProps;
    document.getElementById('detailClient').textContent  = event.title;
    document.getElementById('detailPickup').textContent  = p.pickup  || '—';
    document.getElementById('detailDropoff').textContent = p.dropoff || '—';
    document.getElementById('detailFlight').textContent  = p.flight  || '—';
    document.getElementById('detailPax').textContent     = (p.pax_adt||0) + 'A ' + (p.pax_chd||0) + 'C ' + (p.pax_bby||0) + 'B';
    document.getElementById('detailTime').textContent    = event.startStr?.slice(0,16).replace('T',' ') || '';

    const sel = document.getElementById('detailDriverSel');
    sel.innerHTML = '<option value=""><?= t('board.no_driver') ?></option>'
        + DRIVERS.map(d => `<option value="${d.id}" ${d.id == p.driver_id ? 'selected' : ''}>${escHtml(d.name)}</option>`).join('');

    document.getElementById('detailSaveBtn').onclick = async () => {
        const driverId = sel.value || (p.driver_id ? 'unassign' : null);
        const date = event.startStr.slice(0,10);
        const time = event.startStr.slice(11,16);
        const ok = await saveUpdate(event.id, date, time, driverId);
        if (ok) { closeOverlay('sbDetailOverlay'); calendar.refetchEvents(); }
    };

    document.getElementById('detailUnassignBtn').style.display = p.driver_id ? 'block' : 'none';
    document.getElementById('detailUnassignBtn').onclick = async () => {
        const ok = await saveUpdate(event.id, event.startStr.slice(0,10), event.startStr.slice(11,16), 'unassign');
        if (ok) { closeOverlay('sbDetailOverlay'); calendar.refetchEvents(); loadStaged(); }
    };

    openOverlay('sbDetailOverlay');
}

// ── API save ──────────────────────────────────────────────────
async function saveUpdate(rideId, date, time, driverId) {
    try {
        const body = new URLSearchParams({ ride_id: rideId, date, time });
        if (driverId !== null) body.append('driver_id', driverId);
        const res  = await fetch('/SRMT/public/admin/schedule-board-update.php', { method: 'POST', body });
        const data = await res.json();
        if (!data.success) console.error(data.error);
        return data.success;
    } catch(e) { return false; }
}

// ── Helpers ───────────────────────────────────────────────────
function openOverlay(id)  { document.getElementById(id).classList.add('open'); }
function closeOverlay(id) { document.getElementById(id).classList.remove('open'); }
function escHtml(s)  { const d = document.createElement('div'); d.textContent = s||''; return d.innerHTML; }
function fmtDate(s)  {
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

<!-- Toolbar -->
<div class="px-4 mt-5 mb-3 flex items-center gap-3 flex-wrap">
    <div>
        <h1 class="text-xl font-black"><?= t('board.title') ?></h1>
        <p class="text-[10px] text-zinc-500 font-semibold mt-0.5"><?= t('board.subtitle') ?></p>
    </div>
</div>

<div class="sb-wrap">
    <!-- Staged panel -->
    <div class="sb-staged">
        <div class="sb-staged-header">
            <span><?= t('board.unassigned') ?></span>
            <span class="sb-staged-count" id="sbStagedCount">…</span>
        </div>
        <div class="sb-staged-list" id="sbStagedList">
            <div style="text-align:center;padding:24px 8px;font-size:11px;color:#94a3b8;font-weight:600"><?= t('board.loading') ?></div>
        </div>
    </div>

    <!-- Calendar -->
    <div class="sb-calendar-wrap">
        <!-- Toolbar -->
        <div class="sb-toolbar">
            <button class="sb-btn sb-btn-icon" onclick="prevWeek()">‹</button>
            <button class="sb-btn sb-btn-icon" onclick="nextWeek()">›</button>
            <button class="sb-btn" onclick="goToday()"><?= t('board.today') ?></button>
            <span class="sb-title" id="sbTitle"></span>
            <div class="sb-legend" id="sbLegend"></div>
            <div style="display:flex;gap:6px;margin-left:auto">
                <button class="sb-btn sb-view-btn active" data-view="timeGridWeek" onclick="setView('timeGridWeek')"><?= t('board.week') ?></button>
                <button class="sb-btn sb-view-btn" data-view="timeGridDay" onclick="setView('timeGridDay')"><?= t('board.day') ?></button>
                <button class="sb-btn sb-view-btn" data-view="dayGridMonth" onclick="setView('dayGridMonth')"><?= t('board.month') ?></button>
            </div>
        </div>
        <!-- FullCalendar -->
        <div class="sb-calendar" id="sbCalendar"></div>
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
        <div class="sb-detail-row"><span class="sb-detail-label"><?= t('board.pickup') ?></span><span class="sb-detail-val" id="detailPickup"></span></div>
        <div class="sb-detail-row"><span class="sb-detail-label"><?= t('board.dropoff') ?></span><span class="sb-detail-val" id="detailDropoff"></span></div>
        <div class="sb-detail-row"><span class="sb-detail-label"><?= t('board.flight') ?></span><span class="sb-detail-val" id="detailFlight"></span></div>
        <div class="sb-detail-row"><span class="sb-detail-label"><?= t('board.pax') ?></span><span class="sb-detail-val" id="detailPax"></span></div>
        <div style="margin-top:12px;margin-bottom:6px;font-size:11px;font-weight:700;color:#94a3b8"><?= t('board.driver') ?></div>
        <select class="sb-select" id="detailDriverSel"></select>
        <div class="sb-modal-actions">
            <button class="sb-modal-cancel" onclick="closeOverlay('sbDetailOverlay')"><?= t('board.cancel') ?></button>
            <button class="sb-modal-confirm" id="detailSaveBtn"><?= t('board.save') ?></button>
        </div>
        <button class="sb-unassign-btn" id="detailUnassignBtn"><?= t('board.unassign') ?></button>
    </div>
</div>
