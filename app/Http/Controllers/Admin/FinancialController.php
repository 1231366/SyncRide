<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Repositories\ExpenseRepository;
use App\Repositories\FinancialReportRepository;
use App\Repositories\LogRepository;
use App\Repositories\UserRepository;

/**
 * Gestão financeira: relatório com valores REAIS dos serviços (receita,
 * custo do motorista, margem) filtrável por intervalo de datas, fornecedor
 * e motorista — com sub-totais e exportação CSV — além do módulo de despesas.
 */
final class FinancialController extends BaseController
{
    private const UPLOAD_DIR = __DIR__ . '/../../../../public/uploads/expenses/';
    private const UPLOAD_URL = '/SRMT/public/uploads/expenses/';

    private ExpenseRepository         $expenses;
    private LogRepository             $logs;
    private FinancialReportRepository $report;
    private UserRepository            $users;

    public function __construct()
    {
        $this->expenses = ExpenseRepository::default();
        $this->logs     = LogRepository::default();
        $this->report   = FinancialReportRepository::default();
        $this->users    = UserRepository::default();
    }

    /** GET /admin/financial.php?from=YYYY-MM-DD&to=...&supplier=...&driver=... */
    public function index(): void
    {
        [$from, $to, $supplier, $driverId] = $this->filters();

        $report        = $this->report->report($from, $to, $supplier, $driverId);
        $expenses      = $this->expenses->byDateRange($from, $to);
        $totalExpenses = array_sum(array_map(static fn($e): float => $e->amount, $expenses));
        $netProfit     = $report['totals']['margin'] - $totalExpenses;

        $byCategory = [];
        foreach ($expenses as $expense) {
            $byCategory[$expense->category] = ($byCategory[$expense->category] ?? 0.0) + $expense->amount;
        }

        // ── Analytics ────────────────────────────────────────────────────
        $byDay  = $this->report->byDay($from, $to, $supplier, $driverId);
        $byHour = $this->report->byHour($from, $to, $supplier, $driverId);
        $byType = $this->report->byType($from, $to, $supplier, $driverId);

        // Period-over-period comparison
        $fromDt     = new \DateTimeImmutable($from);
        $toDt       = new \DateTimeImmutable($to);
        $periodDays = max(1, (int) $fromDt->diff($toDt)->days + 1);
        $prevTo     = $fromDt->modify('-1 day')->format('Y-m-d');
        $prevFrom   = (new \DateTimeImmutable($prevTo))->modify('-' . ($periodDays - 1) . ' days')->format('Y-m-d');
        $prevTotals = $this->report->summary($prevFrom, $prevTo, $supplier, $driverId);

        // Derived KPIs
        $tot = $report['totals'];
        $marginPct     = (float) $tot['revenue'] > 0
            ? round((float) $tot['margin'] / (float) $tot['revenue'] * 100, 1) : 0.0;
        $prevMarginPct = $prevTotals['revenue'] > 0
            ? round($prevTotals['margin'] / $prevTotals['revenue'] * 100, 1) : 0.0;
        $netMarginPct  = (float) $tot['revenue'] > 0
            ? round($netProfit / (float) $tot['revenue'] * 100, 1) : 0.0;
        $avgTicket     = (int) $tot['count'] > 0
            ? round((float) $tot['revenue'] / (int) $tot['count'], 2) : 0.0;
        $avgPerDay     = round((float) $tot['revenue'] / $periodDays, 2);

        $pct = static fn (float $cur, float $prev): ?float =>
            $prev != 0.0 ? round(($cur - $prev) / abs($prev) * 100, 1) : null;

        $pctRevenue    = $pct((float) $tot['revenue'], $prevTotals['revenue']);
        $pctMargin     = $pct((float) $tot['margin'],  $prevTotals['margin']);
        $pctCount      = $pct((float) $tot['count'],   (float) $prevTotals['count']);
        $diffMarginPct = $prevTotals['revenue'] > 0
            ? round($marginPct - $prevMarginPct, 1) : null;

        // Phase 3 — projection for current open period
        $today           = date('Y-m-d');
        $isCurrentPeriod = $from <= $today && $today <= $to;
        $projRevenue     = null;
        $projMargin      = null;
        if ($isCurrentPeriod && (float) $tot['revenue'] > 0) {
            $daysElapsed = max(1, (int) $fromDt->diff(new \DateTimeImmutable($today))->days + 1);
            $projRevenue = round((float) $tot['revenue'] / $daysElapsed * $periodDays, 2);
            $projMargin  = round((float) $tot['margin']  / $daysElapsed * $periodDays, 2);
        }

        $this->view('admin.financial.index', [
            'from'            => $from,
            'to'              => $to,
            'supplier'        => $supplier,
            'driverId'        => $driverId,
            'suppliers'       => $this->report->suppliers(),
            'drivers'         => $this->users->byRole(User::ROLE_DRIVER),
            'report'          => $report,
            'totalExpenses'   => $totalExpenses,
            'netProfit'       => $netProfit,
            'expenses'        => $expenses,
            'categoryLabels'  => array_keys($byCategory),
            'categoryValues'  => array_values($byCategory),
            'flash'           => $_GET['success'] ?? null,
            // analytics
            'byDay'           => $byDay,
            'byHour'          => $byHour,
            'byType'          => $byType,
            'prevTotals'      => $prevTotals,
            'prevFrom'        => $prevFrom,
            'prevTo'          => $prevTo,
            'periodDays'      => $periodDays,
            'marginPct'       => $marginPct,
            'prevMarginPct'   => $prevMarginPct,
            'netMarginPct'    => $netMarginPct,
            'avgTicket'       => $avgTicket,
            'avgPerDay'       => $avgPerDay,
            'pctRevenue'      => $pctRevenue,
            'pctMargin'       => $pctMargin,
            'pctCount'        => $pctCount,
            'diffMarginPct'   => $diffMarginPct,
            'isCurrentPeriod' => $isCurrentPeriod,
            'projRevenue'     => $projRevenue,
            'projMargin'      => $projMargin,
        ]);
    }

    /** GET /admin/financial-report.php — standalone print/PDF view */
    public function report(): void
    {
        [$from, $to, $supplier, $driverId] = $this->filters();

        $report        = $this->report->report($from, $to, $supplier, $driverId);
        $expenses      = $this->expenses->byDateRange($from, $to);
        $totalExpenses = array_sum(array_map(static fn($e): float => $e->amount, $expenses));
        $netProfit     = $report['totals']['margin'] - $totalExpenses;

        $byCategory = [];
        foreach ($expenses as $e) {
            $byCategory[$e->category] = ($byCategory[$e->category] ?? 0.0) + $e->amount;
        }

        $byDay  = $this->report->byDay($from, $to, $supplier, $driverId);
        $byHour = $this->report->byHour($from, $to, $supplier, $driverId);
        $byType = $this->report->byType($from, $to, $supplier, $driverId);

        $fromDt     = new \DateTimeImmutable($from);
        $toDt       = new \DateTimeImmutable($to);
        $periodDays = max(1, (int) $fromDt->diff($toDt)->days + 1);
        $prevTo     = $fromDt->modify('-1 day')->format('Y-m-d');
        $prevFrom   = (new \DateTimeImmutable($prevTo))->modify('-' . ($periodDays - 1) . ' days')->format('Y-m-d');
        $prevTotals = $this->report->summary($prevFrom, $prevTo, $supplier, $driverId);

        $tot           = $report['totals'];
        $marginPct     = (float) $tot['revenue'] > 0
            ? round((float) $tot['margin'] / (float) $tot['revenue'] * 100, 1) : 0.0;
        $netMarginPct  = (float) $tot['revenue'] > 0
            ? round($netProfit / (float) $tot['revenue'] * 100, 1) : 0.0;
        $avgTicket     = (int) $tot['count'] > 0
            ? round((float) $tot['revenue'] / (int) $tot['count'], 2) : 0.0;
        $avgPerDay     = round((float) $tot['revenue'] / $periodDays, 2);

        $driverName = t('fin.all_drivers');
        if ($driverId !== null) {
            foreach ($this->users->byRole(User::ROLE_DRIVER) as $u) {
                if ((int) $u->id === $driverId) { $driverName = $u->name; break; }
            }
        }

        extract([
            'from'           => $from,
            'to'             => $to,
            'supplier'       => $supplier,
            'driverName'     => $driverName,
            'report'         => $report,
            'totalExpenses'  => $totalExpenses,
            'netProfit'      => $netProfit,
            'expenses'       => $expenses,
            'categoryLabels' => array_keys($byCategory),
            'categoryValues' => array_values($byCategory),
            'byDay'          => $byDay,
            'byHour'         => $byHour,
            'byType'         => $byType,
            'prevTotals'     => $prevTotals,
            'periodDays'     => $periodDays,
            'marginPct'      => $marginPct,
            'netMarginPct'   => $netMarginPct,
            'avgTicket'      => $avgTicket,
            'avgPerDay'      => $avgPerDay,
        ]);
        require __DIR__ . '/../../../../resources/views/admin/financial/report.php';
        exit;
    }

    /** GET /admin/financial-export.php — exporta o relatório filtrado em CSV. */
    public function export(): never
    {
        [$from, $to, $supplier, $driverId] = $this->filters();
        $report = $this->report->report($from, $to, $supplier, $driverId);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $filename = "financeiro_{$from}_{$to}.csv";
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");

        $out = fopen('php://output', 'wb');
        fprintf($out, "\xEF\xBB\xBF"); // BOM p/ Excel abrir UTF-8 corretamente
        fputcsv($out, ['Data', 'Hora', 'Cliente', 'Recolha', 'Entrega', 'Tipo', 'Fornecedor', 'Motorista', 'Receita', 'Custo motorista', 'Margem']);
        foreach ($report['rows'] as $r) {
            fputcsv($out, [
                $r['serviceDate'],
                substr((string) $r['serviceStartTime'], 0, 5),
                $r['NomeCliente'],
                $r['serviceStartPoint'],
                $r['serviceTargetPoint'],
                ((int) $r['serviceType']) === 0 ? 'Shared' : 'Private',
                $r['supplier'],
                $r['driver_name'],
                number_format((float) ($r['total_price'] ?? 0), 2, '.', ''),
                number_format((float) ($r['valor_motorista'] ?? 0), 2, '.', ''),
                number_format((float) $r['margin'], 2, '.', ''),
            ]);
        }
        $t = $report['totals'];
        fputcsv($out, []);
        fputcsv($out, ['', '', '', '', '', '', '', 'TOTAIS', number_format($t['revenue'], 2, '.', ''), number_format($t['driver_cost'], 2, '.', ''), number_format($t['margin'], 2, '.', '')]);
        fclose($out);
        exit;
    }

    /**
     * Lê os filtros do pedido; intervalo por omissão = mês corrente.
     * @return array{0:string,1:string,2:?string,3:?int}
     */
    private function filters(): array
    {
        $from = (string) $this->input('from', '');
        $to   = (string) $this->input('to', '');
        if ($from === '' || !strtotime($from)) {
            $from = date('Y-m-01');
        }
        if ($to === '' || !strtotime($to)) {
            $to = date('Y-m-t');
        }
        $supplier = ($s = (string) $this->input('supplier', '')) !== '' ? $s : null;
        $driverId = (int) $this->input('driver', 0) ?: null;

        return [$from, $to, $supplier, $driverId];
    }

    /**
     * POST /admin/financial-recalc.php — recalcula receita/custo dos serviços do
     * intervalo pelo preçário atual (backfill). Só preenche buracos.
     */
    public function recalculate(): never
    {
        $this->requirePost();
        [$from, $to] = $this->filters();

        $result = \App\Services\PricingRecalculator::default()->range($from, $to);
        $this->logs->record(
            "Admin recalculated pricing {$from}..{$to}: {$result['updated']}/{$result['scanned']} services updated"
        );

        $this->redirect(
            '/SRMT/public/admin/financial.php?from=' . urlencode($from) . '&to=' . urlencode($to)
            . '&success=recalculated&n=' . $result['updated']
        );
    }

    /** POST /admin/save-expense.php — create or delete an expense. */
    public function save(): never
    {
        if (($_GET['action'] ?? '') === 'delete' && isset($_GET['id'])) {
            $this->destroy((int) $_GET['id']);
        }

        $this->requirePost();

        $payload = [
            'category'    => $this->input('category'),
            'description' => $this->input('description'),
            'amount'      => (float) ($this->input('amount') ?? 0),
            'date'        => $this->input('date'),
        ];

        foreach (['category', 'description', 'date'] as $required) {
            if (empty($payload[$required])) {
                $this->abort(422, "Missing field: {$required}");
            }
        }
        if ($payload['amount'] <= 0) {
            $this->abort(422, 'Amount must be greater than zero.');
        }

        if (isset($_FILES['proof']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {
            $payload['file_path'] = $this->storeProof($_FILES['proof']);
        }

        $expense = $this->expenses->create($payload);
        $this->logs->record("Admin logged expense #{$expense->id} ({$expense->category}, €" . number_format($expense->amount, 2) . ')');

        $this->redirect('/SRMT/public/admin/financial.php?success=created');
    }

    private function destroy(int $id): never
    {
        $expense = $this->expenses->find($id);
        if ($expense === null) {
            $this->abort(404, 'Expense not found.');
        }
        if ($expense->filePath !== null) {
            $absolute = $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($expense->filePath, '/');
            if (is_file($absolute)) {
                @unlink($absolute);
            }
        }
        $this->expenses->delete($id);
        $this->logs->record("Admin deleted expense #{$id}");
        $this->redirect('/SRMT/public/admin/financial.php?success=deleted');
    }

    /** @param array<string,mixed> $file */
    private function storeProof(array $file): ?string
    {
        if (!is_dir(self::UPLOAD_DIR)) {
            @mkdir(self::UPLOAD_DIR, 0755, true);
        }
        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'pdf', 'webp'], true)) {
            return null;
        }
        $filename = 'expense_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $extension;
        if (!move_uploaded_file($file['tmp_name'], self::UPLOAD_DIR . $filename)) {
            return null;
        }
        return self::UPLOAD_URL . $filename;
    }
}
