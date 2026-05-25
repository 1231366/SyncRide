<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Repositories\ExpenseRepository;
use App\Repositories\LogRepository;
use App\Repositories\ServiceRepository;
use PDO;

/**
 * Monthly P&L view: estimated revenue (rides × flat fee), expenses by
 * category, net profit, and an expenses CRUD. Filter by ?month=YYYY-MM.
 */
final class FinancialController extends BaseController
{
    private const REVENUE_PER_RIDE = 15.0; // legacy estimate
    private const UPLOAD_DIR = __DIR__ . '/../../../../public/uploads/expenses/';
    private const UPLOAD_URL = '/SRMT/public/uploads/expenses/';

    private ExpenseRepository $expenses;
    private LogRepository     $logs;
    private ServiceRepository $services;

    public function __construct()
    {
        $this->expenses = ExpenseRepository::default();
        $this->logs     = LogRepository::default();
        $this->services = ServiceRepository::default();
    }

    /** GET /admin/financial.php?month=YYYY-MM */
    public function index(): void
    {
        $monthFilter = $this->input('month') ?: date('Y-m');
        $year  = (int) substr((string) $monthFilter, 0, 4);
        $month = (int) substr((string) $monthFilter, 5, 2);
        $firstDay = sprintf('%04d-%02d-01', $year, $month);
        $lastDay  = date('Y-m-t', strtotime($firstDay));

        $rideCount       = count($this->services->byDateRange($firstDay, $lastDay));
        $estimatedRevenue = $rideCount * self::REVENUE_PER_RIDE;
        $totalExpenses   = $this->expenses->totalForMonth(sprintf('%04d-%02d', $year, $month));
        $netProfit       = $estimatedRevenue - $totalExpenses;
        $expenses        = $this->expenses->byDateRange($firstDay, $lastDay);

        $byCategory = [];
        foreach ($expenses as $expense) {
            $byCategory[$expense->category] = ($byCategory[$expense->category] ?? 0.0) + $expense->amount;
        }

        $this->view('admin.financial.index', [
            'monthFilter'      => $monthFilter,
            'rideCount'        => $rideCount,
            'estimatedRevenue' => $estimatedRevenue,
            'totalExpenses'    => $totalExpenses,
            'netProfit'        => $netProfit,
            'expenses'         => $expenses,
            'categoryLabels'   => array_keys($byCategory),
            'categoryValues'   => array_values($byCategory),
            'flash'            => $_GET['success'] ?? null,
        ]);
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
