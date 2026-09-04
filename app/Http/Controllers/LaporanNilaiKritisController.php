<?php

namespace App\Http\Controllers;

use App\Services\ReportExcelService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class LaporanNilaiKritisController extends Controller
{
    public function index()
    {
        return view('laporan.nilai-kritis.index');
    }

    public function getData(Request $request)
    {
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '1024M');
        if (function_exists('set_time_limit')) {
            @set_time_limit(600);
        }

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        $forceRefresh = $request->boolean('refresh', false);
        $cacheKey = 'laporan_kritis_' . md5($startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d'));

        if ($forceRefresh) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
        }

        $result = \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () use ($startDate, $endDate) {
            $oracle = DB::connection('oracle');

            $nilaiKritis = $oracle
                ->table('ord_dtl as od')
                ->join('ord_hdr as oh', 'od.od_tno', '=', 'oh.oh_tno')
                ->leftJoin('hfclinic as hc', 'oh.oh_clinic_code', '=', 'hc.clinic_code')
                ->leftJoin('test_item as ti', 'od.od_testcode', '=', 'ti.ti_code')
                ->select(
                    'oh.oh_trx_dt',
                    'oh.oh_tno',
                    'oh.oh_pid',
                    'oh.oh_apid',
                    'oh.oh_last_name',
                    'oh.oh_dname',
                    'hc.clinic_desc',
                    'od.od_tr_val',
                    'od.od_tr_flag',
                    'od.od_update_on',
                    'od.od_tr_comment',
                    'od.od_validate_by',
                    'ti.ti_name'
                )
                ->whereIn('od.od_tr_flag', ['LL', 'HH'])
                ->whereBetween('oh.oh_trx_dt', [$startDate, $endDate])
                ->orderBy('oh.oh_trx_dt', 'desc')
                ->get();

            return [
                'data' => $nilaiKritis,
                'cached_at' => now()->format('d/m/Y H:i:s'),
            ];
        });

        return response()->json($result['data']);
    }

    public function exportToExcel(Request $request)
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        $oracle = DB::connection('oracle');

        $nilaiKritis = $oracle
            ->table('ord_dtl as od')
            ->join('ord_hdr as oh', 'od.od_tno', '=', 'oh.oh_tno')
            ->leftJoin('hfclinic as hc', 'oh.oh_clinic_code', '=', 'hc.clinic_code')
            ->leftJoin('test_item as ti', 'od.od_testcode', '=', 'ti.ti_code')
            ->select(
                'oh.oh_trx_dt',
                'oh.oh_tno',
                'oh.oh_pid',
                'oh.oh_last_name',
                'oh.oh_dname',
                'hc.clinic_desc',
                'od.od_tr_val',
                'od.od_tr_flag',
                'od.od_update_on',
                'od.od_tr_comment',
                'od.od_validate_by',
                'ti.ti_name'
            )
            ->whereIn('od.od_tr_flag', ['LL', 'HH'])
            ->whereBetween('oh.oh_trx_dt', [$startDate, $endDate])
            ->orderBy('oh.oh_trx_dt', 'asc')
            ->get();

        $periodStr = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        $spreadsheet = ReportExcelService::createSpreadsheet('Laporan Hasil Nilai Kritis Laboratorium', $periodStr);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Nilai Kritis');

        $sheet->setCellValue('A6', 'No');
        $sheet->setCellValue('B6', 'Tanggal Order');
        $sheet->setCellValue('C6', 'No. Order');
        $sheet->setCellValue('D6', 'No. RM');
        $sheet->setCellValue('E6', 'Nama Pasien');
        $sheet->setCellValue('F6', 'Ruangan / Poliklinik');
        $sheet->setCellValue('G6', 'Dokter Pengirim');
        $sheet->setCellValue('H6', 'Pemeriksaan');
        $sheet->setCellValue('I6', 'Hasil Kritis');
        $sheet->setCellValue('J6', 'Flag');
        $sheet->setCellValue('K6', 'Waktu Verifikasi');
        $sheet->setCellValue('L6', 'Validator');
        $sheet->setCellValue('M6', 'Catatan Kritis');

        $rowIdx = 7;
        $no = 1;

        foreach ($nilaiKritis as $row) {
            $sheet->setCellValue("A{$rowIdx}", $no++);
            $sheet->setCellValue("B{$rowIdx}", $row->oh_trx_dt ? date('d/m/Y', strtotime($row->oh_trx_dt)) : '-');
            $sheet->setCellValue("C{$rowIdx}", $row->oh_tno);
            $sheet->setCellValue("D{$rowIdx}", $row->oh_pid);
            $sheet->setCellValue("E{$rowIdx}", $row->oh_last_name);
            $sheet->setCellValue("F{$rowIdx}", $row->clinic_desc ?: '-');
            $sheet->setCellValue("G{$rowIdx}", $row->oh_dname ?: '-');
            $sheet->setCellValue("H{$rowIdx}", $row->ti_name ?: '-');
            $sheet->setCellValue("I{$rowIdx}", $row->od_tr_val);
            $sheet->setCellValue("J{$rowIdx}", $row->od_tr_flag);
            $sheet->setCellValue("K{$rowIdx}", $row->od_update_on ? date('d/m/Y H:i', strtotime($row->od_update_on)) : '-');
            $sheet->setCellValue("L{$rowIdx}", $row->od_validate_by ?: '-');
            $sheet->setCellValue("M{$rowIdx}", $row->od_tr_comment ?: '-');

            $rowIdx++;
        }

        // Total count
        $sheet->setCellValue("A{$rowIdx}", '');
        $sheet->setCellValue("B{$rowIdx}", 'TOTAL KASUS NILAI KRITIS');
        $sheet->setCellValue("C{$rowIdx}", count($nilaiKritis) . ' Kasus');

        ReportExcelService::formatTable($sheet, 6, $rowIdx, 'A', 'M', true);

        $filename = 'Laporan_Nilai_Kritis_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.xlsx';
        return ReportExcelService::streamDownload($spreadsheet, $filename);
    }

    public function exportToWord(Request $request)
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        $oracle = DB::connection('oracle');

        $nilaiKritis = $oracle
            ->table('ord_dtl as od')
            ->join('ord_hdr as oh', 'od.od_tno', '=', 'oh.oh_tno')
            ->leftJoin('hfclinic as hc', 'oh.oh_clinic_code', '=', 'hc.clinic_code')
            ->leftJoin('test_item as ti', 'od.od_testcode', '=', 'ti.ti_code')
            ->select(
                'oh.oh_trx_dt',
                'oh.oh_tno',
                'oh.oh_pid',
                'oh.oh_last_name',
                'hc.clinic_desc',
                'od.od_tr_val',
                'od.od_tr_flag',
                'od.od_update_on',
                'ti.ti_name'
            )
            ->whereIn('od.od_tr_flag', ['LL', 'HH'])
            ->whereBetween('oh.oh_trx_dt', [$startDate, $endDate])
            ->orderBy('oh.oh_trx_dt', 'asc')
            ->get();

        $phpWord = new PhpWord();
        $section = $phpWord->addSection(['orientation' => 'landscape']);

        $tableStyle = ['borderSize' => 6, 'borderColor' => '94A3B8', 'cellMargin' => 60, 'width' => 100 * 50, 'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT];
        $fontStyle = ['name' => 'Plus Jakarta Sans', 'size' => 8];
        $headerFontStyle = ['name' => 'Plus Jakarta Sans', 'size' => 8, 'bold' => true, 'color' => 'FFFFFF'];
        $cellStyle = ['valign' => 'center'];
        $headerCellStyle = ['valign' => 'center', 'bgColor' => 'DC2626'];

        $section->addText('RUMAH SAKIT UMUM DAERAH', ['bold' => true, 'size' => 12, 'name' => 'Plus Jakarta Sans'], ['align' => 'center']);
        $section->addText('LABORATORIUM PATOLOGI KLINIK', ['bold' => true, 'size' => 11, 'name' => 'Plus Jakarta Sans'], ['align' => 'center']);
        $section->addText('LAPORAN HASIL NILAI KRITIS (CRITICAL VALUE REPORT)', ['bold' => true, 'size' => 10, 'name' => 'Plus Jakarta Sans'], ['align' => 'center']);
        $section->addText('Periode: ' . $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y') . ' | Total: ' . count($nilaiKritis) . ' Kasus', ['size' => 8, 'italic' => true], ['align' => 'center']);
        $section->addTextBreak(1);

        $table = $section->addTable($tableStyle);
        $table->addRow();
        $table->addCell(500, $headerCellStyle)->addText('No', $headerFontStyle, ['align' => 'center']);
        $table->addCell(1200, $headerCellStyle)->addText('Tanggal', $headerFontStyle, ['align' => 'center']);
        $table->addCell(1000, $headerCellStyle)->addText('No. RM', $headerFontStyle, ['align' => 'center']);
        $table->addCell(2000, $headerCellStyle)->addText('Nama Pasien', $headerFontStyle);
        $table->addCell(1800, $headerCellStyle)->addText('Ruangan', $headerFontStyle);
        $table->addCell(1800, $headerCellStyle)->addText('Pemeriksaan', $headerFontStyle);
        $table->addCell(1000, $headerCellStyle)->addText('Hasil', $headerFontStyle, ['align' => 'right']);
        $table->addCell(600, $headerCellStyle)->addText('Flag', $headerFontStyle, ['align' => 'center']);
        $table->addCell(1200, $headerCellStyle)->addText('Waktu Validasi', $headerFontStyle, ['align' => 'center']);

        $no = 1;
        foreach ($nilaiKritis as $row) {
            $table->addRow();
            $table->addCell(500, $cellStyle)->addText($no++, $fontStyle, ['align' => 'center']);
            $table->addCell(1200, $cellStyle)->addText($row->oh_trx_dt ? date('d/m/Y', strtotime($row->oh_trx_dt)) : '-', $fontStyle, ['align' => 'center']);
            $table->addCell(1000, $cellStyle)->addText($row->oh_pid, $fontStyle, ['align' => 'center']);
            $table->addCell(2000, $cellStyle)->addText($row->oh_last_name, $fontStyle);
            $table->addCell(1800, $cellStyle)->addText($row->clinic_desc ?: '-', $fontStyle);
            $table->addCell(1800, $cellStyle)->addText($row->ti_name ?: '-', $fontStyle);
            $table->addCell(1000, $cellStyle)->addText($row->od_tr_val, ['bold' => true, 'color' => 'DC2626', 'size' => 8], ['align' => 'right']);
            $table->addCell(600, $cellStyle)->addText($row->od_tr_flag, ['bold' => true, 'size' => 8], ['align' => 'center']);
            $table->addCell(1200, $cellStyle)->addText($row->od_update_on ? date('d/m/Y H:i', strtotime($row->od_update_on)) : '-', $fontStyle, ['align' => 'center']);
        }

        $fileName = 'Laporan_Nilai_Kritis_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.docx';
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');

        return response()->streamDownload(function() use ($objWriter) {
            $objWriter->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}
