<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\LiveLocation;
use App\Support\Database;
use PDO;

/**
 * Driver positioning — both the "current dot" (DriverLiveLocation, one
 * row per driver) and the per-ride breadcrumb trail (RideTracking).
 */
final class LiveLocationRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function default(): self
    {
        return new self(Database::connection());
    }

    /** @return array<LiveLocation> latest dot for every active driver. */
    public function allDrivers(): array
    {
        $stmt = $this->db->query('
            SELECT l.*, u.name
            FROM DriverLiveLocation l
            JOIN Users u ON l.driver_id = u.id
            WHERE u.role = 2
        ');
        return array_map(LiveLocation::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function currentForDriver(int $driverId): ?LiveLocation
    {
        $stmt = $this->db->prepare('SELECT * FROM DriverLiveLocation WHERE driver_id = :id');
        $stmt->execute(['id' => $driverId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? LiveLocation::fromRow($row) : null;
    }

    /** Upsert the driver's current location. */
    public function update(int $driverId, ?int $tripId, float $lat, float $lng, int $speed = 0, int $heading = 0): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO DriverLiveLocation (driver_id, trip_id, latitude, longitude, speed, heading)
            VALUES (:id, :trip, :lat, :lng, :speed, :heading)
            ON DUPLICATE KEY UPDATE
                trip_id     = VALUES(trip_id),
                latitude    = VALUES(latitude),
                longitude   = VALUES(longitude),
                speed       = VALUES(speed),
                heading     = VALUES(heading),
                last_update = CURRENT_TIMESTAMP
        ');
        $stmt->execute([
            'id'      => $driverId,
            'trip'    => $tripId,
            'lat'     => $lat,
            'lng'     => $lng,
            'speed'   => $speed,
            'heading' => $heading,
        ]);
    }

    public function clearForDriver(int $driverId): void
    {
        $this->db->prepare('DELETE FROM DriverLiveLocation WHERE driver_id = :id')
            ->execute(['id' => $driverId]);
    }

    /**
     * Record one breadcrumb on a ride. Composite (ride_id, driver_id)
     * is the primary key, so subsequent records for the same pair
     * overwrite — that matches the legacy behaviour (latest known dot).
     */
    public function trackRide(int $rideId, int $driverId, float $lat, float $lng, float $speed = 0.0, float $heading = 0.0): void
    {
        $stmt = $this->db->prepare('
            INSERT INTO RideTracking (ride_id, driver_id, latitude, longitude, speed, heading)
            VALUES (:rid, :did, :lat, :lng, :speed, :heading)
            ON DUPLICATE KEY UPDATE
                latitude    = VALUES(latitude),
                longitude   = VALUES(longitude),
                speed       = VALUES(speed),
                heading     = VALUES(heading),
                last_update = CURRENT_TIMESTAMP
        ');
        $stmt->execute([
            'rid'     => $rideId,
            'did'     => $driverId,
            'lat'     => $lat,
            'lng'     => $lng,
            'speed'   => $speed,
            'heading' => $heading,
        ]);
    }

    /**
     * Latest tracking dot for a given ride, used by the public /track.php
     * page to plot the client-visible position.
     *
     * @return array{driver_id:int,latitude:float,longitude:float,speed:float,heading:float,last_update:string}|null
     */
    public function trackingFor(int $rideId): ?array
    {
        $stmt = $this->db->prepare('
            SELECT driver_id, latitude, longitude, speed, heading, last_update
            FROM RideTracking
            WHERE ride_id = :rid
            ORDER BY last_update DESC
            LIMIT 1
        ');
        $stmt->execute(['rid' => $rideId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        return [
            'driver_id'   => (int) $row['driver_id'],
            'latitude'    => (float) $row['latitude'],
            'longitude'   => (float) $row['longitude'],
            'speed'       => (float) $row['speed'],
            'heading'     => (float) $row['heading'],
            'last_update' => (string) $row['last_update'],
        ];
    }
}
