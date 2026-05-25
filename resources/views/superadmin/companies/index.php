<?php
use App\Http\View;
View::layout('layouts.superadmin', ['title' => 'Companies — SyncRide', 'active' => 'companies']);

$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
$editId  = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
?>

<div class="max-w-5xl mx-auto">

    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-white">Companies</h1>
            <p class="text-slate-400 mt-1">Create and manage tenant companies.</p>
        </div>
        <button onclick="document.getElementById('modalCreate').classList.remove('hidden')"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition-colors">
            <i data-lucide="plus" class="w-4 h-4"></i> New Company
        </button>
    </div>

    <!-- Flash messages -->
    <?php if ($success): ?>
    <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-sm flex items-center gap-2">
        <i data-lucide="check-circle" class="w-4 h-4"></i>
        <?= match($success) {
            'created' => 'Company created successfully.',
            'updated' => 'Company updated.',
            'deleted' => 'Company deleted.',
            default   => 'Operation completed.',
        } ?>
    </div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="mb-4 px-4 py-3 rounded-xl bg-red-500/15 border border-red-500/30 text-red-400 text-sm flex items-center gap-2">
        <i data-lucide="alert-circle" class="w-4 h-4"></i>
        <?= match($error) {
            'slug_taken'     => 'That slug is already in use. Choose another.',
            'missing_fields' => 'Please fill in all required fields.',
            'not_found'      => 'Company not found.',
            default          => 'An error occurred. Please try again.',
        } ?>
    </div>
    <?php endif; ?>

    <!-- Company table -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-800">
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Company</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Slug</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-slate-400 uppercase tracking-wider">Created</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-slate-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                <?php if (empty($companies)): ?>
                <tr>
                    <td colspan="4" class="px-6 py-12 text-center text-slate-500">
                        No companies yet.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($companies as $company): ?>
                <tr class="hover:bg-slate-800/40 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-indigo-600/20 flex items-center justify-center text-indigo-400 font-bold text-sm">
                                <?= strtoupper(substr($company->name, 0, 2)) ?>
                            </div>
                            <div>
                                <div class="font-semibold text-white"><?= View::e($company->name) ?></div>
                                <div class="text-xs text-slate-500">#<?= $company->id ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <code class="text-xs bg-slate-800 px-2 py-1 rounded text-slate-300"><?= View::e($company->slug) ?></code>
                    </td>
                    <td class="px-6 py-4 text-slate-400">
                        <?= date('d M Y', strtotime($company->createdAt)) ?>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button onclick="openEdit(<?= $company->id ?>, '<?= View::e($company->name) ?>', '<?= View::e($company->slug) ?>')"
                                    class="p-2 rounded-lg text-slate-400 hover:text-indigo-400 hover:bg-indigo-500/10 transition-colors">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <button onclick="openDelete(<?= $company->id ?>, '<?= View::e($company->name) ?>')"
                                    class="p-2 rounded-lg text-slate-400 hover:text-red-400 hover:bg-red-500/10 transition-colors">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                            <button onclick="openAddUser(<?= $company->id ?>, '<?= View::e($company->name) ?>')"
                                    class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-slate-400 hover:text-emerald-400 hover:bg-emerald-500/10 transition-colors text-xs font-medium">
                                <i data-lucide="user-plus" class="w-3.5 h-3.5"></i> Add User
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create Company -->
<div id="modalCreate" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-white">New Company</h3>
            <button onclick="document.getElementById('modalCreate').classList.add('hidden')" class="text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="/SRMT/public/superadmin/companies.php?action=store" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Company Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" required placeholder="e.g. Welcome Agitation"
                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                       oninput="autoSlug(this.value)">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Slug <span class="text-red-400">*</span></label>
                <input type="text" name="slug" id="slugInput" required placeholder="welcome-agitation"
                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm font-mono placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                <p class="text-xs text-slate-500 mt-1">Used in URLs. Lowercase, hyphens only.</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('modalCreate').classList.add('hidden')"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-slate-700 text-slate-300 text-sm font-medium hover:bg-slate-800 transition-colors">Cancel</button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition-colors">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Company -->
<div id="modalEdit" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-5">
            <h3 class="text-lg font-bold text-white">Edit Company</h3>
            <button onclick="document.getElementById('modalEdit').classList.add('hidden')" class="text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" action="/SRMT/public/superadmin/companies.php?action=update" class="space-y-4">
            <input type="hidden" name="id" id="editId">
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Company Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" id="editName" required
                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-1.5">Slug <span class="text-red-400">*</span></label>
                <input type="text" name="slug" id="editSlug" required
                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm font-mono placeholder-slate-500 focus:outline-none focus:border-indigo-500">
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('modalEdit').classList.add('hidden')"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-slate-700 text-slate-300 text-sm font-medium hover:bg-slate-800 transition-colors">Cancel</button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold transition-colors">Save</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Delete Company -->
<div id="modalDelete" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-sm p-6">
        <div class="text-center mb-5">
            <div class="w-12 h-12 rounded-full bg-red-500/15 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="trash-2" class="w-6 h-6 text-red-400"></i>
            </div>
            <h3 class="text-lg font-bold text-white">Delete Company?</h3>
            <p class="text-slate-400 text-sm mt-2">This will delete <strong id="deleteCompanyName" class="text-white"></strong> and cannot be undone.</p>
        </div>
        <form method="POST" action="/SRMT/public/superadmin/companies.php?action=destroy" class="flex gap-3">
            <input type="hidden" name="id" id="deleteId">
            <button type="button" onclick="document.getElementById('modalDelete').classList.add('hidden')"
                    class="flex-1 px-4 py-2.5 rounded-xl border border-slate-700 text-slate-300 text-sm font-medium hover:bg-slate-800 transition-colors">Cancel</button>
            <button type="submit"
                    class="flex-1 px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-500 text-white text-sm font-semibold transition-colors">Delete</button>
        </form>
    </div>
</div>

<!-- Modal: Add User to Company -->
<div id="modalAddUser" class="hidden fixed inset-0 bg-black/70 flex items-center justify-center z-50 p-4">
    <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-lg font-bold text-white">Add User</h3>
                <p class="text-sm text-slate-400 mt-0.5">Adding to <span id="addUserCompanyName" class="text-indigo-400 font-medium"></span></p>
            </div>
            <button onclick="document.getElementById('modalAddUser').classList.add('hidden')" class="text-slate-400 hover:text-white">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div id="addUserResult" class="hidden mb-4 px-4 py-3 rounded-xl text-sm"></div>
        <form id="formAddUser" class="space-y-4">
            <input type="hidden" name="company_id" id="addUserCompanyId">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Full Name <span class="text-red-400">*</span></label>
                    <input type="text" name="name" required placeholder="John Driver"
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Role <span class="text-red-400">*</span></label>
                    <select name="role" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm focus:outline-none focus:border-indigo-500">
                        <option value="1">Admin</option>
                        <option value="2" selected>Driver</option>
                        <option value="3">Partner</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-300 mb-1.5">Email <span class="text-red-400">*</span></label>
                <input type="email" name="email" required placeholder="user@company.com"
                       class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Password <span class="text-red-400">*</span></label>
                    <input type="password" name="password" required minlength="6"
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-300 mb-1.5">Phone</label>
                    <input type="text" name="phone" placeholder="+351 9xx xxx xxx"
                           class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-white text-sm placeholder-slate-500 focus:outline-none focus:border-indigo-500">
                </div>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" onclick="document.getElementById('modalAddUser').classList.add('hidden')"
                        class="flex-1 px-4 py-2.5 rounded-xl border border-slate-700 text-slate-300 text-sm font-medium hover:bg-slate-800 transition-colors">Cancel</button>
                <button type="submit"
                        class="flex-1 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold transition-colors">Create User</button>
            </div>
        </form>
    </div>
</div>

<script>
function autoSlug(name) {
    document.getElementById('slugInput').value = name.toLowerCase()
        .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
}
function openEdit(id, name, slug) {
    document.getElementById('editId').value   = id;
    document.getElementById('editName').value = name;
    document.getElementById('editSlug').value = slug;
    document.getElementById('modalEdit').classList.remove('hidden');
}
function openDelete(id, name) {
    document.getElementById('deleteId').value               = id;
    document.getElementById('deleteCompanyName').textContent = name;
    document.getElementById('modalDelete').classList.remove('hidden');
}
function openAddUser(companyId, companyName) {
    document.getElementById('addUserCompanyId').value          = companyId;
    document.getElementById('addUserCompanyName').textContent  = companyName;
    document.getElementById('addUserResult').classList.add('hidden');
    document.getElementById('formAddUser').reset();
    document.getElementById('addUserCompanyId').value          = companyId;
    document.getElementById('modalAddUser').classList.remove('hidden');
}

document.getElementById('formAddUser').addEventListener('submit', async function(e) {
    e.preventDefault();
    const data   = new FormData(this);
    const result = document.getElementById('addUserResult');
    try {
        const res  = await fetch('/SRMT/public/superadmin/companies.php?action=store_user', { method: 'POST', body: data });
        const json = await res.json();
        result.classList.remove('hidden', 'bg-red-500/15', 'border-red-500/30', 'text-red-400', 'bg-emerald-500/15', 'border-emerald-500/30', 'text-emerald-400');
        if (json.success) {
            result.className = 'mb-4 px-4 py-3 rounded-xl text-sm bg-emerald-500/15 border border-emerald-500/30 text-emerald-400';
            result.textContent = 'User created successfully (ID #' + json.user_id + ').';
            this.reset();
        } else {
            result.className = 'mb-4 px-4 py-3 rounded-xl text-sm bg-red-500/15 border border-red-500/30 text-red-400';
            result.textContent = json.message || 'Error creating user.';
        }
    } catch {
        result.className = 'mb-4 px-4 py-3 rounded-xl text-sm bg-red-500/15 border border-red-500/30 text-red-400';
        result.textContent = 'Network error. Try again.';
    }
});

<?php if ($editId): ?>
openEdit(<?= $editId ?>, <?= json_encode($companies[array_search($editId, array_column($companies, 'id'))]?->name ?? '') ?>, <?= json_encode($companies[array_search($editId, array_column($companies, 'id'))]?->slug ?? '') ?>);
<?php endif; ?>
</script>
