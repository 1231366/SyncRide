<?php

declare(strict_types=1);

namespace App\Models;

final class Vehicle
{
    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE   = 1;

    public function __construct(
        public readonly int $id,
        public readonly string $brand,
        public readonly string $model,
        public readonly string $licensePlate,
        public readonly ?string $inspectionDate,
        public readonly ?string $insuranceDate,
        public readonly int $status,
        public readonly ?string $photoPath,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id:             (int) $row['id'],
            brand:          (string) $row['brand'],
            model:          (string) $row['model'],
            licensePlate:   (string) $row['license_plate'],
            inspectionDate: $row['inspection_date'] ?? null,
            insuranceDate:  $row['insurance_date'] ?? null,
            status:         (int) $row['status'],
            photoPath:      $row['photo_path'] ?? null,
        );
    }

    public function isActive(): bool { return $this->status === self::STATUS_ACTIVE; }
    public function fullName(): string { return "{$this->brand} {$this->model}"; }
}
