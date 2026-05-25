<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Immutable User snapshot.
 *
 * Maps to rows in the `Users` table. Column names in the legacy schema
 * are kept (email, password, role, name, phone, …) and the constructor
 * accepts the raw associative array PDO returns.
 */
final class User
{
    public const ROLE_SUPER_ADMIN = 0;
    public const ROLE_ADMIN       = 1;
    public const ROLE_DRIVER      = 2;
    public const ROLE_PARTNER     = 3;

    public function __construct(
        public readonly int     $id,
        public readonly string  $email,
        public readonly string  $name,
        public readonly int     $role,
        public readonly ?string $phone,
        public readonly ?string $profilePhotoPath,
        public readonly ?int    $assignedVehicleId,
        public readonly ?int    $secondaryRole,
        public readonly ?int    $companyId,
    ) {
    }

    /** Build from the row PDO::FETCH_ASSOC returns. */
    public static function fromRow(array $row): self
    {
        return new self(
            id:                (int) $row['id'],
            email:             (string) $row['email'],
            name:              (string) $row['name'],
            role:              (int) $row['role'],
            phone:             isset($row['phone']) ? (string) $row['phone'] : null,
            profilePhotoPath:  $row['profile_photo_path'] ?? null,
            assignedVehicleId: isset($row['assigned_vehicle_id']) ? (int) $row['assigned_vehicle_id'] : null,
            secondaryRole:     isset($row['secondary_role']) ? (int) $row['secondary_role'] : null,
            companyId:         isset($row['company_id']) ? (int) $row['company_id'] : null,
        );
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_SUPER_ADMIN => 'Super-admin',
            self::ROLE_ADMIN       => 'Admin',
            self::ROLE_DRIVER      => 'Driver',
            self::ROLE_PARTNER     => 'Partner',
            default                => 'Unknown',
        };
    }
}
