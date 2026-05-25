<?php

declare(strict_types=1);

namespace App\Models;

final class Expense
{
    public function __construct(
        public readonly int $id,
        public readonly string $category,
        public readonly string $description,
        public readonly float $amount,
        public readonly string $date,
        public readonly ?string $filePath,
        public readonly ?string $createdAt,
        public readonly ?int    $companyId,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id:          (int) $row['id'],
            category:    (string) $row['category'],
            description: (string) $row['description'],
            amount:      (float) $row['amount'],
            date:        (string) $row['date'],
            filePath:    $row['file_path'] ?? null,
            createdAt:   $row['created_at'] ?? null,
            companyId:   isset($row['company_id']) ? (int) $row['company_id'] : null,
        );
    }
}
