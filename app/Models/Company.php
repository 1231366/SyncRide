<?php

declare(strict_types=1);

namespace App\Models;

final class Company
{
    public function __construct(
        public readonly int    $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $createdAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id:        (int)    $row['id'],
            name:      (string) $row['name'],
            slug:      (string) $row['slug'],
            createdAt: (string) ($row['created_at'] ?? ''),
        );
    }
}
