<?php

namespace app\components;

use DateTimeImmutable;
use RuntimeException;
use ZipArchive;

/**
 * Small OOXML writer for the single-sheet reserve report.
 */
class XlsxWriter
{
    public function writeReserveReport(
        string $filePath,
        array $accounts,
        string $dateFrom,
        string $dateTo,
        array $summary = []
    ): void
    {
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('Для формирования XLSX требуется PHP-расширение zip.');
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Не удалось создать файл отчёта.');
        }

        try {
            $zip->addFromString('[Content_Types].xml', $this->contentTypes());
            $zip->addFromString('_rels/.rels', $this->rootRelationships());
            $zip->addFromString('docProps/app.xml', $this->appProperties());
            $zip->addFromString('docProps/core.xml', $this->coreProperties());
            $zip->addFromString('xl/workbook.xml', $this->workbook());
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationships());
            $zip->addFromString('xl/styles.xml', $this->styles());
            $zip->addFromString(
                'xl/worksheets/sheet1.xml',
                $this->worksheet($accounts, $dateFrom, $dateTo, $summary)
            );
        } finally {
            $zip->close();
        }
    }

    private function worksheet(array $accounts, string $dateFrom, string $dateTo, array $summary): string
    {
        $title = sprintf(
            'Расшифровка за период с %s по %s',
            $this->displayDate($dateFrom),
            $this->displayDate($dateTo)
        );
        $headers = [
            'Клиент',
            'Счёт',
            'Сумма списанного остатка',
            'Валюта',
            'Дата',
            'Сумма возврата из резерва',
            'Дата',
        ];

        $rows = [];
        $rows[] = '<row r="1" ht="24" customHeight="1">'
            . $this->textCell('A1', $title, 1)
            . '</row>';

        $headerCells = '';
        foreach ($headers as $column => $header) {
            $headerCells .= $this->textCell($this->columnName($column) . '2', $header, 2);
        }
        $rows[] = '<row r="2" ht="42" customHeight="1">' . $headerCells . '</row>';

        $rowNumber = 3;
        foreach ($accounts as $account) {
            $cells = [
                $this->textCell('A' . $rowNumber, $account->customer, 3),
                $this->textCell('B' . $rowNumber, $account->system_ac_no, 3),
                $this->numberCell('C' . $rowNumber, $account->amount_transfer, 4),
                $this->textCell('D' . $rowNumber, $account->currency, 3),
                $this->dateCell('E' . $rowNumber, $account->application_date),
                $this->numberCell('F' . $rowNumber, $account->employee_sum, 4),
                $this->dateCell('G' . $rowNumber, $account->employee_date_sum),
            ];
            $rows[] = '<row r="' . $rowNumber . '">' . implode('', $cells) . '</row>';
            $rowNumber++;
        }

        $detailLastRow = max(2, $rowNumber - 1);
        $summaryTitleRow = $detailLastRow + 2;
        $summaryHeaderRow = $summaryTitleRow + 1;
        $summarySubheaderRow = $summaryHeaderRow + 1;
        $summaryDataRow = $summarySubheaderRow + 1;

        $rows[] = '<row r="' . $summaryTitleRow . '" ht="24" customHeight="1">'
            . $this->textCell(
                'A' . $summaryTitleRow,
                sprintf(
                    'Сводный отчёт с %s по %s',
                    $this->displayDate($dateFrom),
                    $this->displayDate($dateTo)
                ),
                1
            )
            . '</row>';
        $rows[] = '<row r="' . $summaryHeaderRow . '" ht="30" customHeight="1">'
            . $this->textCell('A' . $summaryHeaderRow, 'Валюта', 2)
            . $this->textCell('B' . $summaryHeaderRow, 'Остаток резерва на начало периода', 2)
            . $this->textCell('E' . $summaryHeaderRow, 'Оборот за период', 2)
            . $this->textCell('G' . $summaryHeaderRow, 'Остаток резерва на конец периода', 2)
            . '</row>';
        $rows[] = '<row r="' . $summarySubheaderRow . '" ht="30" customHeight="1">'
            . $this->textCell('E' . $summarySubheaderRow, 'Списание (+)', 2)
            . $this->textCell('F' . $summarySubheaderRow, 'Возврат (-)', 2)
            . '</row>';

        $mergeCells = [
            'A1:G1',
            'A' . $summaryTitleRow . ':G' . $summaryTitleRow,
            'A' . $summaryHeaderRow . ':A' . $summarySubheaderRow,
            'B' . $summaryHeaderRow . ':D' . $summarySubheaderRow,
            'E' . $summaryHeaderRow . ':F' . $summaryHeaderRow,
            'G' . $summaryHeaderRow . ':G' . $summarySubheaderRow,
        ];

        foreach ($summary as $item) {
            $closingFormula = 'B' . $summaryDataRow . '+E' . $summaryDataRow . '-F' . $summaryDataRow;
            $rows[] = '<row r="' . $summaryDataRow . '" ht="22" customHeight="1">'
                . $this->textCell('A' . $summaryDataRow, $item['currency'], 3)
                . $this->numberCell('B' . $summaryDataRow, $item['opening_balance'], 4)
                . $this->numberCell('E' . $summaryDataRow, $item['debit'], 4)
                . $this->numberCell('F' . $summaryDataRow, $item['return'], 4)
                . $this->formulaCell('G' . $summaryDataRow, $closingFormula, $item['closing_balance'], 4)
                . '</row>';
            $mergeCells[] = 'B' . $summaryDataRow . ':D' . $summaryDataRow;
            $summaryDataRow++;
        }

        $lastRow = max($summarySubheaderRow, $summaryDataRow - 1);
        $mergeXml = '';
        foreach ($mergeCells as $range) {
            $mergeXml .= '<mergeCell ref="' . $range . '"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<dimension ref="A1:G' . $lastRow . '"/>'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="2" topLeftCell="A3" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . '<cols>'
            . '<col min="1" max="1" width="14" customWidth="1"/>'
            . '<col min="2" max="2" width="25" customWidth="1"/>'
            . '<col min="3" max="3" width="24" customWidth="1"/>'
            . '<col min="4" max="4" width="12" customWidth="1"/>'
            . '<col min="5" max="5" width="14" customWidth="1"/>'
            . '<col min="6" max="6" width="25" customWidth="1"/>'
            . '<col min="7" max="7" width="14" customWidth="1"/>'
            . '</cols>'
            . '<sheetData>' . implode('', $rows) . '</sheetData>'
            . '<autoFilter ref="A2:G' . $detailLastRow . '"/>'
            . '<mergeCells count="' . count($mergeCells) . '">' . $mergeXml . '</mergeCells>'
            . '<pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/>'
            . '</worksheet>';
    }

    private function textCell(string $reference, $value, int $style): string
    {
        $value = $value === null ? '' : (string) $value;
        return '<c r="' . $reference . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">'
            . $this->escape($value)
            . '</t></is></c>';
    }

    private function numberCell(string $reference, $value, int $style): string
    {
        if ($value === null || $value === '') {
            return '<c r="' . $reference . '" s="' . $style . '"/>';
        }

        return '<c r="' . $reference . '" s="' . $style . '"><v>'
            . $this->escape((string) (float) $value)
            . '</v></c>';
    }

    private function formulaCell(string $reference, string $formula, $value, int $style): string
    {
        return '<c r="' . $reference . '" s="' . $style . '"><f>'
            . $this->escape($formula)
            . '</f><v>' . $this->escape((string) (float) $value) . '</v></c>';
    }

    private function dateCell(string $reference, $value): string
    {
        if (!$value) {
            return '<c r="' . $reference . '" s="5"/>';
        }

        $date = new DateTimeImmutable((string) $value);
        $origin = new DateTimeImmutable('1899-12-30');
        $serial = (int) $origin->diff($date)->format('%a');

        return '<c r="' . $reference . '" s="5"><v>' . $serial . '</v></c>';
    }

    private function displayDate(string $value): string
    {
        return (new DateTimeImmutable($value))->format('d.m.Y');
    }

    private function columnName(int $zeroBasedColumn): string
    {
        return chr(65 + $zeroBasedColumn);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';
    }

    private function rootRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Резервы" sheetId="1" r:id="rId1"/></sheets>'
            . '<calcPr calcId="191029" fullCalcOnLoad="1" forceFullCalc="1"/>'
            . '</workbook>';
    }

    private function workbookRelationships(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<numFmts count="2"><numFmt numFmtId="164" formatCode="# ##0.00"/><numFmt numFmtId="165" formatCode="dd/mm/yy"/></numFmts>'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="2"><border/><border><left style="thin"><color auto="1"/></left><right style="thin"><color auto="1"/></right><top style="thin"><color auto="1"/></top><bottom style="thin"><color auto="1"/></bottom><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="6">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '<xf numFmtId="0" fontId="1" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyAlignment="1"><alignment vertical="center"/></xf>'
            . '<xf numFmtId="164" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="right" vertical="center"/></xf>'
            . '<xf numFmtId="165" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyAlignment="1"><alignment horizontal="center" vertical="center"/></xf>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function appProperties(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>Dormants</Application></Properties>';
    }

    private function coreProperties(): string
    {
        $created = gmdate('Y-m-d\TH:i:s\Z');
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>Отчёт по резервам</dc:title><dc:creator>Dormants</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $created . '</dcterms:created>'
            . '</cp:coreProperties>';
    }
}
