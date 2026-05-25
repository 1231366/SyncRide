<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Expense;
use App\Support\Database;
use PDO;
use RuntimeException;

final class ExpenseRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function default(): self
    {
        return new self(Database::connection());
    }

    /** @return array<Expense> */
    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM Expenses ORDER BY date DESC, id DESC');
        return array_map(Expense::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<Expense> */
    public function byDateRange(string $from, string $to): array
    {
        $stmt = $this->db->prepare('SELECT * FROM Expenses WHERE date BETWEEN :from AND :to ORDER BY date DESC');
        $stmt->execute(['from' => $from, 'to' => $to]);
        return array_map(Expense::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function totalForMonth(string $yearMonth): float
    {
        $stmt = $this->db->prepare('
            SELECT COALESCE(SUM(amount), 0) FROM Expenses
            WHERE DATE_FORMAT(date, "%Y-%m") = :ym
        ');
        $stmt->execute(['ym' => $yearMonth]);
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
        $stmt = $this->db->prepare('
            INSERT INTO Expenses (category, description, amount, date, file_path)
            VALUES (:category, :description, :amount, :date, :file)
        ');
        $stmt->execute([
            'category'    => $data['category'],
            'description' => $data['description'],
            'amount'      => (float) $data['amount'],
            'date'        => $data['date'],
            'file'        => $data['file_path'] ?? null,
        ]);
        return $this->find((int) $this->db->lastInsertId())
            ?? throw new RuntimeException('ExpenseRepository::create — reload failed');
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM Expenses WHERE id = :id')->execute(['id' => $id]);
    }
}
