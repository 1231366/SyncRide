<?php
use App\Http\View;

/**
 * @var string                        $card
 * @var array<string>                 $cards
 * @var array<array<string,mixed>>    $rows
 * @var array<string,int>             $totals
 * @var string|null                   $flash
 * @var string|null                   $error
 */

$cardLabels = [
    'mts'                    => t('pricing.card_mts'),
    'prtours_retail'         => t('pricing.card_prtours'),
    'driver_company_vehicle' => t('pricing.card_driver_cv'),
    'driver_own_vehicle'     => t('pricing.card_driver_ov'),
];

View::layout('layouts.admin', [
    'title'  => t('pricing.title') . ' — SyncRide OS',
    'active' => 'pricing',
    'extraHead' => '
        <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
        <style>
        .rate-table td, .rate-table th { vertical-align: middle; }
        .rate-table td[contenteditable="true"] { cursor: text; outline: none; }
        .rate-table td[contenteditable="true"]:focus { background: rgba(6,182,212,.1); border-radius: 6px; }
        .card-tab { cursor: pointer; padding: 8px 18px; border-radius: 12px; font-size: 11px; font-weight: 800; border: 1px solid rgba(255,255,255,.08); transition: all .15s; }
        .card-tab.active { background: rgba(6,182,212,.15); color: #06b6d4; border-color: rgba(6,182,212,.35); }
        .card-tab:not(.active) { color: #71717a; }
        .card-tab:not(.active):hover { background: rgba(255,255,255,.05); color: #a1a1aa; }
        /* ── Mobile column hiding ───────────────────────── */
        @media (max-width: 640px) {
            .rate-table .col-dist,
            .rate-table .col-tier,
            .rate-table .col-hx,
            .rate-table .col-valid { display: none !important; }
            /* Remove min-width forcing scroll on mobile */
            .rate-table { min-width: 0 !important; }
        }
        </style>
    ',
    'extraScripts' => '
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    ',
]);

ob_start();
?>

<div class="p-4 md:p-8 max-w-7xl mx-auto">

<?php if ($flash): ?>
<div class="glass rounded-xl px-4 py-3 mb-4 text-emerald-400 text-sm font-bold flex items-center gap-2">
    <i data-lucide="check-circle" class="w-4 h-4"></i> <?= View::e($flash) ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="glass rounded-xl px-4 py-3 mb-4 text-red-400 text-sm font-bold flex items-center gap-2">
    <i data-lucide="alert-circle" class="w-4 h-4"></i> <?= View::e($error) ?>
</div>
<?php endif; ?>

<!-- Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-black text-white"><?= t('pricing.title') ?></h1>
        <p class="text-[10px] text-zinc-500 uppercase tracking-widest mt-0.5"><?= t('pricing.subtitle') ?></p>
    </div>
    <button onclick="openAddModal()" class="glass px-4 py-3 rounded-xl text-xs font-bold text-emerald-400 flex items-center gap-2 hover:bg-emerald-400/10 transition-colors" style="min-height:44px;touch-action:manipulation">
        <i data-lucide="plus" class="w-3.5 h-3.5"></i> <?= t('pricing.add_rate') ?>
    </button>
</div>

<!-- Search bar -->
<div class="relative mb-5">
    <i data-lucide="search" class="w-4 h-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-zinc-500 pointer-events-none"></i>
    <input type="text" id="rateSearch" placeholder="<?= t('pricing.search_ph') ?>"
        class="w-full glass rounded-xl pl-10 pr-4 py-3 text-sm text-white placeholder-zinc-500 outline-none border border-white/10 focus:border-cyan-500/40"
        oninput="filterRates(this.value)">
</div>

<!-- Card tabs -->
<div class="flex flex-wrap gap-2 mb-6">
    <?php foreach ($cards as $c): ?>
    <a href="?card=<?= urlencode($c) ?>" class="card-tab <?= $c === $card ? 'active' : '' ?>">
        <?= View::e($cardLabels[$c] ?? $c) ?>
        <span class="ms-1 text-[9px] opacity-60">(<?= $totals[$c] ?>)</span>
    </a>
    <?php endforeach; ?>
</div>

<!-- Table -->
<div class="glass rounded-2xl overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm rate-table min-w-[640px]" id="rateTable">
        <thead>
            <tr class="text-left text-[9px] uppercase tracking-widest text-zinc-500 border-b border-white/10">
                <th class="px-4 py-3"><?= t('pricing.resort') ?></th>
                <th class="px-4 py-3 col-dist"><?= t('pricing.distributor') ?></th>
                <th class="px-4 py-3"><?= t('pricing.vehicle') ?></th>
                <th class="px-4 py-3 text-center col-tier"><?= t('pricing.pax_tier') ?></th>
                <th class="px-4 py-3 text-right"><?= t('pricing.price') ?></th>
                <th class="px-4 py-3 text-right col-hx"><?= t('pricing.hotel_extra') ?></th>
                <th class="px-4 py-3 col-valid"><?= t('pricing.valid_until') ?></th>
                <th class="px-4 py-3 w-20"></th>
            </tr>
        </thead>
        <tbody>
        <tr id="rateNoResults" style="display:none">
            <td colspan="8" class="px-4 py-8 text-center text-zinc-500 text-xs"><?= t('pricing.no_results') ?></td>
        </tr>
        <?php if (empty($rows)): ?>
            <tr>
                <td colspan="8" class="px-4 py-10 text-center text-zinc-500 text-xs">
                    <?= t('pricing.no_rates') ?>
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($rows as $r): ?>
            <tr class="border-b border-white/5 hover:bg-white/[.03] transition-colors rate-row" data-id="<?= (int) $r['id'] ?>">
                <td class="px-4 py-2.5 font-medium" contenteditable="true" data-field="resort"><?= View::e((string) ($r['resort'] ?? '')) ?></td>
                <td class="px-4 py-2.5 text-zinc-400 col-dist" contenteditable="true" data-field="distributor_code"><?= View::e((string) ($r['distributor_code'] ?? '')) ?></td>
                <td class="px-4 py-2.5 text-zinc-400" contenteditable="true" data-field="vehicle_label"><?= View::e((string) ($r['vehicle_label'] ?? '')) ?></td>
                <td class="px-4 py-2.5 text-center text-zinc-400 col-tier" contenteditable="true" data-field="pax_tier"><?= $r['pax_tier'] !== null ? (int) $r['pax_tier'] : '' ?></td>
                <td class="px-4 py-2.5 text-right text-emerald-400 font-bold" contenteditable="true" data-field="price"><?= $r['price'] !== null ? number_format((float) $r['price'], 2) : '' ?></td>
                <td class="px-4 py-2.5 text-right text-blue-400 col-hx" contenteditable="true" data-field="hotel_extra"><?= $r['hotel_extra'] !== null ? number_format((float) $r['hotel_extra'], 2) : '' ?></td>
                <td class="px-4 py-2.5 text-zinc-500 text-xs col-valid" contenteditable="true" data-field="valid_until"><?= View::e((string) ($r['valid_until'] ?? '')) ?></td>
                <td class="px-4 py-2.5">
                    <div class="flex gap-1.5 justify-end">
                        <button onclick="saveRow(this)" class="w-10 h-10 glass rounded-xl flex items-center justify-center text-emerald-400 hover:bg-emerald-400/10 transition-colors cursor-pointer touch-manipulation" title="<?= t('pricing.save') ?>" style="touch-action:manipulation">
                            <i data-lucide="save" class="w-4 h-4"></i>
                        </button>
                        <button onclick="deleteRow(this)" class="w-10 h-10 glass rounded-xl flex items-center justify-center text-red-400/70 hover:bg-red-400/10 transition-colors cursor-pointer" title="<?= t('pricing.delete') ?>" style="touch-action:manipulation">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
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

<!-- Nota sobre supplier -->
<p class="text-[10px] text-zinc-600 mt-3"><?= t('pricing.supplier_note') ?>: <span class="text-zinc-400"><?= View::e(array_values(array_unique(array_filter(array_column($rows, 'supplier'))))[0] ?? '—') ?></span> (<?= t('pricing.supplier_from_card') ?>)</p>

</div>

<!-- Modal: adicionar tarifa -->
<div class="modal fade" id="addRateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background:#111;border:1px solid rgba(255,255,255,.1);border-radius:20px">
            <div class="modal-header border-b border-white/10 px-6 py-4">
                <h5 class="modal-title font-black text-white text-base"><?= t('pricing.add_rate') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('pricing.resort') ?></label>
                        <input id="nr_resort" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" placeholder="ex. Lisbon">
                    </div>
                    <div>
                        <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('pricing.distributor') ?></label>
                        <input id="nr_dist" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" placeholder="ex. LOVP (vazio = wildcard)">
                    </div>
                    <div>
                        <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('pricing.vehicle') ?></label>
                        <input id="nr_veh" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" placeholder="ex. Standard">
                    </div>
                    <div>
                        <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('pricing.pax_tier') ?></label>
                        <input id="nr_tier" type="number" min="1" max="20" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1" placeholder="vazio = sem escalão">
                    </div>
                    <div>
                        <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('pricing.price') ?> (€)</label>
                        <input id="nr_price" type="number" step="0.01" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1">
                    </div>
                    <div>
                        <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('pricing.hotel_extra') ?> (€)</label>
                        <input id="nr_hx" type="number" step="0.01" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1">
                    </div>
                    <div>
                        <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('pricing.valid_until') ?></label>
                        <input id="nr_valid" type="date" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white mt-1">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-t border-white/10 px-6 py-4">
                <button type="button" class="glass px-5 py-2.5 rounded-xl text-sm font-bold" data-bs-dismiss="modal"><?= t('pricing.cancel') ?></button>
                <button type="button" onclick="submitAddRate()" class="bg-emerald-600 px-5 py-2.5 rounded-xl text-sm font-bold text-white ms-2"><?= t('pricing.add_rate') ?></button>
            </div>
        </div>
    </div>
</div>

<script>
const CURRENT_CARD = <?= json_encode($card) ?>;

function filterRates(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('#rateTable .rate-row').forEach(tr => {
        const text = Array.from(tr.querySelectorAll('[data-field]'))
            .map(td => td.innerText.toLowerCase()).join(' ');
        tr.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
    const visible = document.querySelectorAll('#rateTable .rate-row:not([style*="none"])').length;
    const noData  = document.getElementById('rateNoResults');
    if (noData) noData.style.display = visible === 0 ? '' : 'none';
}

function openAddModal() {
    ['nr_resort','nr_dist','nr_veh','nr_tier','nr_price','nr_hx','nr_valid'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
    new bootstrap.Modal(document.getElementById('addRateModal')).show();
}

function rowData(btn) {
    const tr = btn.closest('tr');
    const get = f => tr.querySelector('[data-field="'+f+'"]')?.innerText?.trim() ?? '';
    return {
        id:               parseInt(tr.dataset.id),
        card:             CURRENT_CARD,
        resort:           get('resort'),
        distributor_code: get('distributor_code'),
        vehicle_label:    get('vehicle_label'),
        pax_tier:         get('pax_tier'),
        price:            get('price').replace(',','.'),
        hotel_extra:      get('hotel_extra').replace(',','.'),
        valid_until:      get('valid_until'),
    };
}

function saveRow(btn) {
    const data = rowData(btn);
    const fd = new FormData();
    Object.entries(data).forEach(([k, v]) => fd.append(k, v));
    fetch('/SRMT/public/admin/pricing-save.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) toastr.success('<?= t('pricing.saved') ?>');
            else toastr.error(d.error || 'Erro');
        }).catch(() => toastr.error('Falha de rede'));
}

function deleteRow(btn) {
    if (!confirm('<?= t('pricing.delete_confirm') ?>')) return;
    const id = parseInt(btn.closest('tr').dataset.id);
    const fd = new FormData(); fd.append('id', id);
    fetch('/SRMT/public/admin/pricing-delete.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) { btn.closest('tr').remove(); toastr.success('<?= t('pricing.deleted') ?>'); }
            else toastr.error(d.error || 'Erro');
        }).catch(() => toastr.error('Falha de rede'));
}

function submitAddRate() {
    const fd = new FormData();
    fd.append('card',             CURRENT_CARD);
    fd.append('resort',           document.getElementById('nr_resort').value.trim());
    fd.append('distributor_code', document.getElementById('nr_dist').value.trim());
    fd.append('vehicle_label',    document.getElementById('nr_veh').value.trim());
    fd.append('pax_tier',         document.getElementById('nr_tier').value.trim());
    fd.append('price',            document.getElementById('nr_price').value.trim());
    fd.append('hotel_extra',      document.getElementById('nr_hx').value.trim());
    fd.append('valid_until',      document.getElementById('nr_valid').value.trim());
    fetch('/SRMT/public/admin/pricing-save.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            if (d.success) { toastr.success('<?= t('pricing.added') ?>'); setTimeout(() => location.reload(), 700); }
            else toastr.error(d.error || 'Erro');
        }).catch(() => toastr.error('Falha de rede'));
}
</script>

<?php
$content = ob_get_clean();
echo $content;
