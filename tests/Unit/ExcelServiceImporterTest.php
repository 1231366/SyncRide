<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Service;
use App\Services\ExcelServiceImporter;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Exercita o mapeamento do importador (parse) — sem tocar na base de dados.
 * Usa o próprio Excel do cliente como fixture.
 */
final class ExcelServiceImporterTest extends TestCase
{
    private const FIXTURE = __DIR__ . '/../Fixtures/services_sample.xlsx';

    /** @return array<int,array<string,mixed>> */
    private function parsed(): array
    {
        // O PDO não é usado pelo parse(); passamos um in-memory só para satisfazer o construtor.
        $importer = new ExcelServiceImporter(new PDO('sqlite::memory:'), 7);
        return $importer->parse(self::FIXTURE);
    }

    public function testParsesAllDataRows(): void
    {
        $this->assertCount(14, $this->parsed());
    }

    public function testMapsCoreFields(): void
    {
        $row = $this->parsed()[0]; // Jost, Sabrina
        $this->assertSame('2026-05-15', $row['serviceDate']);
        $this->assertSame('03:30:00', $row['serviceStartTime']);
        $this->assertSame('Jost, Sabrina', $row['NomeCliente']);
        $this->assertSame('MTS', $row['supplier']);
        $this->assertSame('HPCH', $row['distributor_code']);
        $this->assertSame('Lisbon', $row['resort']);
        $this->assertSame('05727871', $row['reference_no']);
        $this->assertSame('new', $row['_status']);
    }

    public function testOutboundLegGoesHotelToAirport(): void
    {
        $row = $this->parsed()[0]; // OT
        $this->assertSame('OT', $row['leg_code']);
        $this->assertSame('Smy Lisboa', $row['serviceStartPoint']);
        $this->assertSame('Aeroporto LIS', $row['serviceTargetPoint']);
    }

    public function testInboundLegGoesAirportToHotel(): void
    {
        // THOMAS, LASHAWN — leg IN
        $thomas = array_values(array_filter($this->parsed(), static fn($r) => $r['NomeCliente'] === 'THOMAS, LASHAWN'))[0];
        $this->assertSame('IN', $thomas['leg_code']);
        $this->assertSame('Aeroporto LIS', $thomas['serviceStartPoint']);
        $this->assertSame('Jupiter Lisboa - Rooftop and Spa', $thomas['serviceTargetPoint']);
    }

    public function testSharedVehicleIsDetected(): void
    {
        $shared = array_values(array_filter($this->parsed(), static fn($r) => $r['vehicle_label'] === 'Shared'));
        $this->assertNotEmpty($shared);
        foreach ($shared as $r) {
            $this->assertSame(Service::TYPE_SHARED, $r['serviceType']);
        }
    }

    public function testPrivateVehicleIsDetected(): void
    {
        $row = $this->parsed()[0]; // Private Taxi
        $this->assertSame(Service::TYPE_PRIVATE, $row['serviceType']);
    }

    public function testExtractsPhoneFromNotes(): void
    {
        $thomas = array_values(array_filter($this->parsed(), static fn($r) => $r['NomeCliente'] === 'THOMAS, LASHAWN'))[0];
        $this->assertSame('13013673152', $thomas['ClientNumber']);
    }

    public function testNonMtsSupplierKeepsExplicitValues(): void
    {
        // Get-e — sem cartão tipo-MTS, valores vêm preenchidos no Excel
        $gete = array_values(array_filter($this->parsed(), static fn($r) => $r['supplier'] === 'Get-e'))[0];
        $this->assertEqualsWithDelta(22.0, $gete['total_price'], 0.001);
        $this->assertEqualsWithDelta(22.0, $gete['valor_motorista'], 0.001);
    }

    public function testOwLegSplitsStayHotelOnDash(): void
    {
        $importer = new ExcelServiceImporter(new \PDO('sqlite::memory:'), 7);
        $ref = new \ReflectionMethod($importer, 'resolveOwEndpoints');
        $ref->setAccessible(true);

        // Formato padrão PRtours: "Ponto A - Ponto B"
        $this->assertSame(
            ['Term Cruzeiros', 'Browns Central Hotel'],
            $ref->invoke($importer, 'Term Cruzeiros - Browns Central Hotel', null, null)
        );

        // Nomes com traço interno: limite de split = 2 (só o primeiro traço)
        $this->assertSame(
            ['Hotel Bairro Alto', 'Cervejaria Ribadouro - Centro'],
            $ref->invoke($importer, 'Hotel Bairro Alto - Cervejaria Ribadouro - Centro', null, null)
        );

        // Fallback: Dep/Arr preenchidos (localização não-aeroporto)
        $this->assertSame(
            ['LIS', 'OPO'],
            $ref->invoke($importer, '—', 'LIS', 'OPO')
        );

        // Fallback final: mesmo ponto nos dois lados
        $this->assertSame(
            ['Smy Lisboa', 'Smy Lisboa'],
            $ref->invoke($importer, 'Smy Lisboa', null, null)
        );
    }

    public function testSharedGroupSharesGroupingRef(): void
    {
        $rows   = $this->parsed();
        $groups = [];
        foreach ($rows as $r) {
            if ($r['grouping_ref'] !== null) {
                $groups[$r['grouping_ref']][] = $r;
            }
        }
        // Há pelo menos um grupo com >1 serviço (shared agregado)
        $multi = array_filter($groups, static fn($g) => count($g) > 1);
        $this->assertNotEmpty($multi);
    }

    /**
     * Regressão: uma ida-e-volta partilha o mesmo voucher/"Reference No" nas
     * duas direções (Chegada=IN, Saída=OT). O fixture já traz este caso real
     * (TRANSAVIA). Sem o leg_code na chave de deduplicação, a Saída aparecia
     * sempre marcada como duplicada da Chegada — ver queixa do Gonçalo.
     */
    public function testRoundTripSameReferenceIsNotFlaggedDuplicate(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE Services (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            serviceDate TEXT, serviceStartTime TEXT, NomeCliente TEXT, FlightNumber TEXT,
            reference_no TEXT NULL, leg_code TEXT NULL
        )');

        $importer = new ExcelServiceImporter($pdo, 7);
        $result   = $importer->preview(self::FIXTURE);

        $transavia = array_values(array_filter($result['rows'], static fn($r) => $r['reference_no'] === 'TRANSAVIA'));
        $this->assertCount(2, $transavia, 'fixture deve ter as duas pernas TRANSAVIA (OT + IN)');
        $legs = array_column($transavia, 'leg_code');
        $this->assertContains('OT', $legs);
        $this->assertContains('IN', $legs);

        foreach ($transavia as $row) {
            $this->assertSame('new', $row['_status'], "leg {$row['leg_code']} não devia ser marcada duplicada");
        }
    }
}
