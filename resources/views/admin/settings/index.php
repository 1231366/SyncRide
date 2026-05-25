<?php
use App\Http\View;

/**
 * @var bool   $trip_report_enabled
 * @var string $trip_report_cc
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
                border-radius: 22px;
                padding: 28px;
                backdrop-filter: blur(20px);
                margin-bottom: 16px;
            }
            .settings-card h2 {
                font-size: 13px; font-weight: 800; color: #fff;
                letter-spacing: -.01em; margin-bottom: 4px;
            }
            .settings-card p {
                font-size: 11px; color: #a1a1aa; font-weight: 600; margin-bottom: 20px;
            }
            .setting-row {
                display: flex; align-items: flex-start; justify-content: space-between;
                gap: 24px; padding: 18px 0;
                border-bottom: 1px solid rgba(255,255,255,0.06);
            }
            .setting-row:last-child { border-bottom: none; padding-bottom: 0; }
            .setting-row:first-child { padding-top: 0; }
            .setting-label { font-size: 13px; font-weight: 700; color: #e4e4e7; }
            .setting-desc  { font-size: 11px; color: #71717a; font-weight: 500; margin-top: 3px; }

            /* Toggle switch */
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

            /* Email tag input */
            .tag-area {
                background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1);
                border-radius: 12px; padding: 10px 14px; min-height: 52px;
                display: flex; flex-wrap: wrap; gap: 6px; align-items: center; cursor: text;
                transition: border-color .2s;
            }
            .tag-area:focus-within { border-color: rgba(59,130,246,0.5); }
            .tag {
                display: inline-flex; align-items: center; gap: 6px;
                background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3);
                color: #93c5fd; font-size: 12px; font-weight: 600;
                padding: 3px 10px; border-radius: 999px;
            }
            .tag button {
                background: none; border: none; color: #93c5fd; cursor: pointer;
                padding: 0; line-height: 1; font-size: 14px; opacity: .7;
            }
            .tag button:hover { opacity: 1; }
            .tag-input {
                border: none; outline: none; background: transparent;
                color: #e4e4e7; font-size: 13px; min-width: 180px; flex: 1;
            }
            .tag-hint { font-size: 10px; color: #52525b; margin-top: 6px; font-weight: 500; }

            /* Save button */
            .btn-save {
                background: linear-gradient(135deg, #2563eb, #1d4ed8);
                color: #fff; border: none; border-radius: 12px;
                padding: 12px 28px; font-size: 13px; font-weight: 800;
                cursor: pointer; transition: opacity .15s, transform .1s;
                letter-spacing: -.01em;
            }
            .btn-save:hover   { opacity: .9; }
            .btn-save:active  { transform: scale(.97); }
            .btn-save:disabled { opacity: .4; cursor: default; }

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
        <p class="text-[11px] text-zinc-500 font-semibold mt-1">Automations and notification preferences for this account.</p>
    </div>

    <?php if ($flash): ?>
        <div class="flash-ok"><i data-lucide="check-circle" class="w-4 h-4 inline-block mr-2"></i>Settings saved successfully.</div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="flash-err"><i data-lucide="alert-circle" class="w-4 h-4 inline-block mr-2"></i><?= View::e($error) ?></div>
    <?php endif; ?>

    <form action="/SRMT/public/admin/settings-save.php" method="POST" id="settingsForm">

        <!-- Automations card -->
        <div class="settings-card">
            <h2><i data-lucide="zap" class="w-4 h-4 inline-block mr-1" style="color:#a78bfa"></i> Automations</h2>
            <p>Control which automated actions fire at the end of a trip.</p>

            <div class="setting-row">
                <div>
                    <div class="setting-label">Trip Completion Report</div>
                    <div class="setting-desc">Sends an email summary to the partner (and CC recipients) when a trip is marked complete.</div>
                </div>
                <label class="toggle-wrap">
                    <input type="checkbox" name="trip_report_enabled" value="1" id="toggleTripReport"
                           <?= $trip_report_enabled ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                </label>
            </div>
        </div>

        <!-- Recipients card -->
        <div class="settings-card">
            <h2><i data-lucide="mail" class="w-4 h-4 inline-block mr-1" style="color:#60a5fa"></i> Email Recipients</h2>
            <p>These addresses receive a CC copy on every trip report. Separate multiple emails with Enter or comma.</p>

            <input type="hidden" name="trip_report_cc" id="ccHidden" value="<?= View::e($trip_report_cc) ?>">

            <div class="tag-area" id="tagArea">
                <!-- tags rendered by JS -->
                <input class="tag-input" id="tagInput" type="email" multiple
                       placeholder="name@company.com" autocomplete="off">
            </div>
            <div class="tag-hint">Press <kbd>Enter</kbd> or <kbd>,</kbd> to add · Click × to remove</div>
        </div>

        <button type="submit" class="btn-save" id="saveBtn">Save Settings</button>

    </form>
</main>

<script>
(function () {
    const area   = document.getElementById('tagArea');
    const input  = document.getElementById('tagInput');
    const hidden = document.getElementById('ccHidden');

    let emails = hidden.value
        .split(',')
        .map(e => e.trim())
        .filter(e => e !== '');

    function render() {
        area.querySelectorAll('.tag').forEach(t => t.remove());
        emails.forEach((email, i) => {
            const tag = document.createElement('span');
            tag.className = 'tag';
            tag.innerHTML = `${email}<button type="button" aria-label="Remove" data-i="${i}">&times;</button>`;
            area.insertBefore(tag, input);
        });
        hidden.value = emails.join(', ');
    }

    function addEmail(raw) {
        const e = raw.trim().replace(/,$/, '');
        if (!e) return;
        const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e);
        if (!valid) { input.style.color = '#f87171'; return; }
        if (!emails.includes(e)) emails.push(e);
        input.value = '';
        input.style.color = '';
        render();
    }

    input.addEventListener('keydown', ev => {
        if (ev.key === 'Enter' || ev.key === ',') {
            ev.preventDefault();
            addEmail(input.value);
        }
        if (ev.key === 'Backspace' && input.value === '' && emails.length) {
            emails.pop();
            render();
        }
    });

    input.addEventListener('blur', () => { if (input.value.trim()) addEmail(input.value); });

    area.addEventListener('click', ev => {
        if (ev.target.tagName === 'BUTTON' && ev.target.dataset.i !== undefined) {
            emails.splice(parseInt(ev.target.dataset.i, 10), 1);
            render();
        } else {
            input.focus();
        }
    });

    // Paste: split on comma/semicolon/space and add each
    input.addEventListener('paste', ev => {
        ev.preventDefault();
        const text = (ev.clipboardData || window.clipboardData).getData('text');
        text.split(/[,;\s]+/).forEach(addEmail);
    });

    render();
})();
</script>
