<?php

declare(strict_types=1);

namespace App\Models;

final class DriverConversation
{
    public const STATUS_OPEN   = 'open';
    public const STATUS_CLOSED = 'closed';

    public function __construct(
        public readonly int $id,
        public readonly int $driverId,
        public readonly ?string $title,
        public readonly string $status,
        public readonly bool $isGeneral,
        public readonly ?string $pinnedAt,
        public readonly ?int $linkedRideId,
        public readonly ?int $createdBy,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id:            (int) $row['id'],
            driverId:      (int) $row['driver_id'],
            title:         $row['title'] ?? null,
            status:        (string) $row['status'],
            isGeneral:     (bool) $row['is_general'],
            pinnedAt:      $row['pinned_at'] ?? null,
            linkedRideId:  isset($row['linked_ride_id']) ? (int) $row['linked_ride_id'] : null,
            createdBy:     isset($row['created_by']) ? (int) $row['created_by'] : null,
            createdAt:     (string) $row['created_at'],
            updatedAt:     (string) $row['updated_at'],
        );
    }

    public function isClosed(): bool { return $this->status === self::STATUS_CLOSED; }
    public function isPinned(): bool { return $this->pinnedAt !== null; }

    /** Label shown in the UI when there's no admin-given title yet. */
    public function displayTitle(): string
    {
        return $this->isGeneral ? 'Geral' : ($this->title ?? 'Novo tópico');
    }
}
