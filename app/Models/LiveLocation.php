<?php

declare(strict_types=1);

namespace App\Models;

/**
 * The "current GPS dot" for a driver. Sourced from `DriverLiveLocation`
 * (one row per driver, overwritten on each update).
 */
final class LiveLocation
{
    public function __construct(
        public readonly int $driverId,
        public readonly ?int $tripId,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
        public readonly int $speed,
        public readonly int $heading,
        public readonly string $lastUpdate,
        public readonly ?string $driverName = null,
    ) {
    }

    public static function fromRow(array $row): self
    {
        return new self(
            driverId:   (int) $row['driver_id'],
            tripId:     isset($row['trip_id']) ? (int) $row['trip_id'] : null,
            latitude:   isset($row['latitude'])  ? (float) $row['latitude']  : null,
            longitude:  isset($row['longitude']) ? (float) $row['longitude'] : null,
            speed:      (int) ($row['speed']   ?? 0),
            heading:    (int) ($row['heading'] ?? 0),
            lastUpdate: (string) ($row['last_update'] ?? ''),
            driverName: $row['name'] ?? null,
        );
    }
}
