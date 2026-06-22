<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Repositories\FinancialReportRepository;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Testa a agregação do relatório financeiro (totais, margem, sub-totais e
 * filtros) com SQLite em memória — independente da BD real.
 */
final class FinancialReportRepositoryTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('CREATE TABLE Services (
            ID INTEGER PRIMARY KEY, serviceDate TEXT, serviceStartTime TEXT, NomeCliente TEXT,
            serviceStartPoint TEXT, serviceTargetPoint TEXT, supplier TEXT, serviceType INTEGER,
            total_price REAL, valor_motorista REAL, company_id INTEGER
        )');
        $this->pdo->exec('CREATE TABLE Services_Rides (RideID INTEGER, UserID INTEGER)');
        $this->pdo->exec('CREATE TABLE Users (id INTEGER PRIMARY KEY, name TEXT)');
        $this->pdo->exec("INSERT INTO Users (id,name) VALUES (10,'Joao')");

        $svc = $this->pdo->prepare('INSERT INTO Services
            (ID,serviceDate,serviceStartTime,NomeCliente,serviceStartPoint,serviceTargetPoint,supplier,serviceType,total_price,valor_motorista,company_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)');
        // svc1 MTS, com motorista Joao
        $svc->execute([1, '2026-05-10', '08:00:00', 'A', 'X', 'Y', 'MTS',   1, 14.0,  6.0,  1]);
        // svc2 MTS, sem motorista
        $svc->execute([2, '2026-05-11', '09:00:00', 'B', 'X', 'Y', 'MTS',   1, 15.6,  0.0,  1]);
        // svc3 Get-e, motorista Joao
        $svc->execute([3, '2026-05-12', '10:00:00', 'C', 'X', 'Y', 'Get-e', 1, 22.0,  22.0, 1]);
        // svc4 outra empresa (deve ser excluído quando companyId=1)
        $svc->execute([4, '2026-05-13', '11:00:00', 'D', 'X', 'Y', 'MTS',   1, 99.0,  0.0,  2]);
        // svc5 fora do intervalo
        $svc->execute([5, '2026-06-01', '11:00:00', 'E', 'X', 'Y', 'MTS',   1, 99.0,  0.0,  1]);
        $this->pdo->exec('INSERT INTO Services_Rides (RideID,UserID) VALUES (1,10),(3,10)');
    }

    private function repo(): FinancialReportRepository
    {
        return new FinancialReportRepository($this->pdo, 1);
    }

    public function testTotalsRevenueCostMargin(): void
    {
        $t = $this->repo()->report('2026-05-01', '2026-05-31')['totals'];
        $this->assertSame(3, $t['count']);
        $this->assertSame(51.6, $t['revenue']);
        $this->assertSame(28.0, $t['driver_cost']);
        $this->assertSame(23.6, $t['margin']);
    }

    public function testBySupplierBreakdown(): void
    {
        $by = $this->repo()->report('2026-05-01', '2026-05-31')['by_supplier'];
        $this->assertSame(2, $by['MTS']['count']);
        $this->assertSame(29.6, $by['MTS']['revenue']);
        $this->assertSame(23.6, $by['MTS']['margin']);
        $this->assertSame(0.0, $by['Get-e']['margin']);
    }

    public function testByDriverBreakdownGroupsUnassigned(): void
    {
        $by = $this->repo()->report('2026-05-01', '2026-05-31')['by_driver'];
        $this->assertSame(2, $by['Joao']['count']);   // svc1 + svc3
        $this->assertSame(8.0, $by['Joao']['margin']); // (14-6)+(22-22)
        $this->assertSame(1, $by['—']['count']);       // svc2 sem motorista
        $this->assertSame(15.6, $by['—']['margin']);
    }

    public function testSupplierFilter(): void
    {
        $t = $this->repo()->report('2026-05-01', '2026-05-31', 'MTS')['totals'];
        $this->assertSame(2, $t['count']);
        $this->assertSame(29.6, $t['revenue']);
    }

    public function testDriverFilter(): void
    {
        $t = $this->repo()->report('2026-05-01', '2026-05-31', null, 10)['totals'];
        $this->assertSame(2, $t['count']);
    }

    public function testTenancyExcludesOtherCompany(): void
    {
        // svc4 (company 2, €99) não entra nos totais da empresa 1
        $t = $this->repo()->report('2026-05-01', '2026-05-31')['totals'];
        $this->assertLessThan(99.0, $t['revenue']);
    }

    public function testDateRangeExcludesOutsiders(): void
    {
        // svc5 é de Junho — não conta em Maio
        $t = $this->repo()->report('2026-05-01', '2026-05-31')['totals'];
        $this->assertSame(3, $t['count']);
    }

    public function testSuppliersList(): void
    {
        $this->assertSame(['Get-e', 'MTS'], $this->repo()->suppliers());
    }
}
