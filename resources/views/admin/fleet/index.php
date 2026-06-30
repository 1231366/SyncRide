<?php
/** @var array<array<string,mixed>> $vehicles */
/** @var array<App\Models\User>     $drivers */
/** @var int    $totalVehicles */
/** @var int    $activeCount */
/** @var int    $alertCount */
/** @var ?string $flash */

use App\Http\View;

View::layout('layouts.admin', [
    'title'        => 'Fleet — SyncRide OS',
    'active'       => 'fleet',
    'extraScripts' => '
        <script>
            var SR_FLEET = { edit: "' . t('fleet.edit_vehicle') . '", newV: "' . t('fleet.new_vehicle') . '" };
            function openVehicleModal(v) {
                document.getElementById("modalTitle").innerText = v ? SR_FLEET.edit : SR_FLEET.newV;
                document.getElementById("vehicleId").value = v?.id ?? "";
                document.getElementById("brandIn").value = v?.brand ?? "";
                document.getElementById("modelIn").value = v?.model ?? "";
                document.getElementById("plateIn").value = v?.license_plate ?? "";
                document.getElementById("inspIn").value = v?.inspection_date ?? "";
                document.getElementById("insuIn").value = v?.insurance_date ?? "";
                document.getElementById("statusIn").value = v?.status ?? 1;
                document.getElementById("existingPhoto").value = v?.photo_path ?? "";
                document.getElementById("driverIn").value = v?.assigned_driver_user_id ?? "";
                document.getElementById("modalOverlay").classList.add("active");
                document.getElementById("vehicleModal").classList.add("active");
            }
            function closeModal() {
                document.getElementById("modalOverlay").classList.remove("active");
                document.getElementById("vehicleModal").classList.remove("active");
            }
        </script>
    ',
]);

$flashMessages = [
    'created' => t('fleet.added'),
    'updated' => t('fleet.updated'),
    'deleted' => t('fleet.removed'),
];
?>

<?php if ($flash !== null && isset($flashMessages[$flash])): ?>
<script>document.addEventListener('DOMContentLoaded', () => toastr.success("<?= View::e($flashMessages[$flash]) ?>"));</script>
<?php endif; ?>

<section class="px-6 mt-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-black"><?= t('fleet.title') ?></h2>
        <button onclick="openVehicleModal(null)" class="glass rounded-full px-4 py-2 text-xs font-bold flex items-center gap-2 active:scale-95">
            <i data-lucide="plus" class="w-4 h-4 text-blue-500"></i> <?= t('fleet.new') ?>
        </button>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="glass p-3 rounded-2xl text-center">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fleet.vehicles') ?></p>
            <h3 class="text-2xl font-black mt-1"><?= (int) $totalVehicles ?></h3>
        </div>
        <div class="glass p-3 rounded-2xl text-center">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fleet.active') ?></p>
            <h3 class="text-2xl font-black mt-1 text-emerald-500"><?= (int) $activeCount ?></h3>
        </div>
        <div class="glass p-3 rounded-2xl text-center">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fleet.alerts') ?></p>
            <h3 class="text-2xl font-black mt-1 <?= $alertCount > 0 ? 'text-amber-500' : 'text-zinc-500' ?>"><?= (int) $alertCount ?></h3>
        </div>
    </div>

    <div class="space-y-2">
        <?php foreach ($vehicles as $v): ?>
            <?php $vehicleJson = json_encode($v, JSON_HEX_APOS | JSON_HEX_QUOT); ?>
            <div class="glass p-4 rounded-2xl">
                <div class="flex items-center gap-4">
                    <?php if (!empty($v['photo_url'])): ?>
                        <img src="<?= View::e($v['photo_url']) ?>" class="w-16 h-16 rounded-2xl object-cover border border-white/10" alt="">
                    <?php else: ?>
                        <div class="w-16 h-16 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center"><i data-lucide="truck" class="w-6 h-6 text-zinc-500"></i></div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <h4 class="text-sm font-bold"><?= View::e($v['brand']) ?> <?= View::e($v['model']) ?></h4>
                            <?php if ($v['alert']): ?><span class="text-[9px] bg-amber-500/20 text-amber-300 px-2 py-0.5 rounded-full font-bold uppercase">Alert</span><?php endif; ?>
                            <?php if ((int) $v['status'] !== 1): ?><span class="text-[9px] bg-zinc-700/40 text-zinc-300 px-2 py-0.5 rounded-full font-bold uppercase"><?= t('fleet.status_inactive') ?></span><?php endif; ?>
                        </div>
                        <p class="text-[10px] text-zinc-400 mt-1"><?= View::e($v['license_plate']) ?> • <?= View::e($v['assigned_driver_name'] ?? t('fleet.unassigned')) ?></p>
                        <p class="text-[9px] text-zinc-500 mt-1">
                            <?= t('fleet.inspection') ?>: <?= View::e($v['inspection_date'] ?? '—') ?> • <?= t('fleet.insurance') ?>: <?= View::e($v['insurance_date'] ?? '—') ?>
                        </p>
                    </div>
                    <div class="flex flex-col gap-2">
                        <button onclick='openVehicleModal(<?= $vehicleJson ?>)' class="w-8 h-8 glass rounded-full flex items-center justify-center text-zinc-500"><i data-lucide="edit-3" class="w-3.5 h-3.5"></i></button>
                        <a href="/SRMT/public/admin/save-vehicle.php?action=delete&id=<?= (int) $v['id'] ?>" onclick="return confirm('Delete this vehicle?')" class="w-8 h-8 glass rounded-full flex items-center justify-center text-red-500/60"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($vehicles === []): ?>
            <div class="glass p-6 rounded-2xl text-center text-zinc-500 text-xs"><?= t('fleet.no_vehicles') ?></div>
        <?php endif; ?>
    </div>
</section>

<div class="modal-overlay" id="modalOverlay" onclick="closeModal()"></div>
<div class="modal-os" id="vehicleModal">
    <div class="flex justify-between items-start mb-6">
        <h3 id="modalTitle" class="text-lg font-black text-white"><?= t('fleet.new_vehicle') ?></h3>
        <button onclick="closeModal()" class="text-zinc-600"><i data-lucide="x-circle"></i></button>
    </div>

    <form action="/SRMT/public/admin/save-vehicle.php" method="POST" enctype="multipart/form-data" class="space-y-4">
        <input type="hidden" name="vehicle_id" id="vehicleId">
        <input type="hidden" name="existing_photo_path" id="existingPhoto">

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fleet.brand') ?></label>
                <input type="text" name="brand" id="brandIn" required class="w-full mt-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white">
            </div>
            <div>
                <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fleet.model') ?></label>
                <input type="text" name="model" id="modelIn" required class="w-full mt-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white">
            </div>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fleet.plate') ?></label>
            <input type="text" name="license_plate" id="plateIn" required class="w-full mt-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white uppercase">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fleet.inspection') ?></label>
                <input type="date" name="inspection_date" id="inspIn" class="w-full mt-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white">
            </div>
            <div>
                <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fleet.insurance') ?></label>
                <input type="date" name="insurance_date" id="insuIn" class="w-full mt-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white">
            </div>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fleet.status') ?></label>
            <select name="status" id="statusIn" class="w-full mt-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white">
                <option value="1"><?= t('fleet.status_active') ?></option>
                <option value="0"><?= t('fleet.status_inactive') ?></option>
            </select>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fleet.assigned_driver') ?></label>
            <select name="assigned_driver_id" id="driverIn" class="w-full mt-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white">
                <option value=""><?= t('fleet.unassigned') ?></option>
                <?php foreach ($drivers as $driver): ?>
                    <option value="<?= (int) $driver->id ?>"><?= View::e($driver->name) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fleet.photo') ?></label>
            <input type="file" name="vehicle_photo" accept="image/*" class="w-full mt-1 text-xs text-zinc-300">
        </div>
        <button type="submit" class="w-full bg-blue-600 rounded-xl py-3 font-bold text-sm"><?= t('fleet.save') ?></button>
    </form>
</div>
