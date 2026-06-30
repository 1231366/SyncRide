<?php
use App\Http\View;
View::layout('layouts.superadmin', ['title' => 'Companies — SyncRide', 'active' => 'companies']);

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>

<div class="max-w-5xl mx-auto px-4 md:px-6 pt-8">

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold">Companies</h1>
            <p class="text-slate-400 mt-1 text-sm">Manage tenant companies and their admins.</p>
        </div>
        <button onclick="openModal('modalCreate')"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-semibold transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> New
        </button>
    </div>

    <!-- Flash -->
    <?php if ($success): ?>
    <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-sm flex items-center gap-2">
        <i data-lucide="check-circle" class="w-4 h-4"></i>
        <?= match($success) { 'created'=>'Company created.', 'updated'=>'Company updated.', 'deleted'=>'Company deleted.', default=>'Done.' } ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="mb-4 px-4 py-3 rounded-xl bg-red-500/15 border border-red-500/30 text-red-400 text-sm flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4"></i>
        <?= match($error) { 'slug_taken'=>'Slug already in use.', 'missing_fields'=>'Fill in all required fields.', 'not_found'=>'Company not found.', default=>'An error occurred.' } ?>
    </div>
    <?php endif; ?>

    <!-- Company cards -->
    <?php if (empty($companies)): ?>
    <div class="glass rounded-2xl p-12 text-center">
        <i data-lucide="building-2" class="w-12 h-12 text-slate-500 mx-auto mb-4"></i>
        <p class="text-slate-400">No companies yet.</p>
    </div>
    <?php else: ?>
    <div class="space-y-4">
        <?php foreach ($companies as $company):
            $admins = $adminsByCompany[$company->id] ?? [];
        ?>
        <div class="glass rounded-2xl overflow-hidden">

            <!-- Company header row -->
            <div class="flex items-center justify-between px-5 py-4 flex-wrap gap-3">
                <div class="flex items-center gap-4 min-w-0">
                    <div class="w-11 h-11 rounded-xl bg-violet-600/20 flex items-center justify-center text-violet-400 font-bold text-sm flex-shrink-0">
                        <?= strtoupper(substr($company->name, 0, 2)) ?>
                    </div>
                    <div class="min-w-0">
                        <div class="font-bold text-white text-base truncate"><?= View::e($company->name) ?></div>
                        <div class="flex items-center gap-3 mt-0.5 flex-wrap">
                            <code class="text-xs bg-white/10 px-2 py-0.5 rounded text-slate-400"><?= View::e($company->slug) ?></code>
                            <span class="text-xs text-slate-500">since <?= date('d M Y', strtotime($company->createdAt)) ?></span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2 flex-wrap flex-shrink-0">
                    <!-- Grace access toggle -->
                    <button id="grace-btn-<?= $company->id ?>"
                            onclick="toggleGrace(<?= $company->id ?>, this)"
                            data-grace="<?= $company->graceAccess ? '1' : '0' ?>"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-all <?= $company->graceAccess ? 'bg-emerald-500/20 text-emerald-400 ring-1 ring-emerald-500/40' : 'bg-white/5 text-slate-500 hover:bg-white/10' ?>">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                        <span>Grace<?= $company->graceAccess ? ' ✓' : '' ?></span>
                    </button>
                    <button onclick="generateInvite(<?= $company->id ?>, '<?= View::e($company->name) ?>')"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-violet-600/20 hover:bg-violet-600/30 text-violet-400 text-xs font-semibold transition-colors">
                        <i data-lucide="link" class="w-3.5 h-3.5"></i> Invite
                    </button>
                    <button onclick="openAddAdmin(<?= $company->id ?>, '<?= View::e($company->name) ?>')"
                            class="flex items-center gap-1.5 px-3 py-2 rounded-xl bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 text-xs font-semibold transition-colors">
                        <i data-lucide="user-plus" class="w-3.5 h-3.5"></i> Admin
                    </button>
                    <button onclick="openEdit(<?= $company->id ?>, '<?= View::e($company->name) ?>', '<?= View::e($company->slug) ?>')"
                            class="p-2 rounded-lg text-slate-500 hover:text-violet-400 hover:bg-violet-500/10 transition-colors">
                        <i data-lucide="pencil" class="w-4 h-4"></i>
                    </button>
                    <button onclick="openDelete(<?= $company->id ?>, '<?= View::e($company->name) ?>')"
                            class="p-2 rounded-lg text-slate-500 hover:text-red-400 hover:bg-red-500/10 transition-colors">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>

            <!-- Admins section -->
            <div class="border-t border-white/10 px-5 py-4">
                <div class="flex items-center gap-2 mb-3">
                    <i data-lucide="shield-check" class="w-4 h-4 text-slate-500"></i>
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Admins (<?= count($admins) ?>)</span>
                </div>

                <?php if (empty($admins)): ?>
                <div class="flex items-center gap-3 py-3 px-4 rounded-xl bg-amber-500/10 border border-amber-500/20">
                    <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-400 flex-shrink-0"></i>
                    <span class="text-sm text-amber-400">No admin yet — click <strong>Admin</strong> to set up this company.</span>
                </div>
                <?php else: ?>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                    <?php foreach ($admins as $admin): ?>
                    <div class="flex items-center gap-3 px-4 py-3 rounded-xl bg-white/5">
                        <div class="w-8 h-8 rounded-full bg-violet-700/40 flex items-center justify-center text-xs font-bold text-violet-300 flex-shrink-0">
                            <?= strtoupper(substr($admin['name'], 0, 2)) ?>
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-medium text-white truncate"><?= View::e($admin['name']) ?></div>
                            <div class="text-xs text-slate-400 truncate"><?= View::e($admin['email']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal overlays -->
<div class="modal-overlay" id="overlay" onclick="closeAllModals()"></div>

<!-- Modal: Create Company -->
<div id="modalCreate" class="modal-os">
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg font-bold text-white">New Company</h3>
        <button onclick="closeAllModals()" class="text-slate-400 hover:text-white">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>
    <form method="POST" action="/SRMT/public/superadmin/companies.php?action=store" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5">Company Name <span class="text-red-400">*</span></label>
            <input type="text" name="name" required placeholder="e.g. Lisbon Transfers"
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-violet-500"
                   oninput="autoSlug(this.value)">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5">Slug <span class="text-red-400">*</span></label>
            <input type="text" name="slug" id="slugInput" required placeholder="lisbon-transfers"
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm font-mono placeholder-slate-500 focus:outline-none focus:border-violet-500">
            <p class="text-xs text-slate-500 mt-1">Lowercase, hyphens only.</p>
        </div>
        <div class="flex gap-3 pt-2">
            <button type="button" onclick="closeAllModals()"
                    class="flex-1 px-4 py-2.5 rounded-xl border border-white/10 text-slate-300 text-sm font-medium hover:bg-white/5 transition-colors">Cancel</button>
            <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-semibold transition-colors">Create</button>
        </div>
    </form>
</div>

<!-- Modal: Edit Company -->
<div id="modalEdit" class="modal-os">
    <div class="flex items-center justify-between mb-5">
        <h3 class="text-lg font-bold text-white">Edit Company</h3>
        <button onclick="closeAllModals()" class="text-slate-400 hover:text-white">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>
    <form method="POST" action="/SRMT/public/superadmin/companies.php?action=update" class="space-y-4">
        <input type="hidden" name="id" id="editId">
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5">Company Name <span class="text-red-400">*</span></label>
            <input type="text" name="name" id="editName" required
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-violet-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-300 mb-1.5">Slug <span class="text-red-400">*</span></label>
            <input type="text" name="slug" id="editSlug" required
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm font-mono focus:outline-none focus:border-violet-500">
        </div>
        <div class="flex gap-3 pt-2">
            <button type="button" onclick="closeAllModals()"
                    class="flex-1 px-4 py-2.5 rounded-xl border border-white/10 text-slate-300 text-sm font-medium hover:bg-white/5 transition-colors">Cancel</button>
            <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-violet-600 hover:bg-violet-500 text-white text-sm font-semibold transition-colors">Save</button>
        </div>
    </form>
</div>

<!-- Modal: Delete Company -->
<div id="modalDelete" class="modal-os">
    <div class="text-center mb-5">
        <div class="w-12 h-12 rounded-full bg-red-500/15 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="trash-2" class="w-6 h-6 text-red-400"></i>
        </div>
        <h3 class="text-lg font-bold text-white">Delete Company?</h3>
        <p class="text-slate-400 text-sm mt-2">Deleting <strong id="deleteCompanyName" class="text-white"></strong> cannot be undone.</p>
    </div>
    <form method="POST" action="/SRMT/public/superadmin/companies.php?action=destroy" class="flex gap-3">
        <input type="hidden" name="id" id="deleteId">
        <button type="button" onclick="closeAllModals()"
                class="flex-1 px-4 py-2.5 rounded-xl border border-white/10 text-slate-300 text-sm font-medium hover:bg-white/5 transition-colors">Cancel</button>
        <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-sm font-semibold transition-colors">Delete</button>
    </form>
</div>

<!-- Modal: Add Admin -->
<div id="modalAddAdmin" class="modal-os">
    <div class="flex items-center justify-between mb-1">
        <h3 class="text-lg font-bold text-white">Add Admin</h3>
        <button onclick="closeAllModals()" class="text-slate-400 hover:text-white">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>
    <p class="text-sm text-slate-400 mb-5">
        Creating admin for <span id="addAdminCompanyName" class="text-violet-400 font-medium"></span>.
    </p>
    <div id="addAdminResult" class="hidden mb-4 px-4 py-3 rounded-xl text-sm"></div>
    <form id="formAddAdmin" class="space-y-4">
        <input type="hidden" name="company_id" id="addAdminCompanyId">
        <div>
            <label class="block text-xs font-medium text-slate-300 mb-1.5">Full Name <span class="text-red-400">*</span></label>
            <input type="text" name="name" required placeholder="João Silva"
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-violet-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-300 mb-1.5">Email <span class="text-red-400">*</span></label>
            <input type="email" name="email" required placeholder="admin@company.com"
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-violet-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-slate-300 mb-1.5">Password <span class="text-red-400">*</span></label>
            <input type="password" name="password" required minlength="6"
                   class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-violet-500">
            <p class="text-xs text-slate-500 mt-1">They can change it after first login.</p>
        </div>
        <div class="flex gap-3 pt-1">
            <button type="button" onclick="closeAllModals()"
                    class="flex-1 px-4 py-2.5 rounded-xl border border-white/10 text-slate-300 text-sm font-medium hover:bg-white/5 transition-colors">Cancel</button>
            <button type="submit"
                    class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold transition-colors">Create Admin</button>
        </div>
    </form>
</div>

<!-- Modal: Invite Link -->
<div id="modalInvite" class="modal-os">
    <div class="flex items-center justify-between mb-1">
        <h3 class="text-lg font-bold text-white">Invite Admin</h3>
        <button onclick="closeAllModals()" class="text-slate-400 hover:text-white">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>
    <p class="text-sm text-slate-400 mb-5">
        One-time link for <span id="inviteCompanyName" class="text-violet-400 font-medium"></span>. Valid 7 days.
    </p>
    <div id="inviteLoading" class="flex items-center gap-3 py-4 text-slate-400 text-sm">
        <i data-lucide="loader" class="w-4 h-4 animate-spin"></i> Generating link…
    </div>
    <div id="inviteLinkWrap" class="hidden space-y-3">
        <div class="flex items-center gap-2 bg-white/5 border border-white/10 rounded-xl px-4 py-3">
            <span id="inviteLinkText" class="text-sm text-violet-300 truncate flex-1 font-mono"></span>
            <button onclick="copyInviteLink()" class="flex-shrink-0 p-1.5 rounded-lg hover:bg-white/10 text-slate-400 hover:text-white transition-colors">
                <i data-lucide="copy" class="w-4 h-4"></i>
            </button>
        </div>
        <p id="inviteCopied" class="hidden text-xs text-emerald-400 font-medium text-center">✓ Copied to clipboard</p>
        <p class="text-xs text-slate-500 text-center">Share this link with the admin. It can only be used once.</p>
    </div>
    <div id="inviteError" class="hidden px-4 py-3 rounded-xl bg-red-500/15 border border-red-500/30 text-red-400 text-sm"></div>
</div>

<script>
/* ── Modal helpers ─────────────────────────────────────── */
function openModal(id) {
    document.getElementById('overlay').classList.add('active');
    document.getElementById(id).classList.add('active');
    lucide.createIcons();
}
function closeAllModals() {
    document.getElementById('overlay').classList.remove('active');
    document.querySelectorAll('.modal-os').forEach(m => m.classList.remove('active'));
}

function autoSlug(name) {
    document.getElementById('slugInput').value = name.toLowerCase()
        .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
}
function openEdit(id, name, slug) {
    document.getElementById('editId').value   = id;
    document.getElementById('editName').value = name;
    document.getElementById('editSlug').value = slug;
    openModal('modalEdit');
}
function openDelete(id, name) {
    document.getElementById('deleteId').value               = id;
    document.getElementById('deleteCompanyName').textContent = name;
    openModal('modalDelete');
}
function openAddAdmin(companyId, companyName) {
    document.getElementById('addAdminCompanyId').value         = companyId;
    document.getElementById('addAdminCompanyName').textContent = companyName;
    document.getElementById('addAdminResult').classList.add('hidden');
    document.getElementById('formAddAdmin').reset();
    document.getElementById('addAdminCompanyId').value = companyId;
    openModal('modalAddAdmin');
}

async function generateInvite(companyId, companyName) {
    document.getElementById('inviteCompanyName').textContent = companyName;
    document.getElementById('inviteLoading').classList.remove('hidden');
    document.getElementById('inviteLinkWrap').classList.add('hidden');
    document.getElementById('inviteError').classList.add('hidden');
    document.getElementById('inviteCopied').classList.add('hidden');
    openModal('modalInvite');

    try {
        const fd = new FormData();
        fd.append('company_id', companyId);
        const res  = await fetch('/SRMT/public/superadmin/companies.php?action=create_invite', { method: 'POST', body: fd });
        const json = await res.json();
        document.getElementById('inviteLoading').classList.add('hidden');
        if (json.success) {
            document.getElementById('inviteLinkText').textContent = json.link;
            document.getElementById('inviteLinkWrap').classList.remove('hidden');
            lucide.createIcons();
        } else {
            document.getElementById('inviteError').textContent = json.message || 'Error generating link.';
            document.getElementById('inviteError').classList.remove('hidden');
        }
    } catch {
        document.getElementById('inviteLoading').classList.add('hidden');
        document.getElementById('inviteError').textContent = 'Network error. Try again.';
        document.getElementById('inviteError').classList.remove('hidden');
    }
}

function copyInviteLink() {
    const link = document.getElementById('inviteLinkText').textContent;
    navigator.clipboard.writeText(link).then(() => {
        document.getElementById('inviteCopied').classList.remove('hidden');
        setTimeout(() => document.getElementById('inviteCopied').classList.add('hidden'), 2000);
    });
}

async function toggleGrace(companyId, btn) {
    const current = btn.dataset.grace === '1';
    const newVal  = !current;
    btn.disabled  = true;

    try {
        const fd = new FormData();
        fd.append('company_id', companyId);
        fd.append('grace', newVal ? '1' : '0');
        const res  = await fetch('/SRMT/public/superadmin/companies.php?action=toggle_grace', { method: 'POST', body: fd });
        const json = await res.json();

        if (json.success) {
            btn.dataset.grace = newVal ? '1' : '0';
            const span = btn.querySelector('span');
            if (newVal) {
                btn.className = 'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-all bg-emerald-500/20 text-emerald-400 ring-1 ring-emerald-500/40';
                span.textContent = 'Grace ✓';
            } else {
                btn.className = 'flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold transition-all bg-white/5 text-slate-500 hover:bg-white/10';
                span.textContent = 'Grace';
            }
        }
    } catch { /* ignore */ }

    btn.disabled = false;
}

document.getElementById('formAddAdmin').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn    = this.querySelector('button[type=submit]');
    const result = document.getElementById('addAdminResult');
    btn.disabled    = true;
    btn.textContent = 'Creating…';

    try {
        const res  = await fetch('/SRMT/public/superadmin/companies.php?action=store_user', {
            method: 'POST', body: new FormData(this)
        });
        const json = await res.json();
        result.classList.remove('hidden');

        if (json.success) {
            result.className = 'mb-4 px-4 py-3 rounded-xl text-sm bg-emerald-500/15 border border-emerald-500/30 text-emerald-400';
            result.innerHTML = `Admin <strong>${json.name}</strong> (${json.email}) created.`;
            this.reset();
            setTimeout(() => location.reload(), 2000);
        } else {
            result.className = 'mb-4 px-4 py-3 rounded-xl text-sm bg-red-500/15 border border-red-500/30 text-red-400';
            result.textContent = json.message || 'Error creating admin.';
        }
    } catch {
        result.classList.remove('hidden');
        result.className = 'mb-4 px-4 py-3 rounded-xl text-sm bg-red-500/15 border border-red-500/30 text-red-400';
        result.textContent = 'Network error. Try again.';
    }

    btn.disabled    = false;
    btn.textContent = 'Create Admin';
});
</script>
