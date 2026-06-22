<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Leitor leve de ficheiros .xlsx — sem dependências externas.
 *
 * Um .xlsx é um zip de XML. Esta classe abre o zip, resolve as shared
 * strings e os estilos (para distinguir datas/horas de números) e devolve
 * cada folha como um array de linhas (cada linha = array indexado por coluna,
 * 0-based, com buracos preenchidos a `null`).
 *
 * Datas/horas formatadas são convertidas automaticamente para texto:
 *   - data      → 'Y-m-d'
 *   - hora      → 'H:i:s'
 *   - data+hora → 'Y-m-d H:i:s'
 *
 * Propositadamente minimalista (segue o espírito do XmlVoucherImporter feito
 * à mão). Para folhas com formatações exóticas, considerar PhpSpreadsheet.
 */
final class XlsxReader
{
    /** Shared strings indexadas. @var array<int,string> */
    private array $sharedStrings = [];

    /** Índice de cellXf → 'date'|'time'|'datetime'|null (formato). @var array<int,?string> */
    private array $styleKind = [];

    private ZipArchive $zip;

    private function __construct(ZipArchive $zip)
    {
        $this->zip = $zip;
    }

    public static function open(string $path): self
    {
        if (!is_file($path)) {
            throw new RuntimeException("XlsxReader: ficheiro não encontrado: {$path}");
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException("XlsxReader: não foi possível abrir o .xlsx: {$path}");
        }
        $reader = new self($zip);
        $reader->loadSharedStrings();
        $reader->loadStyles();
        return $reader;
    }

    /**
     * Nomes das folhas, na ordem do workbook.
     * @return array<string>
     */
    public function sheetNames(): array
    {
        $wb = $this->xml('xl/workbook.xml');
        $names = [];
        if ($wb !== null && isset($wb->sheets->sheet)) {
            foreach ($wb->sheets->sheet as $sheet) {
                $names[] = (string) $sheet['name'];
            }
        }
        return $names;
    }

    /**
     * Lê uma folha (por nome, ou a primeira) como array de linhas.
     * @return array<int,array<int,mixed>>
     */
    public function rows(?string $sheetName = null): array
    {
        $target = $this->resolveSheetPath($sheetName);
        $xml    = $this->xml($target);
        if ($xml === null || !isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $rowIndex = (int) $row['r'] - 1;          // 1-based → 0-based
            $cells    = [];
            $maxCol   = -1;
            foreach ($row->c as $c) {
                $col = self::columnIndex((string) $c['r']);
                $cells[$col] = $this->cellValue($c);
                $maxCol = max($maxCol, $col);
            }
            // Normaliza para um array contínuo 0..maxCol (buracos = null)
            $line = [];
            for ($i = 0; $i <= $maxCol; $i++) {
                $line[$i] = $cells[$i] ?? null;
            }
            $rows[$rowIndex] = $line;
        }

        ksort($rows);
        return array_values($rows);
    }

    // ── Internals ───────────────────────────────────────────────────────────

    private function cellValue(SimpleXMLElement $c): mixed
    {
        $type = (string) $c['t'];

        // Strings
        if ($type === 's') {
            $idx = (int) $c->v;
            return $this->sharedStrings[$idx] ?? '';
        }
        if ($type === 'inlineStr') {
            return isset($c->is->t) ? (string) $c->is->t : '';
        }
        if ($type === 'str') {           // resultado textual de fórmula
            return (string) $c->v;
        }
        if ($type === 'b') {
            return ((string) $c->v) === '1';
        }

        // Numérico (ou data/hora consoante o estilo)
        if (!isset($c->v) || (string) $c->v === '') {
            return null;
        }
        $raw  = (float) $c->v;
        $kind = $this->styleKind[(int) $c['s']] ?? null;
        if ($kind !== null) {
            return self::excelSerialToString($raw, $kind);
        }
        // Inteiro vs float
        return ($raw == (int) $raw) ? (int) $raw : $raw;
    }

    /** Converte o serial do Excel em data/hora formatada. */
    private static function excelSerialToString(float $serial, string $kind): string
    {
        // Hora pura: usa apenas a fração do dia.
        if ($kind === 'time') {
            $seconds = (int) round(($serial - floor($serial)) * 86400);
            return gmdate('H:i:s', $seconds);
        }
        // Base do Excel: 1899-12-30 (já compensa o bug do ano 1900 p/ datas reais).
        $days    = (int) floor($serial);
        $seconds = (int) round(($serial - $days) * 86400);
        $ts      = ($days - 25569) * 86400 + $seconds; // 25569 = dias entre 1899-12-30 e 1970-01-01
        return $kind === 'datetime' ? gmdate('Y-m-d H:i:s', $ts) : gmdate('Y-m-d', $ts);
    }

    private function loadSharedStrings(): void
    {
        $xml = $this->xml('xl/sharedStrings.xml');
        if ($xml === null) {
            return;
        }
        foreach ($xml->si as $si) {
            // <si><t>..</t></si> ou <si><r><t>..</t></r>...</si> (rich text)
            if (isset($si->t)) {
                $this->sharedStrings[] = (string) $si->t;
            } elseif (isset($si->r)) {
                $buf = '';
                foreach ($si->r as $r) {
                    $buf .= (string) $r->t;
                }
                $this->sharedStrings[] = $buf;
            } else {
                $this->sharedStrings[] = '';
            }
        }
    }

    private function loadStyles(): void
    {
        $xml = $this->xml('xl/styles.xml');
        if ($xml === null) {
            return;
        }

        // Formatos personalizados (numFmtId >= 164)
        $customFormats = [];
        if (isset($xml->numFmts->numFmt)) {
            foreach ($xml->numFmts->numFmt as $fmt) {
                $customFormats[(int) $fmt['numFmtId']] = (string) $fmt['formatCode'];
            }
        }

        if (!isset($xml->cellXfs->xf)) {
            return;
        }
        $i = 0;
        foreach ($xml->cellXfs->xf as $xf) {
            $numFmtId = (int) $xf['numFmtId'];
            $this->styleKind[$i] = self::classifyFormat($numFmtId, $customFormats[$numFmtId] ?? null);
            $i++;
        }
    }

    /** Classifica um numFmt como data/hora/datetime, ou null se for número/texto. */
    private static function classifyFormat(int $numFmtId, ?string $code): ?string
    {
        // Builtin time-only
        if (in_array($numFmtId, [18, 19, 20, 21, 45, 46, 47], true)) {
            return 'time';
        }
        // Builtin date / datetime
        if (in_array($numFmtId, [14, 15, 16, 17], true)) {
            return 'date';
        }
        if ($numFmtId === 22) {
            return 'datetime';
        }
        if ($code === null) {
            return null;
        }
        // Custom: inspeciona o formatCode (remove literais entre aspas e [..]).
        $clean = preg_replace('/\[[^\]]*\]|"[^"]*"/', '', $code);
        $clean = strtolower((string) $clean);
        $hasDate = preg_match('/[dy]/', $clean) === 1 || str_contains($clean, 'mmm');
        $hasTime = preg_match('/[hs]/', $clean) === 1 || str_contains($clean, 'am/pm');
        if ($hasDate && $hasTime) {
            return 'datetime';
        }
        if ($hasTime) {
            return 'time';
        }
        if ($hasDate) {
            return 'date';
        }
        return null;
    }

    /** Resolve o caminho do XML da folha (por nome ou a primeira). */
    private function resolveSheetPath(?string $sheetName): string
    {
        $wb = $this->xml('xl/workbook.xml');
        if ($wb === null || !isset($wb->sheets->sheet)) {
            return 'xl/worksheets/sheet1.xml';
        }

        // Mapa r:id → target (de xl/_rels/workbook.xml.rels)
        $rels = $this->xml('xl/_rels/workbook.xml.rels');
        $relTarget = [];
        if ($rels !== null) {
            foreach ($rels->Relationship as $rel) {
                $relTarget[(string) $rel['Id']] = (string) $rel['Target'];
            }
        }

        $first = null;
        foreach ($wb->sheets->sheet as $sheet) {
            $rid    = (string) $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $target = $relTarget[$rid] ?? '';
            $path   = $target !== '' ? 'xl/' . ltrim($target, '/') : '';
            $first ??= $path;
            if ($sheetName !== null && (string) $sheet['name'] === $sheetName) {
                return $path !== '' ? $path : 'xl/worksheets/sheet1.xml';
            }
        }
        return $first ?: 'xl/worksheets/sheet1.xml';
    }

    private function xml(string $entry): ?SimpleXMLElement
    {
        $contents = $this->zip->getFromName($entry);
        if ($contents === false || $contents === '') {
            return null;
        }
        $xml = @simplexml_load_string($contents);
        return $xml === false ? null : $xml;
    }

    /** 'A'→0, 'B'→1, … 'AA'→26. Ignora a parte numérica da referência (ex.: 'C12'). */
    public static function columnIndex(string $cellRef): int
    {
        $letters = preg_replace('/[^A-Za-z]/', '', $cellRef);
        $letters = strtoupper((string) $letters);
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - 64);
        }
        return $index - 1;
    }
}
