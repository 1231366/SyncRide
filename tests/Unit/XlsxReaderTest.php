<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\XlsxReader;
use PHPUnit\Framework\TestCase;

final class XlsxReaderTest extends TestCase
{
    private const FIXTURE = __DIR__ . '/../Fixtures/services_sample.xlsx';

    public function testListsSheetNames(): void
    {
        $reader = XlsxReader::open(self::FIXTURE);
        $this->assertContains('booking-item-list', $reader->sheetNames());
    }

    public function testReadsHeaderAndResolvesSharedStrings(): void
    {
        $rows   = XlsxReader::open(self::FIXTURE)->rows('booking-item-list');
        $header = $rows[0];

        $this->assertSame('Start Date', $header[0]);
        $this->assertSame('Fornecedor', $header[1]);
        // Primeira linha de dados — shared string resolvida
        $this->assertSame('MTS', $rows[1][1]);
    }

    public function testConvertsExcelDateSerialToIsoDate(): void
    {
        $rows = XlsxReader::open(self::FIXTURE)->rows('booking-item-list');
        $this->assertSame('2026-05-15', $rows[1][0]); // Start Date
    }

    public function testConvertsExcelTimeSerialToHms(): void
    {
        $rows = XlsxReader::open(self::FIXTURE)->rows('booking-item-list');
        // 2ª coluna "Pick-Up Time" (índice 17) = hora esperada de recolha
        $this->assertSame('03:30:00', $rows[1][17]);
    }

    public function testKeepsNumbersAsNumeric(): void
    {
        $rows = XlsxReader::open(self::FIXTURE)->rows('booking-item-list');
        $this->assertSame(3, $rows[1][6]);      // Adults
        $this->assertEqualsWithDelta(15.6, $rows[1][22], 0.001); // Valor Serviço
    }

    /** @dataProvider columnRefs */
    public function testColumnIndex(string $ref, int $expected): void
    {
        $this->assertSame($expected, XlsxReader::columnIndex($ref));
    }

    public static function columnRefs(): array
    {
        return [
            ['A1', 0], ['B2', 1], ['Z9', 25], ['AA1', 26], ['AH969', 33],
        ];
    }
}
