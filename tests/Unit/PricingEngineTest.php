<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Service;
use App\Repositories\PricingRepository;
use App\Services\PricingEngine;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Testa o motor de preçário com uma BD SQLite em memória semeada com tarifas
 * conhecidas (subconjunto das folhas reais da PRtours).
 */
final class PricingEngineTest extends TestCase
{
    private PricingEngine $engine;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE PricingRates (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            company_id INTEGER NULL, card TEXT, supplier TEXT NULL,
            resort TEXT NULL, distributor_code TEXT NULL, vehicle_label TEXT NULL,
            pax_tier INTEGER NULL, price REAL NULL, hotel_extra REAL NULL, valid_until TEXT NULL
        )');

        $repo = new PricingRepository($pdo, null);
        // Motorista — viatura empresa (Lisbon: base 6, hotel extra 2)
        $repo->insert(['card' => 'driver_company_vehicle', 'resort' => 'Lisbon', 'price' => 6.0, 'hotel_extra' => 2.0]);
        // Motorista — carro próprio
        $repo->insert(['card' => 'driver_own_vehicle', 'resort' => 'Lisbon', 'vehicle_label' => 'Standard', 'price' => 12.0]);
        $repo->insert(['card' => 'driver_own_vehicle', 'resort' => 'Lisbon', 'vehicle_label' => 'Mini Van', 'price' => 25.0]);
        foreach ([2 => 11.0, 3 => 13.0, 4 => 15.0] as $tier => $price) {
            $repo->insert(['card' => 'driver_own_vehicle', 'resort' => 'Lisbon', 'vehicle_label' => 'Shared', 'pax_tier' => $tier, 'price' => $price]);
        }
        // MTS — específico LOVP vs wildcard
        $repo->insert(['card' => 'mts', 'resort' => 'Lisbon', 'distributor_code' => 'LOVP', 'vehicle_label' => 'Standard', 'price' => 14.0]);
        $repo->insert(['card' => 'mts', 'resort' => 'Lisbon', 'distributor_code' => null, 'vehicle_label' => 'Standard', 'price' => 15.6]);

        $this->engine = new PricingEngine($repo);
    }

    public function testCompanyVehiclePayout(): void
    {
        $this->assertSame(6.0, $this->engine->driverPayout('Lisbon', 'Private Taxi', Service::TYPE_PRIVATE, 2, false, PricingEngine::BASIS_COMPANY));
    }

    public function testCompanyVehicleAddsHotelExtra(): void
    {
        $this->assertSame(8.0, $this->engine->driverPayout('Lisbon', 'Private Taxi', Service::TYPE_PRIVATE, 2, true, PricingEngine::BASIS_COMPANY));
    }

    public function testOwnVehicleStandard(): void
    {
        $this->assertSame(12.0, $this->engine->driverPayout('Lisbon', 'Private Taxi', Service::TYPE_PRIVATE, 1, false, PricingEngine::BASIS_OWN));
    }

    public function testOwnVehicleSharedUsesPaxTier(): void
    {
        $this->assertSame(11.0, $this->engine->driverPayout('Lisbon', 'Shared', Service::TYPE_SHARED, 2, false, PricingEngine::BASIS_OWN));
        $this->assertSame(13.0, $this->engine->driverPayout('Lisbon', 'Shared', Service::TYPE_SHARED, 3, false, PricingEngine::BASIS_OWN));
    }

    public function testOwnVehicleSharedClampsAboveFour(): void
    {
        // 5 pax → escalão máximo (4) = 15
        $this->assertSame(15.0, $this->engine->driverPayout('Lisbon', 'Shared', Service::TYPE_SHARED, 5, false, PricingEngine::BASIS_OWN));
    }

    public function testMtsSpecificDistributorWins(): void
    {
        $this->assertSame(14.0, $this->engine->serviceRevenue('MTS', 'Lisbon', 'LOVP', 'Private Taxi', Service::TYPE_PRIVATE));
    }

    public function testMtsWildcardWhenNoDistributor(): void
    {
        $this->assertSame(15.6, $this->engine->serviceRevenue('MTS', 'Lisbon', null, 'Private Taxi', Service::TYPE_PRIVATE));
    }

    public function testNonMtsSupplierHasNoTableRate(): void
    {
        $this->assertNull($this->engine->serviceRevenue('Get-e', 'Lisbon', null, 'Mini Van', Service::TYPE_PRIVATE));
    }

    public function testMissingResortReturnsNull(): void
    {
        $this->assertNull($this->engine->driverPayout(null, 'Standard', Service::TYPE_PRIVATE, 1, false, PricingEngine::BASIS_OWN));
    }

    /** @dataProvider vehicleLabels */
    public function testCanonicalVehicleMapping(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->engine->canonicalVehicle($input));
    }

    public static function vehicleLabels(): array
    {
        return [
            ['Private Taxi', 'Standard'],
            ['Mini Van', 'Mini Van'],
            ['Mini Van + Private Taxi', 'Mini Van'],
            ['Private Luxury Car', 'Private Luxury Car'],
            ['Private Luxury Minibus', 'Private Luxury Minibus'],
            ['Shared', 'Shared'],
        ];
    }
}
