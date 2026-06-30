<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PricingRepository;
use App\Repositories\UserRepository;
use App\Support\Database;
use App\Support\Session;
use PDO;

/**
 * Aplica o preçário a um serviço — ponto único onde se decide a receita e o
 * custo do motorista a partir do {@see PricingEngine}.
 *
 * Reutilizado por:
 *   - {@see ExcelServiceImporter}  — calcula no momento da importação;
 *   - {@see PricingRecalculator}   — recalcula serviços já existentes (backfill).
 *
 * Regra de ouro: um valor explícito (> 0) já gravado NUNCA é sobreposto — só se
 * preenchem buracos. Assim os valores manuais (ex.: Get-e, serviços avulso)
 * mantêm-se e só os tabelados (ex.: MTS, sem valor no Excel) é que são calculados.
 */
final class ServicePricing
{
    public function __construct(
        private readonly PricingEngine   $engine,
        private readonly UserRepository  $users,
    ) {
    }

    public static function default(): self
    {
        return new self(PricingEngine::default(), UserRepository::default());
    }

    /** Versão com escopo explícito de empresa (usada pelo importador). */
    public static function forCompany(PDO $db, ?int $companyId): self
    {
        return new self(
            new PricingEngine(new PricingRepository($db, $companyId)),
            new UserRepository($db, $companyId),
        );
    }

    /**
     * Receita do serviço. Mantém o valor explícito; senão tenta o preçário
     * (só fornecedores tabelados, ex.: MTS). Devolve null se nada se aplicar.
     */
    public function revenue(
        ?float  $current,
        ?string $supplier,
        ?string $resort,
        ?string $distributorCode,
        ?string $vehicleLabel,
        int     $serviceType
    ): ?float {
        if ($current !== null && $current > 0) {
            return $current;
        }
        $computed = $this->engine->serviceRevenue($supplier, $resort, $distributorCode, $vehicleLabel, $serviceType);
        return $computed ?? $current;
    }

    /**
     * Custo do motorista + base de pagamento usada. Só calcula se houver
     * condutor; mantém valor explícito; senão usa o preçário com a base do
     * serviço (ou, em falta, o default do condutor).
     *
     * @return array{0:?float,1:?string}  [custo, pay_basis]
     */
    public function payout(
        ?float  $current,
        ?int    $driverId,
        ?string $payBasis,
        ?string $resort,
        ?string $vehicleLabel,
        int     $serviceType,
        int     $pax,
        bool    $hotelExtra
    ): array {
        if ($driverId === null || $driverId <= 0) {
            return [$current, $payBasis];
        }
        if ($current !== null && $current > 0) {
            return [$current, $payBasis];
        }
        $basis = in_array($payBasis, [PricingEngine::BASIS_COMPANY, PricingEngine::BASIS_OWN], true)
            ? $payBasis
            : $this->users->defaultPayBasis($driverId);

        $computed = $this->engine->driverPayout($resort, $vehicleLabel, $serviceType, $pax, $hotelExtra, $basis);
        return [$computed ?? $current, $basis];
    }
}
