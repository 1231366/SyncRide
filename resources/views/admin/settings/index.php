<?php
use App\Http\View;

/**
 * @var bool   $trip_report_enabled
 * @var string $trip_report_cc
 * @var bool   $voucher_enabled
 * @var string $voucher_cc
 * @var bool   $no_show_enabled
 * @var string $no_show_cc
 * @var bool   $schedule_enabled
 * @var string $schedule_recipient
 * @var string|null $flash
 * @var string|null $error
 */

View::layout('layouts.admin', [
    'title'  => 'Settings — SyncRide OS',
    'active' => 'settings',
    'extraHead' => '
        <style>
            .settings-card {
                background: rgba(255,255,255,0.05);
                border: 1px solid rgba(255,255,255,0.08);
                border-radius: 22px; padding: 28px;
                backdrop-filter: blur(20px); margin-bottom: 16px;
            }
            .card-heading {
                display: flex; align-items: center; gap: 8px;
                font-size: 13px; font-weight: 800; color: #fff;
                letter-spacing: -.01em; margin-bottom: 4px;
            }
            .card-sub { font-size: 11px; color: #a1a1aa; font-weight: 600; margin-bottom: 20px; }

            .setting-row {
                display: flex; align-items: flex-start; justify-content: space-between;
                gap: 24px; padding: 18px 0;
                border-bottom: 1px solid rgba(255,255,255,0.06);
            }
            .setting-row:last-of-type { border-bottom: none; padding-bottom: 0; }
            .setting-row:first-of-type { padding-top: 0; }
            .setting-label { font-size: 13px; font-weight: 700; color: #e4e4e7; }
            .setting-desc  { font-size: 11px; color: #71717a; font-weight: 500; margin-top: 3px; }

            /* Toggle */
            .toggle-wrap { position: relative; width: 48px; height: 28px; flex-shrink: 0; }
            .toggle-wrap input { opacity: 0; width: 0; height: 0; position: absolute; }
            .toggle-track {
                position: absolute; inset: 0; border-radius: 999px;
                background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.12);
                cursor: pointer; transition: background .2s, border-color .2s;
            }
            .toggle-track::after {
                content: ""; position: absolute; top: 3px; left: 3px;
                width: 20px; height: 20px; border-radius: 50%;
                background: #71717a; transition: transform .2s, background .2s;
            }
            input:checked + .toggle-track { background: rgba(34,197,94,0.25); border-color: rgba(34,197,94,0.4); }
            input:checked + .toggle-track::after { transform: translateX(20px); background: #22c55e; }

            /* Tag input */
            .tag-area {
                background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
                border-radius: 12px; padding: 10px 14px; min-height: 52px;
                display: flex; flex-wrap: wrap; gap: 6px; align-items: center;
                cursor: text; transition: border-color .2s; margin-top: 14px;
            }
            .tag-area:focus-within { border-color: rgba(59,130,246,0.5); }
            .email-tag {
                display: inline-flex; align-items: center; gap: 6px;
                background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3);
                color: #93c5fd; font-size: 12px; font-weight: 600;
                padding: 3px 10px; border-radius: 999px;
            }
            .email-tag button {
                background: none; border: none; color: #93c5fd; cursor: pointer;
                padding: 0; line-height: 1; font-size: 14px; opacity: .7;
            }
            .email-tag button:hover { opacity: 1; }
            .tag-input {
                border: none; outline: none; background: transparent;
                color: #e4e4e7; font-size: 13px; min-width: 200px; flex: 1;
            }
            .tag-hint { font-size: 10px; color: #52525b; margin-top: 6px; font-weight: 500; }

            /* Save */
            .btn-save {
                background: linear-gradient(135deg, #2563eb, #1d4ed8);
                color: #fff; border: none; border-radius: 12px;
                padding: 12px 28px; font-size: 13px; font-weight: 800;
                cursor: pointer; transition: opacity .15s, transform .1s;
            }
            .btn-save:hover  { opacity: .9; }
            .btn-save:active { transform: scale(.97); }

            /* Flash */
            .flash-ok  { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.25);
                         color: #86efac; border-radius: 12px; padding: 12px 18px;
                         font-size: 12px; font-weight: 700; margin-bottom: 16px; }
            .flash-err { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.25);
                         color: #fca5a5; border-radius: 12px; padding: 12px 18px;
                         font-size: 12px; font-weight: 700; margin-bottom: 16px; }
        </style>
    ',
]);
?>

<main class="px-6 mt-8 pb-24">

    <div class="mb-6">
        <h1 class="text-[24px] font-extrabold tracking-tight">Settings</h1>
        <p class="text-[11px] text-zinc-500 font-semibold mt-1">Automation toggles and notification recipients — scoped to this account.</p>
    </div>

    <?php if ($flash): ?>
        <div class="flash-ok"><i data-lucide="check-circle" class="w-4 h-4 inline-block mr-2"></i>Settings saved.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="flash-err"><i data-lucide="alert-circle" class="w-4 h-4 inline-block mr-2"></i><?= View::e($error) ?></div>
    <?php endif; ?>

    <form action="/SRMT/public/admin/settings-save.php" method="POST" id="settingsForm">

        <!-- 1. Trip Report -->
        <div class="settings-card">
            <div class="card-heading"><i data-lucide="file-check-2" class="w-4 h-4" style="color:#a78bfa"></i> Trip Completion Report</div>
            <div class="card-sub">Email sent automatically when a driver marks a trip as completed.</div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Enable</div>
                    <div class="setting-desc">Sends a summary to the partner + all CC recipients below.</div>
                </div>
                <label class="toggle-wrap">
                    <input type="checkbox" name="trip_report_enabled" value="1" <?= $trip_report_enabled ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                </label>
            </div>
            <input type="hidden" name="trip_report_cc" id="hidden_trip_report_cc" value="<?= View::e($trip_report_cc) ?>">
            <div class="tag-area" id="area_trip_report_cc">
                <input class="tag-input" data-target="hidden_trip_report_cc" data-area="area_trip_report_cc" type="email" placeholder="cc@example.com" autocomplete="off">
            </div>
            <div class="tag-hint">Press <kbd>Enter</kbd> or <kbd>,</kbd> to add · Click × to remove</div>
        </div>

        <!-- 2. Voucher -->
        <div class="settings-card">
            <div class="card-heading"><i data-lucide="ticket" class="w-4 h-4" style="color:#34d399"></i> Voucher Confirmation</div>
            <div class="card-sub">Email sent when a driver uploads a voucher photo at pickup.</div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Enable</div>
                    <div class="setting-desc">Sends the photo + ride details to the recipients below.</div>
                </div>
                <label class="toggle-wrap">
                    <input type="checkbox" name="voucher_enabled" value="1" <?= $voucher_enabled ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                </label>
            </div>
            <input type="hidden" name="voucher_cc" id="hidden_voucher_cc" value="<?= View::e($voucher_cc) ?>">
            <div class="tag-area" id="area_voucher_cc">
                <input class="tag-input" data-target="hidden_voucher_cc" data-area="area_voucher_cc" type="email" placeholder="ops@example.com" autocomplete="off">
            </div>
            <div class="tag-hint">Press <kbd>Enter</kbd> or <kbd>,</kbd> to add · Click × to remove</div>
        </div>

        <!-- 3. No-Show -->
        <div class="settings-card">
            <div class="card-heading"><i data-lucide="user-x" class="w-4 h-4" style="color:#f87171"></i> No-Show Alert</div>
            <div class="card-sub">Email sent when a driver reports a no-show. The partner is always notified automatically for partner rides.</div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Enable</div>
                    <div class="setting-desc">Sends photo evidence + GPS location to internal recipients.</div>
                </div>
                <label class="toggle-wrap">
                    <input type="checkbox" name="no_show_enabled" value="1" <?= $no_show_enabled ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                </label>
            </div>
            <input type="hidden" name="no_show_cc" id="hidden_no_show_cc" value="<?= View::e($no_show_cc) ?>">
            <div class="tag-area" id="area_no_show_cc">
                <input class="tag-input" data-target="hidden_no_show_cc" data-area="area_no_show_cc" type="email" placeholder="alerts@example.com" autocomplete="off">
            </div>
            <div class="tag-hint">Press <kbd>Enter</kbd> or <kbd>,</kbd> to add · Click × to remove — these are your internal recipients</div>
        </div>

        <!-- 4. Schedule -->
        <div class="settings-card">
            <div class="card-heading"><i data-lucide="calendar-clock" class="w-4 h-4" style="color:#fbbf24"></i> Daily Operations Schedule</div>
            <div class="card-sub">Email sent when admin triggers the schedule dispatch — contains tomorrow's rides with driver assignments.</div>
            <div class="setting-row">
                <div>
                    <div class="setting-label">Enable</div>
                    <div class="setting-desc">Allow the schedule email to be sent from the dashboard.</div>
                </div>
                <label class="toggle-wrap">
                    <input type="checkbox" name="schedule_enabled" value="1" <?= $schedule_enabled ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                </label>
            </div>
            <input type="hidden" name="schedule_recipient" id="hidden_schedule_recipient" value="<?= View::e($schedule_recipient) ?>">
            <div class="tag-area" id="area_schedule_recipient">
                <input class="tag-input" data-target="hidden_schedule_recipient" data-area="area_schedule_recipient" type="email" placeholder="ops@example.com" autocomplete="off">
            </div>
            <div class="tag-hint">Press <kbd>Enter</kbd> or <kbd>,</kbd> to add · Click × to remove</div>
        </div>

        <button type="submit" class="btn-save">Save Settings</button>

    </form>
</main>

<script>
(function () {
    function initTagInput(areaId, hiddenId) {
        const area   = document.getElementById(areaId);
        const hidden = document.getElementById(hiddenId);
        const input  = area.querySelector('.tag-input');

        let emails = hidden.value.split(',').map(e => e.trim()).filter(Boolean);

        function render() {
            area.querySelectorAll('.email-tag').forEach(t => t.remove());
            emails.forEach((email, i) => {
                const tag = document.createElement('span');
                tag.className = 'email-tag';
                tag.innerHTML = `${email}<button type="button" data-i="${i}">&times;</button>`;
                area.insertBefore(tag, input);
            });
            hidden.value = emails.join(', ');
        }

        function addEmail(raw) {
            const e = raw.trim().replace(/,$/, '');
            if (!e) return;
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e)) { input.style.color = '#f87171'; return; }
            if (!emails.includes(e)) emails.push(e);
            input.value = ''; input.style.color = '';
            render();
        }

        input.addEventListener('keydown', ev => {
            if (ev.key === 'Enter' || ev.key === ',') { ev.preventDefault(); addEmail(input.value); }
            if (ev.key === 'Backspace' && input.value === '' && emails.length) { emails.pop(); render(); }
        });
        input.addEventListener('blur', () => { if (input.value.trim()) addEmail(input.value); });
        input.addEventListener('paste', ev => {
            ev.preventDefault();
            (ev.clipboardData || window.clipboardData).getData('text').split(/[,;\s]+/).forEach(addEmail);
        });
        area.addEventListener('click', ev => {
            if (ev.target.tagName === 'BUTTON' && ev.target.dataset.i !== undefined) {
                emails.splice(parseInt(ev.target.dataset.i, 10), 1); render();
            } else { input.focus(); }
        });

        render();
    }

    initTagInput('area_trip_report_cc',    'hidden_trip_report_cc');
    initTagInput('area_voucher_cc',        'hidden_voucher_cc');
    initTagInput('area_no_show_cc',        'hidden_no_show_cc');
    initTagInput('area_schedule_recipient','hidden_schedule_recipient');
})();
</script>
