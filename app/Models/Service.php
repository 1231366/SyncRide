<?php

declare(strict_types=1);

namespace App\Models;

/**
 * A scheduled transfer service ("ride" in the user-facing UI).
 *
 * Maps the `Services` table — the legacy column names are kept as-is
 * because the production schema is untouched. The DTO exposes English
 * accessors so consumers do not pepper the codebase with `NomeCliente`.
 */
final class Service
{
    /** status_id values used by drivers / dashboards. Source: legacy code. */
    public const STATUS_PENDING       = 0;
    public const STATUS_ON_THE_WAY    = 1;
    public const STATUS_AT_PICKUP     = 2;
    public const STATUS_WITH_CLIENT   = 3;
    public const STATUS_ON_TRIP       = 4;
    public const STATUS_COMPLETED     = 5;

    /** serviceType — 0 = shared, 1 = private. */
    public const TYPE_SHARED  = 0;
    public const TYPE_PRIVATE = 1;

    public function __construct(
        public readonly int $id,
        public readonly string $date,             // serviceDate (Y-m-d)
        public readonly string $startTime,        // serviceStartTime (H:i:s)
        public readonly int $paxAdults,           // paxADT
        public readonly int $paxChildren,         // paxCHD
        public readonly string $pickupAddress,    // serviceStartPoint
        public readonly string $dropoffAddress,   // serviceTargetPoint
        public readonly ?string $flightNumber,    // FlightNumber
        public readonly ?string $clientName,      // NomeCliente
        public readonly ?string $clientPhone,     // ClientNumber
        public readonly int $type,                // serviceType
        public readonly int $noShowStatus,        // noShowStatus
        public readonly ?string $noShowPhotoPath, // noShowPhotoPath
        public readonly int $statusId,            // status_id
        public readonly ?float $totalPrice,       // total_price
        public readonly string $approvalStatus,   // status_pedido
        public readonly ?int $partnerId,          // partner_id
        public readonly ?int $driverRating,       // driver_rating
        public readonly bool $hasKey,             // has_key
        public readonly ?string $tsCompleted,     // ts_completed
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            id:               (int) $row['ID'],
            date:             (string) $row['serviceDate'],
            startTime:        (string) $row['serviceStartTime'],
            paxAdults:        (int) $row['paxADT'],
            paxChildren:      (int) $row['paxCHD'],
            pickupAddress:    (string) $row['serviceStartPoint'],
            dropoffAddress:   (string) $row['serviceTargetPoint'],
            flightNumber:     $row['FlightNumber'] ?? null,
            clientName:       $row['NomeCliente'] ?? null,
            clientPhone:      $row['ClientNumber'] ?? null,
            type:             (int) ($row['serviceType'] ?? 0),
            noShowStatus:     (int) ($row['noShowStatus'] ?? 0),
            noShowPhotoPath:  $row['noShowPhotoPath'] ?? null,
            statusId:         (int) ($row['status_id'] ?? 0),
            totalPrice:       isset($row['total_price']) ? (float) $row['total_price'] : null,
            approvalStatus:   (string) ($row['status_pedido'] ?? 'aprovado'),
            partnerId:        isset($row['partner_id']) ? (int) $row['partner_id'] : null,
            driverRating:     isset($row['driver_rating']) ? (int) $row['driver_rating'] : null,
            hasKey:           (bool) ($row['has_key'] ?? false),
            tsCompleted:      $row['ts_completed'] ?? null,
        );
    }

    public function isCompleted(): bool   { return $this->statusId === self::STATUS_COMPLETED; }
    public function isNoShow(): bool      { return $this->noShowStatus === 1; }
    public function isShared(): bool      { return $this->type === self::TYPE_SHARED; }
    public function totalPax(): int       { return $this->paxAdults + $this->paxChildren; }
}
