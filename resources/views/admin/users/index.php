<?php
/** @var array<App\Models\User> $users */
/** @var int $totalAdmins */
/** @var int $totalDrivers */
/** @var int $totalPartners */
/** @var string|null $flash */

use App\Http\View;
use App\Models\User;

ob_start();
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
<script>
function openNewUserModal() { document.getElementById("modalOverlay").classList.add("active"); document.getElementById("createUserModal").classList.add("active"); }

async function submitCreateUser(e) {
    e.preventDefault();
    const form = e.target;
    if (form.dataset.submitting === '1') return; // guard against double-submit
    form.dataset.submitting = '1';
    const btn  = form.querySelector('button[type="submit"]');
    if (btn) btn.disabled = true;
    try {
        const res = await fetch(form.action, { method: 'POST', body: new FormData(form) });
        if (res.redirected) { window.location = res.url; return; }
        if (res.status === 409) {
            let text, d;
            try { text = await res.text(); } catch(_) { text = '{}'; }
            try { d = JSON.parse(text); } catch(_) { d = {}; }
            if ((d.message === 'driver_exists' || d.message === 'driver_already_in_company') && d.driver) {
                const alreadyIn = d.message === 'driver_already_in_company';
                document.getElementById('addDriverName').textContent  = d.driver.name  || '';
                document.getElementById('addDriverEmail').textContent = d.driver.email || '';
                document.getElementById('addDriverId').value          = d.driver.id    || '';
                document.getElementById('addDriverDesc').textContent  = alreadyIn
                    ? '<?= t('users.driver_already_in_company') ?>'
                    : '<?= t('users.driver_exists_desc') ?>';
                document.getElementById('addDriverConfirmBtn').style.display = alreadyIn ? 'none' : '';
                closeAllModals();
                setTimeout(function() {
                    document.getElementById("modalOverlay").classList.add("active");
                    document.getElementById("addDriverModal").classList.add("active");
                }, 350);
                return;
            }
        }
        if (!res.ok) { toastr.error('<?= t('users.error_creating') ?>'); }
    } catch(err) { toastr.error('Network error.'); }
    finally {
        form.dataset.submitting = '0';
        if (btn) btn.disabled = false;
    }
}

async function confirmAddDriver() {
    const uid = document.getElementById('addDriverId').value;
    const btn = document.getElementById('addDriverConfirmBtn');
    btn.disabled = true;
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const res = await fetch('/SRMT/public/admin/user-add-to-company.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken,
            },
            body: 'user_id=' + encodeURIComponent(uid),
        });
        const text = await res.text();
        let d;
        try { d = JSON.parse(text); } catch(e) { toastr.error('Server error (' + res.status + ')'); return; }
        if (d.success) {
            toastr.success(d.name + ' <?= t('users.added_to_company') ?>');
            closeAllModals();
            setTimeout(() => location.reload(), 900);
        } else {
            toastr.error(d.error || 'Error');
        }
    } catch(err) {
        toastr.error('Network error.');
    } finally {
        btn.disabled = false;
    }
}
function openEditModal(u) {
    document.getElementById("editId").value    = u.id;
    document.getElementById("editName").value  = u.name;
    document.getElementById("editEmail").value = u.email;
    document.getElementById("editPhone").value = u.phone ?? "";
    document.getElementById("editRole").value  = u.role;
    document.getElementById("modalOverlay").classList.add("active");
    document.getElementById("editUserModal").classList.add("active");
}
function closeAllModals() {
    document.querySelectorAll(".modal-os").forEach(m => m.classList.remove("active"));
    document.getElementById("modalOverlay").classList.remove("active");
}
function confirmDelete(id, name) {
    if (!confirm("Delete " + name + "?")) return;
    const f = document.createElement("form"); f.method = "POST"; f.action = "/SRMT/public/admin/user-delete.php";
    const i = document.createElement("input"); i.name = "id"; i.value = id;
    f.appendChild(i); document.body.appendChild(f); f.submit();
}
function generatePass(targetId) {
    const chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#";
    let pw = ""; for (let i = 0; i < 14; i++) pw += chars[Math.floor(Math.random() * chars.length)];
    document.getElementById(targetId).value = pw;
}

// ── Invite links ──────────────────────────────────────────────
function openInviteModal() {
    document.getElementById("modalOverlay").classList.add("active");
    document.getElementById("inviteModal").classList.add("active");
    document.getElementById("inviteResult").style.display = "none";
    loadInvites();
}
async function generateInvite() {
    const role  = document.getElementById("inviteRole").value;
    const label = document.getElementById("inviteLabel").value;
    const btn   = document.getElementById("inviteGenBtn");
    btn.disabled = true;
    try {
        const res = await fetch("/SRMT/public/admin/invite-create.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded", "Accept": "application/json" },
            body: "role=" + encodeURIComponent(role) + "&label=" + encodeURIComponent(label),
        });
        const d = await res.json();
        if (d.success) {
            document.getElementById("inviteLinkInput").value = d.link;
            document.getElementById("inviteResult").style.display = "block";
            loadInvites();
        } else { toastr.error(d.error || "Error"); }
    } catch(e) { toastr.error("Network error."); }
    finally { btn.disabled = false; }
}
function copyInviteLink() {
    const inp = document.getElementById("inviteLinkInput");
    inp.select(); navigator.clipboard.writeText(inp.value);
    toastr.success("<?= t('invites.copied') ?>");
}
async function loadInvites() {
    const box = document.getElementById("inviteList");
    box.innerHTML = '<div style="text-align:center;padding:14px;color:#94a3b8;font-size:12px">…</div>';
    try {
        const res = await fetch("/SRMT/public/admin/invites-data.php", { headers: { "Accept": "application/json" } });
        const d   = await res.json();
        const rows = d.data || [];
        if (rows.length === 0) { box.innerHTML = '<div style="text-align:center;padding:14px;color:#94a3b8;font-size:12px"><?= t('invites.none') ?></div>'; return; }
        const statusColors = { pending: '#3b82f6', used: '#10b981', expired: '#94a3b8' };
        const statusLabels = { pending: '<?= t('invites.status_pending') ?>', used: '<?= t('invites.status_used') ?>', expired: '<?= t('invites.status_expired') ?>' };
        box.innerHTML = rows.map(r => `
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;padding:9px 0;border-bottom:1px solid rgba(255,255,255,0.06)">
                <div style="min-width:0">
                    <div style="font-size:12px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${r.label || r.role}</div>
                    <div style="font-size:9px;color:#94a3b8;font-weight:600">${r.role}${r.used_by ? ' · ' + r.used_by : ''}</div>
                </div>
                <div style="display:flex;align-items:center;gap:6px;flex-shrink:0">
                    <span style="font-size:9px;font-weight:800;text-transform:uppercase;color:${statusColors[r.status]}">${statusLabels[r.status]}</span>
                    ${r.status === 'pending' ? `<button onclick="copyOne('${r.link.replace(/'/g,"\\'")}')" style="background:none;border:none;color:#60a5fa;cursor:pointer"><i class="bi bi-clipboard"></i></button>` : ''}
                    <button onclick="revokeInvite(${r.id})" style="background:none;border:none;color:#f87171;cursor:pointer"><i class="bi bi-trash3"></i></button>
                </div>
            </div>`).join('');
    } catch(e) { box.innerHTML = ''; }
}
function copyOne(link) { navigator.clipboard.writeText(link); toastr.success("<?= t('invites.copied') ?>"); }
async function revokeInvite(id) {
    const res = await fetch("/SRMT/public/admin/invite-delete.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded", "Accept": "application/json" },
        body: "id=" + id,
    });
    const d = await res.json();
    if (d.success) loadInvites(); else toastr.error(d.error || "Error");
}
</script>
<?php
$usersScripts = ob_get_clean();

View::layout('layouts.admin', [
    'title'        => 'Team — SyncRide OS',
    'active'       => 'users',
    'extraScripts' => $usersScripts,
]);

$flashMessages = [
    'user_created' => t('users.created'),
    'user_updated' => t('users.updated'),
    'user_deleted' => t('users.deleted'),
];
?>

<?php if ($flash !== null && isset($flashMessages[$flash])): ?>
<script>document.addEventListener('DOMContentLoaded', () => toastr.success("<?= View::e($flashMessages[$flash]) ?>"));</script>
<?php endif; ?>

<section class="px-6 mt-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-black"><?= t('users.title') ?></h2>
        <div class="flex gap-2">
            <button onclick="openInviteModal()" class="glass rounded-full px-4 py-2 text-xs font-bold flex items-center gap-2 active:scale-95 transition-transform">
                <i data-lucide="link" class="w-4 h-4 text-emerald-500"></i> <?= t('invites.title') ?>
            </button>
            <button onclick="openNewUserModal()" class="glass rounded-full px-4 py-2 text-xs font-bold flex items-center gap-2 active:scale-95 transition-transform">
                <i data-lucide="plus" class="w-4 h-4 text-blue-500"></i> <?= t('users.new') ?>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-3">
        <div class="glass p-3 rounded-2xl text-center">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('users.admins') ?></p>
            <h3 class="text-2xl font-black mt-1"><?= (int) $totalAdmins ?></h3>
        </div>
        <div class="glass p-3 rounded-2xl text-center">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('users.drivers') ?></p>
            <h3 class="text-2xl font-black mt-1 text-blue-500"><?= (int) $totalDrivers ?></h3>
        </div>
        <div class="glass p-3 rounded-2xl text-center">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('users.partners') ?></p>
            <h3 class="text-2xl font-black mt-1 text-emerald-500"><?= (int) $totalPartners ?></h3>
        </div>
    </div>
</section>

<section class="px-6 mt-6">
    <div class="space-y-2">
        <?php foreach ($users as $user): ?>
            <?php
                $uInitial   = mb_strtoupper(mb_substr($user->name, 0, 1, 'UTF-8'));
                $uSvg       = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><circle cx="20" cy="20" r="20" fill="#2563eb"/><text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="white" font-size="17" font-weight="bold" font-family="system-ui">' . htmlspecialchars($uInitial) . '</text></svg>';
                $uFallback  = 'data:image/svg+xml;base64,' . base64_encode($uSvg);
                $cleanPath  = str_replace('Includes/dist/pages/', '', $user->profilePhotoPath ?? '');
                $avatar     = $cleanPath !== ''
                    ? '/SRMT/public/' . ltrim($cleanPath, '/')
                    : $uFallback;
                $userJson = json_encode([
                    'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
                    'phone' => $user->phone, 'role' => $user->role,
                ], JSON_HEX_APOS | JSON_HEX_QUOT);
            ?>
            <div class="glass p-3 rounded-2xl flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="<?= View::e($avatar) ?>" onerror="this.onerror=null;this.src='<?= $uFallback ?>'" class="w-10 h-10 rounded-full border border-white/10 object-cover" alt="">
                    <div>
                        <h4 class="text-sm font-bold leading-tight"><?= View::e($user->name) ?></h4>
                        <p class="text-[9px] text-zinc-500 font-bold"><?= View::e($user->email) ?> • <?= View::e($user->roleLabel()) ?></p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick='openEditModal(<?= $userJson ?>)' class="w-8 h-8 glass rounded-full flex items-center justify-center text-zinc-500 active:scale-90"><i data-lucide="edit-3" class="w-3.5 h-3.5"></i></button>
                    <button onclick="confirmDelete(<?= (int) $user->id ?>, '<?= View::e(addslashes($user->name)) ?>')" class="w-8 h-8 glass rounded-full flex items-center justify-center text-red-500/60 active:scale-90"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<div class="modal-overlay" id="modalOverlay" onclick="closeAllModals()"></div>

<!-- Create user -->
<div class="modal-os" id="createUserModal">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h3 class="text-lg font-black text-white"><?= t('users.new_member') ?></h3>
            <p class="text-[9px] text-blue-500 font-bold uppercase"><?= t('users.create_account') ?></p>
        </div>
        <button onclick="closeAllModals()" class="text-zinc-600"><i data-lucide="x-circle"></i></button>
    </div>

    <form action="/SRMT/public/admin/user-create.php" method="POST" class="space-y-4" onsubmit="submitCreateUser(event)">
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('users.full_name') ?></label>
            <input type="text" name="name" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" required>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('users.email') ?></label>
            <input type="email" name="email" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" required>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('users.password') ?></label>
            <div class="flex gap-2 mt-1">
                <input type="text" name="password" id="genPass" class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white" required>
                <button type="button" onclick="generatePass('genPass')" class="glass px-4 rounded-xl text-blue-500"><i data-lucide="wand-2" class="w-4 h-4"></i></button>
            </div>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('users.phone') ?></label>
            <input type="text" name="phone" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" required>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('users.role') ?></label>
            <select name="role" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" required>
                <option value=""><?= t('users.choose') ?></option>
                <option value="1"><?= t('users.admin') ?></option>
                <option value="2"><?= t('users.driver') ?></option>
                <option value="3"><?= t('users.partner') ?></option>
            </select>
        </div>
        <button type="submit" class="w-full bg-blue-600 rounded-xl py-3 font-bold text-sm"><?= t('users.create') ?></button>
    </form>
</div>

<!-- Driver already exists → Add to company confirmation -->
<div class="modal-os" id="addDriverModal">
    <div class="flex justify-between items-start mb-5">
        <h3 class="text-base font-black"><?= t('users.driver_exists_title') ?></h3>
        <button onclick="closeAllModals()" class="text-zinc-600"><i data-lucide="x-circle"></i></button>
    </div>
    <div class="glass p-3 rounded-xl mb-4 flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-blue-500/10 flex items-center justify-center flex-shrink-0">
            <i data-lucide="user" class="w-4 h-4 text-blue-500"></i>
        </div>
        <div>
            <p class="font-bold text-sm" id="addDriverName"></p>
            <p class="text-[10px] text-zinc-500" id="addDriverEmail"></p>
        </div>
    </div>
    <p class="text-xs text-zinc-500 mb-5" id="addDriverDesc"><?= t('users.driver_exists_desc') ?></p>
    <input type="hidden" id="addDriverId">
    <div class="flex gap-3">
        <button onclick="closeAllModals()" class="flex-1 py-3 rounded-xl text-sm font-bold glass"><?= t('users.cancel') ?></button>
        <button id="addDriverConfirmBtn" onclick="confirmAddDriver()"
            class="flex-1 py-3 rounded-xl text-sm font-bold bg-blue-600 text-white">
            <?= t('users.add_to_company') ?>
        </button>
    </div>
</div>

<!-- Invite by link -->
<div class="modal-os" id="inviteModal">
    <div class="flex justify-between items-start mb-5">
        <div>
            <h3 class="text-lg font-black text-white"><?= t('invites.title') ?></h3>
            <p class="text-[9px] text-emerald-500 font-bold uppercase"><?= t('invites.create') ?></p>
        </div>
        <button onclick="closeAllModals()" class="text-zinc-600"><i data-lucide="x-circle"></i></button>
    </div>

    <div class="space-y-3">
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('invites.role') ?></label>
            <select id="inviteRole" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1">
                <option value="2"><?= t('users.driver') ?></option>
                <option value="3"><?= t('users.partner') ?></option>
                <option value="1"><?= t('users.admin') ?></option>
            </select>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('invites.label') ?></label>
            <input type="text" id="inviteLabel" placeholder="<?= t('invites.label_ph') ?>" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1">
        </div>
        <button id="inviteGenBtn" onclick="generateInvite()" class="w-full bg-emerald-600 rounded-xl py-3 font-bold text-sm">
            <i data-lucide="link" class="w-4 h-4 inline"></i> <?= t('invites.generate') ?>
        </button>

        <div id="inviteResult" style="display:none">
            <div class="flex gap-2 mt-1">
                <input id="inviteLinkInput" readonly class="flex-1 bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-xs text-white">
                <button onclick="copyInviteLink()" class="glass px-4 rounded-xl text-emerald-400 text-xs font-bold"><?= t('invites.copy') ?></button>
            </div>
        </div>

        <hr class="border-white/10 my-2">
        <div class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('invites.pending') ?></div>
        <div id="inviteList" style="max-height:200px;overflow-y:auto"></div>
    </div>
</div>

<!-- Edit user -->
<div class="modal-os" id="editUserModal">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h3 class="text-lg font-black text-white"><?= t('users.edit_member') ?></h3>
            <p class="text-[9px] text-blue-500 font-bold uppercase"><?= t('users.update_account') ?></p>
        </div>
        <button onclick="closeAllModals()" class="text-zinc-600"><i data-lucide="x-circle"></i></button>
    </div>

    <form action="/SRMT/public/admin/user-edit.php" method="POST" class="space-y-4">
        <input type="hidden" name="id" id="editId">
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('users.full_name') ?></label>
            <input type="text" name="name" id="editName" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" required>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('users.email') ?></label>
            <input type="email" name="email" id="editEmail" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" required>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('users.phone') ?></label>
            <input type="text" name="phone" id="editPhone" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1">
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('users.role') ?></label>
            <select name="role" id="editRole" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" required>
                <option value="1"><?= t('users.admin') ?></option>
                <option value="2"><?= t('users.driver') ?></option>
                <option value="3"><?= t('users.partner') ?></option>
            </select>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('users.new_password') ?></label>
            <input type="text" name="password" placeholder="<?= t('users.blank_to_keep') ?>" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1">
        </div>
        <button type="submit" class="w-full bg-blue-600 rounded-xl py-3 font-bold text-sm"><?= t('users.save') ?></button>
    </form>
</div>
