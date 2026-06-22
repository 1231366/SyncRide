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

        $this->view('admin.financial.index', [
            'from'           => $from,
            'to'             => $to,
            'supplier'       => $supplier,
            'driverId'       => $driverId,
            'suppliers'      => $this->report->suppliers(),
            'drivers'        => $this->users->byRole(User::ROLE_DRIVER),
            'report'         => $report,
            'totalExpenses'  => $totalExpenses,
            'netProfit'      => $netProfit,
            'expenses'       => $expenses,
            'categoryLabels' => array_keys($byCategory),
            'categoryValues' => array_values($byCategory),
            'flash'          => $_GET['success'] ?? null,
        ]);
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
