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
}
