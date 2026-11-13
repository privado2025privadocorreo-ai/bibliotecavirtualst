<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use ZipArchive;

final class ExcelExport
{
    public static function write(string $path, array $headers, array $rows): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('La extensión ZipArchive es necesaria para exportar a Excel.');
        }

        $sheetRows = [];
        foreach (array_merge([$headers], $rows) as $rowNumber => $row) {
            $cells = [];
            foreach (array_values($row) as $columnIndex => $value) {
                $reference = self::columnName($columnIndex + 1) . ($rowNumber + 1);
                $cells[] = '<c r="' . $reference . '" t="inlineStr"><is><t xml:space="preserve">'
                    . self::escape((string)($value ?? ''))
                    . '</t></is></c>';
            }
            $sheetRows[] = '<row r="' . ($rowNumber + 1) . '">' . implode('', $cells) . '</row>';
        }

        $entries = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                . '<Default Extension="xml" ContentType="application/xml"/>'
                . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                . '</Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
                . '</Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
                . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                . '<sheets><sheet name="Inventario" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                . '</Relationships>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'
                . implode('', $sheetRows)
                . '</sheetData></worksheet>',
        ];

        $archive = new ZipArchive();
        $opened = $archive->open($path, ZipArchive::OVERWRITE);
        if ($opened !== true) {
            throw new RuntimeException('No se pudo crear el archivo Excel (código ZIP: ' . $opened . ').');
        }

        foreach ($entries as $name => $contents) {
            if (!$archive->addFromString($name, $contents)) {
                $archive->close();
                throw new RuntimeException('No se pudo escribir el contenido del archivo Excel.');
            }
        }

        if (!$archive->close()) {
            throw new RuntimeException('No se pudo finalizar el archivo Excel.');
        }
    }

    private static function columnName(int $column): string
    {
        $name = '';
        while ($column > 0) {
            $remainder = ($column - 1) % 26;
            $name = chr(65 + $remainder) . $name;
            $column = intdiv($column - 1, 26);
        }

        return $name;
    }

    private static function escape(string $value): string
    {
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value) ?? '';
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
