<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Expense;
use App\Support\Database;
use App\Support\Session;
use PDO;
use RuntimeException;

final class ExpenseRepository
{
    public function __construct(
        private readonly PDO  $db,
        private readonly ?int $companyId = null,
    ) {
    }

    public static function default(): self
    {
        return new self(Database::connection(), Session::companyId());
    }

    /** @return array<Expense> */
    public function all(): array
    {
        $sql  = 'SELECT * FROM Expenses WHERE 1=1 ' . $this->companyClause('AND') . ' ORDER BY date DESC, id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->companyBindings());
        return array_map(Expense::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<Expense> */
    public function byDateRange(string $from, string $to): array
    {
        $sql  = 'SELECT * FROM Expenses WHERE date BETWEEN :from AND :to ' . $this->companyClause('AND') . ' ORDER BY date DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['from' => $from, 'to' => $to], $this->companyBindings()));
        return array_map(Expense::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function totalForMonth(string $yearMonth): float
    {
        $sql  = 'SELECT COALESCE(SUM(amount), 0) FROM Expenses WHERE DATE_FORMAT(date, "%Y-%m") = :ym ' . $this->companyClause('AND');
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['ym' => $yearMonth], $this->companyBindings()));
        return (float) $stmt->fetchColumn();
    }

    public function find(int $id): ?Expense
    {
        $stmt = $this->db->prepare('SELECT * FROM Expenses WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? Expense::fromRow($row) : null;
    }

    public function create(array $data): Expense
    {
        foreach (['category', 'description', 'amount', 'date'] as $r) {
            if (!isset($data[$r])) {
                throw new RuntimeException("ExpenseRepository::create — missing field: {$r}");
            }
        }
        $cid  = isset($data['company_id']) ? (int) $data['company_id'] : $this->companyId;
        $stmt = $this->db->prepare('
            INSERT INTO Expenses (category, description, amount, date, file_path, company_id)
            VALUES (:category, :description, :amount, :date, :file, :company_id)
        ');
        $stmt->execute([
            'category'    => $data['category'],
            'description' => $data['description'],
            'amount'      => (float) $data['amount'],
            'date'        => $data['date'],
            'file'        => $data['file_path'] ?? null,
            'company_id'  => $cid,
        ]);
        return $this->find((int) $this->db->lastInsertId())
            ?? throw new RuntimeException('ExpenseRepository::create — reload failed');
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM Expenses WHERE id = :id')->execute(['id' => $id]);
    }

    // ------------------------------------------------------------------ helpers

    private function companyClause(string $prefix = 'WHERE'): string
    {
        return $this->companyId !== null ? "{$prefix} company_id = :company_id" : '';
    }

    private function companyBindings(): array
    {
        return $this->companyId !== null ? ['company_id' => $this->companyId] : [];
    }
}
