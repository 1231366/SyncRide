<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\Database;
use App\Support\Session;
use PDO;

/**
 * Relatório financeiro baseado nos valores REAIS dos serviços:
 * receita (Σ total_price), custo do motorista (Σ valor_motorista) e margem.
 *
 * Filtra por intervalo de datas e, opcionalmente, por fornecedor e/ou
 * motorista. Tudo scoped à empresa da sessão (super-admin vê tudo).
 */
final class FinancialReportRepository
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
     * @return array{
     *   rows: array<array<string,mixed>>,
     *   totals: array{count:int,revenue:float,driver_cost:float,margin:float},
     *   by_supplier: array<string,array{count:int,revenue:float,driver_cost:float,margin:float}>,
     *   by_driver: array<string,array{count:int,revenue:float,driver_cost:float,margin:float}>
     * }
     */
    public function report(string $from, string $to, ?string $supplier = null, ?int $driverId = null): array
    {
        $where  = ['s.serviceDate BETWEEN :from AND :to'];
        $params = ['from' => $from, 'to' => $to];

        if ($this->companyId !== null) {
            $where[] = 's.company_id = :cid';
            $params['cid'] = $this->companyId;
        }
        if ($supplier !== null && $supplier !== '') {
            $where[] = 's.supplier = :supplier';
            $params['supplier'] = $supplier;
        }
        if ($driverId !== null && $driverId > 0) {
            $where[] = 'sr.UserID = :driver';
            $params['driver'] = $driverId;
        }

        $sql = 'SELECT s.ID, s.serviceDate, s.serviceStartTime, s.NomeCliente,
                       s.serviceStartPoint, s.serviceTargetPoint, s.supplier,
                       s.serviceType, s.total_price, s.valor_motorista,
                       u.name AS driver_name
                FROM Services s
                LEFT JOIN Services_Rides sr ON sr.RideID = s.ID
                LEFT JOIN Users u           ON u.id = sr.UserID
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY s.serviceDate, s.serviceStartTime';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totals     = ['count' => 0, 'revenue' => 0.0, 'driver_cost' => 0.0, 'margin' => 0.0];
        $bySupplier = [];
        $byDriver   = [];

        foreach ($rows as &$r) {
            $revenue = (float) ($r['total_price']     ?? 0);
            $cost    = (float) ($r['valor_motorista'] ?? 0);
            $margin  = $revenue - $cost;
            $r['margin'] = round($margin, 2);

            $totals['count']++;
            $totals['revenue']     += $revenue;
            $totals['driver_cost'] += $cost;
            $totals['margin']      += $margin;

            $sup = ($r['supplier'] ?? '') !== '' ? (string) $r['supplier'] : '—';
            $this->accumulate($bySupplier, $sup, $revenue, $cost, $margin);

            $drv = ($r['driver_name'] ?? '') !== '' ? (string) $r['driver_name'] : '—';
            $this->accumulate($byDriver, $drv, $revenue, $cost, $margin);
        }
        unset($r);

        $totals['revenue']     = round($totals['revenue'], 2);
        $totals['driver_cost'] = round($totals['driver_cost'], 2);
        $totals['margin']      = round($totals['margin'], 2);

        $byRevenue = static fn(array $a, array $b): int => $b['revenue'] <=> $a['revenue'];
        uasort($bySupplier, $byRevenue);
        uasort($byDriver, $byRevenue);

        return ['rows' => $rows, 'totals' => $totals, 'by_supplier' => $bySupplier, 'by_driver' => $byDriver];
    }

    /** @return array<string> fornecedores distintos (para o filtro). */
    public function suppliers(): array
    {
        $sql = 'SELECT DISTINCT supplier FROM Services WHERE supplier IS NOT NULL AND supplier <> ""';
        if ($this->companyId !== null) {
            $sql .= ' AND company_id = :cid';
            $stmt = $this->db->prepare($sql . ' ORDER BY supplier');
            $stmt->execute(['cid' => $this->companyId]);
        } else {
            $stmt = $this->db->query($sql . ' ORDER BY supplier');
        }
        return array_map(static fn($v): string => (string) $v, $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    /** @param array<string,array{count:int,revenue:float,driver_cost:float,margin:float}> $bucket */
    private function accumulate(array &$bucket, string $key, float $revenue, float $cost, float $margin): void
    {
        if (!isset($bucket[$key])) {
            $bucket[$key] = ['count' => 0, 'revenue' => 0.0, 'driver_cost' => 0.0, 'margin' => 0.0];
        }
        $bucket[$key]['count']++;
        $bucket[$key]['revenue']     = round($bucket[$key]['revenue']     + $revenue, 2);
        $bucket[$key]['driver_cost'] = round($bucket[$key]['driver_cost'] + $cost,    2);
        $bucket[$key]['margin']      = round($bucket[$key]['margin']      + $margin,  2);
    }

    // ── Analytics extensions ──────────────────────────────────────────────

    /**
     * Lightweight totals only — no rows — used for period-over-period comparison.
     * @return array{count:int,revenue:float,driver_cost:float,margin:float}
     */
    public function summary(string $from, string $to, ?string $supplier = null, ?int $driverId = null): array
    {
        [$where, $params, $join] = $this->buildWhere($from, $to, $supplier, $driverId);
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS cnt,
                    COALESCE(SUM(s.total_price), 0) AS revenue,
                    COALESCE(SUM(COALESCE(s.valor_motorista, 0)), 0) AS driver_cost,
                    COALESCE(SUM(s.total_price - COALESCE(s.valor_motorista, 0)), 0) AS margin
             FROM Services s ' . $join . ' WHERE ' . implode(' AND ', $where)
        );
        $stmt->execute($params);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return [
            'count'       => (int)   ($r['cnt']        ?? 0),
            'revenue'     => (float) ($r['revenue']     ?? 0),
            'driver_cost' => (float) ($r['driver_cost'] ?? 0),
            'margin'      => (float) ($r['margin']      ?? 0),
        ];
    }

    /**
     * Revenue / cost / margin grouped by calendar day.
     * @return array<string, array{count:int,revenue:float,driver_cost:float,margin:float}>
     */
    public function byDay(string $from, string $to, ?string $supplier = null, ?int $driverId = null): array
    {
        [$where, $params, $join] = $this->buildWhere($from, $to, $supplier, $driverId);
        $stmt = $this->db->prepare(
            'SELECT s.serviceDate AS d,
                    COUNT(*) AS cnt,
                    COALESCE(SUM(s.total_price), 0) AS revenue,
                    COALESCE(SUM(COALESCE(s.valor_motorista, 0)), 0) AS driver_cost,
                    COALESCE(SUM(s.total_price - COALESCE(s.valor_motorista, 0)), 0) AS margin
             FROM Services s ' . $join . ' WHERE ' . implode(' AND ', $where) . '
             GROUP BY s.serviceDate ORDER BY s.serviceDate'
        );
        $stmt->execute($params);
        $result = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[$row['d']] = [
                'count'       => (int)   $row['cnt'],
                'revenue'     => (float) $row['revenue'],
                'driver_cost' => (float) $row['driver_cost'],
                'margin'      => (float) $row['margin'],
            ];
        }
        return $result;
    }

    /**
     * Ride count bucketed by hour of day.
     * @return array<int,int>  24-element array (index = hour).
     */
    public function byHour(string $from, string $to, ?string $supplier = null, ?int $driverId = null): array
    {
        [$where, $params, $join] = $this->buildWhere($from, $to, $supplier, $driverId);
        $stmt = $this->db->prepare(
            'SELECT HOUR(s.serviceStartTime) AS h, COUNT(*) AS cnt
             FROM Services s ' . $join . ' WHERE ' . implode(' AND ', $where) . '
             GROUP BY HOUR(s.serviceStartTime) ORDER BY h'
        );
        $stmt->execute($params);
        $result = array_fill(0, 24, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $result[(int) $row['h']] = (int) $row['cnt'];
        }
        return $result;
    }

    /**
     * Revenue split by service type (0 = shared, 1 = private).
     * @return array{private:array{count:int,revenue:float},shared:array{count:int,revenue:float}}
     */
    public function byType(string $from, string $to, ?string $supplier = null, ?int $driverId = null): array
    {
        [$where, $params, $join] = $this->buildWhere($from, $to, $supplier, $driverId);
        $stmt = $this->db->prepare(
            'SELECT s.serviceType, COUNT(*) AS cnt,
                    COALESCE(SUM(s.total_price), 0) AS revenue
             FROM Services s ' . $join . ' WHERE ' . implode(' AND ', $where) . '
             GROUP BY s.serviceType'
        );
        $stmt->execute($params);
        $result = ['private' => ['count' => 0, 'revenue' => 0.0], 'shared' => ['count' => 0, 'revenue' => 0.0]];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $key            = ((int) $row['serviceType']) === 1 ? 'private' : 'shared';
            $result[$key]   = ['count' => (int) $row['cnt'], 'revenue' => (float) $row['revenue']];
        }
        return $result;
    }

    /**
     * Builds WHERE conditions and an optional JOIN for analytics queries.
     * Avoids joining Services_Rides when not needed, preventing phantom duplicate rows.
     *
     * @return array{0:list<string>,1:array<string,mixed>,2:string}
     */
    private function buildWhere(string $from, string $to, ?string $supplier, ?int $driverId): array
    {
        $where  = ['s.serviceDate BETWEEN :from AND :to'];
        $params = ['from' => $from, 'to' => $to];
        $join   = '';

        if ($this->companyId !== null) {
            $where[]        = 's.company_id = :cid';
            $params['cid']  = $this->companyId;
        }
        if ($supplier !== null && $supplier !== '') {
            $where[]             = 's.supplier = :supplier';
            $params['supplier']  = $supplier;
        }
        if ($driverId !== null && $driverId > 0) {
            $join             = 'INNER JOIN Services_Rides sr ON sr.RideID = s.ID';
            $where[]          = 'sr.UserID = :driver';
            $params['driver'] = $driverId;
        }
        return [$where, $params, $join];
    }
}
