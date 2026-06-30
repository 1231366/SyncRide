<?php
/** @var array<array<string,mixed>> $recentBatches */
/** @var ?string $flash */
/** @var ?string $error */

use App\Http\View;

View::layout('layouts.admin', [
    'title'  => 'Importar serviços — SyncRide OS',
    'active' => 'import',
]);
?>

<section class="px-6 mt-6 max-w-6xl mx-auto pb-24">
    <div class="flex justify-between items-center mb-1">
        <h2 class="text-xl font-black flex items-center gap-2"><i data-lucide="file-spreadsheet" class="w-5 h-5 text-emerald-400"></i> <?= t('import.title') ?></h2>
    </div>
    <p class="text-[11px] text-zinc-500 mb-5"><?= t('import.subtitle') ?></p>

    <!-- ── Upload ─────────────────────────────────────────────────── -->
    <div class="glass p-6 rounded-[24px] mb-6" id="uploadCard">
        <label id="dropZone" for="fileInput"
               class="flex flex-col items-center justify-center gap-3 border-2 border-dashed border-white/15 rounded-2xl py-10 cursor-pointer hover:border-emerald-400/50 transition">
            <i data-lucide="upload-cloud" class="w-10 h-10 text-zinc-500"></i>
            <span class="text-sm font-bold" id="dropLabel"><?= t('import.drop_here') ?></span>
            <span class="text-[10px] text-zinc-500">.xlsx · máx. 10 MB</span>
            <input type="file" id="fileInput" accept=".xlsx" class="hidden">
        </label>
        <div class="flex justify-end mt-4">
            <button id="analyzeBtn" disabled
                    class="glass rounded-full px-6 text-xs font-bold flex items-center gap-2 opacity-50 cursor-not-allowed" style="min-height:44px;touch-action:manipulation">
                <i data-lucide="scan-search" class="w-4 h-4 text-blue-400"></i> <?= t('import.analyze') ?>
            </button>
        </div>
    </div>

    <!-- ── Pré-visualização ───────────────────────────────────────── -->
    <div id="previewSection" class="hidden">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <div class="glass p-4 rounded-2xl"><p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('import.total') ?></p><h3 class="text-2xl font-black mt-1" id="sumTotal">0</h3></div>
            <div class="glass p-4 rounded-2xl"><p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('import.new') ?></p><h3 class="text-2xl font-black mt-1 text-emerald-400" id="sumNew">0</h3></div>
            <div class="glass p-4 rounded-2xl"><p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('import.duplicate') ?></p><h3 class="text-2xl font-black mt-1 text-amber-400" id="sumDup">0</h3></div>
            <div class="glass p-4 rounded-2xl"><p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('import.invalid') ?></p><h3 class="text-2xl font-black mt-1 text-red-400" id="sumInvalid">0</h3></div>
        </div>

        <div class="glass rounded-2xl overflow-hidden mb-4">
            <div class="overflow-x-auto max-h-[460px]">
                <table class="w-full text-[11px]">
                    <thead class="bg-white/5 text-zinc-400 sticky top-0">
                        <tr class="text-left">
                            <th class="px-3 py-2 font-black"></th>
                            <th class="px-3 py-2 font-black"><?= t('import.col_date') ?></th>
                            <th class="px-3 py-2 font-black"><?= t('import.col_client') ?></th>
                            <th class="px-3 py-2 font-black"><?= t('import.col_route') ?></th>
                            <th class="px-3 py-2 font-black text-center">Pax</th>
                            <th class="px-3 py-2 font-black"><?= t('import.col_type') ?></th>
                            <th class="px-3 py-2 font-black"><?= t('import.col_supplier') ?></th>
                            <th class="px-3 py-2 font-black text-right">€</th>
                            <th class="px-3 py-2 font-black text-right" style="color:#38bdf8"><?= t('import.col_driver') ?></th>
                        </tr>
                    </thead>
                    <tbody id="previewBody"></tbody>
                </table>
            </div>
        </div>
        <p class="text-[10px] text-zinc-500 mb-4 hidden" id="truncatedNote"><?= t('import.truncated') ?></p>

        <div class="flex justify-end gap-2">
            <button id="cancelBtn" class="glass rounded-full px-5 text-xs font-bold flex items-center gap-2" style="min-height:44px;touch-action:manipulation"><i data-lucide="x" class="w-4 h-4"></i> <?= t('import.cancel') ?></button>
            <button id="confirmBtn" class="rounded-full px-5 text-xs font-black flex items-center gap-2 bg-emerald-500 text-black hover:bg-emerald-400 transition" style="min-height:44px;touch-action:manipulation">
                <i data-lucide="check" class="w-4 h-4"></i> <span id="confirmLabel"><?= t('import.confirm') ?></span>
            </button>
        </div>
    </div>

    <!-- ── Lotes recentes ─────────────────────────────────────────── -->
    <div class="mt-10">
        <h3 class="text-sm font-black uppercase tracking-widest text-zinc-400 mb-3"><?= t('import.recent') ?></h3>
        <div class="space-y-2" id="recentList">
            <?php foreach ($recentBatches as $b): ?>
                <div class="glass p-3 rounded-2xl flex items-center justify-between" data-batch="<?= (int) $b['id'] ?>">
                    <div>
                        <h4 class="text-sm font-bold flex items-center gap-2"><i data-lucide="file-spreadsheet" class="w-3.5 h-3.5 text-emerald-400"></i> <?= View::e((string) ($b['filename'] ?? 'import.xlsx')) ?></h4>
                        <p class="text-[10px] text-zinc-500 mt-1">
                            <?= View::e((string) $b['created_at']) ?> ·
                            <span class="text-emerald-400"><?= (int) $b['rows_inserted'] ?> <?= t('import.inserted') ?></span>,
                            <?= (int) $b['rows_skipped'] ?> <?= t('import.skipped') ?>
                        </p>
                    </div>
                    <button onclick="undoBatch(<?= (int) $b['id'] ?>)" class="glass rounded-full px-4 text-[11px] font-bold flex items-center gap-1.5 text-amber-400 shrink-0" style="min-height:44px;touch-action:manipulation">
                        <i data-lucide="undo-2" class="w-3.5 h-3.5"></i> <?= t('import.undo') ?>
                    </button>
                </div>
            <?php endforeach; ?>
            <?php if ($recentBatches === []): ?>
                <div class="glass p-6 rounded-2xl text-center text-zinc-500 text-xs" id="noBatches"><?= t('import.no_batches') ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
const $ = (id) => document.getElementById(id);
let pickedFile = null;

const I18N = {
    confirm: <?= json_encode(t('import.confirm')) ?>,
    importing: <?= json_encode(t('import.importing')) ?>,
    analyzing: <?= json_encode(t('import.analyzing')) ?>,
    done: <?= json_encode(t('import.done')) ?>,
    undone: <?= json_encode(t('import.undone')) ?>,
    confirm_undo: <?= json_encode(t('import.confirm_undo')) ?>,
};

// ── File picking ──────────────────────────────────────────────
$('fileInput').addEventListener('change', (e) => setFile(e.target.files[0]));
const dz = $('dropZone');
['dragover','dragenter'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.add('border-emerald-400/60'); }));
['dragleave','drop'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.remove('border-emerald-400/60'); }));
dz.addEventListener('drop', e => { if (e.dataTransfer.files.length) setFile(e.dataTransfer.files[0]); });

function setFile(f) {
    if (!f) return;
    if (!f.name.toLowerCase().endsWith('.xlsx')) { toastr.error('Apenas .xlsx'); return; }
    pickedFile = f;
    $('dropLabel').textContent = f.name;
    const btn = $('analyzeBtn');
    btn.disabled = false; btn.classList.remove('opacity-50','cursor-not-allowed');
}

// ── Analyze (preview) ─────────────────────────────────────────
$('analyzeBtn').addEventListener('click', async () => {
    if (!pickedFile) return;
    const btn = $('analyzeBtn'); btn.disabled = true; btn.innerHTML = I18N.analyzing;
    const fd = new FormData(); fd.append('file', pickedFile);
    try {
        const res = await fetch('/SRMT/public/admin/import-preview.php', {
            method: 'POST', headers: { 'X-CSRF-Token': CSRF, 'X-Requested-With': 'XMLHttpRequest' }, body: fd,
        });
        const data = await res.json();
        if (!data.success) { toastr.error(data.message || 'Erro'); return; }
        renderPreview(data);
    } catch (err) { toastr.error('Falha de rede'); }
    finally { btn.disabled = false; btn.innerHTML = '<i data-lucide="scan-search" class="w-4 h-4 text-blue-400"></i> <?= t('import.analyze') ?>'; if (window.lucide) lucide.createIcons(); }
});

let currentToken = null;
function renderPreview(data) {
    currentToken = data.token;
    $('sumTotal').textContent   = data.summary.total;
    $('sumNew').textContent     = data.summary.new;
    $('sumDup').textContent     = data.summary.duplicate;
    $('sumInvalid').textContent = data.summary.invalid;
    $('truncatedNote').classList.toggle('hidden', !data.truncated);

    const badge = (s) => ({
        'new':       '<span class="px-2 py-0.5 rounded-full bg-emerald-500/15 text-emerald-400 font-bold">novo</span>',
        'duplicate': '<span class="px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-400 font-bold">duplicado</span>',
        'invalid':   '<span class="px-2 py-0.5 rounded-full bg-red-500/15 text-red-400 font-bold">inválido</span>',
    }[s] || s);
    const esc = (v) => (v == null ? '' : String(v).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])));

    $('previewBody').innerHTML = data.rows.map(r => `
        <tr class="border-t border-white/5 ${r.status !== 'new' ? 'opacity-50' : ''}">
            <td class="px-3 py-2">${badge(r.status)}${r.reason ? `<div class="text-[9px] text-zinc-500">${esc(r.reason)}</div>` : ''}</td>
            <td class="px-3 py-2 whitespace-nowrap">${esc(r.date)} <span class="text-zinc-500">${esc(r.time)}</span></td>
            <td class="px-3 py-2">${esc(r.client)}</td>
            <td class="px-3 py-2 text-zinc-400">${esc(r.pickup)} <span class="text-zinc-600">→</span> ${esc(r.dropoff)}</td>
            <td class="px-3 py-2 text-center">${r.pax}</td>
            <td class="px-3 py-2">${r.type === 'Shared' ? '<span class="text-cyan-400">Shared</span>' : 'Private'}</td>
            <td class="px-3 py-2 text-zinc-400">${esc(r.supplier)}</td>
            <td class="px-3 py-2 text-right text-emerald-400 font-bold">${r.price != null ? '€'+r.price : ''}</td>
            <td class="px-3 py-2 text-right" style="color:#38bdf8">${r.driver != null ? '€'+r.driver : ''}</td>
        </tr>`).join('');

    $('confirmLabel').textContent = `${I18N.confirm} (${data.summary.new})`;
    $('confirmBtn').disabled = data.summary.new === 0;
    $('previewSection').classList.remove('hidden');
    $('previewSection').scrollIntoView({ behavior: 'smooth' });
    if (window.lucide) lucide.createIcons();
}

$('cancelBtn').addEventListener('click', () => { $('previewSection').classList.add('hidden'); currentToken = null; });

// ── Confirm (commit) ──────────────────────────────────────────
$('confirmBtn').addEventListener('click', async () => {
    if (!currentToken) return;
    const btn = $('confirmBtn'); btn.disabled = true; const lbl = $('confirmLabel').textContent;
    $('confirmLabel').textContent = I18N.importing;
    const fd = new FormData();
    fd.append('token', currentToken);
    fd.append('filename', pickedFile ? pickedFile.name : 'import.xlsx');
    fd.append('csrf_token', CSRF);
    try {
        const res = await fetch('/SRMT/public/admin/import-commit.php', {
            method: 'POST', headers: { 'X-CSRF-Token': CSRF, 'X-Requested-With': 'XMLHttpRequest' }, body: fd,
        });
        const data = await res.json();
        if (!data.success) { toastr.error(data.message || 'Erro'); $('confirmLabel').textContent = lbl; btn.disabled = false; return; }
        toastr.success(`${I18N.done}: ${data.inserted}`);
        setTimeout(() => location.reload(), 900);
    } catch (err) { toastr.error('Falha de rede'); $('confirmLabel').textContent = lbl; btn.disabled = false; }
});

// ── Undo ──────────────────────────────────────────────────────
async function undoBatch(id) {
    if (!confirm(I18N.confirm_undo)) return;
    const fd = new FormData(); fd.append('batch_id', id); fd.append('csrf_token', CSRF);
    try {
        const res = await fetch('/SRMT/public/admin/import-undo.php', {
            method: 'POST', headers: { 'X-CSRF-Token': CSRF, 'X-Requested-With': 'XMLHttpRequest' }, body: fd,
        });
        const data = await res.json();
        if (!data.success) { toastr.error(data.message || 'Erro'); return; }
        toastr.success(`${I18N.undone}: ${data.deleted}`);
        setTimeout(() => location.reload(), 800);
    } catch (err) { toastr.error('Falha de rede'); }
}
</script>
