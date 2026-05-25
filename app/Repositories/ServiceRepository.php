<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Service;
use App\Support\Database;
use PDO;
use RuntimeException;

/**
 * Data-access layer for the `Services` table and its join with
 * `Services_Rides` (driver assignment).
 *
 * Method names are English; SQL keeps the production column names
 * (NomeCliente, FlightNumber, serviceDate, …).
 */
final class ServiceRepository
{
    public function __construct(private readonly PDO $db)
    {
    }

    public static function default(): self
    {
        return new self(Database::connection());
    }

    public function find(int $id): ?Service
    {
        $stmt = $this->db->prepare('SELECT * FROM Services WHERE ID = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? Service::fromRow($row) : null;
    }

    /** @return array<Service> */
    public function byDate(string $date): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM Services
            WHERE serviceDate = :date
            ORDER BY serviceStartTime
        ');
        $stmt->execute(['date' => $date]);
        return array_map(Service::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<Service> */
    public function byDateRange(string $from, string $to): array
    {
        $stmt = $this->db->prepare('
            SELECT * FROM Services
            WHERE serviceDate BETWEEN :from AND :to
            ORDER BY serviceDate, serviceStartTime
        ');
        $stmt->execute(['from' => $from, 'to' => $to]);
        return array_map(Service::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<Service> rides assigned to a given driver, optionally for a given date. */
    public function forDriver(int $driverId, ?string $date = null): array
    {
        $sql = '
            SELECT s.*
            FROM Services s
            JOIN Services_Rides sr ON sr.RideID = s.ID
            WHERE sr.UserID = :uid
        ';
        $params = ['uid' => $driverId];
        if ($date !== null) {
            $sql .= ' AND s.serviceDate = :date';
            $params['date'] = $date;
        }
        $sql .= ' ORDER BY s.serviceDate, s.serviceStartTime';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(Service::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<Service> rides owned by a partner (status_pedido='aprovado' filter optional). */
    public function forPartner(int $partnerId, bool $approvedOnly = false): array
    {
        $sql = 'SELECT * FROM Services WHERE partner_id = :pid';
        $params = ['pid' => $partnerId];
        if ($approvedOnly) {
            $sql .= " AND status_pedido = 'aprovado'";
        }
        $sql .= ' ORDER BY serviceDate DESC, serviceStartTime';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(Service::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<Service> no-show rides, most recent first. */
    public function noShows(?string $from = null, ?string $to = null): array
    {
        $sql = 'SELECT * FROM Services WHERE noShowStatus = 1';
        $params = [];
        if ($from !== null) { $sql .= ' AND serviceDate >= :from'; $params['from'] = $from; }
        if ($to   !== null) { $sql .= ' AND serviceDate <= :to';   $params['to']   = $to; }
        $sql .= ' ORDER BY serviceDate DESC, serviceStartTime';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(Service::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(array $data): Service
    {
        foreach (['serviceDate', 'serviceStartTime', 'paxADT', 'paxCHD', 'serviceStartPoint', 'serviceTargetPoint'] as $required) {
            if (!isset($data[$required])) {
                throw new RuntimeException("ServiceRepository::create — missing field: {$required}");
            }
        }

        $stmt = $this->db->prepare('
            INSERT INTO Services
                (serviceDate, serviceStartTime, paxADT, paxCHD, serviceStartPoint, serviceTargetPoint,
                 FlightNumber, NomeCliente, ClientNumber, serviceType, partner_id, total_price, status_pedido)
            VALUES
                (:date, :time, :adults, :children, :pickup, :dropoff,
                 :flight, :client, :phone, :type, :partner, :price, :approval)
        ');
        $stmt->execute([
            'date'     => $data['serviceDate'],
            'time'     => $data['serviceStartTime'],
            'adults'   => (int) $data['paxADT'],
            'children' => (int) $data['paxCHD'],
            'pickup'   => $data['serviceStartPoint'],
            'dropoff'  => $data['serviceTargetPoint'],
            'flight'   => $data['FlightNumber']   ?? null,
            'client'   => $data['NomeCliente']    ?? null,
            'phone'    => $data['ClientNumber']   ?? null,
            'type'     => (int) ($data['serviceType']   ?? 1),
            'partner'  => isset($data['partner_id']) ? (int) $data['partner_id'] : null,
            'price'    => isset($data['total_price']) ? (float) $data['total_price'] : null,
            'approval' => $data['status_pedido']  ?? 'aprovado',
        ]);

        return $this->find((int) $this->db->lastInsertId())
            ?? throw new RuntimeException('ServiceRepository::create — reload failed');
    }

    public function updateStatus(int $id, int $statusId): void
    {
        $ts = match ($statusId) {
            Service::STATUS_ON_THE_WAY  => 'ts_start_pickup',
            Service::STATUS_AT_PICKUP   => 'ts_arrived_pickup',
            Service::STATUS_WITH_CLIENT => 'ts_with_client',
            Service::STATUS_ON_TRIP     => 'ts_start_trip',
            Service::STATUS_COMPLETED   => 'ts_completed',
            default                     => null,
        };

        if ($ts !== null) {
            $sql = "UPDATE Services SET status_id = :st, {$ts} = NOW() WHERE ID = :id";
        } else {
            $sql = 'UPDATE Services SET status_id = :st WHERE ID = :id';
        }
        $this->db->prepare($sql)->execute(['st' => $statusId, 'id' => $id]);
    }

    public function markNoShow(int $id, ?string $photoPath, ?string $lat, ?string $lng): void
    {
        $stmt = $this->db->prepare('
            UPDATE Services
            SET noShowStatus = 1, noShowPhotoPath = :photo, noShowLat = :lat, noShowLng = :lng
            WHERE ID = :id
        ');
        $stmt->execute(['photo' => $photoPath, 'lat' => $lat, 'lng' => $lng, 'id' => $id]);
    }

    public function setTripType(int $id, int $type): void
    {
        $this->db->prepare('UPDATE Services SET serviceType = :t WHERE ID = :id')
            ->execute(['t' => $type, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM Services_Rides WHERE RideID = :id')->execute(['id' => $id]);
            $this->db->prepare('DELETE FROM Services WHERE ID = :id')->execute(['id' => $id]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteAll(): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->exec('DELETE FROM Services_Rides');
            $this->db->exec('DELETE FROM Services');
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Assign a driver to a ride; replaces any existing assignment. */
    public function assignDriver(int $serviceId, int $driverId): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM Services_Rides WHERE RideID = :rid')
                ->execute(['rid' => $serviceId]);
            $this->db->prepare('INSERT INTO Services_Rides (RideID, UserID) VALUES (:rid, :uid)')
                ->execute(['rid' => $serviceId, 'uid' => $driverId]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** Returns assigned driver id for a ride, or null if unassigned. */
    public function assignedDriver(int $serviceId): ?int
    {
        $stmt = $this->db->prepare('SELECT UserID FROM Services_Rides WHERE RideID = :rid LIMIT 1');
        $stmt->execute(['rid' => $serviceId]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /**
     * Count rides per driver in a date range, for ranking tables.
     *
     * @return array<array{driver_id:int,driver_name:string,total:int}>
     */
    public function rankByDriver(string $from, string $to): array
    {
        $stmt = $this->db->prepare('
            SELECT u.id AS driver_id, u.name AS driver_name, COUNT(*) AS total
            FROM Services s
            JOIN Services_Rides sr ON sr.RideID = s.ID
            JOIN Users u           ON u.id = sr.UserID
            WHERE s.serviceDate BETWEEN :from AND :to
            GROUP BY u.id, u.name
            ORDER BY total DESC
        ');
        $stmt->execute(['from' => $from, 'to' => $to]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_map(static fn(array $r): array => [
            'driver_id'   => (int) $r['driver_id'],
            'driver_name' => (string) $r['driver_name'],
            'total'       => (int) $r['total'],
        ], $rows);
    }

    /**
     * Raw rows for the admin rides DataTable, filtered by status context.
     *
     * Returns associative arrays (not models) because we need joined
     * partner/driver names in one shot.
     *
     * @return array<array<string,mixed>>
     */
    public function listForAdmin(string $filter): array
    {
        $cols = 's.ID, s.serviceDate, s.serviceStartTime, s.paxADT, s.paxCHD,
                 s.serviceStartPoint, s.serviceTargetPoint, s.FlightNumber,
                 s.NomeCliente, s.ClientNumber, s.serviceType, s.total_price,
                 s.has_key, s.partner_id, s.status_pedido';

        $sql = match ($filter) {
            'requests' => "SELECT {$cols}, NULL AS driverName, p.name AS partner_name
                FROM Services s
                LEFT JOIN Users p ON s.partner_id = p.ID
                WHERE s.status_pedido = 'pendente'
                ORDER BY s.serviceDate ASC, s.serviceStartTime ASC",

            'today' => "SELECT {$cols}, u.name AS driverName, p.name AS partner_name
                FROM Services s
                LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
                LEFT JOIN Users u ON sr.UserID = u.ID
                LEFT JOIN Users p ON s.partner_id = p.ID
                WHERE s.serviceDate = CURDATE()
                  AND (s.status_pedido = 'aprovado' OR s.status_pedido IS NULL)
                ORDER BY s.serviceStartTime ASC",

            'pending' => "SELECT {$cols}, NULL AS driverName, p.name AS partner_name
                FROM Services s
                LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
                LEFT JOIN Users p ON s.partner_id = p.ID
                WHERE sr.UserID IS NULL
                  AND (s.status_pedido = 'aprovado' OR s.status_pedido IS NULL)
                ORDER BY s.serviceDate, s.serviceStartTime",

            'assigned' => "SELECT {$cols}, u.name AS driverName, p.name AS partner_name
                FROM Services s
                INNER JOIN Services_Rides sr ON s.ID = sr.RideID
                INNER JOIN Users u ON sr.UserID = u.ID
                LEFT JOIN Users p ON s.partner_id = p.ID
                WHERE (s.status_pedido = 'aprovado' OR s.status_pedido IS NULL)
                ORDER BY s.serviceDate, s.serviceStartTime",

            default => "SELECT {$cols}, u.name AS driverName, p.name AS partner_name
                FROM Services s
                LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
                LEFT JOIN Users u ON sr.UserID = u.ID
                LEFT JOIN Users p ON s.partner_id = p.ID
                WHERE (s.status_pedido = 'aprovado' OR s.status_pedido IS NULL)
                ORDER BY s.serviceDate, s.serviceStartTime",
        };

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPendingRequests(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM Services WHERE status_pedido = 'pendente'"
        )->fetchColumn();
    }

    public function countToday(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM Services
             WHERE serviceDate = CURDATE()
               AND (status_pedido = 'aprovado' OR status_pedido IS NULL)"
        )->fetchColumn();
    }

    public function countUnassigned(): int
    {
        return (int) $this->db->query(
            "SELECT COUNT(*) FROM Services s
             LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
             WHERE sr.UserID IS NULL
               AND (s.status_pedido = 'aprovado' OR s.status_pedido IS NULL)"
        )->fetchColumn();
    }

    public function update(int $id, array $data): void
    {
        $this->db->prepare('
            UPDATE Services
            SET serviceDate        = :date,
                serviceStartTime   = :time,
                serviceStartPoint  = :pickup,
                serviceTargetPoint = :dropoff,
                paxADT             = :adults,
                paxCHD             = :children,
                FlightNumber       = :flight,
                NomeCliente        = :client,
                ClientNumber       = :phone,
                total_price        = :price
            WHERE ID = :id
        ')->execute([
            'date'     => $data['serviceDate'],
            'time'     => $data['serviceStartTime'],
            'pickup'   => $data['serviceStartPoint'],
            'dropoff'  => $data['serviceTargetPoint'],
            'adults'   => (int) ($data['paxADT'] ?? 0),
            'children' => (int) ($data['paxCHD'] ?? 0),
            'flight'   => $data['FlightNumber'] ?? null,
            'client'   => $data['NomeCliente']  ?? null,
            'phone'    => $data['ClientNumber'] ?? null,
            'price'    => (float) ($data['total_price'] ?? 0),
            'id'       => $id,
        ]);
    }

    public function deleteBulk(array $ids): void
    {
        if (empty($ids)) {
            return;
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $this->db->beginTransaction();
        try {
            $this->db->prepare("DELETE FROM Services_Rides WHERE RideID IN ({$ph})")->execute($ids);
            $this->db->prepare("DELETE FROM Services WHERE ID IN ({$ph})")->execute($ids);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function setApprovalStatus(int $id, string $status): void
    {
        $this->db->prepare('UPDATE Services SET status_pedido = :s WHERE ID = :id')
            ->execute(['s' => $status, 'id' => $id]);
    }

    /**
     * No-show records with driver name for the admin DataTable.
     *
     * @return array<array<string,mixed>>
     */
    public function listNoShowsForAdmin(): array
    {
        return $this->db->query("
            SELECT s.ID, s.serviceDate, s.serviceStartTime,
                   s.serviceStartPoint, s.serviceTargetPoint,
                   s.noShowPhotoPath, u.name AS driverName
            FROM Services s
            LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
            LEFT JOIN Users u ON sr.UserID = u.ID
            WHERE s.noShowStatus = 1
            ORDER BY s.serviceDate DESC, s.serviceStartTime DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns the service row joined with partner email/name for email dispatch.
     *
     * @return array<string,mixed>|null
     */
    public function findWithPartner(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.NomeCliente, s.serviceStartPoint, s.serviceTargetPoint,
                   s.serviceDate, s.partner_id,
                   u.email AS partner_email, u.name AS partner_name
            FROM Services s
            LEFT JOIN Users u ON s.partner_id = u.id
            WHERE s.ID = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Returns the five driver-progress timestamps for the logs modal. */
    public function getTimestamps(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ts_start_pickup, ts_arrived_pickup, ts_with_client,
                    ts_start_trip, ts_completed
             FROM Services WHERE ID = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ── Driver-stats page helpers ─────────────────────────────────────────

    /** KPI cards for a single driver across a date range. */
    public function driverStats(int $driverId, string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare('
            SELECT
                (SELECT COUNT(*) FROM Services_Rides sr
                    JOIN Services s ON sr.RideID = s.ID
                    WHERE sr.UserID = :uid1 AND s.serviceDate = CURDATE()) AS trips_today,
                (SELECT AVG(s.driver_rating)
                    FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID
                    WHERE sr.UserID = :uid2 AND s.driver_rating IS NOT NULL) AS avg_rating,
                (SELECT COUNT(*) FROM Services_Rides sr
                    JOIN Services s ON sr.RideID = s.ID
                    WHERE sr.UserID = :uid3 AND s.serviceDate BETWEEN :from1 AND :to1) AS trips_period,
                (SELECT COUNT(*) FROM Services_Rides sr
                    WHERE sr.UserID = :uid4) AS trips_total
        ');
        $stmt->execute([
            'uid1' => $driverId, 'uid2' => $driverId,
            'uid3' => $driverId, 'uid4' => $driverId,
            'from1' => $startDate, 'to1' => $endDate,
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int,int> 12-element array indexed 0–11 (Jan–Dec). */
    public function driverMonthly(int $driverId, string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare('
            SELECT MONTH(s.serviceDate) AS m, COUNT(sr.associationID) AS total
            FROM Services_Rides sr
            JOIN Services s ON sr.RideID = s.ID
            WHERE sr.UserID = :uid AND s.serviceDate BETWEEN :from AND :to
            GROUP BY m
        ');
        $stmt->execute(['uid' => $driverId, 'from' => $startDate, 'to' => $endDate]);
        $result = array_fill(0, 12, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) as $month => $total) {
            $result[(int) $month - 1] = (int) $total;
        }
        return $result;
    }

    /** @return array<array<string,mixed>> */
    public function driverRecentRides(int $driverId, string $startDate, string $endDate, int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        $stmt  = $this->db->prepare("
            SELECT s.ID, s.serviceDate, s.serviceStartTime,
                   s.serviceStartPoint, s.serviceTargetPoint
            FROM Services s
            JOIN Services_Rides sr ON s.ID = sr.RideID
            WHERE sr.UserID = :uid AND s.serviceDate BETWEEN :from AND :to
            ORDER BY s.serviceDate DESC
            LIMIT {$limit}
        ");
        $stmt->execute(['uid' => $driverId, 'from' => $startDate, 'to' => $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** KPI cards for a single partner. */
    public function partnerStats(int $partnerId, string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare('
            SELECT
                (SELECT COUNT(*) FROM Services WHERE partner_id = :pid1 AND serviceDate = CURDATE()) AS trips_today,
                (SELECT COUNT(*) FROM Services WHERE partner_id = :pid2 AND serviceDate BETWEEN :from AND :to) AS trips_period,
                (SELECT COUNT(*) FROM Services WHERE partner_id = :pid3) AS trips_total
        ');
        $stmt->execute([
            'pid1' => $partnerId, 'pid2' => $partnerId, 'pid3' => $partnerId,
            'from' => $startDate, 'to' => $endDate,
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int,int> 12-element array. */
    public function partnerMonthly(int $partnerId, string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare('
            SELECT MONTH(serviceDate) AS m, COUNT(ID) AS total
            FROM Services WHERE partner_id = :pid AND serviceDate BETWEEN :from AND :to
            GROUP BY m
        ');
        $stmt->execute(['pid' => $partnerId, 'from' => $startDate, 'to' => $endDate]);
        $result = array_fill(0, 12, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) as $month => $total) {
            $result[(int) $month - 1] = (int) $total;
        }
        return $result;
    }

    /** @return array<array<string,mixed>> */
    public function partnerRecentRides(int $partnerId, string $startDate, string $endDate, int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        $stmt  = $this->db->prepare("
            SELECT ID, serviceDate, serviceStartTime,
                   serviceStartPoint, serviceTargetPoint
            FROM Services
            WHERE partner_id = :pid AND serviceDate BETWEEN :from AND :to
            ORDER BY serviceDate DESC
            LIMIT {$limit}
        ");
        $stmt->execute(['pid' => $partnerId, 'from' => $startDate, 'to' => $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Global KPI cards for the overview (no driver/partner filter). */
    public function overviewStats(string $startDate, string $endDate): array
    {
        $driverCount  = (int) $this->db->query('SELECT COUNT(*) FROM Users WHERE role = 2')->fetchColumn();
        $todayCount   = (int) $this->db->query("SELECT COUNT(*) FROM Services WHERE serviceDate = CURDATE()")->fetchColumn();
        $stmt         = $this->db->prepare('SELECT COUNT(*) FROM Services WHERE serviceDate BETWEEN :from AND :to');
        $stmt->execute(['from' => $startDate, 'to' => $endDate]);
        $periodCount  = (int) $stmt->fetchColumn();
        $totalCount   = (int) $this->db->query('SELECT COUNT(*) FROM Services')->fetchColumn();

        return compact('driverCount', 'todayCount', 'periodCount', 'totalCount');
    }

    /** @return array<int,int> 12-element array for the overview monthly chart. */
    public function monthlyByPeriod(string $startDate, string $endDate): array
    {
        $stmt = $this->db->prepare('
            SELECT MONTH(serviceDate) AS m, COUNT(ID) AS total
            FROM Services WHERE serviceDate BETWEEN :from AND :to
            GROUP BY m
        ');
        $stmt->execute(['from' => $startDate, 'to' => $endDate]);
        $result = array_fill(0, 12, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) as $month => $total) {
            $result[(int) $month - 1] = (int) $total;
        }
        return $result;
    }

    /**
     * Top drivers by ride count in period, including avg rating.
     * @return array<array{id:int,name:string,trips_period:int,avg_rating:float|null}>
     */
    public function driverLeaderboard(string $startDate, string $endDate, int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));
        $stmt  = $this->db->prepare("
            SELECT u.id, u.name,
                (SELECT COUNT(*) FROM Services_Rides sr
                    JOIN Services s ON sr.RideID = s.ID
                    WHERE sr.UserID = u.id AND s.serviceDate BETWEEN :from1 AND :to1) AS trips_period,
                (SELECT AVG(s.driver_rating)
                    FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID
                    WHERE sr.UserID = u.id AND s.driver_rating IS NOT NULL) AS avg_rating
            FROM Users u
            WHERE u.role = 2
            ORDER BY trips_period DESC
            LIMIT {$limit}
        ");
        $stmt->execute(['from1' => $startDate, 'to1' => $endDate]);
        return array_map(static fn(array $r): array => [
            'id'           => (int) $r['id'],
            'name'         => (string) $r['name'],
            'trips_period' => (int) $r['trips_period'],
            'avg_rating'   => $r['avg_rating'] !== null ? (float) $r['avg_rating'] : null,
        ], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Top partners by ride count in period.
     * @return array<array{id:int,name:string,trips_period:int}>
     */
    public function partnerLeaderboard(string $startDate, string $endDate, int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));
        $stmt  = $this->db->prepare("
            SELECT u.id, u.name,
                (SELECT COUNT(*) FROM Services s WHERE s.partner_id = u.id
                    AND s.serviceDate BETWEEN :from AND :to) AS trips_period
            FROM Users u
            WHERE u.role = 3
            ORDER BY trips_period DESC
            LIMIT {$limit}
        ");
        $stmt->execute(['from' => $startDate, 'to' => $endDate]);
        return array_map(static fn(array $r): array => [
            'id'           => (int) $r['id'],
            'name'         => (string) $r['name'],
            'trips_period' => (int) $r['trips_period'],
        ], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }
}
