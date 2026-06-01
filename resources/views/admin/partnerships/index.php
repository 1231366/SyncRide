<?php
/**
 * @var array<array<string,mixed>>          $partnerships
 * @var array<array<string,mixed>>          $activePartners
 * @var array<App\Models\Company>           $allCompanies
 * @var int                                 $myCompanyId
 * @var array{sent:array,received:array}    $stats
 * @var string                              $filterFrom
 * @var string                              $filterTo
 * @var int|null                            $filterPartner
 * @var string|null                         $flash
 */

use App\Http\View;

ob_start();
?>
<script>
function openInviteModal() {
    document.getElementById("modalOverlay").classList.add("active");
    document.getElementById("inviteModal").classList.add("active");
}
function closeAllModals() {
    document.querySelectorAll(".modal-os").forEach(m => m.classList.remove("active"));
    document.getElementById("modalOverlay").classList.remove("active");
}
function applyFilters() {
    const from    = document.getElementById("filterFrom").value;
    const to      = document.getElementById("filterTo").value;
    const partner = document.getElementById("filterPartner").value;
    const url = new URL(window.location);
    if (from)    url.searchParams.set("from", from);    else url.searchParams.delete("from");
    if (to)      url.searchParams.set("to", to);        else url.searchParams.delete("to");
    if (partner) url.searchParams.set("partner_id", partner); else url.searchParams.delete("partner_id");
    window.location = url.toString();
}
function setPreset(preset) {
    const now   = new Date();
    let from, to;
    if (preset === "this_month") {
        from = new Date(now.getFullYear(), now.getMonth(), 1);
        to   = new Date(now.getFullYear(), now.getMonth() + 1, 0);
    } else if (preset === "last_month") {
        from = new Date(now.getFullYear(), now.getMonth() - 1, 1);
        to   = new Date(now.getFullYear(), now.getMonth(), 0);
    } else if (preset === "this_year") {
        from = new Date(now.getFullYear(), 0, 1);
        to   = new Date(now.getFullYear(), 11, 31);
    }
    const fmt = d => d.toISOString().slice(0,10);
    document.getElementById("filterFrom").value = fmt(from);
    document.getElementById("filterTo").value   = fmt(to);
    applyFilters();
}
async function respondPartnership(id, action) {
    const res = await fetch("/SRMT/public/admin/partnership-respond.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "id=" + id + "&action=" + action
    });
    const d = await res.json();
    if (d.success) location.reload();
    else alert(d.error || "Error");
}
async function invitePartner(e) {
    e.preventDefault();
    const sel = document.getElementById("inviteTarget");
    const id  = sel.value;
    if (!id) return;
    const btn = document.getElementById("inviteBtn");
    btn.disabled = true;
    btn.textContent = "<?= t('partnerships.sending') ?>";
    const res = await fetch("/SRMT/public/admin/partnership-invite.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body: "target_company_id=" + id
    });
    const d = await res.json();
    btn.disabled = false;
    btn.textContent = "<?= t('partnerships.send_invite') ?>";
    if (d.success) { closeAllModals(); location.reload(); }
    else alert(d.error || "Error");
}
</script>
<?php $partnershipsScripts = ob_get_clean(); ?>
<?php
View::layout('layouts.admin', [
    'title'        => t('partnerships.title') . ' — SyncRide OS',
    'active'       => 'partnerships',
    'extraScripts' => $partnershipsScripts,
]);

$myId = (int) $myCompanyId;

$sentTotal = array_sum(array_column($stats['sent'],     'count'));
$recvTotal = array_sum(array_column($stats['received'], 'count'));
?>

<section class="px-5 mt-6">

    <div class="flex justify-between items-center mb-4">
        <div>
            <h2 class="text-xl font-black"><?= t('partnerships.title') ?></h2>
            <p class="text-[10px] text-zinc-500 font-semibold mt-0.5"><?= t('partnerships.subtitle') ?></p>
        </div>
        <button onclick="openInviteModal()" class="glass rounded-full px-4 py-2 text-xs font-bold flex items-center gap-2 active:scale-95 transition-transform">
            <i data-lucide="plus" class="w-4 h-4 text-blue-500"></i> <?= t('partnerships.invite_btn') ?>
        </button>
    </div>

    <!-- Filters -->
    <div class="glass p-4 rounded-2xl mb-4">
        <p class="text-[9px] uppercase tracking-widest text-zinc-500 font-black mb-3">
            <i data-lucide="filter" class="w-3 h-3 inline mr-1"></i><?= t('partnerships.filter_title') ?>
        </p>
        <div class="flex flex-wrap gap-2 mb-3">
            <button onclick="setPreset('this_month')" class="text-[10px] font-bold px-3 py-1 rounded-full glass active:scale-95 transition-transform <?= (date('Y-m-01') === $filterFrom && date('Y-m-t') === $filterTo) ? 'text-blue-500' : 'text-zinc-500' ?>">
                <?= t('partnerships.preset_this_month') ?>
            </button>
            <button onclick="setPreset('last_month')" class="text-[10px] font-bold px-3 py-1 rounded-full glass active:scale-95 transition-transform text-zinc-500">
                <?= t('partnerships.preset_last_month') ?>
            </button>
            <button onclick="setPreset('this_year')" class="text-[10px] font-bold px-3 py-1 rounded-full glass active:scale-95 transition-transform text-zinc-500">
                <?= t('partnerships.preset_this_year') ?>
            </button>
        </div>
        <div class="flex gap-2 flex-wrap">
            <input type="date" id="filterFrom" value="<?= htmlspecialchars($filterFrom) ?>"
                class="flex-1 min-w-0 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold">
            <input type="date" id="filterTo" value="<?= htmlspecialchars($filterTo) ?>"
                class="flex-1 min-w-0 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold">
            <select id="filterPartner"
                class="flex-1 min-w-0 rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold">
                <option value=""><?= t('partnerships.filter_all_partners') ?></option>
                <?php foreach ($activePartners as $ap): ?>
                <option value="<?= (int) $ap['partner_id'] ?>" <?= $filterPartner === (int) $ap['partner_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $ap['partner_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <button onclick="applyFilters()" class="rounded-xl bg-blue-600 text-white px-4 py-2 text-xs font-bold active:scale-95 transition-transform">
                <?= t('partnerships.filter_apply') ?>
            </button>
        </div>
    </div>

    <!-- Stats -->
    <?php if ($sentTotal > 0 || $recvTotal > 0): ?>
    <div class="grid grid-cols-2 gap-3 mb-5">

        <!-- Sent -->
        <div class="glass p-4 rounded-2xl">
            <p class="text-[9px] uppercase tracking-widest text-zinc-500 font-black mb-1">
                <i data-lucide="send" class="w-3 h-3 inline mr-1 text-orange-500"></i><?= t('partnerships.sent_to') ?>
            </p>
            <div class="flex items-end gap-2 mb-3">
                <span class="text-2xl font-black text-orange-500"><?= $sentTotal ?></span>
                <span class="text-[10px] text-zinc-500 font-semibold mb-0.5"><?= t('partnerships.trips') ?></span>
            </div>
            <?php if (empty($stats['sent'])): ?>
            <p class="text-zinc-500 text-xs"><?= t('partnerships.none_yet') ?></p>
            <?php else: ?>
            <?php foreach ($stats['sent'] as $s): ?>
            <div class="flex justify-between items-center py-1.5 border-b border-white/10 last:border-0">
                <span class="text-xs font-semibold truncate"><?= View::e((string) $s['to_company_name']) ?></span>
                <span class="text-orange-500 font-black text-sm"><?= (int) $s['count'] ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Received -->
        <div class="glass p-4 rounded-2xl">
            <p class="text-[9px] uppercase tracking-widest text-zinc-500 font-black mb-1">
                <i data-lucide="inbox" class="w-3 h-3 inline mr-1 text-blue-500"></i><?= t('partnerships.received_from') ?>
            </p>
            <div class="flex items-end gap-2 mb-3">
                <span class="text-2xl font-black text-blue-500"><?= $recvTotal ?></span>
                <span class="text-[10px] text-zinc-500 font-semibold mb-0.5"><?= t('partnerships.trips') ?></span>
            </div>
            <?php if (empty($stats['received'])): ?>
            <p class="text-zinc-500 text-xs"><?= t('partnerships.none_yet') ?></p>
            <?php else: ?>
            <?php foreach ($stats['received'] as $r): ?>
            <div class="flex justify-between items-center py-1.5 border-b border-white/10 last:border-0">
                <span class="text-xs font-semibold truncate"><?= View::e((string) $r['from_company_name']) ?></span>
                <span class="text-blue-500 font-black text-sm"><?= (int) $r['count'] ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
    <?php else: ?>
    <div class="glass p-5 rounded-2xl text-center mb-5">
        <p class="text-zinc-500 text-sm font-semibold"><?= t('partnerships.no_stats_period') ?></p>
        <p class="text-zinc-400 text-xs mt-1"><?= t('partnerships.no_stats_desc') ?></p>
    </div>
    <?php endif; ?>

    <!-- Partnership list -->
    <?php if (empty($partnerships)): ?>
    <div class="glass p-8 rounded-2xl text-center">
        <i data-lucide="handshake" class="w-12 h-12 mx-auto text-zinc-400 mb-3"></i>
        <p class="font-bold text-zinc-500"><?= t('partnerships.empty_title') ?></p>
        <p class="text-xs text-zinc-400 mt-1"><?= t('partnerships.empty_desc') ?></p>
        <button onclick="openInviteModal()" class="mt-4 glass rounded-full px-5 py-2 text-xs font-bold text-blue-500">
            <?= t('partnerships.first_invite') ?>
        </button>
    </div>
    <?php else: ?>
    <p class="text-[9px] uppercase tracking-widest text-zinc-500 font-black mb-2 mt-1">
        <i data-lucide="building-2" class="w-3 h-3 inline mr-1"></i><?= t('partnerships.list_title') ?>
    </p>
    <div class="flex flex-col gap-3">
    <?php foreach ($partnerships as $p):
        $isA         = (int) $p['company_id_a'] === $myId;
        $partnerName = View::e((string) ($isA ? $p['company_b_name'] : $p['company_a_name']));
        $pId         = (int) $p['id'];
        $status      = (string) $p['status'];
        $iAmInvited  = !$isA && $status === 'pending';
    ?>
    <div class="glass p-4 rounded-2xl flex items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-full bg-blue-500/10 flex items-center justify-center flex-shrink-0">
                <i data-lucide="building-2" class="w-4 h-4 text-blue-500"></i>
            </div>
            <div class="min-w-0">
                <p class="font-bold text-sm leading-tight truncate"><?= $partnerName ?></p>
                <p class="text-[9px] text-zinc-500 font-semibold mt-0.5">
                    <?= $isA ? t('partnerships.you_invited') : t('partnerships.invited_you') ?> &middot;
                    <?= htmlspecialchars(substr((string) $p['created_at'], 0, 10)) ?>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <?php if ($status === 'active'): ?>
                <span class="text-[9px] font-black uppercase tracking-wider text-emerald-500 bg-emerald-500/10 border border-emerald-500/20 rounded-full px-2 py-0.5"><?= t('partnerships.status_active') ?></span>
            <?php elseif ($iAmInvited): ?>
                <button onclick="respondPartnership(<?= $pId ?>, 'accept')"
                    class="text-xs font-bold px-3 py-1 rounded-full bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 active:scale-95 transition-transform">
                    <?= t('partnerships.accept') ?>
                </button>
                <button onclick="respondPartnership(<?= $pId ?>, 'reject')"
                    class="text-xs font-bold px-3 py-1 rounded-full bg-red-500/10 text-red-500 border border-red-500/20 active:scale-95 transition-transform">
                    <?= t('partnerships.reject') ?>
                </button>
            <?php elseif ($status === 'pending'): ?>
                <span class="text-[9px] font-black uppercase tracking-wider text-amber-500 bg-amber-500/10 border border-amber-500/20 rounded-full px-2 py-0.5"><?= t('partnerships.status_pending') ?></span>
            <?php else: ?>
                <span class="text-[9px] font-black uppercase tracking-wider text-red-400 bg-red-400/10 border border-red-400/20 rounded-full px-2 py-0.5"><?= t('partnerships.status_rejected') ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

</section>

<div id="modalOverlay" class="modal-overlay" onclick="closeAllModals()"></div>

<div id="inviteModal" class="modal-os">
    <div class="flex justify-between items-center mb-5">
        <h3 class="font-black text-lg"><?= t('partnerships.modal_title') ?></h3>
        <button onclick="closeAllModals()" class="glass w-8 h-8 rounded-full flex items-center justify-center border-0">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
    <p class="text-xs text-zinc-500 mb-4"><?= t('partnerships.modal_desc') ?></p>
    <form onsubmit="invitePartner(event)">
        <select id="inviteTarget" name="target_company_id"
            class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm font-semibold mb-4">
            <option value=""><?= t('partnerships.modal_select') ?></option>
            <?php foreach ($allCompanies as $c):
                $cid = (int) $c->id;
                if ($cid === $myId) continue;
            ?>
            <option value="<?= $cid ?>"><?= View::e((string) $c->name) ?></option>
            <?php endforeach; ?>
        </select>
        <button id="inviteBtn" type="submit"
            class="w-full py-3 rounded-xl bg-blue-600 text-white font-bold text-sm active:scale-95 transition-transform">
            <?= t('partnerships.send_invite') ?>
        </button>
    </form>
</div>
