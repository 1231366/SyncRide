<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\ServiceRepository;
use App\Services\RideExportService;
use PDO;
use PHPUnit\Framework\TestCase;
use ZipArchive;

/**
 * Weekly Excel export for the XML-only tenant: right columns, blank "Valor
 * Serviço" always, blank Distrib/Referen/Cidade when the import never
 * captured them, and partner-portal requests excluded (delegated rides kept).
 */
final class RideExportServiceTest extends TestCase
{
    private function seededDb(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE Services (
            ID INTEGER PRIMARY KEY, company_id INTEGER,
            serviceDate TEXT, serviceStartTime TEXT, paxADT INT, paxCHD INT, paxBBY INT,
            serviceStartPoint TEXT, serviceTargetPoint TEXT, FlightNumber TEXT,
            NomeCliente TEXT, distributor_code TEXT, reference_no TEXT, resort TEXT,
            leg_code TEXT, status_pedido TEXT, partner_id INTEGER, original_company_id INTEGER
        )');

        // A full XML-import row (IN leg, distributor/reference/resort captured).
        $pdo->exec("INSERT INTO Services
            (ID, company_id, serviceDate, serviceStartTime, paxADT, paxCHD, paxBBY,
             serviceStartPoint, serviceTargetPoint, FlightNumber, NomeCliente,
             distributor_code, reference_no, resort, leg_code)
            VALUES (101, 7, '2026-07-05', '08:15:00', 2, 0, 0,
                    'Airport OPO', 'Hotel Cristal Porto', 'W61085', 'Zabrzanski, Krzysztof',
                    'ITAK', '13670154', 'Porto', 'IN')");

        // A legacy row with no distributor/reference/resort/leg_code (pre-existing data).
        $pdo->exec("INSERT INTO Services
            (ID, company_id, serviceDate, serviceStartTime, paxADT, paxCHD, paxBBY,
             serviceStartPoint, serviceTargetPoint, FlightNumber, NomeCliente)
            VALUES (102, 7, '2026-07-06', '10:00:00', 1, 0, 0,
                    'Hotel da Bolsa', 'Airport OPO', 'FR2400', 'Some, Guest')");

        // A ride delegated from a partner hostel (delegateTo() never touches
        // partner_id, only original_company_id) — must still appear.
        $pdo->exec("INSERT INTO Services
            (ID, company_id, serviceDate, serviceStartTime, paxADT, paxCHD, paxBBY, NomeCliente,
             original_company_id)
            VALUES (103, 7, '2026-07-07', '09:00:00', 1, 0, 0, 'Delegated, Guest', 9)");

        // A partner-portal request, already approved (status_pedido defaults to
        // 'aprovado' for every ride, so it alone can't distinguish this from a
        // normal ride — partner_id is what marks it) — must be excluded.
        $pdo->exec("INSERT INTO Services
            (ID, company_id, serviceDate, serviceStartTime, paxADT, paxCHD, paxBBY, NomeCliente,
             partner_id, status_pedido)
            VALUES (104, 7, '2026-07-08', '11:00:00', 1, 0, 0, 'Partner, Request', 5, 'aprovado')");

        // Another company's ride — must never leak into this export.
        $pdo->exec("INSERT INTO Services
            (ID, company_id, serviceDate, serviceStartTime, paxADT, paxCHD, paxBBY, NomeCliente)
            VALUES (105, 8, '2026-07-05', '08:00:00', 1, 0, 0, 'Other, Company')");

        return $pdo;
    }

    private function sheetRows(string $xlsx): array
    {
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx_test_');
        file_put_contents($tmp, $xlsx);
        $zip = new ZipArchive();
        $zip->open($tmp);
        $x = simplexml_load_string($zip->getFromName('xl/worksheets/sheet1.xml'));
        $zip->close();
        unlink($tmp);
        $x->registerXPathNamespace('s', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rows = [];
        foreach ($x->xpath('//s:row') as $row) {
            $cells = [];
            foreach ($row->c as $c) {
                $cells[] = (string) $c->is->t;
            }
            $rows[] = $cells;
        }
        return $rows;
    }

    public function testExportShapeAndFiltering(): void
    {
        $pdo  = $this->seededDb();
        $repo = new ServiceRepository($pdo, 7);
        $svc  = new RideExportService($repo);

        $xlsx = $svc->buildWeeklyExport('2026-07-01', '2026-07-31');
        $rows = $this->sheetRows($xlsx);

        // Header + 3 rows for company 7 (XML row, legacy row, delegated row) —
        // the partner-portal request and the other company's ride are excluded.
        $this->assertSame(
            ['DATA', 'Hora', 'Distrib', 'Referen', 'Tickets', 'Adultos', 'Crianças', 'Bebes',
             'Valor Serviço', 'Cidade', 'Voo', 'Nome', 'Hotel/Alojamento', 'Saida/chegada'],
            $rows[0]
        );
        $this->assertCount(4, $rows); // header + 3 data rows

        $names = array_column(array_slice($rows, 1), 11);
        $this->assertContains('Zabrzanski, Krzysztof', $names);
        $this->assertContains('Some, Guest', $names);
        $this->assertContains('Delegated, Guest', $names);
        $this->assertNotContains('Partner, Request', $names);
        $this->assertNotContains('Other, Company', $names);
    }

    public function testFullyCapturedRowFormatting(): void
    {
        $pdo  = $this->seededDb();
        $repo = new ServiceRepository($pdo, 7);
        $svc  = new RideExportService($repo);
        $rows = $this->sheetRows($svc->buildWeeklyExport('2026-07-05', '2026-07-05'));

        $this->assertSame(
            ['05.07.2026', '08:15:00', 'ITAK', '13670154', '101', '2', '0', '0',
             '', 'Porto', 'W61085', 'Zabrzanski, Krzysztof', 'Hotel Cristal Porto', 'CHEGADA'],
            $rows[1]
        );
    }

    public function testLegacyRowLeavesUncapturedFieldsBlank(): void
    {
        $pdo  = $this->seededDb();
        $repo = new ServiceRepository($pdo, 7);
        $svc  = new RideExportService($repo);
        $rows = $this->sheetRows($svc->buildWeeklyExport('2026-07-06', '2026-07-06'));

        // Distrib, Referen and Cidade blank (never captured); direction still
        // derived from the airport-name heuristic since leg_code is null.
        $this->assertSame(
            ['06.07.2026', '10:00:00', '', '', '102', '1', '0', '0',
             '', '', 'FR2400', 'Some, Guest', 'Hotel da Bolsa', 'SAIDA'],
            $rows[1]
        );
    }
}
