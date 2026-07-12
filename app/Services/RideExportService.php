<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ServiceRepository;
use App\Support\XlsxWriter;

/**
 * Weekly Excel export for the tenant that only ever imports via XML voucher
 * feed (never the PRtours Excel importer) — their own reporting format,
 * built from whatever we actually captured on import. Fields the XML feed
 * never carried historically (Distrib, Referen, Cidade) are blank on older
 * rides; "Valor Serviço" is always left blank on purpose, for the client to
 * fill in by hand.
 */
final class RideExportService
{
    private const HEADERS = [
        'DATA', 'Hora', 'Distrib', 'Referen', 'Tickets', 'Adultos', 'Crianças',
        'Bebes', 'Valor Serviço', 'Cidade', 'Voo', 'Nome', 'Hotel/Alojamento', 'Saida/chegada',
    ];

    public function __construct(private readonly ServiceRepository $services)
    {
    }

    public static function default(): self
    {
        return new self(ServiceRepository::default());
    }

    /** Builds the .xlsx binary for the given date range (inclusive), scoped to the current company. */
    public function buildWeeklyExport(string $dateFrom, string $dateTo): string
    {
        $rows = array_map(
            fn(array $r): array => $this->toRow($r),
            $this->services->forWeeklyExport($dateFrom, $dateTo)
        );
        return XlsxWriter::build(self::HEADERS, $rows, 'Viagens');
    }

    /** @return array<int,string> */
    private function toRow(array $r): array
    {
        [$hotel, $direction] = $this->hotelAndDirection($r);

        return [
            $this->formatDate((string) $r['serviceDate']),
            $this->formatTime((string) $r['serviceStartTime']),
            (string) ($r['distributor_code'] ?? ''),
            (string) ($r['reference_no'] ?? ''),
            (string) $r['ID'],
            (string) $r['paxADT'],
            (string) $r['paxCHD'],
            (string) $r['paxBBY'],
            '', // Valor Serviço — sempre em branco, o cliente preenche à mão
            (string) ($r['resort'] ?? ''),
            $this->blankIfNA((string) ($r['FlightNumber'] ?? '')),
            (string) ($r['NomeCliente'] ?? ''),
            $hotel,
            $direction,
        ];
    }

    private function formatDate(string $isoDate): string
    {
        $ts = strtotime($isoDate);
        return $ts !== false ? date('d.m.Y', $ts) : '';
    }

    private function formatTime(string $time): string
    {
        return substr($time, 0, 8);
    }

    private function blankIfNA(string $v): string
    {
        return strtoupper(trim($v)) === 'N/A' ? '' : $v;
    }

    /**
     * @param array<string,mixed> $r
     * @return array{0:string,1:string} [hotel/alojamento, 'SAIDA'|'CHEGADA'|'']
     */
    private function hotelAndDirection(array $r): array
    {
        $leg    = strtoupper(trim((string) ($r['leg_code'] ?? '')));
        $pickup = (string) ($r['serviceStartPoint']  ?? '');
        $dropoff = (string) ($r['serviceTargetPoint'] ?? '');

        if ($leg === 'IN') {
            return [$dropoff, 'CHEGADA']; // airport -> hotel
        }
        if ($leg === 'OT') {
            return [$pickup, 'SAIDA']; // hotel -> airport
        }

        // Legacy rides with no leg_code: same text heuristic as the admin IN/OUT badge.
        $isAirport = static fn(string $s): bool =>
            (bool) preg_match('/aeroport|airport|\bLIS\b|\bOPO\b|\bFAO\b|\bFNC\b|\bPDL\b/i', $s);
        $pickIsAir  = $isAirport($pickup);
        $dropIsAir  = $isAirport($dropoff);

        if ($pickIsAir === $dropIsAir) {
            return ['', '']; // both or neither — can't tell, leave blank
        }
        return $pickIsAir ? [$dropoff, 'CHEGADA'] : [$pickup, 'SAIDA'];
    }
}
