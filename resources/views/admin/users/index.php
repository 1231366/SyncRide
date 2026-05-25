<?php
/** @var array<App\Models\User> $users */
/** @var int $totalAdmins */
/** @var int $totalDrivers */
/** @var int $totalPartners */
/** @var string|null $flash */

use App\Http\View;
use App\Models\User;

View::layout('layouts.admin', [
    'title'        => 'Team — SyncRide OS',
    'active'       => 'users',
    'extraScripts' => '
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
        <script>
            function openNewUserModal()  { document.getElementById("modalOverlay").classList.add("active"); document.getElementById("createUserModal").classList.add("active"); }
            function openEditModal(u)    {
                document.getElementById("editId").value    = u.id;
                document.getElementById("editName").value  = u.name;
                document.getElementById("editEmail").value = u.email;
                document.getElementById("editPhone").value = u.phone ?? "";
                document.getElementById("editRole").value  = u.role;
                document.getElementById("modalOverlay").classList.add("active");
                document.getElementById("editUserModal").classList.add("active");
            }
            function closeAllModals()    {
                document.querySelectorAll(".modal-os").forEach(m => m.classList.remove("active"));
                document.getElementById("modalOverlay").classList.remove("active");
            }
            function confirmDelete(id, name) {
                if (!confirm("Delete " + name + "?")) return;
                const f = document.createElement("form");
                f.method = "POST";
                f.action = "/SRMT/public/admin/user-delete.php";
                const i = document.createElement("input");
                i.name = "id"; i.value = id;
                f.appendChild(i); document.body.appendChild(f); f.submit();
            }
            function generatePass(targetId) {
                const chars = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#";
                let pw = ""; for (let i = 0; i < 14; i++) pw += chars[Math.floor(Math.random() * chars.length)];
                document.getElementById(targetId).value = pw;
            }
        </script>
        <style>
            .modal-os {
                position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) scale(0.9);
                width: 90%; max-width: 400px; visibility: hidden; opacity: 0;
                background: rgba(20,20,20,0.95); backdrop-filter: blur(30px);
                border-radius: 28px; border: 1px solid rgba(255,255,255,0.15);
                z-index: 4000; transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
                padding: 24px; max-height: 85vh; overflow-y: auto;
            }
            .modal-os.active { visibility: visible; opacity: 1; transform: translate(-50%,-50%) scale(1); }
            .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(5px); visibility: hidden; opacity: 0; z-index: 3999; transition: all 0.3s; }
            .modal-overlay.active { visibility: visible; opacity: 1; }
        </style>
    ',
]);

$flashMessages = [
    'user_created' => 'User created.',
    'user_updated' => 'User updated.',
    'user_deleted' => 'User deleted.',
];
?>

<?php if ($flash !== null && isset($flashMessages[$flash])): ?>
<script>document.addEventListener('DOMContentLoaded', () => toastr.success("<?= View::e($flashMessages[$flash]) ?>"));</script>
<?php endif; ?>

<section class="px-6 mt-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-black">Team</h2>
        <button onclick="openNewUserModal()" class="glass rounded-full px-4 py-2 text-xs font-bold flex items-center gap-2 active:scale-95 transition-transform">
            <i data-lucide="plus" class="w-4 h-4 text-blue-500"></i> New
        </button>
    </div>

    <div class="grid grid-cols-3 gap-3">
        <div class="glass p-3 rounded-2xl text-center">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black">Admins</p>
            <h3 class="text-2xl font-black mt-1"><?= (int) $totalAdmins ?></h3>
        </div>
        <div class="glass p-3 rounded-2xl text-center">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black">Drivers</p>
            <h3 class="text-2xl font-black mt-1 text-blue-500"><?= (int) $totalDrivers ?></h3>
        </div>
        <div class="glass p-3 rounded-2xl text-center">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black">Partners</p>
            <h3 class="text-2xl font-black mt-1 text-emerald-500"><?= (int) $totalPartners ?></h3>
        </div>
    </div>
</section>

<section class="px-6 mt-6">
    <div class="space-y-2">
        <?php foreach ($users as $user): ?>
            <?php
                $avatar = $user->profilePhotoPath
                    ? '/SRMT/' . ltrim($user->profilePhotoPath, '/')
                    : 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($user->name);
                $userJson = json_encode([
                    'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
                    'phone' => $user->phone, 'role' => $user->role,
                ], JSON_HEX_APOS | JSON_HEX_QUOT);
            ?>
            <div class="glass p-3 rounded-2xl flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <img src="<?= View::e($avatar) ?>" class="w-10 h-10 rounded-full border border-white/10 object-cover" alt="">
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
            <h3 class="text-lg font-black text-white">New member</h3>
            <p class="text-[9px] text-blue-500 font-bold uppercase">Create account</p>
        </div>
        <button onclick="closeAllModals()" class="text-zinc-600"><i data-lucide="x-circle"></i></button>
    </div>

    <form action="/SRMT/public/admin/user-create.php" method="POST" class="space-y-4">
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black">Full name</label>
            <input type="text" name="name" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" required>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black">Email</label>
            <input type="email" name="email" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" required>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black">Password</label>
            <div class="flex gap-2 mt-1">
                <input type="text" name="password" id="genPass" class="flex-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white" required>
                <button type="button" onclick="generatePass('genPass')" class="glass px-4 rounded-xl text-blue-500"><i data-lucide="wand-2" class="w-4 h-4"></i></button>
            </div>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black">Phone</label>
            <input type="text" name="phone" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" required>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black">Role</label>
            <select name="role" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" required>
                <option value="">Choose…</option>
                <option value="1">Admin</option>
                <option value="2">Driver</option>
                <option value="3">Partner</option>
            </select>
        </div>
        <button type="submit" class="w-full bg-blue-600 rounded-xl py-3 font-bold text-sm">Create</button>
    </form>
</div>

<!-- Edit user -->
<div class="modal-os" id="editUserModal">
    <div class="flex justify-between items-start mb-6">
        <div>
            <h3 class="text-lg font-black text-white">Edit member</h3>
            <p class="text-[9px] text-blue-500 font-bold uppercase">Update account</p>
        </div>
        <button onclick="closeAllModals()" class="text-zinc-600"><i data-lucide="x-circle"></i></button>
    </div>

    <form action="/SRMT/public/admin/user-edit.php" method="POST" class="space-y-4">
        <input type="hidden" name="id" id="editId">
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black">Full name</label>
            <input type="text" name="name" id="editName" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" required>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black">Email</label>
            <input type="email" name="email" id="editEmail" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" required>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black">Phone</label>
            <input type="text" name="phone" id="editPhone" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1">
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black">Role</label>
            <select name="role" id="editRole" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" required>
                <option value="1">Admin</option>
                <option value="2">Driver</option>
                <option value="3">Partner</option>
            </select>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black">New password (optional)</label>
            <input type="text" name="password" placeholder="Leave blank to keep current" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1">
        </div>
        <button type="submit" class="w-full bg-blue-600 rounded-xl py-3 font-bold text-sm">Save</button>
    </form>
</div>
