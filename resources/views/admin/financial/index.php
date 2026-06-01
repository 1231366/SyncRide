<?php
/** @var string $monthFilter */
/** @var int    $rideCount */
/** @var float  $estimatedRevenue */
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

<section class="px-6 mt-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-black"><?= t('fin.title') ?></h2>
        <form method="GET" class="flex items-center gap-2">
            <input type="month" name="month" value="<?= View::e($monthFilter) ?>" onchange="this.form.submit()"
                   class="bg-white/5 border border-white/10 rounded-xl px-3 py-2 text-xs text-white">
        </form>
    </div>

    <div class="grid grid-cols-3 gap-3 mb-6">
        <div class="glass p-4 rounded-2xl">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.revenue') ?></p>
            <h3 class="text-xl font-black mt-1 text-emerald-400">€<?= number_format($estimatedRevenue, 0) ?></h3>
            <p class="text-[9px] text-zinc-500 mt-1"><?= (int) $rideCount ?> rides × €15</p>
        </div>
        <div class="glass p-4 rounded-2xl">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.expenses') ?></p>
            <h3 class="text-xl font-black mt-1 text-red-400">€<?= number_format($totalExpenses, 2) ?></h3>
        </div>
        <div class="glass p-4 rounded-2xl">
            <p class="text-[8px] uppercase tracking-widest text-zinc-500 font-black"><?= t('fin.net') ?></p>
            <h3 class="text-xl font-black mt-1 <?= $netProfit >= 0 ? 'text-blue-400' : 'text-red-500' ?>">€<?= number_format($netProfit, 2) ?></h3>
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
                    <a href="/SRMT/public/admin/save-expense.php?action=delete&id=<?= (int) $expense->id ?>" onclick="return confirm('Delete this expense?')" class="w-8 h-8 glass rounded-full flex items-center justify-center text-red-500/60"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i></a>
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
