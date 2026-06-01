<?php
/**
 * Self-contained "change password" modal + trigger, shared by every role layout.
 * Open it from anywhere with: openChangePassword()
 * Relies only on vanilla JS and the global CSRF fetch interceptor in each layout.
 */
?>
<div id="cpwOverlay" style="position:fixed;inset:0;z-index:5000;background:rgba(0,0,0,.5);backdrop-filter:blur(4px);display:none;align-items:center;justify-content:center;padding:20px;">
    <div style="width:100%;max-width:380px;background:#0f172a;border:1px solid rgba(255,255,255,.12);border-radius:22px;padding:26px;font-family:'Plus Jakarta Sans',system-ui,sans-serif;color:#f1f5f9;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
            <h3 style="font-size:16px;font-weight:800;margin:0;"><?= t('pwd.title') ?></h3>
            <button onclick="closeChangePassword()" style="background:none;border:none;color:#94a3b8;font-size:22px;cursor:pointer;line-height:1;">&times;</button>
        </div>
        <form id="cpwForm" onsubmit="submitChangePassword(event)">
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:5px;"><?= t('pwd.current') ?></label>
                <input type="password" id="cpwCurrent" required style="width:100%;height:44px;border-radius:12px;padding:0 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);color:#f1f5f9;font-size:14px;font-family:inherit;">
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:5px;"><?= t('pwd.new') ?></label>
                <input type="password" id="cpwNew" required minlength="6" style="width:100%;height:44px;border-radius:12px;padding:0 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);color:#f1f5f9;font-size:14px;font-family:inherit;">
            </div>
            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:5px;"><?= t('pwd.confirm') ?></label>
                <input type="password" id="cpwConfirm" required minlength="6" style="width:100%;height:44px;border-radius:12px;padding:0 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);color:#f1f5f9;font-size:14px;font-family:inherit;">
            </div>
            <div id="cpwMsg" style="display:none;font-size:12px;font-weight:600;margin-bottom:12px;"></div>
            <div style="display:flex;gap:8px;">
                <button type="button" onclick="closeChangePassword()" style="flex:1;height:44px;border-radius:12px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.10);color:#f1f5f9;font-weight:700;font-size:13px;cursor:pointer;"><?= t('pwd.cancel') ?></button>
                <button type="submit" id="cpwSaveBtn" style="flex:1;height:44px;border-radius:12px;background:#2563eb;border:none;color:#fff;font-weight:700;font-size:13px;cursor:pointer;"><?= t('pwd.save') ?></button>
            </div>
        </form>
    </div>
</div>
<script>
function openChangePassword() { document.getElementById('cpwOverlay').style.display = 'flex'; }
function closeChangePassword() {
    document.getElementById('cpwOverlay').style.display = 'none';
    document.getElementById('cpwForm').reset();
    document.getElementById('cpwMsg').style.display = 'none';
}
async function submitChangePassword(e) {
    e.preventDefault();
    const msg = document.getElementById('cpwMsg');
    const btn = document.getElementById('cpwSaveBtn');
    const showMsg = (text, ok) => { msg.textContent = text; msg.style.color = ok ? '#34d399' : '#fca5a5'; msg.style.display = 'block'; };
    const newP = document.getElementById('cpwNew').value;
    const conf = document.getElementById('cpwConfirm').value;
    if (newP !== conf) { showMsg('<?= t('pwd.confirm') ?> ✗', false); return; }
    btn.disabled = true;
    try {
        const body = new URLSearchParams({
            current_password: document.getElementById('cpwCurrent').value,
            new_password: newP,
            confirm_password: conf,
        });
        const res = await fetch('/SRMT/public/change-password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body,
        });
        const d = await res.json();
        if (d.success) {
            showMsg('<?= t('pwd.success') ?>', true);
            setTimeout(closeChangePassword, 1200);
        } else {
            showMsg(d.error || 'Error', false);
        }
    } catch(err) { showMsg('Network error.', false); }
    finally { btn.disabled = false; }
}
</script>
