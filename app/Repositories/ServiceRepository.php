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

    /**
     * Repository for a driver's own data. No company scope — a driver's rides
     * are defined by Services_Rides.UserID, so they must see every assigned ride
     * across all companies they belong to (shared drivers). The UserID filter
     * already guarantees isolation: a driver only ever sees their own rides.
     */
    public static function forDriverContext(): self
    {
        return new self(Database::connection(), null);
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
        // No company scope here — a shared driver may see rides from any company they belong to.
        // company_name is joined so the driver knows which company each trip belongs to.
        $sql    = 'SELECT s.*, c.name AS company_name
                   FROM Services s
                   JOIN Services_Rides sr ON sr.RideID = s.ID
                   LEFT JOIN Companies c ON s.company_id = c.id
                   WHERE sr.UserID = :uid
                     AND s.aggregated_into IS NULL';
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
        $cid  = $this->companyId; // always use session company — never trust caller-supplied company_id
        $explicitPrice = isset($data['total_price']) && $data['total_price'] !== null && (float) $data['total_price'] > 0;
        $stmt = $this->db->prepare('
            INSERT INTO Services
                (serviceDate, serviceStartTime, paxADT, paxCHD, paxBBY,
                 serviceStartPoint, serviceTargetPoint,
                 FlightNumber, NomeCliente, ClientNumber, serviceType, partner_id, total_price,
                 price_explicit, valor_motorista, pay_basis, status_pedido, admin_note, company_id)
            VALUES
                (:date, :time, :adults, :children, :bby, :pickup, :dropoff,
                 :flight, :client, :phone, :type, :partner, :price,
                 :price_explicit, :driver_pay, :pay_basis, :approval, :admin_note, :company_id)
        ');
        $stmt->execute([
            'date'          => $data['serviceDate'],
            'time'          => $data['serviceStartTime'],
            'adults'        => (int) $data['paxADT'],
            'children'      => (int) $data['paxCHD'],
            'bby'           => (int) ($data['paxBBY'] ?? 0),
            'pickup'        => $data['serviceStartPoint'],
            'dropoff'       => $data['serviceTargetPoint'],
            'flight'        => $data['FlightNumber']  ?? null,
            'client'        => $data['NomeCliente']   ?? null,
            'phone'         => $data['ClientNumber']  ?? null,
            'type'          => (int) ($data['serviceType']  ?? 1),
            'partner'       => isset($data['partner_id']) ? (int) $data['partner_id'] : null,
            'price'         => isset($data['total_price'])     ? (float) $data['total_price']     : null,
            'price_explicit' => $explicitPrice ? 1 : 0,
            'driver_pay'    => isset($data['valor_motorista']) && $data['valor_motorista'] !== null ? (float) $data['valor_motorista'] : null,
            'pay_basis'     => $data['pay_basis'] ?? null,
            'approval'      => $data['status_pedido'] ?? 'aprovado',
            'admin_note'    => (isset($data['admin_note']) && trim((string) $data['admin_note']) !== '') ? trim((string) $data['admin_note']) : null,
            'company_id'    => $cid,
        ]);
        return $this->find((int) $this->db->lastInsertId())
            ?? throw new RuntimeException('ServiceRepository::create — reload failed');
    }

    public function updateStatus(int $id, int $statusId): void
    {
        if (!$this->ownedBy($id)) return;
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

    /** Nota deixada pelo condutor (ex: antes de concluir). Controller valida o dono. */
    public function setDriverNote(int $id, ?string $note): void
    {
        $clean = ($note !== null && trim($note) !== '') ? trim($note) : null;
        $this->db->prepare('UPDATE Services SET driver_note = :n WHERE ID = :id')
            ->execute(['n' => $clean, 'id' => $id]);
    }

    /** Nota do escritório para o condutor — scoped à empresa via ownedBy(). */
    public function setAdminNote(int $id, ?string $note): void
    {
        if (!$this->ownedBy($id)) return;
        $clean = ($note !== null && trim($note) !== '') ? trim($note) : null;
        $this->db->prepare('UPDATE Services SET admin_note = :n WHERE ID = :id')
            ->execute(['n' => $clean, 'id' => $id]);
    }

    public function markNoShow(int $id, ?string $photoPath, ?string $lat, ?string $lng, ?string $reportPath = null, ?string $noShowAt = null): void
    {
        if (!$this->ownedBy($id)) return;
        $this->db->prepare('
            UPDATE Services
            SET noShowStatus = 1, noShowPhotoPath = :photo,
                noShowLat = :lat, noShowLng = :lng,
                noShowReportPath = :report,
                noShowAt = :noShowAt
            WHERE ID = :id
        ')->execute([
            'photo'    => $photoPath,
            'lat'      => $lat,
            'lng'      => $lng,
            'report'   => $reportPath,
            'noShowAt' => $noShowAt ?? date('Y-m-d H:i:s'),
            'id'       => $id,
        ]);
    }

    public function setTripType(int $id, int $type): void
    {
        if (!$this->ownedBy($id)) return;
        $this->db->prepare('UPDATE Services SET serviceType = :t WHERE ID = :id')
            ->execute(['t' => $type, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        if (!$this->ownedBy($id)) return;
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
        if (!$this->ownedBy($serviceId)) return;
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

    /**
     * Agrega 2+ serviços numa viagem multi-paragem: elege o de menor ID como
     * mestre, cria as paragens ordenadas em ServiceStops, e marca os restantes
     * como filhos (aggregated_into). Os filhos ficam invisíveis na listagem —
     * só o mestre aparece, com o crachá de paragen e o modal de reordenação.
     *
     * Rejeita se algum serviço já for mestre ou já for filho de outro grupo.
     *
     * @param  array<int> $ids
     * @return int|null   ID do serviço mestre, ou null se inválido
     */
    public function aggregate(array $ids): ?int
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $ids = array_filter($ids, fn(int $id): bool => $id > 0 && $this->ownedBy($id));
        $ids = array_values($ids);
        if (count($ids) < 2) {
            return null;
        }

        $ph = implode(',', array_fill(0, count($ids), '?'));

        // Rejeitar se algum já for mestre ou já estiver dentro de outro grupo.
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM Services WHERE ID IN ({$ph}) AND (is_aggregate_master = 1 OR aggregated_into IS NOT NULL)");
        $stmt->execute($ids);
        if ((int) $stmt->fetchColumn() > 0) {
            return null;
        }

        // Carregar dados completos de todos os serviços.
        $stmt = $this->db->prepare("SELECT ID, serviceType, serviceStartPoint, serviceTargetPoint, serviceStartTime, NomeCliente, paxADT, paxCHD, paxBBY, reference_no, import_notes FROM Services WHERE ID IN ({$ph}) ORDER BY ID ASC");
        $stmt->execute($ids);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($services) < 2) {
            return null;
        }

        // Só serviços do tipo Shared (0) podem ser agrupados.
        foreach ($services as $svc) {
            if ((int) $svc['serviceType'] !== 0) {
                return null;
            }
        }

        $masterId = (int) $services[0]['ID'];
        $childIds = array_map(fn(array $s): int => (int) $s['ID'], array_slice($services, 1));

        $this->db->beginTransaction();
        try {
            // Criar paragens para todos os serviços (mestre + filhos).
            $order = 0;
            foreach ($services as $svc) {
                $pax = (int) $svc['paxADT'] + (int) $svc['paxCHD'] + (int) $svc['paxBBY'];
                if ((string) ($svc['serviceStartPoint'] ?? '') !== '') {
                    $this->insertStop($masterId, (int) $svc['ID'], $order++, 'pickup',
                        (string) $svc['serviceStartPoint'],
                        $svc['serviceStartTime'] !== null ? substr((string) $svc['serviceStartTime'], 0, 8) : null,
                        $svc['NomeCliente'] ?? null, $pax > 0 ? $pax : null,
                        $svc['reference_no'] ?? null, $svc['import_notes'] ?? null);
                }
                if ((string) ($svc['serviceTargetPoint'] ?? '') !== '') {
                    $this->insertStop($masterId, (int) $svc['ID'], $order++, 'dropoff',
                        (string) $svc['serviceTargetPoint'],
                        null, $svc['NomeCliente'] ?? null, $pax > 0 ? $pax : null, null, null);
                }
            }

            // Marcar filhos.
            if ($childIds !== []) {
                $phC = implode(',', array_fill(0, count($childIds), '?'));
                $this->db->prepare("UPDATE Services SET aggregated_into = ?, grouping_ref = ? WHERE ID IN ({$phC})")
                    ->execute(array_merge([$masterId, 'G-' . $masterId], $childIds));
            }

            // Marcar mestre.
            $this->db->prepare('UPDATE Services SET is_aggregate_master = 1, grouping_ref = ? WHERE ID = ?')
                ->execute(['G-' . $masterId, $masterId]);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            return null;
        }

        return $masterId;
    }

    /** Insere uma paragem em ServiceStops (auxiliar de aggregate). */
    private function insertStop(int $masterId, int $sourceId, int $order, string $type,
        string $location, ?string $time, ?string $client, ?int $pax, ?string $ref, ?string $notes): void
    {
        $this->db->prepare('
            INSERT INTO ServiceStops
                (master_service_id, source_service_id, stop_order, stop_type,
                 location, scheduled_time, client_name, pax_total, reference_no, notes)
            VALUES (:master, :source, :ord, :type, :loc, :time, :client, :pax, :ref, :notes)
        ')->execute([
            'master' => $masterId, 'source' => $sourceId, 'ord' => $order,
            'type'   => $type,     'loc'    => $location, 'time' => $time,
            'client' => $client,   'pax'    => $pax,      'ref'  => $ref, 'notes' => $notes,
        ]);
    }

    /**
     * Desagrega toda a viagem mestre: liberta os filhos (ficam independentes),
     * apaga as paragens, e repõe o mestre como serviço normal.
     * Os dados originais de cada serviço (pickup/dropoff/hora) nunca foram
     * alterados, por isso a reversão é perfeita.
     */
    public function disaggregate(int $masterId): void
    {
        if (!$this->ownedBy($masterId)) {
            return;
        }

        $stmt = $this->db->prepare('SELECT is_aggregate_master FROM Services WHERE ID = :id');
        $stmt->execute(['id' => $masterId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || (int) $row['is_aggregate_master'] === 0) {
            return;
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare('UPDATE Services SET aggregated_into = NULL, grouping_ref = NULL WHERE aggregated_into = :mid')
                ->execute(['mid' => $masterId]);
            $this->db->prepare('UPDATE Services SET is_aggregate_master = 0, grouping_ref = NULL WHERE ID = :id')
                ->execute(['id' => $masterId]);
            $this->db->prepare('DELETE FROM ServiceStops WHERE master_service_id = :mid')
                ->execute(['mid' => $masterId]);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
        }
    }

    /**
     * Devolve as paragens de uma viagem mestre ordenadas.
     * @return array<array<string,mixed>>
     */
    public function getStops(int $masterId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM ServiceStops WHERE master_service_id = :mid ORDER BY stop_order, id');
        $stmt->execute(['mid' => $masterId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Reordena as paragens de uma viagem mestre.
     * @param array<int> $orderedIds IDs das paragens na nova ordem (índice = stop_order)
     */
    public function reorderStops(int $masterId, array $orderedIds): void
    {
        $stmt = $this->db->prepare('UPDATE ServiceStops SET stop_order = :ord WHERE id = :id AND master_service_id = :mid');
        foreach ($orderedIds as $order => $stopId) {
            $stmt->execute(['ord' => $order, 'id' => (int) $stopId, 'mid' => $masterId]);
        }
    }

    /**
     * Guarda a ordem + campos de todas as paragens de uma viagem mestre numa
     * única transacção. Cada elemento de $stops deve ter:
     *   id, stop_type ('pickup'|'dropoff'), location, scheduled_time, client_name, pax_total
     *
     * @param array<array<string,mixed>> $stops
     */
    public function saveStops(int $masterId, array $stops): void
    {
        $stmt = $this->db->prepare('
            UPDATE ServiceStops SET
                stop_order     = :ord,
                stop_type      = :type,
                location       = :loc,
                scheduled_time = :time,
                client_name    = :client,
                pax_total      = :pax
            WHERE id = :id AND master_service_id = :mid
        ');
        $this->db->beginTransaction();
        try {
            foreach ($stops as $order => $stop) {
                $type = $stop['stop_type'] === 'dropoff' ? 'dropoff' : 'pickup';
                $stmt->execute([
                    'ord'    => $order,
                    'type'   => $type,
                    'loc'    => trim((string) ($stop['location'] ?? '')),
                    'time'   => !empty($stop['scheduled_time']) ? substr((string) $stop['scheduled_time'], 0, 8) : null,
                    'client' => !empty($stop['client_name']) ? (string) $stop['client_name'] : null,
                    'pax'    => isset($stop['pax_total']) && is_numeric($stop['pax_total']) ? (int) $stop['pax_total'] : null,
                    'id'     => (int) $stop['id'],
                    'mid'    => $masterId,
                ]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Fixa a base de pagamento e (opcionalmente) o valor ao motorista,
     * calculado na atribuição pelo PricingEngine. Se $payout for null, mantém
     * o valor_motorista existente (ex.: o que veio do Excel) e só grava a base.
     */
    public function setDriverPricing(int $serviceId, string $payBasis, ?float $payout): void
    {
        if (!$this->ownedBy($serviceId)) return;
        if ($payout !== null) {
            $this->db->prepare('UPDATE Services SET pay_basis = :pb, valor_motorista = :vm WHERE ID = :id')
                ->execute(['pb' => $payBasis, 'vm' => $payout, 'id' => $serviceId]);
        } else {
            $this->db->prepare('UPDATE Services SET pay_basis = :pb WHERE ID = :id')
                ->execute(['pb' => $payBasis, 'id' => $serviceId]);
        }
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
        string $orderDir,
        string $dateFrom = '',
        string $dateTo   = ''
    ): array {
        $cols = 's.ID, s.serviceDate, s.serviceStartTime, s.paxADT, s.paxCHD, s.paxBBY,
                 s.serviceStartPoint, s.serviceTargetPoint, s.FlightNumber,
                 s.NomeCliente, s.ClientNumber, s.serviceType, s.total_price,
                 s.valor_motorista, s.leg_code,
                 s.grouping_ref, s.is_aggregate_master,
                 s.has_key, s.partner_id, s.status_pedido,
                 COALESCE(s.status_id, 0) AS status_id,
                 s.driver_note, s.admin_note,
                 s.original_company_id, oc.name AS origin_company_name';

        [$join, $where, $baseParams, $driverCol, $countJoin] = $this->filterFragments($filter);

        // Filhos de viagens agregadas nunca aparecem na listagem — só o mestre.
        $where .= ' AND s.aggregated_into IS NULL';

        // Delegated trips: original_company_id = mine (company_id has already moved to the partner).
        if ($filter === 'delegated' && $this->companyId !== null) {
            $tenancyWhere  = ' AND s.original_company_id = ?';
            $tenancyParams = [$this->companyId];
        } elseif ($filter === 'today' && $this->companyId !== null) {
            // Include rides delegated out by this company so they remain visible
            // in the Today tab for flow-control purposes.
            $tenancyWhere  = ' AND (s.company_id = ? OR s.original_company_id = ?)';
            $tenancyParams = [$this->companyId, $this->companyId];
        } else {
            $tenancyWhere  = $this->companyId !== null ? ' AND s.company_id = ?' : '';
            $tenancyParams = $this->companyId !== null ? [$this->companyId]      : [];
        }

        // Optional date-range filter (part of the base dataset, like tenancy).
        $dateWhere  = '';
        $dateParams = [];
        if ($dateFrom !== '' && strtotime($dateFrom)) { $dateWhere .= ' AND s.serviceDate >= ?'; $dateParams[] = $dateFrom; }
        if ($dateTo   !== '' && strtotime($dateTo))   { $dateWhere .= ' AND s.serviceDate <= ?'; $dateParams[] = $dateTo; }

        $searchWhere  = '';
        $searchParams = [];
        if ($search !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $searchWhere  = ' AND (s.NomeCliente LIKE ? OR s.FlightNumber LIKE ?'
                          . ' OR s.serviceStartPoint LIKE ? OR s.serviceTargetPoint LIKE ?'
                          . ' OR s.grouping_ref LIKE ? OR s.reference_no LIKE ?)';
            $searchParams = [$like, $like, $like, $like, $like, $like];
        }

        // COUNT uses the minimal join: COUNT(*) when no row-multiplying join is
        // needed, COUNT(DISTINCT) only when a Services_Rides join is present.
        $countBase = $countJoin === ''
            ? "SELECT COUNT(*) FROM Services s WHERE {$where}"
            : "SELECT COUNT(DISTINCT s.ID) FROM Services s {$countJoin} WHERE {$where}";

        $totalStmt = $this->db->prepare("{$countBase}{$tenancyWhere}{$dateWhere}");
        $totalStmt->execute(array_merge($baseParams, $tenancyParams, $dateParams));
        $total = (int) $totalStmt->fetchColumn();

        $filteredStmt = $this->db->prepare("{$countBase}{$tenancyWhere}{$dateWhere}{$searchWhere}");
        $filteredStmt->execute(array_merge($baseParams, $tenancyParams, $dateParams, $searchParams));
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
        $dataSql = "SELECT {$cols},
                    COALESCE((SELECT COUNT(*) FROM ServiceStops WHERE master_service_id = s.ID), 0) AS stop_count,
                    {$driverCol}, p.name AS partner_name
                    FROM Services s {$join}
                    LEFT JOIN Companies oc ON s.original_company_id = oc.id
                    WHERE {$where}{$tenancyWhere}{$dateWhere}{$searchWhere}
                    ORDER BY {$orderSql}
                    LIMIT {$length} OFFSET {$start}";
        $dataStmt = $this->db->prepare($dataSql);
        $dataStmt->execute(array_merge($baseParams, $tenancyParams, $dateParams, $searchParams));

        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
        if ($filter === 'delegated') {
            foreach ($rows as &$row) {
                $row['_delegated_out'] = 1;
            }
            unset($row);
        } elseif ($filter === 'today' && $this->companyId !== null) {
            foreach ($rows as &$row) {
                if (!empty($row['original_company_id']) && (int) $row['original_company_id'] === $this->companyId) {
                    $row['_delegated_out'] = 1;
                }
            }
            unset($row);
        }

        return [
            'data'            => $rows,
            'recordsTotal'    => $total,
            'recordsFiltered' => $filtered,
        ];
    }

    /**
     * @return array{0:string, 1:string, 2:array<mixed>, 3:string, 4:string}
     *         [dataJoin, where, params, driverCol, countJoin]
     * countJoin is the MINIMAL join needed to evaluate the WHERE — used by the
     * COUNT queries so they don't drag in the Users/Companies joins (which only
     * exist to show names). This keeps COUNT(*) fast as the table grows.
     */
    private function filterFragments(string $filter): array
    {
        $joinSR  = 'LEFT JOIN Services_Rides sr ON s.ID = sr.RideID';
        $joinU   = 'LEFT JOIN Users u ON sr.UserID = u.ID';
        $joinP   = 'LEFT JOIN Users p ON s.partner_id = p.ID';
        $innerSR = 'INNER JOIN Services_Rides sr ON s.ID = sr.RideID';
        $active  = "(s.status_pedido = 'aprovado' OR s.status_pedido IS NULL)";

        return match ($filter) {
            'requests' => [
                $joinP,
                "s.status_pedido = 'pendente'",
                [],
                'NULL AS driverName',
                '', // count: no join needed
            ],
            'today' => [
                // dc join needed to show the partner company name on delegated-out rows
                "{$joinSR} {$joinU} {$joinP} LEFT JOIN Companies dc ON s.company_id = dc.id",
                "s.serviceDate = CURDATE() AND {$active}",
                [],
                "CASE WHEN s.original_company_id IS NOT NULL THEN COALESCE(u.name, dc.name) ELSE u.name END AS driverName",
                '',
            ],
            'tomorrow' => [
                "{$joinSR} {$joinU} {$joinP}",
                "s.serviceDate = CURDATE() + INTERVAL 1 DAY AND {$active}",
                [],
                'u.name AS driverName',
                '',
            ],
            'pending' => [
                // Anti-join via NOT EXISTS — faster and more scalable than LEFT JOIN + IS NULL,
                // and lets both COUNT(*) and the data query skip the Services_Rides join entirely.
                $joinP,
                "NOT EXISTS (SELECT 1 FROM Services_Rides sr WHERE sr.RideID = s.ID) AND {$active}",
                [],
                'NULL AS driverName',
                '',
            ],
            'assigned' => [
                "{$innerSR} INNER JOIN Users u ON sr.UserID = u.ID {$joinP}",
                $active,
                [],
                'u.name AS driverName',
                $innerSR, // count needs the INNER join to keep only assigned rides
            ],
            'delegated' => [
                "{$joinSR} {$joinU} LEFT JOIN Companies dc ON s.company_id = dc.id {$joinP}",
                '1=1',
                [],
                "COALESCE(u.name, dc.name) AS driverName",
                '',
            ],
            default => [
                "{$joinSR} {$joinU} {$joinP}",
                $active,
                [],
                'u.name AS driverName',
                '',
            ],
        };
    }

    /** @return array<array<string,mixed>> */
    public function listForAdmin(string $filter): array
    {
        $cols = 's.ID, s.serviceDate, s.serviceStartTime, s.paxADT, s.paxCHD, s.paxBBY,
                 s.serviceStartPoint, s.serviceTargetPoint, s.FlightNumber,
                 s.NomeCliente, s.ClientNumber, s.serviceType, s.total_price,
                 s.valor_motorista,
                 s.grouping_ref, s.is_aggregate_master,
                 s.has_key, s.partner_id, s.status_pedido';
        // Filhos de viagens agregadas escondidos também aqui (schedule board, etc.)
        $csc  = $this->sc('AND', 's') . ' AND s.aggregated_into IS NULL';
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
        if ($this->companyId !== null) {
            $sql  = "SELECT COUNT(*) FROM Services WHERE serviceDate = CURDATE() AND (status_pedido = 'aprovado' OR status_pedido IS NULL) AND (company_id = :cid OR original_company_id = :cid2)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['cid' => $this->companyId, 'cid2' => $this->companyId]);
        } else {
            $sql  = "SELECT COUNT(*) FROM Services WHERE serviceDate = CURDATE() AND (status_pedido = 'aprovado' OR status_pedido IS NULL)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([]);
        }
        return (int) $stmt->fetchColumn();
    }

    public function countTomorrow(): int
    {
        $sql  = "SELECT COUNT(*) FROM Services WHERE serviceDate = CURDATE() + INTERVAL 1 DAY AND (status_pedido = 'aprovado' OR status_pedido IS NULL) " . $this->sc('AND');
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->cb());
        return (int) $stmt->fetchColumn();
    }

    public function countUnassigned(): int
    {
        $sql  = "SELECT COUNT(*) FROM Services s
                 WHERE NOT EXISTS (SELECT 1 FROM Services_Rides sr WHERE sr.RideID = s.ID)
                   AND (s.status_pedido = 'aprovado' OR s.status_pedido IS NULL) " . $this->sc('AND', 's');
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->cb());
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): void
    {
        if (!$this->ownedBy($id)) return;
        $price = (float) ($data['total_price'] ?? 0);
        // price_explicit: set to 1 if a price is explicitly provided; otherwise keep existing value
        // (edit form has the price field disabled so it often submits as 0 — don't reset the flag)
        $this->db->prepare('
            UPDATE Services
            SET serviceDate=:date, serviceStartTime=:time, serviceStartPoint=:pickup,
                serviceTargetPoint=:dropoff, paxADT=:adults, paxCHD=:children,
                paxBBY=:bby,
                FlightNumber=:flight, NomeCliente=:client, ClientNumber=:phone,
                total_price=:price,
                price_explicit = IF(:price2 > 0, 1, price_explicit),
                valor_motorista=:driver_pay,
                admin_note=:admin_note,
                leg_code=:leg_code
            WHERE ID = :id
        ')->execute([
            'date'       => $data['serviceDate'],
            'time'       => $data['serviceStartTime'],
            'pickup'     => $data['serviceStartPoint'],
            'dropoff'    => $data['serviceTargetPoint'],
            'adults'     => (int) ($data['paxADT'] ?? 0),
            'children'   => (int) ($data['paxCHD'] ?? 0),
            'bby'        => (int) ($data['paxBBY'] ?? 0),
            'flight'     => $data['FlightNumber'] ?? null,
            'client'     => $data['NomeCliente']  ?? null,
            'phone'      => $data['ClientNumber'] ?? null,
            'price'      => $price,
            'price2'     => $price,
            'driver_pay' => isset($data['valor_motorista']) && $data['valor_motorista'] !== null ? (float) $data['valor_motorista'] : null,
            'admin_note' => (isset($data['admin_note']) && trim((string) $data['admin_note']) !== '') ? trim((string) $data['admin_note']) : null,
            'leg_code'   => (isset($data['leg_code']) && in_array($data['leg_code'], ['IN', 'OT'], true)) ? $data['leg_code'] : null,
            'id'         => $id,
        ]);
    }

    public function deleteBulk(array $ids): void
    {
        if (empty($ids)) return;

        // Filter to only IDs belonging to this company (prevents cross-tenant bulk delete)
        if ($this->companyId !== null) {
            $ph      = implode(',', array_fill(0, count($ids), '?'));
            $owned   = $this->db->prepare("SELECT ID FROM Services WHERE ID IN ({$ph}) AND company_id = ?");
            $owned->execute(array_merge(array_values($ids), [$this->companyId]));
            $ids = array_column($owned->fetchAll(PDO::FETCH_ASSOC), 'ID');
            if (empty($ids)) return;
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
        if (!$this->ownedBy($id)) return;
        $this->db->prepare('UPDATE Services SET status_pedido = :s WHERE ID = :id')
            ->execute(['s' => $status, 'id' => $id]);
    }

    /**
     * Recall or reject a delegated trip — reverts company_id back to original_company_id.
     * Works for both the sender (recalls it back) and the receiver (rejects/returns it).
     * Caller must be either the original owner or the current handler.
     */
    public function recallDelegation(int $rideId, int $callerCompanyId): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('
                UPDATE Services
                SET company_id = original_company_id, original_company_id = NULL
                WHERE ID = :id
                  AND original_company_id IS NOT NULL
                  AND (original_company_id = :cid OR company_id = :cid2)
            ');
            $stmt->execute(['id' => $rideId, 'cid' => $callerCompanyId, 'cid2' => $callerCompanyId]);
            $updated = $stmt->rowCount() > 0;

            if ($updated) {
                // Remove any driver assignment the partner may have added
                $this->db->prepare('DELETE FROM Services_Rides WHERE RideID = :id')
                    ->execute(['id' => $rideId]);
            }

            $this->db->commit();
            return $updated;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Delegate a trip to another company: sets company_id = target and records the original owner.
     * Only succeeds if the ride currently belongs to originCompanyId and is not yet delegated.
     */
    public function delegateTo(int $rideId, int $targetCompanyId, int $originCompanyId): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('
                UPDATE Services
                SET company_id = :target, original_company_id = :origin
                WHERE ID = :id AND company_id = :current AND original_company_id IS NULL
            ');
            $stmt->execute([
                'target'  => $targetCompanyId,
                'origin'  => $originCompanyId,
                'id'      => $rideId,
                'current' => $originCompanyId,
            ]);
            $updated = $stmt->rowCount() > 0;

            if ($updated) {
                // Only remove the driver if they don't also belong to the target company.
                // Shared drivers (in UserCompanies for both companies) stay assigned.
                $driverStmt = $this->db->prepare(
                    'SELECT UserID FROM Services_Rides WHERE RideID = :rid LIMIT 1'
                );
                $driverStmt->execute(['rid' => $rideId]);
                $assignedUserId = $driverStmt->fetchColumn();

                $keepDriver = false;
                if ($assignedUserId !== false) {
                    $inTargetStmt = $this->db->prepare('
                        SELECT 1 FROM Users u
                        LEFT JOIN UserCompanies uc ON uc.user_id = u.id
                        WHERE u.id = :uid
                          AND (u.company_id = :cid OR uc.company_id = :cid2)
                        LIMIT 1
                    ');
                    $inTargetStmt->execute([
                        'uid'  => $assignedUserId,
                        'cid'  => $targetCompanyId,
                        'cid2' => $targetCompanyId,
                    ]);
                    $keepDriver = $inTargetStmt->fetchColumn() !== false;
                }

                if (!$keepDriver) {
                    $this->db->prepare('DELETE FROM Services_Rides WHERE RideID = :id')
                        ->execute(['id' => $rideId]);
                }
            }

            $this->db->commit();
            return $updated;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Trips that the given company delegated out to partners.
     * These no longer appear in the normal scoped view because company_id changed.
     * @return array{data: array<array<string,mixed>>, recordsTotal: int, recordsFiltered: int}
     */
    public function listDelegatedOutPaginated(
        int    $companyId,
        int    $start,
        int    $length,
        string $search
    ): array {
        $like         = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
        $searchWhere  = $search !== '' ? ' AND (s.NomeCliente LIKE ? OR s.serviceStartPoint LIKE ? OR s.serviceTargetPoint LIKE ?)' : '';
        $searchParams = $search !== '' ? [$like, $like, $like] : [];

        $countSql = "SELECT COUNT(*) FROM Services s WHERE s.original_company_id = ?{$searchWhere}";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute(array_merge([$companyId], $searchParams));
        $total = (int) $countStmt->fetchColumn();

        $dataSql = "
            SELECT s.ID, s.serviceDate, s.serviceStartTime,
                   s.serviceStartPoint, s.serviceTargetPoint,
                   s.NomeCliente, s.FlightNumber, s.paxADT, s.paxCHD, s.paxBBY,
                   s.total_price, s.status_pedido,
                   c.name AS current_company_name
            FROM Services s
            JOIN Companies c ON s.company_id = c.id
            WHERE s.original_company_id = ?{$searchWhere}
            ORDER BY s.serviceDate DESC, s.serviceStartTime DESC
            LIMIT {$length} OFFSET {$start}
        ";
        $dataStmt = $this->db->prepare($dataSql);
        $dataStmt->execute(array_merge([$companyId], $searchParams));

        return [
            'data'         => $dataStmt->fetchAll(PDO::FETCH_ASSOC),
            'recordsTotal' => $total,
        ];
    }

    /** @return array<array<string,mixed>> */
    public function markVoucher(int $id, string $photoPath): void
    {
        if (!$this->ownedBy($id)) return;
        $this->db->prepare('UPDATE Services SET voucher_photo = :photo WHERE ID = :id')
            ->execute(['photo' => $photoPath, 'id' => $id]);
    }

    public function listVouchersForAdmin(): array
    {
        $sql  = "SELECT s.ID, s.serviceDate, s.serviceStartTime,
                        s.NomeCliente, s.serviceStartPoint, s.serviceTargetPoint,
                        s.voucher_photo,
                        u.name AS driverName
                 FROM Services s
                 LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
                 LEFT JOIN Users u ON sr.UserID = u.ID
                 WHERE s.voucher_photo IS NOT NULL " . $this->sc('AND', 's') . "
                 ORDER BY s.serviceDate DESC, s.serviceStartTime DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->cb());
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listNoShowsForAdmin(): array
    {
        $sql  = "SELECT s.ID, s.serviceDate, s.serviceStartTime,
                        s.serviceStartPoint, s.serviceTargetPoint,
                        s.noShowPhotoPath, s.noShowReportPath,
                        u.name AS driverName
                 FROM Services s
                 LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
                 LEFT JOIN Users u ON sr.UserID = u.ID
                 WHERE s.noShowStatus = 1 " . $this->sc('AND', 's') . "
                 ORDER BY s.serviceDate DESC, s.serviceStartTime DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->cb());
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Approximate search over no-show records for SyncAI — "find the no-show
     * from a few months ago" with a driver name and rough dates, not an exact ID.
     * @return array<array<string,mixed>>
     */
    public function searchNoShows(?string $driverName, ?string $dateFrom, ?string $dateTo, ?string $route): array
    {
        $where  = ['s.noShowStatus = 1'];
        $params = $this->cb();
        if ($driverName) { $where[] = 'u.name LIKE :dname'; $params['dname'] = '%' . $driverName . '%'; }
        if ($dateFrom)   { $where[] = 's.serviceDate >= :dfrom'; $params['dfrom'] = $dateFrom; }
        if ($dateTo)     { $where[] = 's.serviceDate <= :dto'; $params['dto'] = $dateTo; }
        if ($route)      { $where[] = '(s.serviceStartPoint LIKE :route1 OR s.serviceTargetPoint LIKE :route2)'; $params['route1'] = $params['route2'] = '%' . $route . '%'; }
        $companyClause = $this->companyId !== null ? 'AND s.company_id = :company_id' : '';
        $sql = "SELECT s.ID, s.serviceDate, s.serviceStartTime, s.serviceStartPoint, s.serviceTargetPoint,
                       s.noShowPhotoPath, s.noShowReportPath, s.NomeCliente, s.FlightNumber, s.paxADT, s.paxCHD, u.name AS driverName
                FROM Services s
                LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
                LEFT JOIN Users u ON sr.UserID = u.ID
                WHERE " . implode(' AND ', $where) . " {$companyClause}
                ORDER BY s.serviceDate DESC, s.serviceStartTime DESC
                LIMIT 15";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Same shape as searchNoShows() but over voucher photos. */
    public function searchVouchers(?string $driverName, ?string $dateFrom, ?string $dateTo, ?string $route, ?string $clientName): array
    {
        $where  = ['s.voucher_photo IS NOT NULL'];
        $params = $this->cb();
        if ($driverName) { $where[] = 'u.name LIKE :dname'; $params['dname'] = '%' . $driverName . '%'; }
        if ($clientName) { $where[] = 's.NomeCliente LIKE :cname'; $params['cname'] = '%' . $clientName . '%'; }
        if ($dateFrom)   { $where[] = 's.serviceDate >= :dfrom'; $params['dfrom'] = $dateFrom; }
        if ($dateTo)     { $where[] = 's.serviceDate <= :dto'; $params['dto'] = $dateTo; }
        if ($route)      { $where[] = '(s.serviceStartPoint LIKE :route1 OR s.serviceTargetPoint LIKE :route2)'; $params['route1'] = $params['route2'] = '%' . $route . '%'; }
        $companyClause = $this->companyId !== null ? 'AND s.company_id = :company_id' : '';
        $sql = "SELECT s.ID, s.serviceDate, s.serviceStartTime, s.NomeCliente, s.FlightNumber, s.paxADT, s.paxCHD,
                       s.serviceStartPoint, s.serviceTargetPoint, s.voucher_photo, u.name AS driverName
                FROM Services s
                LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
                LEFT JOIN Users u ON sr.UserID = u.ID
                WHERE " . implode(' AND ', $where) . " {$companyClause}
                ORDER BY s.serviceDate DESC, s.serviceStartTime DESC
                LIMIT 15";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** General ride/service search for SyncAI — any combination of filters, all optional. */
    public function searchRides(?string $driverName, ?string $clientName, ?string $dateFrom, ?string $dateTo, ?string $route): array
    {
        $where  = ['1=1'];
        $params = $this->cb();
        if ($driverName) { $where[] = 'u.name LIKE :dname'; $params['dname'] = '%' . $driverName . '%'; }
        if ($clientName) { $where[] = 's.NomeCliente LIKE :cname'; $params['cname'] = '%' . $clientName . '%'; }
        if ($dateFrom)   { $where[] = 's.serviceDate >= :dfrom'; $params['dfrom'] = $dateFrom; }
        if ($dateTo)     { $where[] = 's.serviceDate <= :dto'; $params['dto'] = $dateTo; }
        if ($route)      { $where[] = '(s.serviceStartPoint LIKE :route1 OR s.serviceTargetPoint LIKE :route2)'; $params['route1'] = $params['route2'] = '%' . $route . '%'; }
        $companyClause = $this->companyId !== null ? 'AND s.company_id = :company_id' : '';
        $sql = "SELECT s.ID, s.serviceDate, s.serviceStartTime, s.NomeCliente, s.FlightNumber, s.paxADT, s.paxCHD,
                       s.serviceStartPoint, s.serviceTargetPoint, s.noShowStatus, s.total_price, u.name AS driverName
                FROM Services s
                LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
                LEFT JOIN Users u ON sr.UserID = u.ID
                WHERE " . implode(' AND ', $where) . " {$companyClause}
                ORDER BY s.serviceDate DESC, s.serviceStartTime DESC
                LIMIT 20";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Stats for one named driver — SyncAI's "how's X doing" tool. */
    public function statsForDriverByName(string $driverName, int $month, int $year): ?array
    {
        $ucsc  = $this->companyId !== null ? 'AND u.company_id = :company_id' : '';
        $stmt  = $this->db->prepare("
            SELECT u.id, u.name,
                   COUNT(s.ID) AS trips_month,
                   AVG(s.driver_rating) AS avg_rating,
                   SUM(s.noShowStatus) AS no_shows_month
            FROM Users u
            LEFT JOIN Services_Rides sr ON sr.UserID = u.id
            LEFT JOIN Services s ON s.ID = sr.RideID AND MONTH(s.serviceDate) = :month AND YEAR(s.serviceDate) = :year
            WHERE u.name LIKE :dname {$ucsc}
            GROUP BY u.id, u.name
            LIMIT 1
        ");
        $params = array_merge(['dname' => '%' . $driverName . '%', 'month' => $month, 'year' => $year], $this->cb());
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /** @return array<string,mixed>|null */
    public function findWithPartner(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT s.NomeCliente, s.serviceStartPoint, s.serviceTargetPoint,
                   s.serviceDate, s.serviceStartTime,
                   s.noShowLat, s.noShowLng, s.noShowAt,
                   s.ts_start_pickup, s.ts_arrived_pickup,
                   s.company_id,
                   c.name  AS company_name,
                   s.partner_id,
                   p.email AS partner_email, p.name AS partner_name,
                   d.name  AS driver_name
            FROM Services s
            LEFT JOIN Companies c         ON s.company_id = c.id
            LEFT JOIN Users p             ON s.partner_id = p.id
            LEFT JOIN Services_Rides sr   ON s.ID = sr.RideID
            LEFT JOIN Users d             ON sr.UserID = d.id
            WHERE s.ID = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getTimestamps(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT ts_start_pickup, ts_arrived_pickup, ts_with_client, ts_start_trip, ts_completed,
                    is_aggregate_master
             FROM Services WHERE ID = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        // Multi-stop (aggregate master): include per-stop timeline so the admin sees
        // real progress instead of the generic 5-step single-ride timeline.
        $row['is_aggregate_master'] = (int) $row['is_aggregate_master'];
        if ($row['is_aggregate_master'] === 1) {
            $sStmt = $this->db->prepare(
                'SELECT stop_order, stop_type, location, client_name, scheduled_time,
                        ts_arrived, ts_departed
                 FROM ServiceStops
                 WHERE master_service_id = :mid
                 ORDER BY stop_order, id'
            );
            $sStmt->execute(['mid' => $id]);
            $row['stops'] = $sStmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return $row;
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

    /**
     * A driver's rides for the chat's optional "associate to a ride" topic picker —
     * informational only, not a scoping key. With no $term, the most recent 15 (a
     * driver can rack up thousands of rides over time, so browsing without search
     * only ever needs to show a small default list). With a $term, searches by
     * reference number, client name, date, or pickup/dropoff across all of that
     * driver's rides, not just the recent ones — so an old May service is still findable.
     * @return array<array{id:int, label:string}>
     */
    public function recentForDriver(int $driverId, ?string $term = null): array
    {
        $term = trim((string) $term);
        if ($term === '') {
            $stmt = $this->db->prepare('
                SELECT s.ID, s.serviceDate, s.serviceStartTime, s.serviceStartPoint, s.serviceTargetPoint
                FROM Services_Rides sr
                JOIN Services s ON s.ID = sr.RideID
                WHERE sr.UserID = :did
                ORDER BY s.serviceDate DESC, s.serviceStartTime DESC
                LIMIT 15
            ');
            $stmt->execute(['did' => $driverId]);
        } else {
            $like = '%' . $term . '%';
            $stmt = $this->db->prepare('
                SELECT s.ID, s.serviceDate, s.serviceStartTime, s.serviceStartPoint, s.serviceTargetPoint
                FROM Services_Rides sr
                JOIN Services s ON s.ID = sr.RideID
                WHERE sr.UserID = :did
                  AND (s.reference_no LIKE :t1 OR s.NomeCliente LIKE :t2 OR s.serviceStartPoint LIKE :t3
                       OR s.serviceTargetPoint LIKE :t4 OR s.serviceDate LIKE :t5 OR s.ID LIKE :t6)
                ORDER BY s.serviceDate DESC, s.serviceStartTime DESC
                LIMIT 20
            ');
            $stmt->execute(['did' => $driverId, 't1' => $like, 't2' => $like, 't3' => $like, 't4' => $like, 't5' => $like, 't6' => $like]);
        }
        return array_map(
            static fn(array $r): array => ['id' => (int) $r['ID'], 'label' => self::formatRideLabel($r)],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /** Same label format as recentForDriver(), for a single known ride — used by the chat's pinned ride banner. */
    public function labelFor(int $rideId): ?string
    {
        $stmt = $this->db->prepare('
            SELECT ID, serviceDate, serviceStartTime, serviceStartPoint, serviceTargetPoint
            FROM Services WHERE ID = :id
        ');
        $stmt->execute(['id' => $rideId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? self::formatRideLabel($row) : null;
    }

    private static function formatRideLabel(array $r): string
    {
        $date = substr((string) $r['serviceDate'], 5); // mm-dd
        $time = substr((string) $r['serviceStartTime'], 0, 5);
        return "#{$r['ID']} · {$date} {$time} · {$r['serviceStartPoint']} → {$r['serviceTargetPoint']}";
    }

    /** @return array<array<string,mixed>> */
    public function driverDashboardRides(int $driverId, ?int $serviceType = null): array
    {
        $sql    = 'SELECT s.ID AS ServiceID, s.serviceDate, s.serviceStartTime,
                          s.serviceStartPoint, s.serviceTargetPoint, s.leg_code,
                          s.paxADT, s.paxCHD, s.paxBBY,
                          s.FlightNumber, s.NomeCliente,
                          s.ClientNumber, s.serviceType,
                          IF(s.price_explicit = 1, s.total_price, NULL) AS total_price,
                          s.has_key,
                          s.partner_id, COALESCE(s.status_id, 0) AS status_id,
                          s.driver_note, s.admin_note,
                          u.name AS AgencyName, u.phone AS AgencyPhone,
                          COALESCE(s.is_aggregate_master, 0) AS is_aggregate_master
                   FROM Services_Rides sr
                   INNER JOIN Services s ON sr.RideID = s.ID
                   LEFT  JOIN Users u    ON s.partner_id = u.id
                   WHERE sr.UserID = :uid
                     AND s.aggregated_into IS NULL
                     ' . $this->sc('AND', 's');
        $params = array_merge(['uid' => $driverId], $this->cb());
        if ($serviceType !== null) {
            $sql .= ' AND s.serviceType = :stype';
            $params['stype'] = $serviceType;
        }
        $sql .= ' ORDER BY s.serviceDate ASC, s.serviceStartTime ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // For aggregate masters, fetch stops in a single query and merge by master ID
        $masterIds = array_values(array_map(
            static fn(array $r): int => (int) $r['ServiceID'],
            array_filter($rows, static fn(array $r): bool => (int) $r['is_aggregate_master'] === 1)
        ));

        $stopsByMaster = [];
        if ($masterIds !== []) {
            $ph    = implode(',', array_fill(0, count($masterIds), '?'));
            $sStmt = $this->db->prepare(
                "SELECT ss.id, ss.master_service_id, ss.source_service_id, ss.stop_type,
                        ss.location, ss.scheduled_time, ss.client_name, ss.pax_total,
                        ss.ts_arrived, ss.ts_departed,
                        s.FlightNumber AS flight_number, s.ClientNumber AS client_number,
                        s.paxADT AS pax_adt, s.paxCHD AS pax_chd, s.paxBBY AS pax_bby
                 FROM ServiceStops ss
                 LEFT JOIN Services s ON ss.source_service_id = s.ID
                 WHERE ss.master_service_id IN ({$ph})
                 ORDER BY ss.master_service_id, ss.stop_order, ss.id"
            );
            $sStmt->execute($masterIds);
            foreach ($sStmt->fetchAll(PDO::FETCH_ASSOC) as $s) {
                $stopsByMaster[(int) $s['master_service_id']][] = [
                    'id'           => (int) $s['id'],
                    'source_id'    => (int) $s['source_service_id'],
                    'type'         => $s['stop_type'],
                    'location'     => $s['location'],
                    'time'         => $s['scheduled_time'],
                    'client'       => $s['client_name'],
                    'client_phone' => $s['client_number'],
                    'pax'          => $s['pax_total'],
                    'pax_adt'      => (int) ($s['pax_adt'] ?? 0),
                    'pax_chd'      => (int) ($s['pax_chd'] ?? 0),
                    'pax_bby'      => (int) ($s['pax_bby'] ?? 0),
                    'flight'       => $s['flight_number'],
                    'ts_arrived'   => $s['ts_arrived'],
                    'ts_departed'  => $s['ts_departed'],
                ];
            }
        }

        foreach ($rows as &$row) {
            $row['stops'] = $stopsByMaster[(int) $row['ServiceID']] ?? [];
        }
        return $rows;
    }

    public function updateStopTimestamp(int $stopId, int $masterId, string $field): bool
    {
        if (!in_array($field, ['ts_arrived', 'ts_departed'], true)) {
            return false;
        }
        $stmt = $this->db->prepare(
            "UPDATE ServiceStops SET {$field} = NOW() WHERE id = :id AND master_service_id = :mid"
        );
        $stmt->execute(['id' => $stopId, 'mid' => $masterId]);
        return $stmt->rowCount() > 0;
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
                        SUM(CASE WHEN status_pedido = "aprovado" THEN 1 ELSE 0 END) AS approved,
                        SUM(CASE WHEN serviceDate = CURDATE() THEN 1 ELSE 0 END) AS today,
                        SUM(CASE WHEN MONTH(serviceDate) = MONTH(CURDATE()) AND YEAR(serviceDate) = YEAR(CURDATE()) THEN 1 ELSE 0 END) AS this_month,
                        SUM(CASE WHEN noShowStatus = 1 THEN 1 ELSE 0 END) AS noshows
                 FROM Services WHERE partner_id = :pid';
        // No company scope — partner_id already isolates the partner's own rides.
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pid' => $partnerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'total'      => (int) ($row['total']      ?? 0),
            'pending'    => (int) ($row['pending']     ?? 0),
            'approved'   => (int) ($row['approved']    ?? 0),
            'today'      => (int) ($row['today']       ?? 0),
            'this_month' => (int) ($row['this_month']  ?? 0),
            'noshows'    => (int) ($row['noshows']     ?? 0),
        ];
    }

    /** @return array<array<string,mixed>> No-shows for rides belonging to a partner. */
    public function partnerNoShows(int $partnerId): array
    {
        $sql  = "SELECT s.ID, s.serviceDate, s.serviceStartTime,
                        s.serviceStartPoint, s.serviceTargetPoint,
                        s.NomeCliente, s.noShowPhotoPath, s.noShowReportPath,
                        s.noShowLat, s.noShowLng
                 FROM Services s
                 WHERE s.partner_id = :pid AND s.noShowStatus = 1
                 ORDER BY s.serviceDate DESC, s.serviceStartTime DESC";
        // No company scope — partner_id already isolates the partner's own rides.
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pid' => $partnerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function partnerRidesByStatus(int $partnerId, string $status): array
    {
        // No company scope — partner_id already isolates the partner's own rides,
        // and a company mismatch must never hide a partner's own request from them.
        $sql  = 'SELECT * FROM Services WHERE partner_id = :pid AND status_pedido = :status ORDER BY serviceDate DESC, serviceStartTime DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pid' => $partnerId, 'status' => $status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createForPartner(int $partnerId, array $data): int
    {
        $cid  = $this->companyId; // always use session company — never trust caller-supplied company_id
        $stmt = $this->db->prepare('
            INSERT INTO Services (
                serviceDate, serviceStartTime, serviceStartPoint, serviceTargetPoint,
                paxADT, paxCHD, paxBBY, NomeCliente, FlightNumber, partner_id,
                status_pedido, serviceType, ClientNumber, total_price, has_key, company_id, admin_note
            ) VALUES (
                :date, :time, :pickup, :dropoff,
                :pax_adt, :pax_chd, :bby, :client_name, :flight, :partner_id,
                "pendente", 1, :client_phone, :price, :has_key, :company_id, :admin_note
            )
        ');
        $note = isset($data['admin_note']) && trim((string) $data['admin_note']) !== ''
            ? trim((string) $data['admin_note']) : null;
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
            'admin_note'   => $note,
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
            'SELECT COUNT(*) FROM Services WHERE ID = :id AND partner_id = :pid AND COALESCE(status_id, 0) < 3'
        );
        $check->execute(['id' => $rideId, 'pid' => $partnerId]);
        if ((int) $check->fetchColumn() === 0) {
            return false;
        }

        $note = isset($data['admin_note']) && trim((string) $data['admin_note']) !== ''
            ? trim((string) $data['admin_note']) : null;
        $this->db->prepare('
            UPDATE Services
            SET serviceDate=:date, serviceStartTime=:time,
                serviceStartPoint=:pickup, serviceTargetPoint=:dropoff,
                paxADT=:adt, paxCHD=:chd, paxBBY=:bby,
                FlightNumber=:flight, NomeCliente=:client,
                ClientNumber=:phone, admin_note=:admin_note
            WHERE ID = :id AND partner_id = :pid AND COALESCE(status_id, 0) < 3
        ')->execute([
            'date'       => $data['date'],
            'time'       => $data['time'],
            'pickup'     => $data['pickup'],
            'dropoff'    => $data['dropoff'],
            'adt'        => (int) ($data['pax_adt']  ?? 1),
            'chd'        => (int) ($data['pax_chd']  ?? 0),
            'bby'        => (int) ($data['pax_bby']  ?? 0),
            'flight'     => $data['flight']       ?? '',
            'client'     => $data['client_name']  ?? '',
            'phone'      => $data['client_phone'] ?? '',
            'admin_note' => $note,
            'id'         => $rideId,
            'pid'        => $partnerId,
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

    /**
     * Full company ranking for SyncAI's driver-comparison tool — every driver
     * with all three metrics for one month, so it can answer "who's top/bottom"
     * on trips, rating, or no-shows without a separate query per driver.
     * @return array<array<string,mixed>>
     */
    public function driverRankingForMonth(int $month, int $year): array
    {
        $ucsc = $this->companyId !== null ? 'AND u.company_id = :company_id' : '';
        $stmt = $this->db->prepare("
            SELECT u.name,
                   COUNT(CASE WHEN MONTH(s.serviceDate) = :month1 AND YEAR(s.serviceDate) = :year1 THEN sr.RideID END) AS trips,
                   AVG(CASE WHEN MONTH(s.serviceDate) = :month2 AND YEAR(s.serviceDate) = :year2 THEN s.driver_rating END) AS rating,
                   SUM(CASE WHEN MONTH(s.serviceDate) = :month3 AND YEAR(s.serviceDate) = :year3 THEN s.noShowStatus ELSE 0 END) AS no_shows
            FROM Users u
            LEFT JOIN Services_Rides sr ON u.id = sr.UserID
            LEFT JOIN Services s ON sr.RideID = s.ID
            WHERE u.role = 2 {$ucsc}
            GROUP BY u.id, u.name
        ");
        $stmt->execute(array_merge(
            ['month1' => $month, 'year1' => $year, 'month2' => $month, 'year2' => $year, 'month3' => $month, 'year3' => $year],
            $this->cb()
        ));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ── Scoping helpers ────────────────────────────────────────────────────

    /**
     * Returns an AND/WHERE clause for company scoping.
     * @param string $prefix  'AND' or 'WHERE'
     * @param string $alias   table alias ('s', 'u', or '' for no alias)
     */
    /**
     * Returns a date→count map of rides assigned to a driver for a given month.
     * @return array<string,int>  e.g. ['2026-05-14' => 3, '2026-05-20' => 1]
     */
    public function forDriverMonthCounts(int $driverId, string $yearMonth): array
    {
        $from = $yearMonth . '-01';
        $to   = (new \DateTimeImmutable($from))->modify('last day of this month')->format('Y-m-d');
        $sql  = 'SELECT s.serviceDate, COUNT(*) AS cnt
                 FROM Services s
                 JOIN Services_Rides sr ON sr.RideID = s.ID
                 WHERE sr.UserID = :uid
                   AND s.serviceDate BETWEEN :from AND :to '
              . $this->sc('AND', 's')
              . ' GROUP BY s.serviceDate';
        $params = array_merge(['uid' => $driverId, 'from' => $from, 'to' => $to], $this->cb());
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['serviceDate']] = (int) $row['cnt'];
        }
        return $result;
    }

    // ── Schedule board helpers ─────────────────────────────────────

    /** Upcoming rides with no driver assigned — the "staged" pool. */
    public function getStagedRides(): array
    {
        $sql = "
            SELECT s.ID, s.serviceDate, s.serviceStartTime,
                   s.NomeCliente, s.serviceStartPoint, s.serviceTargetPoint,
                   s.FlightNumber, s.paxADT, s.paxCHD, s.paxBBY, s.serviceType
            FROM Services s
            LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
            WHERE sr.UserID IS NULL
              AND (s.status_pedido = 'aprovado' OR s.status_pedido IS NULL)
              AND s.serviceDate >= CURDATE()
            " . $this->sc('AND', 's') . "
            ORDER BY s.serviceDate ASC, s.serviceStartTime ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($this->cb());
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** All rides in a date range with driver info — for FullCalendar events feed. */
    public function getScheduledRides(string $from, string $to): array
    {
        $sql = "
            SELECT s.ID, s.serviceDate, s.serviceStartTime,
                   s.NomeCliente, s.serviceStartPoint, s.serviceTargetPoint,
                   s.FlightNumber, s.paxADT, s.paxCHD, s.paxBBY, s.serviceType,
                   s.status_id,
                   s.ts_start_pickup, s.ts_arrived_pickup, s.ts_with_client,
                   s.ts_start_trip, s.ts_completed,
                   u.id AS driver_id, u.name AS driver_name
            FROM Services s
            LEFT JOIN Services_Rides sr ON s.ID = sr.RideID
            LEFT JOIN Users u ON sr.UserID = u.id
            WHERE s.serviceDate BETWEEN :from AND :to
              AND (s.status_pedido = 'aprovado' OR s.status_pedido IS NULL)
            " . $this->sc('AND', 's') . "
            ORDER BY s.serviceDate ASC, s.serviceStartTime ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge(['from' => $from, 'to' => $to], $this->cb()));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Update only date and time of a ride (scoped to company). */
    public function reschedule(int $id, string $date, string $time): void
    {
        if (!$this->ownedBy($id)) return;
        $this->db->prepare('UPDATE Services SET serviceDate = :date, serviceStartTime = :time WHERE ID = :id')
            ->execute(['date' => $date, 'time' => $time, 'id' => $id]);
    }

    /** Update only the flight number of a ride (scoped to company). */
    public function setFlightNumber(int $id, string $flight): void
    {
        if (!$this->ownedBy($id)) return;
        $this->db->prepare('UPDATE Services SET FlightNumber = :f WHERE ID = :id')
            ->execute(['f' => $flight, 'id' => $id]);
    }

    /**
     * Serviços de um intervalo, com os campos necessários ao recálculo de
     * preços (1 condutor por serviço, via subquery, para não multiplicar linhas).
     *
     * @return array<array<string,mixed>>
     */
    public function forRecalculation(string $from, string $to): array
    {
        $sql = "SELECT s.ID, s.supplier, s.resort, s.distributor_code, s.vehicle_label,
                       s.serviceType, s.paxADT, s.paxCHD, s.paxBBY, s.hotel_extra,
                       s.total_price, s.valor_motorista, s.pay_basis,
                       (SELECT sr.UserID FROM Services_Rides sr WHERE sr.RideID = s.ID LIMIT 1) AS driver_id
                FROM Services s
                WHERE s.serviceDate BETWEEN :from AND :to";
        $params = ['from' => $from, 'to' => $to];
        if ($this->companyId !== null) {
            $sql .= ' AND s.company_id = :cid';
            $params['cid'] = $this->companyId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Grava receita/custo/base recalculados (scoped à empresa). */
    public function applyPricing(int $id, ?float $revenue, ?float $payout, ?string $basis): void
    {
        if (!$this->ownedBy($id)) return;
        $this->db->prepare('UPDATE Services SET total_price = :p, valor_motorista = :v, pay_basis = :b WHERE ID = :id')
            ->execute(['p' => $revenue, 'v' => $payout, 'b' => $basis, 'id' => $id]);
    }

    /** Remove driver assignment from a ride (verifies ownership). */
    public function unassignDriver(int $rideId): void
    {
        if (!$this->ownedBy($rideId)) return;
        $this->db->prepare('DELETE FROM Services_Rides WHERE RideID = :id')
            ->execute(['id' => $rideId]);
    }

    /**
     * Ownership guard for single-row mutations.
     * Returns true if the ride belongs to the current company (or if super-admin).
     * Uses a dedicated parameter name (:_cid) to avoid collisions with existing queries.
     */
    private function ownedBy(int $rideId): bool
    {
        if ($this->companyId === null) return true;
        $stmt = $this->db->prepare('SELECT 1 FROM Services WHERE ID = :_rid AND company_id = :_cid LIMIT 1');
        $stmt->execute(['_rid' => $rideId, '_cid' => $this->companyId]);
        return (bool) $stmt->fetchColumn();
    }

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
