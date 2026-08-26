<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExcelService
{
    /**
     * Create a styled spreadsheet with standard hospital lab header
     */
    public static function createSpreadsheet(string $reportTitle, string $periodStr): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Hospital Header
        $sheet->setCellValue('A1', 'RUMAH SAKIT UMUM DAERAH');
        $sheet->setCellValue('A2', 'INSTALASI LABORATORIUM PATOLOGI KLINIK');
        $sheet->setCellValue('A3', strtoupper($reportTitle));
        $sheet->setCellValue('A4', 'Periode: ' . $periodStr . ' | Dicetak: ' . date('d/m/Y H:i:s'));

        // Header Styling
        $sheet->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(14);
        $sheet->getStyle('A2')->getFont()->setSize(12);
        $sheet->getStyle('A3')->getFont()->setSize(12);
        $sheet->getStyle('A4')->getFont()->setSize(10)->setItalic(true);

        return $spreadsheet;
    }

    /**
     * Style a table range with borders, header styling, and auto column sizing
     */
    public static function formatTable(
        $sheet,
        int $headerRow,
        int $lastRow,
        string $firstCol = 'A',
        string $lastCol = 'Z',
        bool $hasTotalRow = false
    ): void {
        // Table Header Styling
        $headerRange = "{$firstCol}{$headerRow}:{$lastCol}{$headerRow}";
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($headerRow)->setRowHeight(25);

        // Table Borders
        $fullRange = "{$firstCol}{$headerRow}:{$lastCol}{$lastRow}";
        $sheet->getStyle($fullRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

        // Total Row Styling
        if ($hasTotalRow && $lastRow > $headerRow) {
            $totalRange = "{$firstCol}{$lastRow}:{$lastCol}{$lastRow}";
            $sheet->getStyle($totalRange)->getFont()->setBold(true);
            $sheet->getStyle($totalRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
            $sheet->getStyle($totalRange)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);
        }

        // Auto Fit Columns
        $startColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($firstCol);
        $endColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($lastCol);

        for ($i = $startColIdx; $i <= $endColIdx; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }

    /**
     * Download spreadsheet as streamed response
     */
    public static function streamDownload(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
