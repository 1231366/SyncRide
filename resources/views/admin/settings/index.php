<?php
use App\Http\View;

/**
 * @var bool        $trip_report_enabled
 * @var string      $trip_report_agency_email
 * @var string      $trip_report_cc
 * @var string      $trip_report_my_copy
 * @var bool        $voucher_enabled
 * @var string      $voucher_agency_email
 * @var string      $voucher_cc
 * @var string      $voucher_my_copy
 * @var bool        $no_show_enabled
 * @var string      $no_show_agency_email
 * @var string      $no_show_cc
 * @var string      $no_show_cc_always
 * @var string      $no_show_my_copy
 * @var bool        $schedule_enabled
 * @var string      $schedule_recipient
 * @var string      $schedule_my_copy
 * @var bool        $wpp_agenda_enabled
 * @var string      $admin_email
 * @var string|null $flash
 * @var string|null $error
 */

View::layout('layouts.admin', [
    'title'  => 'Settings — SyncRide OS',
    'active' => 'settings',
    'extraHead' => '
        <style>
            .settings-card {
                border-radius: 22px; padding: 28px;
                backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
                margin-bottom: 16px;
            }
            [data-theme="dark"]  .settings-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08); }
            [data-theme="light"] .settings-card { background: rgba(255,255,255,0.65); border: 1px solid rgba(0,0,0,0.08); }
            .card-heading { display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 800; letter-spacing: -.01em; margin-bottom: 4px; }
            .card-sub { font-size: 11px; color: #71717a; font-weight: 600; margin-bottom: 20px; }
            .setting-row {
                display: flex; align-items: flex-start; justify-content: space-between;
                gap: 24px; padding: 16px 0;
            }
            [data-theme="dark"]  .setting-row { border-bottom: 1px solid rgba(255,255,255,0.06); }
            [data-theme="light"] .setting-row { border-bottom: 1px solid rgba(0,0,0,0.06); }
            .setting-row:last-of-type { border-bottom: none; padding-bottom: 0; }
            .setting-row:first-of-type { padding-top: 0; }
            .setting-label { font-size: 13px; font-weight: 700; }
            .setting-desc  { font-size: 11px; color: #71717a; font-weight: 500; margin-top: 3px; }
            .toggle-wrap { position: relative; width: 48px; height: 28px; flex-shrink: 0; }
            .toggle-wrap input { opacity: 0; width: 0; height: 0; position: absolute; }
            .toggle-track {
                position: absolute; inset: 0; border-radius: 999px;
                cursor: pointer; transition: background .2s, border-color .2s;
            }
            [data-theme="dark"]  .toggle-track { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.12); }
            [data-theme="light"] .toggle-track { background: rgba(0,0,0,0.1); border: 1px solid rgba(0,0,0,0.12); }
            .toggle-track::after {
                content: ""; position: absolute; top: 3px; left: 3px;
                width: 20px; height: 20px; border-radius: 50%;
                background: #94a3b8; transition: transform .2s, background .2s;
            }
            input:checked + .toggle-track { background: rgba(34,197,94,0.25); border-color: rgba(34,197,94,0.4); }
            input:checked + .toggle-track::after { transform: translateX(20px); background: #22c55e; }
            .routing-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin: 18px 0 0; }
            @media (max-width: 600px) { .routing-grid { grid-template-columns: 1fr; } }
            .route-box { border-radius: 14px; padding: 16px; }
            [data-theme="dark"]  .route-box { background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.07); }
            [data-theme="light"] .route-box { background: rgba(0,0,0,0.03); border: 1px solid rgba(0,0,0,0.07); }
            .route-box-partner { background: rgba(34,197,94,0.04) !important; border-color: rgba(34,197,94,0.15) !important; }
            .route-title { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #71717a; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
            .route-title-partner { color: #22c55e; }
            .route-dot { width: 6px; height: 6px; border-radius: 50%; background: #94a3b8; flex-shrink: 0; }
            .route-dot-partner { background: #22c55e; }
            .route-auto-badge {
                display: inline-flex; align-items: center; gap: 6px;
                background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2);
                color: #16a34a; font-size: 11px; font-weight: 700; padding: 5px 12px; border-radius: 999px;
            }
            [data-theme="dark"] .route-auto-badge { color: #4ade80; }
            .email-single {
                width: 100%; border-radius: 10px; padding: 9px 13px;
                font-size: 13px; outline: none; transition: border-color .2s; font-family: inherit;
            }
            [data-theme="dark"]  .email-single { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); color: #f1f5f9; }
            [data-theme="light"] .email-single { background: rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.10); color: #0f172a; }
            .email-single:focus { border-color: #2563eb; }
            .email-single::placeholder { color: #94a3b8; }
            .section-divider { border: none; margin: 18px 0; }
            [data-theme="dark"]  .section-divider { border-top: 1px solid rgba(255,255,255,0.06); }
            [data-theme="light"] .section-divider { border-top: 1px solid rgba(0,0,0,0.06); }
            .section-label { font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; color: #94a3b8; margin-bottom: 10px; }
            .tag-area {
                border-radius: 12px; padding: 10px 14px; min-height: 48px;
                display: flex; flex-wrap: wrap; gap: 6px; align-items: center;
                cursor: text; transition: border-color .2s;
            }
            [data-theme="dark"]  .tag-area { background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.1); }
            [data-theme="light"] .tag-area { background: rgba(0,0,0,0.04); border: 1px solid rgba(0,0,0,0.10); }
            .tag-area:focus-within { border-color: #2563eb; }
            .email-tag {
                display: inline-flex; align-items: center; gap: 6px;
                background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3);
                color: #3b82f6; font-size: 12px; font-weight: 600;
                padding: 3px 10px; border-radius: 999px;
            }
            .email-tag button { background: none; border: none; color: #3b82f6; cursor: pointer; padding: 0; line-height: 1; font-size: 14px; opacity: .7; }
            .email-tag button:hover { opacity: 1; }
            .tag-input { border: none; outline: none; background: transparent; font-size: 13px; min-width: 160px; flex: 1; font-family: inherit; }
            [data-theme="dark"]  .tag-input { color: #f1f5f9; }
            [data-theme="light"] .tag-input { color: #0f172a; }
            .tag-hint { font-size: 10px; color: #94a3b8; margin-top: 6px; font-weight: 500; }
            .my-copy-row { display: flex; align-items: center; gap: 10px; margin-top: 14px; }
            .my-copy-row .email-single { flex: 1; }
            .btn-save {
                background: linear-gradient(135deg, #2563eb, #1d4ed8);
                color: #fff; border: none; border-radius: 12px;
                padding: 12px 28px; font-size: 13px; font-weight: 800;
                cursor: pointer; transition: opacity .15s, transform .1s;
            }
            .btn-save:hover  { opacity: .9; }
            .btn-save:active { transform: scale(.97); }
            .flash-ok  { background: rgba(34,197,94,.12); border: 1px solid rgba(34,197,94,.25); color: #16a34a; border-radius: 12px; padding: 12px 18px; font-size: 12px; font-weight: 700; margin-bottom: 16px; }
            .flash-err { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.25); color: #dc2626; border-radius: 12px; padding: 12px 18px; font-size: 12px; font-weight: 700; margin-bottom: 16px; }
            [data-theme="dark"] .flash-ok  { color: #86efac; }
            [data-theme="dark"] .flash-err { color: #fca5a5; }
        </style>
    ',
]);
?>

<main class="px-6 mt-8 pb-24">

    <div class="mb-6">
        <h1 class="text-[24px] font-extrabold tracking-tight"><?= t('settings.title') ?></h1>
        <p class="text-[11px] text-zinc-500 font-semibold mt-1"><?= t('settings.subtitle') ?></p>
    </div>

    <?php if ($flash): ?>
        <div class="flash-ok"><i data-lucide="check-circle" class="w-4 h-4 inline-block mr-2"></i><?= t('settings.saved') ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="flash-err"><i data-lucide="alert-circle" class="w-4 h-4 inline-block mr-2"></i><?= View::e($error) ?></div>
    <?php endif; ?>

    <form action="/SRMT/public/admin/settings-save.php" method="POST">

        <!-- 0. Appearance -->
        <div class="settings-card">
            <div class="card-heading"><i data-lucide="palette" class="w-4 h-4" style="color:#60a5fa"></i> <?= t('settings.appearance') ?></div>
            <div class="card-sub"><?= t('settings.appear_desc') ?></div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" onclick="applyTheme('light',true)" id="btnLight"
                    style="flex:1;min-width:120px;display:flex;align-items:center;justify-content:center;gap:8px;
                           padding:12px 16px;border-radius:14px;font-size:13px;font-weight:700;cursor:pointer;
                           border:2px solid transparent;transition:all .15s;"
                    class="theme-pick-btn" data-t="light">
                    <i data-lucide="sun" style="width:16px;height:16px;"></i> <?= t('settings.light') ?>
                </button>
                <button type="button" onclick="applyTheme('dark',true)" id="btnDark"
                    style="flex:1;min-width:120px;display:flex;align-items:center;justify-content:center;gap:8px;
                           padding:12px 16px;border-radius:14px;font-size:13px;font-weight:700;cursor:pointer;
                           border:2px solid transparent;transition:all .15s;"
                    class="theme-pick-btn" data-t="dark">
                    <i data-lucide="moon" style="width:16px;height:16px;"></i> <?= t('settings.dark') ?>
                </button>
            </div>
        </div>

        <!-- Language -->
        <?php $curLang = $_SESSION['admin_lang'] ?? 'en'; ?>
        <div class="settings-card">
            <div class="card-heading"><i data-lucide="languages" class="w-4 h-4" style="color:#34d399"></i> <?= t('settings.language') ?></div>
            <div class="card-sub"><?= t('settings.lang_desc') ?></div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <a href="/SRMT/public/admin/set-lang.php?lang=en"
                    style="flex:1;min-width:120px;display:flex;align-items:center;justify-content:center;gap:8px;
                           padding:12px 16px;border-radius:14px;font-size:13px;font-weight:700;cursor:pointer;
                           text-decoration:none;
                           border:2px solid <?= $curLang === 'en' ? '#2563eb' : 'transparent' ?>;
                           background:<?= $curLang === 'en' ? 'rgba(37,99,235,.10)' : 'rgba(0,0,0,0.04)' ?>;
                           color:<?= $curLang === 'en' ? '#2563eb' : '#475569' ?>;">
                    🇬🇧 <?= t('settings.lang_en') ?>
                </a>
                <a href="/SRMT/public/admin/set-lang.php?lang=pt"
                    style="flex:1;min-width:120px;display:flex;align-items:center;justify-content:center;gap:8px;
                           padding:12px 16px;border-radius:14px;font-size:13px;font-weight:700;cursor:pointer;
                           text-decoration:none;
                           border:2px solid <?= $curLang === 'pt' ? '#2563eb' : 'transparent' ?>;
                           background:<?= $curLang === 'pt' ? 'rgba(37,99,235,.10)' : 'rgba(0,0,0,0.04)' ?>;
                           color:<?= $curLang === 'pt' ? '#2563eb' : '#475569' ?>;">
                    🇵🇹 <?= t('settings.lang_pt') ?>
                </a>
            </div>
        </div>

        <!-- 1. Trip Report -->
        <div class="settings-card">
            <div class="card-heading"><i data-lucide="file-check-2" class="w-4 h-4" style="color:#a78bfa"></i> <?= t('settings.trip_report') ?></div>
            <div class="card-sub"><?= t('settings.trip_desc') ?></div>

            <div class="setting-row">
                <div>
                    <div class="setting-label"><?= t('settings.enable') ?></div>
                    <div class="setting-desc"><?= t('settings.trigger_desc') ?></div>
                </div>
                <label class="toggle-wrap">
                    <input type="checkbox" name="trip_report_enabled" value="1" <?= $trip_report_enabled ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                </label>
            </div>

            <div class="routing-grid">
                <div class="route-box">
                    <div class="route-title"><span class="route-dot"></span> <?= t('settings.normal_rides') ?></div>
                    <div class="setting-desc" style="margin-bottom:8px;"><?= t('settings.no_partner') ?></div>
                    <input type="email" name="trip_report_agency_email" class="email-single"
                           placeholder="agency@example.com"
                           value="<?= View::e($trip_report_agency_email) ?>">
                </div>
                <div class="route-box route-box-partner">
                    <div class="route-title route-title-partner"><span class="route-dot route-dot-partner"></span> <?= t('settings.partner_rides') ?></div>
                    <div class="setting-desc" style="margin-bottom:10px;"><?= t('settings.sent_to_partner') ?></div>
                    <span class="route-auto-badge"><i data-lucide="check" class="w-3 h-3"></i> <?= t('settings.auto_badge') ?></span>
                </div>
            </div>

            <hr class="section-divider">
            <div class="section-label"><?= t('settings.both_cases') ?></div>

            <div class="setting-desc" style="margin-bottom:8px;"><?= t('settings.extra_cc') ?></div>
            <input type="hidden" name="trip_report_cc" id="hidden_trip_report_cc" value="<?= View::e($trip_report_cc) ?>">
            <div class="tag-area" id="area_trip_report_cc">
                <input class="tag-input" type="email" placeholder="cc@example.com" autocomplete="off">
            </div>
            <div class="tag-hint">Enter · , · paste to add, × to remove</div>

            <div class="my-copy-row">
                <i data-lucide="copy" class="w-4 h-4" style="color:#71717a;flex-shrink:0"></i>
                <span class="setting-label" style="font-size:12px;white-space:nowrap;"><?= t('settings.copy_to_me') ?></span>
                <input type="email" name="trip_report_my_copy" class="email-single"
                       placeholder="<?= View::e($admin_email) ?>"
                       value="<?= View::e($trip_report_my_copy) ?>">
            </div>
        </div>

        <!-- 2. Voucher -->
        <div class="settings-card">
            <div class="card-heading"><i data-lucide="ticket" class="w-4 h-4" style="color:#34d399"></i> <?= t('settings.voucher') ?></div>
            <div class="card-sub"><?= t('settings.voucher_desc') ?></div>

            <div class="setting-row">
                <div>
                    <div class="setting-label"><?= t('settings.enable') ?></div>
                    <div class="setting-desc"><?= t('settings.voucher_enable') ?></div>
                </div>
                <label class="toggle-wrap">
                    <input type="checkbox" name="voucher_enabled" value="1" <?= $voucher_enabled ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                </label>
            </div>

            <div class="routing-grid">
                <div class="route-box">
                    <div class="route-title"><span class="route-dot"></span> <?= t('settings.normal_rides') ?></div>
                    <div class="setting-desc" style="margin-bottom:8px;"><?= t('settings.voucher_to') ?></div>
                    <input type="email" name="voucher_agency_email" class="email-single"
                           placeholder="agency@example.com"
                           value="<?= View::e($voucher_agency_email) ?>">
                </div>
                <div class="route-box route-box-partner">
                    <div class="route-title route-title-partner"><span class="route-dot route-dot-partner"></span> <?= t('settings.partner_rides') ?></div>
                    <div class="setting-desc" style="margin-bottom:10px;"><?= t('settings.sent_to_partner') ?></div>
                    <span class="route-auto-badge"><i data-lucide="check" class="w-3 h-3"></i> <?= t('settings.auto_badge') ?></span>
                </div>
            </div>

            <hr class="section-divider">
            <div class="section-label"><?= t('settings.both_cases') ?></div>

            <div class="setting-desc" style="margin-bottom:8px;"><?= t('settings.extra_cc') ?></div>
            <input type="hidden" name="voucher_cc" id="hidden_voucher_cc" value="<?= View::e($voucher_cc) ?>">
            <div class="tag-area" id="area_voucher_cc">
                <input class="tag-input" type="email" placeholder="cc@example.com" autocomplete="off">
            </div>
            <div class="tag-hint">Enter · , · paste to add, × to remove</div>

            <div class="my-copy-row">
                <i data-lucide="copy" class="w-4 h-4" style="color:#71717a;flex-shrink:0"></i>
                <span class="setting-label" style="font-size:12px;white-space:nowrap;"><?= t('settings.copy_to_me') ?></span>
                <input type="email" name="voucher_my_copy" class="email-single"
                       placeholder="<?= View::e($admin_email) ?>"
                       value="<?= View::e($voucher_my_copy) ?>">
            </div>
        </div>

        <!-- 3. No-Show -->
        <div class="settings-card">
            <div class="card-heading"><i data-lucide="user-x" class="w-4 h-4" style="color:#f87171"></i> <?= t('settings.noshow_alert') ?></div>
            <div class="card-sub"><?= t('settings.noshow_desc') ?></div>

            <div class="setting-row">
                <div>
                    <div class="setting-label"><?= t('settings.enable') ?></div>
                    <div class="setting-desc"><?= t('settings.noshow_enable') ?></div>
                </div>
                <label class="toggle-wrap">
                    <input type="checkbox" name="no_show_enabled" value="1" <?= $no_show_enabled ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                </label>
            </div>

            <div class="routing-grid">
                <div class="route-box">
                    <div class="route-title"><span class="route-dot"></span> <?= t('settings.normal_rides') ?></div>
                    <div class="setting-desc" style="margin-bottom:8px;"><?= t('settings.noshow_to') ?></div>
                    <input type="email" name="no_show_agency_email" class="email-single"
                           placeholder="alerts@example.com"
                           value="<?= View::e($no_show_agency_email) ?>">
                </div>
                <div class="route-box route-box-partner">
                    <div class="route-title route-title-partner"><span class="route-dot route-dot-partner"></span> <?= t('settings.partner_rides') ?></div>
                    <div class="setting-desc" style="margin-bottom:10px;"><?= t('settings.partner_always') ?></div>
                    <span class="route-auto-badge"><i data-lucide="check" class="w-3 h-3"></i> <?= t('settings.auto_badge') ?></span>
                </div>
            </div>

            <hr class="section-divider">
            <div class="section-label"><?= t('settings.both_cases') ?></div>

            <div class="setting-desc" style="margin-bottom:8px;"><?= t('settings.noshow_cc_always') ?></div>
            <input type="hidden" name="no_show_cc_always" id="hidden_no_show_cc_always" value="<?= View::e($no_show_cc_always) ?>">
            <div class="tag-area" id="area_no_show_cc_always">
                <input class="tag-input" type="email" placeholder="owner@example.com" autocomplete="off">
            </div>
            <div class="tag-hint">Enter · , · paste to add, × to remove</div>

            <hr class="section-divider">
            <div class="section-label"><?= t('settings.normal_rides') ?></div>

            <div class="setting-desc" style="margin-bottom:8px;"><?= t('settings.noshow_cc_agency') ?></div>
            <input type="hidden" name="no_show_cc" id="hidden_no_show_cc" value="<?= View::e($no_show_cc) ?>">
            <div class="tag-area" id="area_no_show_cc">
                <input class="tag-input" type="email" placeholder="internal@example.com" autocomplete="off">
            </div>
            <div class="tag-hint">Enter · , · paste to add, × to remove</div>

            <div class="my-copy-row">
                <i data-lucide="copy" class="w-4 h-4" style="color:#71717a;flex-shrink:0"></i>
                <span class="setting-label" style="font-size:12px;white-space:nowrap;"><?= t('settings.copy_to_me') ?></span>
                <input type="email" name="no_show_my_copy" class="email-single"
                       placeholder="<?= View::e($admin_email) ?>"
                       value="<?= View::e($no_show_my_copy) ?>">
            </div>
        </div>

        <!-- 4. Schedule -->
        <div class="settings-card">
            <div class="card-heading"><i data-lucide="calendar-clock" class="w-4 h-4" style="color:#fbbf24"></i> <?= t('settings.schedule') ?></div>
            <div class="card-sub"><?= t('settings.schedule_desc') ?></div>

            <div class="setting-row">
                <div>
                    <div class="setting-label"><?= t('settings.enable') ?></div>
                    <div class="setting-desc"><?= t('settings.schedule_enable') ?></div>
                </div>
                <label class="toggle-wrap">
                    <input type="checkbox" name="schedule_enabled" value="1" <?= $schedule_enabled ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                </label>
            </div>

            <hr class="section-divider">

            <div class="setting-desc" style="margin-bottom:8px;"><?= t('settings.schedule_to') ?></div>
            <input type="hidden" name="schedule_recipient" id="hidden_schedule_recipient" value="<?= View::e($schedule_recipient) ?>">
            <div class="tag-area" id="area_schedule_recipient">
                <input class="tag-input" type="email" placeholder="ops@example.com" autocomplete="off">
            </div>
            <div class="tag-hint">Enter · , · paste to add, × to remove</div>

            <div class="my-copy-row">
                <i data-lucide="copy" class="w-4 h-4" style="color:#71717a;flex-shrink:0"></i>
                <span class="setting-label" style="font-size:12px;white-space:nowrap;"><?= t('settings.copy_to_me') ?></span>
                <input type="email" name="schedule_my_copy" class="email-single"
                       placeholder="<?= View::e($admin_email) ?>"
                       value="<?= View::e($schedule_my_copy) ?>">
            </div>
        </div>

        <!-- 5. WhatsApp Agenda -->
        <div class="settings-card">
            <div class="card-heading"><i data-lucide="message-circle" class="w-4 h-4" style="color:#22c55e"></i> WhatsApp Agenda</div>
            <div class="card-sub">Envio automático da agenda de amanhã para cada condutor via WhatsApp.</div>

            <div class="setting-row">
                <div>
                    <div class="setting-label"><?= t('settings.enable') ?></div>
                    <div class="setting-desc">Ativa o envio automático da agenda via WhatsApp todos os dias.</div>
                </div>
                <label class="toggle-wrap">
                    <input type="checkbox" name="wpp_agenda_enabled" value="1" <?= $wpp_agenda_enabled ? 'checked' : '' ?>>
                    <span class="toggle-track"></span>
                </label>
            </div>
        </div>

        <button type="submit" class="btn-save"><?= t('settings.save') ?></button>

    </form>
</main>

<script>
/* Appearance card — highlight active theme button */
function highlightThemeBtns(t) {
    var isDark = t === 'dark';
    document.querySelectorAll('.theme-pick-btn').forEach(function(btn) {
        var isActive = btn.dataset.t === t;
        btn.style.background  = isActive ? 'rgba(37,99,235,' + (isDark ? '.15' : '.10') + ')' : (isDark ? 'rgba(255,255,255,0.04)' : 'rgba(0,0,0,0.04)');
        btn.style.borderColor = isActive ? '#2563eb' : (isDark ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.08)');
        btn.style.color       = isActive ? '#2563eb' : (isDark ? '#94a3b8' : '#475569');
    });
}
highlightThemeBtns(document.documentElement.dataset.theme || 'light');

/* Wrap applyTheme so buttons stay in sync when toggled from the header */
(function() {
    var orig = window.applyTheme;
    window.applyTheme = function(t, save) {
        orig(t, save);
        highlightThemeBtns(t);
    };
})();

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
    initTagInput('area_no_show_cc_always', 'hidden_no_show_cc_always');
    initTagInput('area_schedule_recipient','hidden_schedule_recipient');
})();
</script>
