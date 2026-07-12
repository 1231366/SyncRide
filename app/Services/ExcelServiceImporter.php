<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Service;
use App\Repositories\ImportBatchRepository;
use App\Repositories\UserRepository;
use App\Support\Database;
use App\Support\Session;
use App\Support\XlsxReader;
use PDO;

/**
 * Importa serviços do Excel da PRtours (folha "booking-item-list").
 *
 * Espelha o {@see XmlVoucherImporter}, mas para o formato Excel plano:
 *   - cabeçalho tolerante (procura por nome de coluna, não por índice fixo);
 *   - direção/recolha/entrega derivadas do "Service Base Code" (IN/OT/OW);
 *   - NÃO atribui motorista (decisão do cliente — entra por atribuir);
 *   - deduplicação por "Reference No" (no ficheiro e contra a BD);
 *   - grava os códigos de preçário (resort, distributor, vehicle) e os dois
 *     valores (serviço + motorista) tal como vêm no Excel.
 *
 * `preview()` analisa sem escrever; `commit()` insere numa transação e regista
 * um lote em ImportBatches (permitindo desfazer).
 */
final class ExcelServiceImporter
{
    private const SHEET = 'booking-item-list';

    private ?ServicePricing $pricing = null;

    public function __construct(
        private readonly PDO  $db,
        private readonly ?int $companyId = null,
    ) {
    }

    public static function default(): self
    {
        return new self(Database::connection(), Session::companyId());
    }

    /** Motor de preçário, criado à medida (escopo da empresa do importador). */
    private function pricing(): ServicePricing
    {
        return $this->pricing ??= ServicePricing::forCompany($this->db, $this->companyId);
    }

    /**
     * Analisa o ficheiro e devolve candidatos + resumo, SEM escrever.
     *
     * @return array{rows: array<int,array<string,mixed>>, summary: array<string,int>}
     */
    public function preview(string $path): array
    {
        $candidates = $this->parse($path);

        $seenRefs = [];
        $newCount = $dupCount = $invalidCount = 0;

        foreach ($candidates as &$row) {
            if ($row['_status'] === 'invalid') {
                $invalidCount++;
                continue;
            }
            $ref = $row['reference_no'];
            // Duplicado dentro do próprio ficheiro. A chave inclui o leg_code porque
            // uma ida-e-volta (IN + OT) partilha o mesmo voucher/"Reference No" —
            // sem o leg_code, a Saída aparecia sempre como duplicada da Chegada.
            $dupKey = $ref . '|' . ($row['leg_code'] ?? '');
            if ($ref !== null && $ref !== '' && isset($seenRefs[$dupKey])) {
                $row['_status'] = 'duplicate';
                $row['_reason'] = 'Repetido no ficheiro';
                $dupCount++;
                continue;
            }
            if ($ref !== null && $ref !== '') {
                $seenRefs[$dupKey] = true;
            }
            // Duplicado contra a base de dados
            if ($this->existsInDb($row)) {
                $row['_status'] = 'duplicate';
                $row['_reason'] = 'Já existe na base de dados';
                $dupCount++;
                continue;
            }
            $newCount++;
        }
        unset($row);

        return [
            'rows'    => $candidates,
            'summary' => [
                'total'     => count($candidates),
                'new'       => $newCount,
                'duplicate' => $dupCount,
                'invalid'   => $invalidCount,
            ],
        ];
    }

    /**
     * Importa de facto: insere os candidatos "new" e regista um lote.
     *
     * @return array{batch_id:int, inserted:int, skipped:int, failed:int}
     */
    public function commit(string $path, string $filename): array
    {
        return $this->persist($this->preview($path)['rows'], $filename);
    }

    /**
     * Insere candidatos já analisados (revalidando a deduplicação contra a BD,
     * caso esta tenha mudado entre o preview e a confirmação) e regista um lote.
     * Usado pelo commit(path) e pelo fluxo preview→sessão→commit do controlador.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array{batch_id:int, inserted:int, skipped:int, failed:int}
     */
    public function persist(array $rows, string $filename): array
    {
        $batches = new ImportBatchRepository($this->db, $this->companyId);
        $batchId = $batches->create($filename, 'excel', count($rows));

        $insert = $this->db->prepare('
            INSERT INTO Services
                (serviceDate, serviceStartTime, paxADT, paxCHD, paxBBY,
                 serviceStartPoint, serviceTargetPoint, FlightNumber,
                 NomeCliente, ClientNumber, serviceType,
                 supplier, grouping_ref, distributor_code, resort, vehicle_label,
                 leg_code, reference_no, total_price, price_explicit, valor_motorista, pay_basis,
                 import_notes, import_batch_id, company_id)
            VALUES
                (:sd, :st, :pa, :pc, :bby, :sp, :tp, :fn,
                 :nc, :cn, :stype,
                 :supplier, :grp, :dist, :resort, :veh,
                 :leg, :ref, :price, :price_explicit, :driver_pay, :basis,
                 :notes, :batch, :cid)
        ');

        $inserted = $skipped = $failed = 0;

        // Cache de resolução de siglas → id de condutor (evita N queries por linha).
        $users      = new UserRepository($this->db, $this->companyId);
        $driverCache = [];

        $assignStmt = $this->db->prepare('INSERT IGNORE INTO Services_Rides (RideID, UserID) VALUES (:rid, :uid)');

        $this->db->beginTransaction();
        try {
            foreach ($rows as $row) {
                // Revalida: só insere os "new" que continuam a não existir na BD.
                if (($row['_status'] ?? '') !== 'new' || $this->existsInDb($row)) {
                    $skipped++;
                    continue;
                }
                try {
                    // Resolver o condutor (se a sigla casar) ANTES de inserir, para
                    // fechar já o custo do motorista no momento da importação.
                    $driverId   = null;
                    $driverCode = trim((string) ($row['_driver_code'] ?? ''));
                    if ($driverCode !== '') {
                        if (!array_key_exists($driverCode, $driverCache)) {
                            $found = $users->findByDriverCode($driverCode);
                            $driverCache[$driverCode] = $found ? $found->id : null;
                        }
                        $driverId = $driverCache[$driverCode];
                    }

                    // Preçário: receita (MTS sem valor no Excel) e custo do motorista
                    // (quando atribuído). Valores explícitos do Excel são respeitados.
                    $type         = (int) $row['serviceType'];
                    $pax          = (int) $row['paxADT'] + (int) $row['paxCHD'] + (int) $row['paxBBY'];
                    $excelPrice   = $row['total_price']; // null = campo vazio no Excel
                    $revenue = $this->pricing()->revenue(
                        $excelPrice, $row['supplier'], $row['resort'],
                        $row['distributor_code'], $row['vehicle_label'], $type
                    );
                    [$payout, $basis] = $this->pricing()->payout(
                        $row['valor_motorista'], $driverId, null,
                        $row['resort'], $row['vehicle_label'], $type, $pax,
                        (bool) ($row['hotel_extra'] ?? false)
                    );

                    // price_explicit: 1 só se o Excel tinha valor explícito no campo "Valor Serviço"
                    $priceExplicit = ($excelPrice !== null && (float) $excelPrice > 0) ? 1 : 0;

                    $insert->execute([
                        'sd'            => $row['serviceDate'],
                        'st'            => $row['serviceStartTime'],
                        'pa'            => $row['paxADT'],
                        'pc'            => $row['paxCHD'],
                        'bby'           => $row['paxBBY'],
                        'sp'            => $row['serviceStartPoint'],
                        'tp'            => $row['serviceTargetPoint'],
                        'fn'            => $row['FlightNumber'],
                        'nc'            => $row['NomeCliente'],
                        'cn'            => $row['ClientNumber'],
                        'stype'         => $row['serviceType'],
                        'supplier'      => $row['supplier'],
                        'grp'           => $row['grouping_ref'],
                        'dist'          => $row['distributor_code'],
                        'resort'        => $row['resort'],
                        'veh'           => $row['vehicle_label'],
                        'leg'           => $row['leg_code'],
                        'ref'           => $row['reference_no'],
                        'price'         => $revenue,
                        'price_explicit' => $priceExplicit,
                        'driver_pay'    => $payout,
                        'basis'         => $basis,
                        'notes'         => $row['import_notes'],
                        'batch'         => $batchId,
                        'cid'           => $this->companyId,
                    ]);
                    $rideId = (int) $this->db->lastInsertId();

                    if ($driverId !== null && $rideId > 0) {
                        $assignStmt->execute(['rid' => $rideId, 'uid' => $driverId]);
                    }

                    $inserted++;
                } catch (\Throwable $e) {
                    $failed++;
                    error_log('ExcelServiceImporter row failed: ' . $e->getMessage());
                }
            }
            $batches->finalize($batchId, $inserted, $skipped, $failed);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return ['batch_id' => $batchId, 'inserted' => $inserted, 'skipped' => $skipped, 'failed' => $failed];
    }

    // ── Parsing ─────────────────────────────────────────────────────────────

    /**
     * Lê o Excel e devolve candidatos normalizados (sem escrever).
     * @return array<int,array<string,mixed>>
     */
    public function parse(string $path): array
    {
        $reader = XlsxReader::open($path);
        $sheet  = in_array(self::SHEET, $reader->sheetNames(), true) ? self::SHEET : null;
        $rows   = $reader->rows($sheet);
        if (count($rows) < 2) {
            return [];
        }

        $map = $this->headerMap($rows[0]);
        $out = [];

        foreach (array_slice($rows, 1, null, true) as $r) {
            // Salta linhas totalmente vazias
            if (!array_filter($r, static fn($v): bool => $v !== null && $v !== '' && trim((string) $v) !== '')) {
                continue;
            }
            $out[] = $this->mapRow($r, $map);
        }
        return $out;
    }

    /**
     * Constrói o mapa nome-de-coluna → lista de índices (tolerante a duplicados,
     * ex.: as duas colunas "Pick-Up Time").
     * @param array<int,mixed> $header
     * @return array<string,array<int>>
     */
    private function headerMap(array $header): array
    {
        $map = [];
        foreach ($header as $i => $name) {
            if ($name === null || $name === '') {
                continue;
            }
            $key = $this->normalize((string) $name);
            $map[$key][] = $i;
        }
        return $map;
    }

    /**
     * @param array<int,mixed>       $r
     * @param array<string,array<int>> $map
     * @return array<string,mixed>
     */
    private function mapRow(array $r, array $map): array
    {
        $get = function (string $name, int $occurrence = 0) use ($r, $map): ?string {
            $key = $this->normalize($name);
            $idx = $map[$key][$occurrence] ?? null;
            if ($idx === null) {
                return null;
            }
            $val = $r[$idx] ?? null;
            if ($val === null) {
                return null;
            }
            $val = trim((string) $val);
            return $val === '' ? null : $val;
        };

        $date     = $this->toDate($get('Start Date'));
        // Hora esperada de recolha: 2ª coluna "Pick-Up Time" (índice 1), com
        // fallback à 1ª, e depois às horas de voo.
        $pickTime = $this->toTime(
            $get('Pick-Up Time', 1)
            ?? $get('Pick-Up Time', 0)
            ?? $get('Arr Time')
            ?? $get('Dep Time')
        );

        $vehicle  = $get('Vehicle');
        $leg      = $get('Service Base Code');
        $hotel    = $get('Stay Hotel');
        [$pickup, $dropoff] = $this->resolveEndpoints($leg, $hotel, $get('Dep Airport'), $get('Arr Airport'));

        $notes = $get('Notes');

        $row = [
            'serviceDate'        => $date,
            'serviceStartTime'   => $pickTime,
            'paxADT'             => (int) ($get('Adults')   ?? 0),
            'paxCHD'             => (int) ($get('Children') ?? 0),
            'paxBBY'             => (int) ($get('Infants')  ?? 0),
            'serviceStartPoint'  => $pickup,
            'serviceTargetPoint' => $dropoff,
            'FlightNumber'       => $get('Flight'),
            'NomeCliente'        => $get('Lead Pax'),
            'ClientNumber'       => $this->extractPhone($notes),
            'serviceType'        => $this->isShared($vehicle) ? Service::TYPE_SHARED : Service::TYPE_PRIVATE,
            'supplier'           => $get('Fornecedor'),
            'grouping_ref'       => $get('Grouping Id'),
            'distributor_code'   => $get('Distributor Code'),
            'resort'             => $get('Resort'),
            'vehicle_label'      => $vehicle,
            'leg_code'           => $leg,
            'reference_no'       => $get('Reference No'),
            'total_price'        => $this->toMoney($get('Valor Serviço')),
            'valor_motorista'    => $this->toMoney($get('Valor Motorista')),
            'import_notes'       => $notes,
            '_driver_code'       => $get('Driver'),
        ];

        // Validação mínima: precisa de data e hora.
        if ($row['serviceDate'] === null || $row['serviceStartTime'] === null) {
            $row['_status'] = 'invalid';
            $row['_reason'] = 'Falta data ou hora de recolha';
        } else {
            $row['_status'] = 'new';
            $row['_reason'] = null;
        }
        return $row;
    }

    /** Recolha/entrega consoante a direção do serviço. */
    private function resolveEndpoints(?string $leg, ?string $hotel, ?string $depAirport, ?string $arrAirport): array
    {
        $hotel = $hotel ?: '—';
        $airportOut = $depAirport ? 'Aeroporto ' . $depAirport : 'Aeroporto';
        $airportIn  = $arrAirport ? 'Aeroporto ' . $arrAirport : 'Aeroporto';

        return match (strtoupper((string) $leg)) {
            'IN'    => [$airportIn, $hotel],
            'OT'    => [$hotel, $airportOut],
            default => $this->resolveOwEndpoints($hotel, $depAirport, $arrAirport),
        };
    }

    /**
     * OW: Stay Hotel pode conter "Ponto A - Ponto B".
     * Fallback 1: colunas Dep/Arr preenchidas (localização não-aeroporto).
     * Fallback 2: mesmo ponto nos dois lados (operador corrige manualmente).
     */
    private function resolveOwEndpoints(?string $hotel, ?string $dep, ?string $arr): array
    {
        $hotel = $hotel ?: '—';

        if (str_contains($hotel, ' - ')) {
            [$a, $b] = explode(' - ', $hotel, 2);
            return [trim($a), trim($b)];
        }

        if ($dep && $arr) {
            return [$dep, $arr];
        }

        return [$hotel, $hotel];
    }

    private function existsInDb(array $row): bool
    {
        $ref = $row['reference_no'] ?? null;
        if ($ref !== null && $ref !== '') {
            // When a reference number is present, it is the identifier — but a
            // round trip shares the same voucher/"Reference No" for both legs
            // (Chegada=IN, Saída=OT), so leg_code must be part of the key too,
            // or the second leg always looks like a duplicate of the first.
            // Do NOT fall through to the tuple fallback — services with identical
            // client/time/flight but different references are distinct (e.g. Transavia crew legs).
            $stmt = $this->db->prepare('
                SELECT COUNT(*) FROM Services
                WHERE reference_no = ? AND (leg_code = ? OR (leg_code IS NULL AND ? IS NULL))
            ');
            $leg = $row['leg_code'] ?? null;
            $stmt->execute([$ref, $leg, $leg]);
            return (int) $stmt->fetchColumn() > 0;
        }
        // Fallback (sem reference): mesma tupla data+hora+cliente+voo (como o XML).
        // Comparação null-safe escrita de forma portátil (evita o operador
        // `<=>`, que é MySQL-only e não existe no SQLite usado nos testes).
        $stmt = $this->db->prepare('
            SELECT COUNT(*) FROM Services
            WHERE serviceDate = ? AND serviceStartTime = ?
              AND (NomeCliente = ? OR (NomeCliente IS NULL AND ? IS NULL))
              AND (FlightNumber = ? OR (FlightNumber IS NULL AND ? IS NULL))
        ');
        $stmt->execute([
            $row['serviceDate'], $row['serviceStartTime'],
            $row['NomeCliente'], $row['NomeCliente'],
            $row['FlightNumber'], $row['FlightNumber'],
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    // ── Helpers de conversão ────────────────────────────────────────────────

    private function isShared(?string $vehicle): bool
    {
        return $vehicle !== null && stripos($vehicle, 'shared') !== false;
    }

    private function normalize(string $s): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $s) ?? $s));
    }

    /** O XlsxReader já devolve 'Y-m-d'; aceita também strings de data soltas. */
    private function toDate(?string $v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $v)) {
            return substr($v, 0, 10);
        }
        $ts = strtotime($v);
        return $ts !== false ? date('Y-m-d', $ts) : null;
    }

    /** Normaliza para 'H:i:s'. O XlsxReader já devolve 'H:i:s' para horas. */
    private function toTime(?string $v): ?string
    {
        if ($v === null) {
            return null;
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $v, $m)) {
            return sprintf('%02d:%02d:%02d', (int) $m[1], (int) $m[2], (int) ($m[3] ?? 0));
        }
        return null;
    }

    private function toMoney(?string $v): ?float
    {
        if ($v === null) {
            return null;
        }
        $v = str_replace([' ', '€'], '', $v);
        $v = str_replace(',', '.', $v);
        return is_numeric($v) ? (float) $v : null;
    }

    private function extractPhone(?string $notes): ?string
    {
        if ($notes === null || trim($notes) === '') {
            return null;
        }
        // 1) Labelled phone: "Phone: …", "Mobile …", "Tel: …", "Telefone …", "Contacto …".
        if (preg_match('/(?:Mobile|Phone(?:\s*number)?|Tel|Telef\w*|Contacto)[:\s]*([+\d][\d\s]{6,})/i', $notes, $m)) {
            return trim($m[1]);
        }
        // 2) Fallback — a standalone number with 9+ digits (PT mobile / international),
        //    so a Notes field that holds just the bare number is still captured.
        //    The 9-digit floor skips shorter reference codes; tokens with letters
        //    (flight numbers like FR202) never match because this is digits-only.
        if (preg_match('/(?<![\w.])(\+?\d[\d\s]{7,}\d)(?![\w])/', $notes, $m)
            && strlen(preg_replace('/\D/', '', $m[1])) >= 9
        ) {
            return trim($m[1]);
        }
        return null;
    }
}
