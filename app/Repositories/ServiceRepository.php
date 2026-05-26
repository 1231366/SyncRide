<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Service;
use App\Support\Database;
use App\Support\Session;
use PDO;
use RuntimeException;

/**
 * Data-access layer for the `Services` table and its join with
 * `Services_Rides` (driver assignment).
 *
 * Every listing/count method is automatically scoped to the company
 * that is stored in the active session. Super-admin (companyId=null)
 * sees all companies. Find-by-ID methods are intentionally unscoped.
 */
final class ServiceRepository
{
    public function __construct(
        private readonly PDO  $db,
        private readonly ?int $companyId = null,
    ) {
    }

    public static function default(): self
    {
        return new self(Database::connection(), Session::companyId());
    }

    public function find(int $id): ?Service
    {
        $stmt = $this->db->prepare('SELECT * FROM Services WHERE ID = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? Service::fromRow($row) : null;
    }

    public function countAllTime(): int
    {
        $sql  = 'SELECT COUNT(*) FROM Services WHERE 1=1 ' . $this->sc('AND');
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->cb());
        return (int) $stmt->fetchColumn();
    }

    public function countThisWeek(): int
    {
        $sql  = 'SELECT COUNT(*) FROM Services WHERE WEEK(serviceDate, 1) = WEEK(CURDATE(), 1) ' . $this->sc('AND');
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->cb());
        return (int) $stmt->fetchColumn();
    }

    /**
     * Next upcoming rides (today from now on, or future dates).
     * @return array<array<string,mixed>>
     */
    public function upcoming(int $limit = 3): array
    {
        $sql  = "SELECT * FROM Services
                 WHERE ((serviceDate > CURDATE()) OR (serviceDate = CURDATE() AND serviceStartTime >= CURTIME()))
                 " . $this->sc('AND') . "
                 ORDER BY serviceDate ASC, serviceStartTime ASC
                 LIMIT {$limit}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->cb());
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<int,int> 12-element array for the current year, scoped to company. */
    public function monthlyThisYear(): array
    {
        $sql  = 'SELECT MONTH(serviceDate) AS m, COUNT(*) AS c FROM Services
                 WHERE YEAR(serviceDate) = YEAR(CURDATE()) ' . $this->sc('AND') . '
                 GROUP BY m';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->cb());
        $result = array_fill(0, 12, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[(int) $row['m'] - 1] = (int) $row['c'];
        }
        return $result;
    }

    /** @return array<Service> */
    public function byDate(string $date): array
    {
        $sql  = 'SELECT * FROM Services WHERE serviceDate = :date ' . $this->sc('AND') . ' ORDER BY serviceStartTime';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['date' => $date], $this->cb()));
        return array_map(Service::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<Service> */
    public function byDateRange(string $from, string $to): array
    {
        $sql  = 'SELECT * FROM Services WHERE serviceDate BETWEEN :from AND :to ' . $this->sc('AND') . ' ORDER BY serviceDate, serviceStartTime';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['from' => $from, 'to' => $to], $this->cb()));
        return array_map(Service::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<Service> rides assigned to a given driver, optionally for a given date. */
    public function forDriver(int $driverId, ?string $date = null): array
    {
        $sql    = 'SELECT s.* FROM Services s JOIN Services_Rides sr ON sr.RideID = s.ID WHERE sr.UserID = :uid ' . $this->sc('AND', 's');
        $params = array_merge(['uid' => $driverId], $this->cb());
        if ($date !== null) {
            $sql .= ' AND s.serviceDate = :date';
            $params['date'] = $date;
        }
        $sql .= ' ORDER BY s.serviceDate, s.serviceStartTime';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(Service::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<Service> rides owned by a partner. */
    public function forPartner(int $partnerId, bool $approvedOnly = false): array
    {
        $sql    = 'SELECT * FROM Services WHERE partner_id = :pid ' . $this->sc('AND');
        $params = array_merge(['pid' => $partnerId], $this->cb());
        if ($approvedOnly) {
            $sql .= " AND status_pedido = 'aprovado'";
        }
        $sql .= ' ORDER BY serviceDate DESC, serviceStartTime';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return array_map(Service::fromRow(...), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<Service> no-show rides. */
    public function noShows(?string $from = null, ?string $to = null): array
    {
        $sql    = 'SELECT * FROM Services WHERE noShowStatus = 1 ' . $this->sc('AND');
        $params = $this->cb();
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
        $cid  = isset($data['company_id']) ? (int) $data['company_id'] : $this->companyId;
        $stmt = $this->db->prepare('
            INSERT INTO Services
                (serviceDate, serviceStartTime, paxADT, paxCHD, paxBBY,
                 serviceStartPoint, serviceTargetPoint,
                 FlightNumber, NomeCliente, ClientNumber, serviceType, partner_id, total_price, status_pedido, company_id)
            VALUES
                (:date, :time, :adults, :children, :bby, :pickup, :dropoff,
                 :flight, :client, :phone, :type, :partner, :price, :approval, :company_id)
        ');
        $stmt->execute([
            'date'       => $data['serviceDate'],
            'time'       => $data['serviceStartTime'],
            'adults'     => (int) $data['paxADT'],
            'children'   => (int) $data['paxCHD'],
            'bby'        => (int) ($data['paxBBY'] ?? 0),
            'pickup'     => $data['serviceStartPoint'],
            'dropoff'    => $data['serviceTargetPoint'],
            'flight'     => $data['FlightNumber']  ?? null,
            'client'     => $data['NomeCliente']   ?? null,
            'phone'      => $data['ClientNumber']  ?? null,
            'type'       => (int) ($data['serviceType']  ?? 1),
            'partner'    => isset($data['partner_id']) ? (int) $data['partner_id'] : null,
            'price'      => isset($data['total_price'])  ? (float) $data['total_price'] : null,
            'approval'   => $data['status_pedido'] ?? 'aprovado',
            'company_id' => $cid,
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
        $sql = $ts !== null
            ? "UPDATE Services SET status_id = :st, {$ts} = NOW() WHERE ID = :id"
            : 'UPDATE Services SET status_id = :st WHERE ID = :id';
        $this->db->prepare($sql)->execute(['st' => $statusId, 'id' => $id]);
    }

    public function markNoShow(int $id, ?string $photoPath, ?string $lat, ?string $lng): void
    {
        $this->db->prepare('
            UPDATE Services SET noShowStatus = 1, noShowPhotoPath = :photo, noShowLat = :lat, noShowLng = :lng
            WHERE ID = :id
        ')->execute(['photo' => $photoPath, 'lat' => $lat, 'lng' => $lng, 'id' => $id]);
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
            $rideSql = 'SELECT ID FROM Services WHERE 1=1 ' . $this->sc('AND');
            $stmt    = $this->db->prepare($rideSql);
            $stmt->execute($this->cb());
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if ($ids !== []) {
                $ph = implode(',', array_fill(0, count($ids), '?'));
                $this->db->prepare("DELETE FROM Services_Rides WHERE RideID IN ({$ph})")->execute($ids);
                $this->db->prepare("DELETE FROM Services WHERE ID IN ({$ph})")->execute($ids);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function assignDriver(int $serviceId, int $driverId): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM Services_Rides WHERE RideID = :rid')->execute(['rid' => $serviceId]);
            $this->db->prepare('INSERT INTO Services_Rides (RideID, UserID) VALUES (:rid, :uid)')->execute(['rid' => $serviceId, 'uid' => $driverId]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function assignedDriver(int $serviceId): ?int
    {
        $stmt = $this->db->prepare('SELECT UserID FROM Services_Rides WHERE RideID = :rid LIMIT 1');
        $stmt->execute(['rid' => $serviceId]);
        $id = $stmt->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    /** @return array<array{driver_id:int,driver_name:string,total:int}> */
    public function rankByDriver(string $from, string $to): array
    {
        $sql    = 'SELECT u.id AS driver_id, u.name AS driver_name, COUNT(*) AS total
                   FROM Services s
                   JOIN Services_Rides sr ON sr.RideID = s.ID
                   JOIN Users u           ON u.id = sr.UserID
                   WHERE s.serviceDate BETWEEN :from AND :to ' . $this->sc('AND', 's') . '
                   GROUP BY u.id, u.name ORDER BY total DESC';
        $stmt   = $this->db->prepare($sql);
        $stmt->execute(array_merge(['from' => $from, 'to' => $to], $this->cb()));
        return array_map(static fn(array $r): array => [
            'driver_id'   => (int) $r['driver_id'],
            'driver_name' => (string) $r['driver_name'],
            'total'       => (int) $r['total'],
        ], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /**
     * Server-side DataTables: returns one page of rides + total / filtered counts.
     * Tenancy is enforced via company_id on every query — never leaks across tenants.
     *
     * @return array{data: array<array<string,mixed>>, recordsTotal: int, recordsFiltered: int}
     */
    public function listForAdminPaginated(
        string $filter,
        int    $start,
        int    $length,
        string $search,
        int    $orderCol,
        string $orderDir
    ): array {
        $cols = 's.ID, s.serviceDate, s.serviceStartTime, s.paxADT, s.paxCHD, s.paxBBY,
                 s.serviceStartPoint, s.serviceTargetPoint, s.FlightNumber,
                 s.NomeCliente, s.ClientNumber, s.serviceType, s.total_price,
                 s.has_key, s.partner_id, s.status_pedido';

        [$join, $where, $baseParams, $driverCol] = $this->filterFragments($filter);

        $tenancyWhere  = $this->companyId !== null ? ' AND s.company_id = ?' : '';
        $tenancyParams = $this->companyId !== null ? [$this->companyId]      : [];

        $searchWhere  = '';
        $searchParams = [];
        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $searchWhere  = ' AND (s.NomeCliente LIKE ? OR s.FlightNumber LIKE ?'
                          . ' OR s.serviceStartPoint LIKE ? OR s.serviceTargetPoint LIKE ?)';
            $searchParams = [$like, $like, $like, $like];
        }

        $countBase = "SELECT COUNT(DISTINCT s.ID) FROM Services s {$join} WHERE {$where}";

        $totalStmt = $this->db->prepare("{$countBase}{$tenancyWhere}");
        $totalStmt->execute(array_merge($baseParams, $tenancyParams));
        $total = (int) $totalStmt->fetchColumn();

        $filteredStmt = $this->db->prepare("{$countBase}{$tenancyWhere}{$searchWhere}");
        $filteredStmt->execute(array_merge($baseParams, $tenancyParams, $searchParams));
        $filtered = (int) $filteredStmt->fetchColumn();

        $colMap = [
            1 => 's.ID',
            2 => 's.serviceDate, s.serviceStartTime',
            4 => 's.serviceStartPoint',
            5 => 's.serviceTargetPoint',
            6 => 's.serviceType',
        ];
        $orderDir = $orderDir === 'DESC' ? 'DESC' : 'ASC';
        $orderSql = isset($colMap[$orderCol])
            ? "{$colMap[$orderCol]} {$orderDir}"
            : 's.serviceDate ASC, s.serviceStartTime ASC';

        // LIMIT/OFFSET must be inlined as integers — PDO binds them as quoted
        // strings which MariaDB rejects in LIMIT context.
        $dataSql = "SELECT {$cols}, {$driverCol}, p.name AS partner_name
                    FROM Services s {$join}
                    WHERE {$where}{$tenancyWhere}{$searchWhere}
                    ORDER BY {$orderSql}
                    LIMIT {$length} OFFSET {$start}";
        $dataStmt = $this->db->prepare($dataSql);
        $dataStmt->execute(array_merge($baseParams, $tenancyParams, $searchParams));

        return [
            'data'            => $dataStmt->fetchAll(PDO::FETCH_ASSOC),
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
        ];
    }

    /** @return array{0:string, 1:string, 2:array<mixed>, 3:string} [join, where, params, driverCol] */
    private function filterFragments(string $filter): array
    {
        $joinSR  = 'LEFT JOIN Services_Rides sr ON s.ID = sr.RideID';
        $joinU   = 'LEFT JOIN Users u ON sr.UserID = u.ID';
        $joinP   = 'LEFT JOIN Users p ON s.partner_id = p.ID';
        $active  = "(s.status_pedido = 'aprovado' OR s.status_pedido IS NULL)";

        return match ($filter) {
            'requests' => [
                $joinP,
                "s.status_pedido = 'pendente'",
                [],
                'NULL AS driverName',
            ],
            'today' => [
                "{$joinSR} {$joinU} {$joinP}",
                "s.serviceDate = CURDATE() AND {$active}",
                [],
                'u.name AS driverName',
            ],
            'pending' => [
                "{$joinSR} {$joinP}",
                "sr.UserID IS NULL AND {$active}",
                [],
                'NULL AS driverName',
            ],
            'assigned' => [
                "INNER JOIN Services_Rides sr ON s.ID = sr.RideID INNER JOIN Users u ON sr.UserID = u.ID {$joinP}",
                $active,
                [],
                'u.name AS driverName',
            ],
            default => [
                "{$joinSR} {$joinU} {$joinP}",
                $active,
                [],
                'u.name AS driverName',
            ],
        };
    }

    /** @return array<array<string,mixed>> */
    public function listForAdmin(string $filter): array
    {
        $cols = 's.ID, s.serviceDate, s.serviceStartTime, s.paxADT, s.paxCHD, s.paxBBY,
                 s.serviceStartPoint, s.serviceTargetPoint, s.FlightNumber,
                 s.NomeCliente, s.ClientNumber, s.serviceType, s.total_price,
                 s.has_key, s.partner_id, s.status_pedido';
        $csc  = $this->sc('AND', 's');
        $cb   = $this->cb();

        [$sql, $params] = match ($filter) {
            'requests' => [
                "SELECT {$cols}, NULL AS driverName, p.name AS partner_name
                 FROM Services s LEFT JOIN Users p ON s.partner_id = p.ID
                 WHERE s.status_pedido = 'pendente' {$csc}
                 ORDER BY s.serviceDate ASC, s.serviceStartTime ASC",
                $cb,
            ],
            'today' => [
                "SELECT {$cols}, u.name AS driverName, p.name AS partner_name
                 FROM Services s
                 LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
                 LEFT JOIN Users u ON sr.UserID = u.ID
                 LEFT JOIN Users p ON s.partner_id = p.ID
                 WHERE s.serviceDate = CURDATE()
                   AND (s.status_pedido = 'aprovado' OR s.status_pedido IS NULL) {$csc}
                 ORDER BY s.serviceStartTime ASC",
                $cb,
            ],
            'pending' => [
                "SELECT {$cols}, NULL AS driverName, p.name AS partner_name
                 FROM Services s
                 LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
                 LEFT JOIN Users p ON s.partner_id = p.ID
                 WHERE sr.UserID IS NULL
                   AND (s.status_pedido = 'aprovado' OR s.status_pedido IS NULL) {$csc}
                 ORDER BY s.serviceDate, s.serviceStartTime",
                $cb,
            ],
            'assigned' => [
                "SELECT {$cols}, u.name AS driverName, p.name AS partner_name
                 FROM Services s
                 INNER JOIN Services_Rides sr ON s.ID = sr.RideID
                 INNER JOIN Users u ON sr.UserID = u.ID
                 LEFT JOIN Users p ON s.partner_id = p.ID
                 WHERE (s.status_pedido = 'aprovado' OR s.status_pedido IS NULL) {$csc}
                 ORDER BY s.serviceDate, s.serviceStartTime",
                $cb,
            ],
            default => [
                "SELECT {$cols}, u.name AS driverName, p.name AS partner_name
                 FROM Services s
                 LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
                 LEFT JOIN Users u ON sr.UserID = u.ID
                 LEFT JOIN Users p ON s.partner_id = p.ID
                 WHERE (s.status_pedido = 'aprovado' OR s.status_pedido IS NULL) {$csc}
                 ORDER BY s.serviceDate, s.serviceStartTime",
                $cb,
            ],
        };

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countPendingRequests(): int
    {
        $sql  = "SELECT COUNT(*) FROM Services WHERE status_pedido = 'pendente' " . $this->sc('AND');
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->cb());
        return (int) $stmt->fetchColumn();
    }

    public function countToday(): int
    {
        $sql  = "SELECT COUNT(*) FROM Services WHERE serviceDate = CURDATE() AND (status_pedido = 'aprovado' OR status_pedido IS NULL) " . $this->sc('AND');
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->cb());
        return (int) $stmt->fetchColumn();
    }

    public function countUnassigned(): int
    {
        $sql  = "SELECT COUNT(*) FROM Services s LEFT JOIN Services_Rides sr ON s.ID = sr.RideID WHERE sr.UserID IS NULL AND (s.status_pedido = 'aprovado' OR s.status_pedido IS NULL) " . $this->sc('AND', 's');
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->cb());
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): void
    {
        $this->db->prepare('
            UPDATE Services
            SET serviceDate=:date, serviceStartTime=:time, serviceStartPoint=:pickup,
                serviceTargetPoint=:dropoff, paxADT=:adults, paxCHD=:children,
                paxBBY=:bby,
                FlightNumber=:flight, NomeCliente=:client, ClientNumber=:phone, total_price=:price
            WHERE ID = :id
        ')->execute([
            'date'     => $data['serviceDate'],
            'time'     => $data['serviceStartTime'],
            'pickup'   => $data['serviceStartPoint'],
            'dropoff'  => $data['serviceTargetPoint'],
            'adults'   => (int) ($data['paxADT'] ?? 0),
            'children' => (int) ($data['paxCHD'] ?? 0),
            'bby'      => (int) ($data['paxBBY'] ?? 0),
            'flight'   => $data['FlightNumber'] ?? null,
            'client'   => $data['NomeCliente']  ?? null,
            'phone'    => $data['ClientNumber'] ?? null,
            'price'    => (float) ($data['total_price'] ?? 0),
            'id'       => $id,
        ]);
    }

    public function deleteBulk(array $ids): void
    {
        if (empty($ids)) return;
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

    /** @return array<array<string,mixed>> */
    public function listNoShowsForAdmin(): array
    {
        $sql  = "SELECT s.ID, s.serviceDate, s.serviceStartTime,
                        s.serviceStartPoint, s.serviceTargetPoint,
                        s.noShowPhotoPath, u.name AS driverName
                 FROM Services s
                 LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
                 LEFT JOIN Users u ON sr.UserID = u.ID
                 WHERE s.noShowStatus = 1 " . $this->sc('AND', 's') . "
                 ORDER BY s.serviceDate DESC, s.serviceStartTime DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->cb());
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** @return array<string,mixed>|null */
    public function findWithPartner(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.NomeCliente, s.serviceStartPoint, s.serviceTargetPoint,
                   s.serviceDate, s.partner_id,
                   u.email AS partner_email, u.name AS partner_name
            FROM Services s LEFT JOIN Users u ON s.partner_id = u.id
            WHERE s.ID = :id
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getTimestamps(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ts_start_pickup, ts_arrived_pickup, ts_with_client, ts_start_trip, ts_completed FROM Services WHERE ID = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ── Driver-stats helpers ───────────────────────────────────────────────

    public function driverStats(int $driverId, string $startDate, string $endDate): array
    {
        $csc = $this->sc('AND', 's');
        $cb  = $this->cb();
        $stmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(*) FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID
                    WHERE sr.UserID = :uid1 AND s.serviceDate = CURDATE() {$csc}) AS trips_today,
                (SELECT AVG(s.driver_rating) FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID
                    WHERE sr.UserID = :uid2 AND s.driver_rating IS NOT NULL {$csc}) AS avg_rating,
                (SELECT COUNT(*) FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID
                    WHERE sr.UserID = :uid3 AND s.serviceDate BETWEEN :from1 AND :to1 {$csc}) AS trips_period,
                (SELECT COUNT(*) FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID
                    WHERE sr.UserID = :uid4 {$csc}) AS trips_total
        ");
        $stmt->execute(array_merge(
            ['uid1' => $driverId, 'uid2' => $driverId, 'uid3' => $driverId, 'uid4' => $driverId, 'from1' => $startDate, 'to1' => $endDate],
            $cb
        ));
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int,int> 12-element array indexed 0–11 (Jan–Dec). */
    public function driverMonthly(int $driverId, string $startDate, string $endDate): array
    {
        $sql  = 'SELECT MONTH(s.serviceDate) AS m, COUNT(sr.associationID) AS total
                 FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID
                 WHERE sr.UserID = :uid AND s.serviceDate BETWEEN :from AND :to ' . $this->sc('AND', 's') . '
                 GROUP BY m';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['uid' => $driverId, 'from' => $startDate, 'to' => $endDate], $this->cb()));
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
        $sql   = "SELECT s.ID, s.serviceDate, s.serviceStartTime, s.serviceStartPoint, s.serviceTargetPoint
                  FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID
                  WHERE sr.UserID = :uid AND s.serviceDate BETWEEN :from AND :to " . $this->sc('AND', 's') . "
                  ORDER BY s.serviceDate DESC LIMIT {$limit}";
        $stmt  = $this->db->prepare($sql);
        $stmt->execute(array_merge(['uid' => $driverId, 'from' => $startDate, 'to' => $endDate], $this->cb()));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function partnerStats(int $partnerId, string $startDate, string $endDate): array
    {
        $csc  = $this->sc('AND');
        $cb   = $this->cb();
        $stmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(*) FROM Services WHERE partner_id = :pid1 AND serviceDate = CURDATE() {$csc}) AS trips_today,
                (SELECT COUNT(*) FROM Services WHERE partner_id = :pid2 AND serviceDate BETWEEN :from AND :to {$csc}) AS trips_period,
                (SELECT COUNT(*) FROM Services WHERE partner_id = :pid3 {$csc}) AS trips_total
        ");
        $stmt->execute(array_merge(['pid1' => $partnerId, 'pid2' => $partnerId, 'pid3' => $partnerId, 'from' => $startDate, 'to' => $endDate], $cb));
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int,int> 12-element array. */
    public function partnerMonthly(int $partnerId, string $startDate, string $endDate): array
    {
        $sql  = 'SELECT MONTH(serviceDate) AS m, COUNT(ID) AS total FROM Services
                 WHERE partner_id = :pid AND serviceDate BETWEEN :from AND :to ' . $this->sc('AND') . ' GROUP BY m';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['pid' => $partnerId, 'from' => $startDate, 'to' => $endDate], $this->cb()));
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
        $sql   = "SELECT ID, serviceDate, serviceStartTime, serviceStartPoint, serviceTargetPoint
                  FROM Services WHERE partner_id = :pid AND serviceDate BETWEEN :from AND :to " . $this->sc('AND') . "
                  ORDER BY serviceDate DESC LIMIT {$limit}";
        $stmt  = $this->db->prepare($sql);
        $stmt->execute(array_merge(['pid' => $partnerId, 'from' => $startDate, 'to' => $endDate], $this->cb()));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function overviewStats(string $startDate, string $endDate): array
    {
        $csc  = $this->sc('AND', 's');
        $ucsc = $this->companyId !== null ? 'AND u.company_id = :company_id' : '';
        $cb   = $this->cb();

        $driverStmt = $this->db->prepare("SELECT COUNT(*) FROM Users u WHERE u.role = 2 {$ucsc}");
        $driverStmt->execute($cb);
        $driverCount = (int) $driverStmt->fetchColumn();

        $todaySql   = "SELECT COUNT(*) FROM Services s WHERE s.serviceDate = CURDATE() {$csc}";
        $todayStmt  = $this->db->prepare($todaySql);
        $todayStmt->execute($cb);
        $todayCount = (int) $todayStmt->fetchColumn();

        $periodSql  = "SELECT COUNT(*) FROM Services s WHERE s.serviceDate BETWEEN :from AND :to {$csc}";
        $periodStmt = $this->db->prepare($periodSql);
        $periodStmt->execute(array_merge(['from' => $startDate, 'to' => $endDate], $cb));
        $periodCount = (int) $periodStmt->fetchColumn();

        $totalSql  = "SELECT COUNT(*) FROM Services s WHERE 1=1 {$csc}";
        $totalStmt = $this->db->prepare($totalSql);
        $totalStmt->execute($cb);
        $totalCount = (int) $totalStmt->fetchColumn();

        return compact('driverCount', 'todayCount', 'periodCount', 'totalCount');
    }

    /** @return array<int,int> 12-element array. */
    public function monthlyByPeriod(string $startDate, string $endDate): array
    {
        $sql  = 'SELECT MONTH(serviceDate) AS m, COUNT(ID) AS total FROM Services s
                 WHERE serviceDate BETWEEN :from AND :to ' . $this->sc('AND', 's') . ' GROUP BY m';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['from' => $startDate, 'to' => $endDate], $this->cb()));
        $result = array_fill(0, 12, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) as $month => $total) {
            $result[(int) $month - 1] = (int) $total;
        }
        return $result;
    }

    /** @return array<array{id:int,name:string,trips_period:int,avg_rating:float|null}> */
    public function driverLeaderboard(string $startDate, string $endDate, int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));
        $csc   = $this->sc('AND', 's');
        $ucsc  = $this->companyId !== null ? 'AND u.company_id = :company_id' : '';
        $cb    = $this->cb();
        $stmt  = $this->db->prepare("
            SELECT u.id, u.name,
                (SELECT COUNT(*) FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID
                    WHERE sr.UserID = u.id AND s.serviceDate BETWEEN :from1 AND :to1 {$csc}) AS trips_period,
                (SELECT AVG(s.driver_rating) FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID
                    WHERE sr.UserID = u.id AND s.driver_rating IS NOT NULL {$csc}) AS avg_rating
            FROM Users u WHERE u.role = 2 {$ucsc}
            ORDER BY trips_period DESC
            LIMIT {$limit}
        ");
        $stmt->execute(array_merge(['from1' => $startDate, 'to1' => $endDate], $cb));
        return array_map(static fn(array $r): array => [
            'id'           => (int) $r['id'],
            'name'         => (string) $r['name'],
            'trips_period' => (int) $r['trips_period'],
            'avg_rating'   => $r['avg_rating'] !== null ? (float) $r['avg_rating'] : null,
        ], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @return array<array{id:int,name:string,trips_period:int}> */
    public function partnerLeaderboard(string $startDate, string $endDate, int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));
        $csc   = $this->sc('AND');
        $ucsc  = $this->companyId !== null ? 'AND u.company_id = :company_id' : '';
        $cb    = $this->cb();
        $stmt  = $this->db->prepare("
            SELECT u.id, u.name,
                (SELECT COUNT(*) FROM Services s
                    WHERE s.partner_id = u.id AND s.serviceDate BETWEEN :from AND :to {$csc}) AS trips_period
            FROM Users u WHERE u.role = 3 {$ucsc}
            ORDER BY trips_period DESC LIMIT {$limit}
        ");
        $stmt->execute(array_merge(['from' => $startDate, 'to' => $endDate], $cb));
        return array_map(static fn(array $r): array => [
            'id'           => (int) $r['id'],
            'name'         => (string) $r['name'],
            'trips_period' => (int) $r['trips_period'],
        ], $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // ── Driver dashboard helpers ───────────────────────────────────────────

    /** @return array<array<string,mixed>> */
    public function driverDashboardRides(int $driverId, ?int $serviceType = null): array
    {
        $sql    = 'SELECT s.ID AS ServiceID, s.serviceDate, s.serviceStartTime,
                          s.serviceStartPoint, s.serviceTargetPoint,
                          s.paxADT, s.paxCHD, s.paxBBY,
                          s.FlightNumber, s.NomeCliente,
                          s.ClientNumber, s.serviceType, s.total_price, s.has_key,
                          s.partner_id, COALESCE(s.status_id, 0) AS status_id,
                          u.name AS AgencyName, u.phone AS AgencyPhone
                   FROM Services_Rides sr
                   INNER JOIN Services s ON sr.RideID = s.ID
                   LEFT  JOIN Users u    ON s.partner_id = u.id
                   WHERE sr.UserID = :uid ' . $this->sc('AND', 's');
        $params = array_merge(['uid' => $driverId], $this->cb());
        if ($serviceType !== null) {
            $sql .= ' AND s.serviceType = :stype';
            $params['stype'] = $serviceType;
        }
        $sql .= ' ORDER BY s.serviceDate ASC, s.serviceStartTime ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function driverCountToday(int $driverId): int
    {
        $sql  = 'SELECT COUNT(*) FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID WHERE sr.UserID = :uid AND s.serviceDate = CURDATE() ' . $this->sc('AND', 's');
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['uid' => $driverId], $this->cb()));
        return (int) $stmt->fetchColumn();
    }

    public function driverCountWeek(int $driverId): int
    {
        $sql  = 'SELECT COUNT(*) FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID WHERE sr.UserID = :uid AND YEARWEEK(s.serviceDate, 1) = YEARWEEK(CURDATE(), 1) ' . $this->sc('AND', 's');
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['uid' => $driverId], $this->cb()));
        return (int) $stmt->fetchColumn();
    }

    public function driverCountAllTime(int $driverId): int
    {
        $sql  = 'SELECT COUNT(*) FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID WHERE sr.UserID = :uid AND s.serviceDate <= CURDATE() ' . $this->sc('AND', 's');
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['uid' => $driverId], $this->cb()));
        return (int) $stmt->fetchColumn();
    }

    public function driverCountLastMonth(int $driverId): int
    {
        $sql  = 'SELECT COUNT(*) FROM Services s JOIN Services_Rides sr ON s.ID = sr.RideID WHERE sr.UserID = :uid AND s.serviceDate BETWEEN CURDATE() - INTERVAL 1 MONTH AND CURDATE() ' . $this->sc('AND', 's');
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['uid' => $driverId], $this->cb()));
        return (int) $stmt->fetchColumn();
    }

    /** @return array<int> distinct years with rides for this driver, desc. */
    public function driverAvailableYears(int $driverId): array
    {
        $sql  = 'SELECT DISTINCT YEAR(s.serviceDate) AS y FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID WHERE sr.UserID = :uid ' . $this->sc('AND', 's') . ' ORDER BY y DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['uid' => $driverId], $this->cb()));
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $rows ?: [(int) date('Y')];
    }

    /** @return array<int,int> 12-element array (0=Jan) for driver in a year. */
    public function driverMonthlyByYear(int $driverId, int $year): array
    {
        $sql  = 'SELECT MONTH(s.serviceDate) AS m, COUNT(sr.RideID) AS total
                 FROM Services_Rides sr JOIN Services s ON sr.RideID = s.ID
                 WHERE sr.UserID = :uid AND YEAR(s.serviceDate) = :year AND s.serviceDate <= CURDATE() ' . $this->sc('AND', 's') . '
                 GROUP BY m';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['uid' => $driverId, 'year' => $year], $this->cb()));
        $result = array_fill(0, 12, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) as $month => $total) {
            $result[(int) $month - 1] = (int) $total;
        }
        return $result;
    }

    // ── Partner helpers ────────────────────────────────────────────────────

    /** @return array{total:int,pending:int,approved:int} */
    public function partnerCounts(int $partnerId): array
    {
        $sql  = 'SELECT COUNT(*) AS total,
                        SUM(CASE WHEN status_pedido = "pendente" THEN 1 ELSE 0 END) AS pending,
                        SUM(CASE WHEN status_pedido = "aprovado" THEN 1 ELSE 0 END) AS approved
                 FROM Services WHERE partner_id = :pid ' . $this->sc('AND');
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['pid' => $partnerId], $this->cb()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return ['total' => (int) ($row['total'] ?? 0), 'pending' => (int) ($row['pending'] ?? 0), 'approved' => (int) ($row['approved'] ?? 0)];
    }

    public function partnerRidesByStatus(int $partnerId, string $status): array
    {
        $sql  = 'SELECT * FROM Services WHERE partner_id = :pid AND status_pedido = :status ' . $this->sc('AND') . ' ORDER BY serviceDate DESC, serviceStartTime DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['pid' => $partnerId, 'status' => $status], $this->cb()));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createForPartner(int $partnerId, array $data): int
    {
        $cid  = isset($data['company_id']) ? (int) $data['company_id'] : $this->companyId;
        $stmt = $this->db->prepare('
            INSERT INTO Services (
                serviceDate, serviceStartTime, serviceStartPoint, serviceTargetPoint,
                paxADT, paxCHD, paxBBY, NomeCliente, FlightNumber, partner_id,
                status_pedido, serviceType, ClientNumber, total_price, has_key, company_id
            ) VALUES (
                :date, :time, :pickup, :dropoff,
                :pax_adt, :pax_chd, :bby, :client_name, :flight, :partner_id,
                "pendente", 1, :client_phone, :price, :has_key, :company_id
            )
        ');
        $stmt->execute([
            'date'         => $data['date'],
            'time'         => $data['time'],
            'pickup'       => $data['pickup'],
            'dropoff'      => $data['dropoff'],
            'pax_adt'      => (int) ($data['pax_adt']  ?? 1),
            'pax_chd'      => (int) ($data['pax_chd']  ?? 0),
            'bby'          => (int) ($data['pax_bby']  ?? 0),
            'client_name'  => $data['client_name'],
            'flight'       => $data['flight'] ?? '',
            'partner_id'   => $partnerId,
            'client_phone' => $data['client_phone'] ?? '',
            'price'        => isset($data['price']) && $data['price'] !== '' ? (float) $data['price'] : null,
            'has_key'      => (int) ($data['has_key'] ?? 0),
            'company_id'   => $cid,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a partner-owned ride only if it belongs to the partner and the
     * driver has not yet started the trip (status_id = 0 or NULL).
     * Returns true on success, false if the ownership/state guard rejects it.
     */
    public function updateForPartner(int $partnerId, int $rideId, array $data): bool
    {
        $check = $this->db->prepare(
            'SELECT COUNT(*) FROM Services WHERE ID = :id AND partner_id = :pid AND COALESCE(status_id, 0) = 0'
        );
        $check->execute(['id' => $rideId, 'pid' => $partnerId]);
        if ((int) $check->fetchColumn() === 0) {
            return false;
        }

        $this->db->prepare('
            UPDATE Services
            SET serviceDate=:date, serviceStartTime=:time,
                serviceStartPoint=:pickup, serviceTargetPoint=:dropoff,
                paxADT=:adt, paxCHD=:chd, paxBBY=:bby,
                FlightNumber=:flight, NomeCliente=:client,
                ClientNumber=:phone
            WHERE ID = :id AND partner_id = :pid AND COALESCE(status_id, 0) = 0
        ')->execute([
            'date'   => $data['date'],
            'time'   => $data['time'],
            'pickup' => $data['pickup'],
            'dropoff'=> $data['dropoff'],
            'adt'    => (int) ($data['pax_adt']  ?? 1),
            'chd'    => (int) ($data['pax_chd']  ?? 0),
            'bby'    => (int) ($data['pax_bby']  ?? 0),
            'flight' => $data['flight']       ?? '',
            'client' => $data['client_name']  ?? '',
            'phone'  => $data['client_phone'] ?? '',
            'id'     => $rideId,
            'pid'    => $partnerId,
        ]);

        return true;
    }

    // ── AI context helpers ─────────────────────────────────────────────────

    /**
     * Today's schedule with driver name — used by AiSyncController.
     * @return array<array<string,mixed>>
     */
    public function todayWithDriver(): array
    {
        $clause = $this->companyId !== null ? 'AND s.company_id = :company_id' : '';
        $stmt   = $this->db->prepare("
            SELECT s.serviceStartTime, s.NomeCliente, s.FlightNumber,
                   s.serviceStartPoint, s.serviceTargetPoint, u.name AS driver
            FROM Services s
            LEFT JOIN Services_Rides sr ON sr.RideID = s.ID
            LEFT JOIN Users u ON u.id = sr.UserID
            WHERE s.serviceDate = CURDATE() {$clause}
            ORDER BY s.serviceStartTime ASC
        ");
        $stmt->execute($this->cb());
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Driver performance for AI: all-time total, this-month total, avg rating.
     * @return array<array<string,mixed>>
     */
    public function driverLeaderboardDetailed(int $month, int $year): array
    {
        $ucsc = $this->companyId !== null ? 'AND u.company_id = :company_id' : '';
        $stmt = $this->db->prepare("
            SELECT u.name,
                   COUNT(sr.RideID) AS total_all_time,
                   SUM(CASE WHEN MONTH(s.serviceDate) = :month AND YEAR(s.serviceDate) = :year THEN 1 ELSE 0 END) AS total_this_month,
                   AVG(s.driver_rating) AS rating
            FROM Users u
            LEFT JOIN Services_Rides sr ON u.id = sr.UserID
            LEFT JOIN Services s ON sr.RideID = s.ID
            WHERE u.role = 2 {$ucsc}
            GROUP BY u.id, u.name
            ORDER BY total_this_month DESC
        ");
        $stmt->execute(array_merge(['month' => $month, 'year' => $year], $this->cb()));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Scoping helpers ────────────────────────────────────────────────────

    /**
     * Returns an AND/WHERE clause for company scoping.
     * @param string $prefix  'AND' or 'WHERE'
     * @param string $alias   table alias ('s', 'u', or '' for no alias)
     */
    private function sc(string $prefix = 'WHERE', string $alias = ''): string
    {
        if ($this->companyId === null) {
            return '';
        }
        $col = $alias !== '' ? "{$alias}.company_id" : 'company_id';
        return "{$prefix} {$col} = :company_id";
    }

    /** Returns the company_id binding array, or empty if super-admin. */
    private function cb(): array
    {
        return $this->companyId !== null ? ['company_id' => $this->companyId] : [];
    }
}
