<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ServiceRepository;

/**
 * Recalcula receita e custo do motorista de serviços JÁ existentes, aplicando o
 * preçário atual via {@see ServicePricing} — o mesmo motor que o importador usa.
 *
 * Serve de "backfill": arruma os serviços importados antes de o motor estar
 * ligado ao import, e fica disponível sempre que o rate card mude. Só preenche
 * buracos — valores explícitos (> 0) nunca são sobrepostos.
 */
final class PricingRecalculator
{
    public function __construct(
        private readonly ServiceRepository $services,
        private readonly ServicePricing    $pricing,
    ) {
    }

    public static function default(): self
    {
        return new self(ServiceRepository::default(), ServicePricing::default());
    }

    /**
     * Recalcula todos os serviços do intervalo.
     *
     * @return array{scanned:int,updated:int}
     */
    public function range(string $from, string $to): array
    {
        $rows    = $this->services->forRecalculation($from, $to);
        $updated = 0;

        foreach ($rows as $r) {
            $type     = (int) $r['serviceType'];
            $pax      = (int) $r['paxADT'] + (int) $r['paxCHD'] + (int) $r['paxBBY'];
            $curRev   = $r['total_price']     !== null ? (float) $r['total_price']     : null;
            $curPay   = $r['valor_motorista'] !== null ? (float) $r['valor_motorista'] : null;
            $driverId = $r['driver_id']       !== null ? (int)   $r['driver_id']       : null;
            $curBasis = $r['pay_basis'] ?? null;

            $revenue = $this->pricing->revenue(
                $curRev, $r['supplier'], $r['resort'],
                $r['distributor_code'], $r['vehicle_label'], $type
            );
            [$payout, $basis] = $this->pricing->payout(
                $curPay, $driverId, $curBasis,
                $r['resort'], $r['vehicle_label'], $type, $pax,
                (bool) ($r['hotel_extra'] ?? false)
            );

            if ($this->differs($curRev, $revenue) || $this->differs($curPay, $payout) || $basis !== $curBasis) {
                $this->services->applyPricing((int) $r['ID'], $revenue, $payout, $basis);
                $updated++;
            }
        }

        return ['scanned' => count($rows), 'updated' => $updated];
    }

    private function differs(?float $a, ?float $b): bool
    {
        if ($a === null && $b === null) {
            return false;
        }
        if ($a === null || $b === null) {
            return true;
        }
        return abs($a - $b) > 0.001;
    }
}
