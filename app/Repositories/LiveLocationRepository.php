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
    public function __construct(
        private readonly PDO  $db,
        private readonly ?int $companyId = null,
    ) {
    }

    public static function default(): self
    {
        return new self(Database::connection(), \App\Support\Session::companyId());
    }

    /** @return array<LiveLocation> latest dot for active drivers scoped to this company. */
    public function allDrivers(): array
    {
        $clause = $this->companyId !== null ? 'AND u.company_id = :cid' : '';
        $sql    = "
            SELECT l.*, u.name
            FROM DriverLiveLocation l
            JOIN Users u ON l.driver_id = u.id
            WHERE u.role = 2 {$clause}
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->companyId !== null ? ['cid' => $this->companyId] : []);
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
     * All active tracking rows joined with driver/service/vehicle data.
     * Used by the admin live-map via tracking-get.php (no ride_id filter).
     *
     * @return array<array<string,mixed>>
     */
    public function allActiveRides(): array
    {
        // Multi-tenancy: company admins only see rides operated by, or delegated out
        // by, their own company. Super-admin (companyId === null) sees everything.
        // INNER JOIN Services (not LEFT) so a tracking row with no matching service
        // can never leak through the tenancy filter.
        $tenancy = $this->companyId !== null
            ? 'AND (s.company_id = :cid OR s.original_company_id = :cid)'
            : '';

        // Subquery picks only the most recent row per (ride_id, driver_id) because
        // RideTracking has no PK — each sendPosition() INSERTs a new row instead of
        // updating in place, so without this the JS stale-filter always sees old rows.
        $stmt = $this->db->prepare("
            SELECT t.ride_id, t.driver_id, t.latitude, t.longitude, t.speed, t.heading, t.last_update,
                   COALESCE(u.name, CONCAT('Driver ', t.driver_id)) AS driver_name,
                   COALESCE(s.NomeCliente, 'Unknown') AS NomeCliente,
                   COALESCE(s.serviceStartPoint, '') AS serviceStartPoint,
                   COALESCE(s.serviceTargetPoint, 'N/A') AS serviceTargetPoint,
                   s.serviceDate, s.status_id, s.is_aggregate_master,
                   v.license_plate AS vehicle_plate,
                   cur_stop.location AS current_stop_location,
                   cur_stop.stop_type AS current_stop_type
            FROM RideTracking t
            INNER JOIN (
                SELECT ride_id, driver_id, MAX(last_update) AS latest
                FROM RideTracking
                GROUP BY ride_id, driver_id
            ) r ON t.ride_id = r.ride_id AND t.driver_id = r.driver_id AND t.last_update = r.latest
            INNER JOIN Services s ON t.ride_id = s.ID {$tenancy}
            LEFT JOIN Users u ON t.driver_id = u.id
            LEFT JOIN Vehicles v ON u.assigned_vehicle_id = v.id
            LEFT JOIN (
                SELECT ss.master_service_id, ss.location, ss.stop_type
                FROM ServiceStops ss
                INNER JOIN (
                    SELECT master_service_id, MIN(id) AS first_id
                    FROM ServiceStops
                    WHERE ts_departed IS NULL
                    GROUP BY master_service_id
                ) active ON ss.master_service_id = active.master_service_id AND ss.id = active.first_id
            ) cur_stop ON s.ID = cur_stop.master_service_id AND s.is_aggregate_master = 1
        ");
        $stmt->execute($this->companyId !== null ? ['cid' => $this->companyId] : []);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Deletes the tracking row for a ride+driver (called when driver stops).
     */
    public function stopRide(int $rideId, int $driverId): void
    {
        $this->db->prepare('DELETE FROM RideTracking WHERE ride_id = :rid AND driver_id = :did')
            ->execute(['rid' => $rideId, 'did' => $driverId]);
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
