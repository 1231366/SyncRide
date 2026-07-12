<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Escritor leve de ficheiros .xlsx — sem dependências externas.
 *
 * Um .xlsx é um zip de XML; esta classe monta o mínimo indispensável (content
 * types, relationships, workbook, uma única folha) com todas as células como
 * inline strings — dispensa sharedStrings.xml e evita ambiguidade de formato
 * (datas/valores ficam exatamente como o texto que se lhes dá).
 *
 * Propositadamente minimalista (segue o espírito do {@see XlsxReader}). Para
 * folhas com formatações exóticas (fórmulas, estilos, múltiplas folhas),
 * considerar PhpSpreadsheet.
 */
final class XlsxWriter
{
    /**
     * @param array<int,string> $headers
     * @param array<int,array<int,string>> $rows cada linha já convertida para string (célula a célula)
     */
    public static function build(array $headers, array $rows, string $sheetName = 'Sheet1'): string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'xlsx_');
        if ($tmpPath === false) {
            throw new RuntimeException('Não foi possível criar ficheiro temporário para o .xlsx');
        }

        $zip = new ZipArchive();
        if ($zip->open($tmpPath, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Não foi possível criar o .xlsx');
        }

        $zip->addEmptyDir('_rels');
        $zip->addEmptyDir('xl');
        $zip->addEmptyDir('xl/_rels');
        $zip->addEmptyDir('xl/worksheets');

        $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
        $zip->addFromString('_rels/.rels', self::rootRelsXml());
        $zip->addFromString('xl/workbook.xml', self::workbookXml($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheetXml($headers, $rows));

        $zip->close();

        $content = file_get_contents($tmpPath);
        unlink($tmpPath);
        if ($content === false) {
            throw new RuntimeException('Não foi possível ler o .xlsx gerado');
        }
        return $content;
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';
    }

    private static function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbookXml(string $sheetName): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . self::escape($sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>';
    }

    /**
     * @param array<int,string> $headers
     * @param array<int,array<int,string>> $rows
     */
    private static function sheetXml(array $headers, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        $xml .= self::rowXml(1, $headers);
        foreach ($rows as $i => $row) {
            $xml .= self::rowXml($i + 2, $row);
        }

        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    /** @param array<int,string> $cells */
    private static function rowXml(int $rowNumber, array $cells): string
    {
        $xml = '<row r="' . $rowNumber . '">';
        foreach (array_values($cells) as $col => $value) {
            $ref = self::columnLetter($col) . $rowNumber;
            $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . self::escape((string) $value) . '</t></is></c>';
        }
        return $xml . '</row>';
    }

    /** 0-based column index → Excel letter (0→A, 25→Z, 26→AA, ...). */
    private static function columnLetter(int $index): string
    {
        $letter = '';
        $index++;
        while ($index > 0) {
            $rem    = ($index - 1) % 26;
            $letter = chr(65 + $rem) . $letter;
            $index  = intdiv($index - 1, 26);
        }
        return $letter;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
