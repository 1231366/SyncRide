<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Service;
use App\Repositories\PricingRepository;
use App\Support\Database;
use App\Support\Session;
use PDO;

/**
 * Motor de preçário.
 *
 * Dois usos:
 *   - driverPayout()  — custo a pagar ao motorista, determinado na ATRIBUIÇÃO
 *     (depende de `pay_basis`: viatura da empresa vs carro próprio). É o uso
 *     principal e fechado.
 *   - serviceRevenue() — receita do serviço (fallback do import quando o Excel
 *     não traz "Valor Serviço"; só aplicável a fornecedores tabelados, ex.: MTS).
 */
final class PricingEngine
{
    public const BASIS_COMPANY = 'company_vehicle';
    public const BASIS_OWN     = 'own_vehicle';

    public function __construct(
        private readonly PricingRepository $rates,
    ) {
    }

    public static function default(): self
    {
        return new self(new PricingRepository(Database::connection(), Session::companyId()));
    }

    /**
     * Quanto pagar ao motorista por este serviço.
     *
     * @param string      $payBasis     company_vehicle | own_vehicle
     * @return float|null  null se não houver tarifa para o resort/veículo.
     */
    public function driverPayout(
        ?string $resort,
        ?string $vehicleLabel,
        int     $serviceType,
        int     $pax,
        bool    $hotelExtra,
        string  $payBasis
    ): ?float {
        if ($resort === null || $resort === '') {
            return null;
        }

        if ($payBasis === self::BASIS_OWN) {
            // Motorista parceiro (viatura própria)
            if ($serviceType === Service::TYPE_SHARED) {
                $tier = max(2, min(4, $pax)); // NOTAS: base até 2 pax, extra por pax até 4
                $rate = $this->rates->findRate([
                    'card'          => 'driver_own_vehicle',
                    'resort'        => $resort,
                    'vehicle_label' => 'Shared',
                    'pax_tier'      => $tier,
                ]);
            } else {
                $rate = $this->rates->findRate([
                    'card'          => 'driver_own_vehicle',
                    'resort'        => $resort,
                    'vehicle_label' => $this->canonicalVehicle($vehicleLabel),
                ]);
            }
            return $rate !== null ? (float) $rate['price'] : null;
        }

        // Motorista da empresa (viatura PRtours) — coluna única "Standard / Shared" por resort
        $rate = $this->rates->findRate([
            'card'   => 'driver_company_vehicle',
            'resort' => $resort,
        ]);
        if ($rate === null) {
            return null;
        }
        $payout = (float) $rate['price'];
        if ($hotelExtra && $rate['hotel_extra'] !== null) {
            $payout += (float) $rate['hotel_extra'];
        }
        return round($payout, 2);
    }

    /**
     * Receita do serviço pelo preçário (fallback). Só para fornecedores
     * tabelados — caso contrário usa-se o valor explícito do Excel.
     */
    public function serviceRevenue(
        ?string $supplier,
        ?string $resort,
        ?string $distributorCode,
        ?string $vehicleLabel,
        int     $serviceType
    ): ?float {
        if ($resort === null || $supplier === null || strtoupper($supplier) !== 'MTS') {
            return null;
        }
        $vehicle = $serviceType === Service::TYPE_SHARED ? 'Shared' : $this->canonicalVehicle($vehicleLabel);
        $rate = $this->rates->findRate([
            'card'             => 'mts',
            'resort'           => $resort,
            'distributor_code' => $distributorCode,
            'vehicle_label'    => $vehicle,
        ]);
        return $rate !== null && $rate['price'] !== null ? (float) $rate['price'] : null;
    }

    /**
     * Mapeia o rótulo de veículo do Excel de serviços para a coluna do preçário.
     * (No preçário, "Standard" é o táxi normal.)
     */
    public function canonicalVehicle(?string $label): string
    {
        $l = strtolower(trim((string) $label));
        return match (true) {
            $l === '' => 'Standard',
            str_contains($l, 'shared')                              => 'Shared',
            str_contains($l, 'minibus')                            => 'Private Luxury Minibus',
            str_contains($l, 'luxury')                             => 'Private Luxury Car',
            str_contains($l, 'van')                                => 'Mini Van', // inclui "Mini Van + Private Taxi"
            str_contains($l, 'taxi'), str_contains($l, 'standard') => 'Standard',
            default                                                 => 'Standard',
        };
    }
}
