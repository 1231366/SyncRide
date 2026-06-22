<?php
/** @var string $from */
/** @var string $to */
/** @var ?string $supplier */
/** @var ?int $driverId */
/** @var array<string> $suppliers */
/** @var array<App\Models\User> $drivers */
/** @var array{rows:array<array<string,mixed>>,totals:array<string,mixed>,by_supplier:array<string,array<string,mixed>>,by_driver:array<string,array<string,mixed>>} $report */
/** @var float  $totalExpenses */
/** @var float  $netProfit */
/** @var array<App\Models\Expense> $expenses */
/** @var array<string> $categoryLabels */
/** @var array<float>  $categoryValues */
/** @var ?string $flash */

use App\Http\View;

View::layout('layouts.admin', [
    'title'  => 'Financial — SyncRide OS',
    'active' => 'financial',
    'extraScripts' => '
        <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
        <script>
            function openExpenseModal() {
                document.getElementById("modalOverlay").classList.add("active");
                document.getElementById("expenseModal").classList.add("active");
            }
            function closeModal() {
                document.getElementById("modalOverlay").classList.remove("active");
                document.getElementById("expenseModal").classList.remove("active");
            }
            document.addEventListener("DOMContentLoaded", () => {
                const labels = ' . json_encode($categoryLabels) . ';
                const values = ' . json_encode($categoryValues) . ';
                if (labels.length > 0) {
                    new ApexCharts(document.querySelector("#expenseChart"), {
                        series: values, labels: labels,
                        chart: { type: "donut", height: 220 },
                        legend: { labels: { colors: "#a1a1aa" } },
                        colors: ["#3b82f6","#10b981","#f59e0b","#ef4444","#8b5cf6","#ec4899"],
                        stroke: { width: 0 },
                        plotOptions: { pie: { donut: { size: "70%" } } }
                    }).render();
                }
            });
        </script>
    ',
]);

$flashMessages = ['created' => t('fin.expense_logged'), 'deleted' => t('fin.expense_removed')];
?>

<?php if ($flash !== null && isset($flashMessages[$flash])): ?>
<script>document.addEventListener('DOMContentLoaded', () => toastr.success("<?= View::e($flashMessages[$flash]) ?>"));</script>
<?php endif; ?>

<section class="px-4 md:px-6 mt-6 max-w-7xl mx-auto">
    <div class="mb-5">
        <h2 class="text-xl font-black mb-4"><?= t('fin.title') ?></h2>
        <form method="GET" class="glass rounded-2xl p-4 flex flex-col gap-3">
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                <div class="flex flex-col gap-1">
                    <span class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.from') ?></span>
                    <input type="date" name="from" value="<?= View::e($from) ?>" class="bg-white/5 border border-white/10 rounded-xl px-3 py-2.5 text-xs text-white w-full" style="min-height:40px">
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.to') ?></span>
                    <input type="date" name="to" value="<?= View::e($to) ?>" class="bg-white/5 border border-white/10 rounded-xl px-3 py-2.5 text-xs text-white w-full" style="min-height:40px">
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.supplier') ?></span>
                    <select name="supplier" class="bg-white/5 border border-white/10 rounded-xl px-3 py-2.5 text-xs text-white w-full" style="min-height:40px">
                        <option value=""><?= t('fin.all_suppliers') ?></option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= View::e($s) ?>" <?= $supplier === $s ? 'selected' : '' ?>><?= View::e($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.driver') ?></span>
                    <select name="driver" class="bg-white/5 border border-white/10 rounded-xl px-3 py-2.5 text-xs text-white w-full" style="min-height:40px">
                        <option value="0"><?= t('fin.all_drivers') ?></option>
                        <?php foreach ($drivers as $d): ?>
                            <option value="<?= (int) $d->id ?>" <?= $driverId === (int) $d->id ? 'selected' : '' ?>><?= View::e($d->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex gap-2 justify-end">
                <button type="submit" class="glass rounded-xl px-4 text-xs font-bold flex items-center gap-2 text-blue-400" style="min-height:44px;touch-action:manipulation"><i data-lucide="filter" class="w-3.5 h-3.5"></i> <?= t('fin.apply') ?></button>
                <a href="/SRMT/public/admin/financial-export.php?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&supplier=<?= urlencode((string) $supplier) ?>&driver=<?= (int) $driverId ?>" class="glass rounded-xl px-4 text-xs font-bold flex items-center gap-2 text-emerald-400" style="min-height:44px"><i data-lucide="download" class="w-3.5 h-3.5"></i> CSV</a>
            </div>
        </form>
    </div>

    <?php $tot = $report['totals']; ?>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="glass p-4 rounded-2xl">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.services') ?></p>
            <h3 class="text-xl font-black mt-1"><?= (int) $tot['count'] ?></h3>
        </div>
        <div class="glass p-4 rounded-2xl">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.revenue') ?></p>
            <h3 class="text-xl font-black mt-1 text-emerald-400">€<?= number_format((float) $tot['revenue'], 2) ?></h3>
        </div>
        <div class="glass p-4 rounded-2xl">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.driver_cost') ?></p>
            <h3 class="text-xl font-black mt-1" style="color:#38bdf8">€<?= number_format((float) $tot['driver_cost'], 2) ?></h3>
        </div>
        <div class="glass p-4 rounded-2xl">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.margin') ?></p>
            <h3 class="text-xl font-black mt-1 <?= ((float) $tot['margin']) >= 0 ? 'text-emerald-400' : 'text-red-500' ?>">€<?= number_format((float) $tot['margin'], 2) ?></h3>
        </div>
        <div class="glass p-4 rounded-2xl">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.net') ?></p>
            <h3 class="text-xl font-black mt-1 <?= $netProfit >= 0 ? 'text-blue-400' : 'text-red-500' ?>">€<?= number_format($netProfit, 2) ?></h3>
            <p class="text-[9px] text-zinc-500 mt-1"><?= t('fin.margin') ?> − <?= t('fin.expenses') ?></p>
        </div>
    </div>

    <!-- Sub-totais por fornecedor e por motorista -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-6">
        <?php
        $subTable = static function (string $title, array $bucket): void {
            echo '<div class="glass p-4 rounded-2xl"><p class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-2">' . View::e($title) . '</p>';
            if ($bucket === []) {
                echo '<p class="text-xs text-zinc-600">—</p></div>';
                return;
            }
            echo '<div class="overflow-x-auto"><table class="w-full text-[11px]"><thead class="text-zinc-500"><tr class="text-left"><th class="py-1 pr-3"></th><th class="py-1 text-center pr-3">#</th><th class="py-1 text-right pr-3">' . t('fin.revenue') . '</th><th class="py-1 text-right">' . t('fin.margin') . '</th></tr></thead><tbody>';
            foreach ($bucket as $name => $v) {
                echo '<tr class="border-t border-white/5"><td class="py-1.5 font-bold">' . View::e((string) $name) . '</td>'
                    . '<td class="py-1.5 text-center text-zinc-400">' . (int) $v['count'] . '</td>'
                    . '<td class="py-1.5 text-right text-emerald-400">€' . number_format((float) $v['revenue'], 2) . '</td>'
                    . '<td class="py-1.5 text-right ' . (((float) $v['margin']) >= 0 ? 'text-emerald-400' : 'text-red-400') . '">€' . number_format((float) $v['margin'], 2) . '</td></tr>';
            }
            echo '</tbody></table></div></div>';
        };
        $subTable(t('fin.by_supplier_t'), $report['by_supplier']);
        $subTable(t('fin.by_driver_t'),   $report['by_driver']);
        ?>
    </div>

    <!-- Detalhe dos serviços -->
    <div class="glass rounded-2xl overflow-hidden mb-8">
        <div class="overflow-x-auto max-h-[520px]">
            <table class="w-full text-[11px]">
                <thead class="bg-white/5 text-zinc-400 sticky top-0">
                    <tr class="text-left">
                        <th class="px-3 py-2 font-black"><?= t('import.col_date') ?></th>
                        <th class="px-3 py-2 font-black"><?= t('import.col_client') ?></th>
                        <th class="px-3 py-2 font-black"><?= t('import.col_route') ?></th>
                        <th class="px-3 py-2 font-black"><?= t('import.col_supplier') ?></th>
                        <th class="px-3 py-2 font-black"><?= t('fin.driver') ?></th>
                        <th class="px-3 py-2 font-black text-right"><?= t('fin.revenue') ?></th>
                        <th class="px-3 py-2 font-black text-right" style="color:#38bdf8"><?= t('fin.driver_cost') ?></th>
                        <th class="px-3 py-2 font-black text-right"><?= t('fin.margin') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report['rows'] as $r): ?>
                        <tr class="border-t border-white/5">
                            <td class="px-3 py-2 whitespace-nowrap"><?= View::e((string) $r['serviceDate']) ?> <span class="text-zinc-500"><?= View::e(substr((string) $r['serviceStartTime'], 0, 5)) ?></span></td>
                            <td class="px-3 py-2"><?= View::e((string) ($r['NomeCliente'] ?? '')) ?></td>
                            <td class="px-3 py-2 text-zinc-400"><?= View::e((string) $r['serviceStartPoint']) ?> <span class="text-zinc-600">→</span> <?= View::e((string) $r['serviceTargetPoint']) ?></td>
                            <td class="px-3 py-2 text-zinc-400"><?= View::e((string) ($r['supplier'] ?? '—')) ?></td>
                            <td class="px-3 py-2"><?= View::e((string) ($r['driver_name'] ?? '—')) ?></td>
                            <td class="px-3 py-2 text-right text-emerald-400"><?= $r['total_price'] !== null ? '€' . number_format((float) $r['total_price'], 2) : '—' ?></td>
                            <td class="px-3 py-2 text-right" style="color:#38bdf8"><?= $r['valor_motorista'] !== null ? '€' . number_format((float) $r['valor_motorista'], 2) : '—' ?></td>
                            <td class="px-3 py-2 text-right <?= ((float) $r['margin']) >= 0 ? 'text-emerald-400' : 'text-red-400' ?>">€<?= number_format((float) $r['margin'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if ($report['rows'] === []): ?>
                        <tr><td colspan="8" class="px-3 py-6 text-center text-zinc-500"><?= t('fin.no_services') ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($categoryLabels !== []): ?>
    <div class="glass p-5 rounded-[24px] mb-6">
        <p class="text-[10px] font-black uppercase tracking-widest text-zinc-500 mb-2"><?= t('fin.by_category') ?></p>
        <div id="expenseChart"></div>
    </div>
    <?php endif; ?>

    <div class="flex justify-between items-center mb-3">
        <h3 class="text-sm font-black uppercase tracking-widest text-zinc-400"><?= t('fin.expenses') ?></h3>
        <button onclick="openExpenseModal()" class="glass rounded-full px-4 py-1.5 text-xs font-bold flex items-center gap-2">
            <i data-lucide="plus" class="w-3.5 h-3.5 text-blue-500"></i> <?= t('fin.new') ?>
        </button>
    </div>

    <div class="space-y-2">
        <?php foreach ($expenses as $expense): ?>
            <div class="glass p-3 rounded-2xl flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-bold"><?= View::e($expense->description) ?></h4>
                    <p class="text-[10px] text-zinc-500 mt-1">
                        <?= View::e($expense->category) ?> • <?= View::e($expense->date) ?>
                        <?php if ($expense->filePath !== null): ?>
                            • <a href="<?= View::e($expense->filePath) ?>" target="_blank" class="text-blue-400">proof</a>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm font-black text-red-400">€<?= number_format($expense->amount, 2) ?></span>
                    <a href="/SRMT/public/admin/save-expense.php?action=delete&id=<?= (int) $expense->id ?>" onclick="return confirm('Delete this expense?')" class="w-11 h-11 glass rounded-full flex items-center justify-center text-red-500/60 hover:text-red-500 transition-colors" style="touch-action:manipulation"><i data-lucide="trash-2" class="w-4 h-4"></i></a>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if ($expenses === []): ?>
            <div class="glass p-6 rounded-2xl text-center text-zinc-500 text-xs"><?= t('fin.no_expenses') ?></div>
        <?php endif; ?>
    </div>
</section>

<div class="modal-overlay" id="modalOverlay" onclick="closeModal()"></div>
<div class="modal-os" id="expenseModal">
    <div class="flex justify-between items-start mb-6">
        <h3 class="text-lg font-black text-white"><?= t('fin.new_expense') ?></h3>
        <button onclick="closeModal()" class="text-zinc-600"><i data-lucide="x-circle"></i></button>
    </div>
    <form action="/SRMT/public/admin/save-expense.php" method="POST" enctype="multipart/form-data" class="space-y-4">
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.category') ?></label>
            <select name="category" required class="w-full mt-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white">
                <option value=""><?= t('fin.choose') ?></option>
                <option value="Fuel"><?= t('fin.fuel') ?></option>
                <option value="Maintenance"><?= t('fin.maintenance') ?></option>
                <option value="Insurance"><?= t('fin.insurance') ?></option>
                <option value="Tolls"><?= t('fin.tolls') ?></option>
                <option value="Parking"><?= t('fin.parking') ?></option>
                <option value="Other"><?= t('fin.other') ?></option>
            </select>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.description') ?></label>
            <input type="text" name="description" required class="w-full mt-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.amount') ?></label>
                <input type="number" name="amount" step="0.01" min="0.01" required class="w-full mt-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white">
            </div>
            <div>
                <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.date') ?></label>
                <input type="date" name="date" required class="w-full mt-1 bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm text-white">
            </div>
        </div>
        <div>
            <label class="text-[9px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.proof') ?></label>
            <input type="file" name="proof" accept="image/*,application/pdf" class="w-full mt-1 text-xs text-zinc-300">
        </div>
        <button type="submit" class="w-full bg-blue-600 rounded-xl py-3 font-bold text-sm"><?= t('fin.save') ?></button>
    </form>
</div>
