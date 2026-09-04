<?php

namespace App\Http\Controllers;

use App\Services\ReportExcelService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LaporanJumlahPemeriksaanController extends Controller
{
    public function index()
    {
        return view('laporan.jumlah-pemeriksaan.index');
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
        $cacheKey = 'laporan_jml_uji_' . md5($startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d'));

        if ($forceRefresh) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
        }

        $result = \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () use ($startDate, $endDate) {
            $oracle = DB::connection('oracle');

            $rawData = $oracle
                ->table('ord_hdr as a')
                ->leftJoin('ord_dtl as b', function ($join) {
                    $join->on('a.oh_tno', '=', 'b.od_tno')
                        ->where('b.od_order_item', '=', 'Y');
                })
                ->leftJoin('test_group as c', 'b.od_test_grp', '=', 'c.tg_code')
                ->leftJoin('test_item as d', 'b.od_testcode', '=', 'd.ti_code')
                ->select(
                    DB::raw("CASE 
                                WHEN a.oh_ptype = 'IN' THEN 'Rawat Inap' 
                                WHEN a.oh_ptype = 'OP' THEN 'Rawat Jalan' 
                                ELSE 'Lainnya' 
                            END AS jenis_rawat"),
                    DB::raw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM') as year_month"),
                    'b.od_test_grp as test_group_code',
                    DB::raw("COALESCE(c.tg_name, b.od_test_grp, 'Lain-lain') as test_group_name"),
                    'b.od_testcode as test_code',
                    DB::raw("COALESCE(d.ti_name, b.od_testcode, 'Pemeriksaan') as test_name"),
                    DB::raw('COUNT(*) as total')
                )
                ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
                ->whereNotNull('b.od_test_grp')
                ->groupBy(
                    DB::raw("CASE 
                                WHEN a.oh_ptype = 'IN' THEN 'Rawat Inap' 
                                WHEN a.oh_ptype = 'OP' THEN 'Rawat Jalan' 
                                ELSE 'Lainnya' 
                            END"),
                    DB::raw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM')"),
                    'b.od_test_grp',
                    'c.tg_name',
                    'b.od_testcode',
                    'd.ti_name'
                )
                ->orderBy(DB::raw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM')"), 'ASC')
                ->orderBy('test_group_name', 'ASC')
                ->orderBy('test_name', 'ASC')
                ->get();

            $structuredData = [];
            $categories = [];
            $months = [];

            foreach ($rawData as $row) {
                $months[$row->year_month] = true;
                $categories[$row->test_group_name] = true;

                $structuredData[$row->test_group_name][$row->test_name][$row->year_month][$row->jenis_rawat] = $row->total;
            }

            return [
                'data' => $structuredData,
                'months' => array_keys($months),
                'categories' => array_keys($categories),
                'raw' => $rawData,
                'cached_at' => now()->format('d/m/Y H:i:s'),
            ];
        });

        return response()->json($result);
    }

    public function exportToExcel(Request $request)
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

        $oracle = DB::connection('oracle');

        // ================= 1. REKAPITULASI LAYANAN (SHEET 1) =================
        $rawData = $oracle
            ->table('ord_hdr as a')
            ->leftJoin('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->leftJoin('test_group as c', 'b.od_test_grp', '=', 'c.tg_code')
            ->leftJoin('test_item as d', 'b.od_testcode', '=', 'd.ti_code')
            ->select(
                'b.od_test_grp as test_group_code',
                DB::raw("COALESCE(c.tg_name, b.od_test_grp, 'Lain-lain') as test_group_name"),
                'b.od_testcode as test_code',
                DB::raw("COALESCE(d.ti_name, b.od_testcode, 'Pemeriksaan') as test_name"),
                DB::raw("COUNT(CASE WHEN a.oh_ptype = 'OP' THEN 1 END) as total_rajal"),
                DB::raw("COUNT(CASE WHEN a.oh_ptype = 'IN' THEN 1 END) as total_ranap"),
                DB::raw("COUNT(CASE WHEN a.oh_ptype NOT IN ('OP', 'IN') OR a.oh_ptype IS NULL THEN 1 END) as total_lainnya"),
                DB::raw("COUNT(*) as total_keseluruhan")
            )
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('b.od_test_grp')
            ->groupBy('b.od_test_grp', 'c.tg_name', 'b.od_testcode', 'd.ti_name')
            ->orderBy('test_group_name', 'ASC')
            ->orderBy('test_name', 'ASC')
            ->get();

        $periodStr = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        $spreadsheet = ReportExcelService::createSpreadsheet('Laporan Jumlah Pemeriksaan Laboratorium', $periodStr);
        
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Rekapitulasi Layanan');

        $sheet1->setCellValue('A6', 'No');
        $sheet1->setCellValue('B6', 'Kelompok Pemeriksaan');
        $sheet1->setCellValue('C6', 'Kode Uji');
        $sheet1->setCellValue('D6', 'Nama Pemeriksaan');
        $sheet1->setCellValue('E6', 'Rawat Jalan');
        $sheet1->setCellValue('F6', 'Rawat Inap');
        $sheet1->setCellValue('G6', 'Lainnya');
        $sheet1->setCellValue('H6', 'Total Jumlah');

        $rowIdx = 7;
        $no = 1;
        $sumRajal = 0;
        $sumRanap = 0;
        $sumLainnya = 0;
        $sumTotal = 0;

        foreach ($rawData as $row) {
            $sheet1->setCellValue("A{$rowIdx}", $no++);
            $sheet1->setCellValue("B{$rowIdx}", $row->test_group_name);
            $sheet1->setCellValue("C{$rowIdx}", $row->test_code);
            $sheet1->setCellValue("D{$rowIdx}", $row->test_name);
            $sheet1->setCellValue("E{$rowIdx}", (int)$row->total_rajal);
            $sheet1->setCellValue("F{$rowIdx}", (int)$row->total_ranap);
            $sheet1->setCellValue("G{$rowIdx}", (int)$row->total_lainnya);
            $sheet1->setCellValue("H{$rowIdx}", (int)$row->total_keseluruhan);

            $sumRajal += (int)$row->total_rajal;
            $sumRanap += (int)$row->total_ranap;
            $sumLainnya += (int)$row->total_lainnya;
            $sumTotal += (int)$row->total_keseluruhan;
            $rowIdx++;
        }

        // Summary row Sheet 1
        $sheet1->setCellValue("A{$rowIdx}", '');
        $sheet1->setCellValue("B{$rowIdx}", 'TOTAL KESELURUHAN');
        $sheet1->setCellValue("C{$rowIdx}", '');
        $sheet1->setCellValue("D{$rowIdx}", '');
        $sheet1->setCellValue("E{$rowIdx}", $sumRajal);
        $sheet1->setCellValue("F{$rowIdx}", $sumRanap);
        $sheet1->setCellValue("G{$rowIdx}", $sumLainnya);
        $sheet1->setCellValue("H{$rowIdx}", $sumTotal);

        ReportExcelService::formatTable($sheet1, 6, $rowIdx, 'A', 'H', true);
        $sheet1->getStyle("A7:A{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle("C7:C{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle("E7:H{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ================= 2. RINCIAN BULANAN (SHEET 2) =================
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Rincian Bulanan');

        // Header Sheet 2
        $sheet2->setCellValue('A1', 'RUMAH SAKIT UMUM DAERAH');
        $sheet2->setCellValue('A2', 'LABORATORIUM PATOLOGI KLINIK');
        $sheet2->setCellValue('A3', 'RINCIAN JUMLAH PEMERIKSAAN PER BULAN & JENIS PELAYANAN');
        $sheet2->setCellValue('A4', 'Periode: ' . $periodStr . ' | Dicetak: ' . date('d/m/Y H:i:s'));
        $sheet2->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet2->getStyle('A1')->getFont()->setSize(13);
        $sheet2->getStyle('A2')->getFont()->setSize(11);
        $sheet2->getStyle('A3')->getFont()->setSize(11);
        $sheet2->getStyle('A4')->getFont()->setSize(9)->setItalic(true);

        // Query Data Bulanan dengan rincian Rajal, Ranap, Lainnya
        $monthlyRaw = $oracle
            ->table('ord_hdr as a')
            ->join('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->leftJoin('test_group as c', 'b.od_test_grp', '=', 'c.tg_code')
            ->leftJoin('test_item as d', 'b.od_testcode', '=', 'd.ti_code')
            ->select(
                'b.od_test_grp as test_group_code',
                DB::raw("COALESCE(c.tg_name, b.od_test_grp, 'Lain-lain') as test_group_name"),
                'b.od_testcode as test_code',
                DB::raw("COALESCE(d.ti_name, b.od_testcode, 'Pemeriksaan') as test_name"),
                DB::raw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM') as year_month"),
                DB::raw("COUNT(CASE WHEN a.oh_ptype = 'OP' THEN 1 END) as rajal"),
                DB::raw("COUNT(CASE WHEN a.oh_ptype = 'IN' THEN 1 END) as ranap"),
                DB::raw("COUNT(CASE WHEN a.oh_ptype NOT IN ('OP', 'IN') OR a.oh_ptype IS NULL THEN 1 END) as lainnya"),
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('b.od_test_grp')
            ->groupBy('b.od_test_grp', 'c.tg_name', 'b.od_testcode', 'd.ti_name', DB::raw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM')"))
            ->orderBy('test_group_name', 'ASC')
            ->orderBy('test_name', 'ASC')
            ->get();

        // Bangun daftar semua bulan dalam rentang tanggal
        $monthList = [];
        $cursor = $startDate->copy()->startOfMonth();
        $endCursor = $endDate->copy()->startOfMonth();
        while ($cursor->lte($endCursor)) {
            $monthList[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        // Susun matriks pemeriksaan per bulan & per layanan
        $testMatrix = [];
        foreach ($monthlyRaw as $mRow) {
            $key = $mRow->test_group_code . '|' . $mRow->test_code;
            if (!isset($testMatrix[$key])) {
                $testMatrix[$key] = [
                    'group' => $mRow->test_group_name,
                    'code' => $mRow->test_code,
                    'name' => $mRow->test_name,
                    'months' => [],
                    'total_rajal' => 0,
                    'total_ranap' => 0,
                    'total_lainnya' => 0,
                    'total_all' => 0
                ];
            }
            $r = (int)$mRow->rajal;
            $in = (int)$mRow->ranap;
            $l = (int)$mRow->lainnya;
            $tot = (int)$mRow->total;

            $testMatrix[$key]['months'][$mRow->year_month] = [
                'rajal' => $r,
                'ranap' => $in,
                'lainnya' => $l,
                'total' => $tot
            ];
            $testMatrix[$key]['total_rajal'] += $r;
            $testMatrix[$key]['total_ranap'] += $in;
            $testMatrix[$key]['total_lainnya'] += $l;
            $testMatrix[$key]['total_all'] += $tot;
        }

        // Susun Header 2 Baris (Baris 6 & 7)
        $sheet2->mergeCells('A6:A7');
        $sheet2->mergeCells('B6:B7');
        $sheet2->mergeCells('C6:C7');
        $sheet2->mergeCells('D6:D7');

        $sheet2->setCellValue('A6', 'No');
        $sheet2->setCellValue('B6', 'Kelompok Pemeriksaan');
        $sheet2->setCellValue('C6', 'Kode Uji');
        $sheet2->setCellValue('D6', 'Nama Pemeriksaan');

        $colIdx = 5;
        $monthColMap = [];
        $monthlySums = [];

        foreach ($monthList as $ym) {
            $startCol = Coordinate::stringFromColumnIndex($colIdx);
            $endCol = Coordinate::stringFromColumnIndex($colIdx + 3);

            $monthLabel = Carbon::createFromFormat('Y-m', $ym)->translatedFormat('F Y');
            $sheet2->mergeCells("{$startCol}6:{$endCol}6");
            $sheet2->setCellValue("{$startCol}6", strtoupper($monthLabel));

            $c1 = Coordinate::stringFromColumnIndex($colIdx);
            $c2 = Coordinate::stringFromColumnIndex($colIdx + 1);
            $c3 = Coordinate::stringFromColumnIndex($colIdx + 2);
            $c4 = Coordinate::stringFromColumnIndex($colIdx + 3);

            $sheet2->setCellValue("{$c1}7", 'Rajal');
            $sheet2->setCellValue("{$c2}7", 'Ranap');
            $sheet2->setCellValue("{$c3}7", 'Lainnya');
            $sheet2->setCellValue("{$c4}7", 'Total');

            $monthColMap[$ym] = ['rajal' => $c1, 'ranap' => $c2, 'lainnya' => $c3, 'total' => $c4];
            $monthlySums[$ym] = ['rajal' => 0, 'ranap' => 0, 'lainnya' => 0, 'total' => 0];
            $colIdx += 4;
        }

        // Header Total Keseluruhan
        $gtStartCol = Coordinate::stringFromColumnIndex($colIdx);
        $gtEndCol = Coordinate::stringFromColumnIndex($colIdx + 3);
        $sheet2->mergeCells("{$gtStartCol}6:{$gtEndCol}6");
        $sheet2->setCellValue("{$gtStartCol}6", 'TOTAL KESELURUHAN');

        $gt1 = Coordinate::stringFromColumnIndex($colIdx);
        $gt2 = Coordinate::stringFromColumnIndex($colIdx + 1);
        $gt3 = Coordinate::stringFromColumnIndex($colIdx + 2);
        $gt4 = Coordinate::stringFromColumnIndex($colIdx + 3);

        $sheet2->setCellValue("{$gt1}7", 'Rajal');
        $sheet2->setCellValue("{$gt2}7", 'Ranap');
        $sheet2->setCellValue("{$gt3}7", 'Lainnya');
        $sheet2->setCellValue("{$gt4}7", 'Total');

        $s2RowIdx = 8;
        $s2No = 1;
        $grandTotalSums = ['rajal' => 0, 'ranap' => 0, 'lainnya' => 0, 'total' => 0];

        foreach ($testMatrix as $tData) {
            $sheet2->setCellValue("A{$s2RowIdx}", $s2No++);
            $sheet2->setCellValue("B{$s2RowIdx}", $tData['group']);
            $sheet2->setCellValue("C{$s2RowIdx}", $tData['code']);
            $sheet2->setCellValue("D{$s2RowIdx}", $tData['name']);

            foreach ($monthList as $ym) {
                $cols = $monthColMap[$ym];
                $mValues = $tData['months'][$ym] ?? ['rajal' => 0, 'ranap' => 0, 'lainnya' => 0, 'total' => 0];

                $sheet2->setCellValue("{$cols['rajal']}{$s2RowIdx}", (int)$mValues['rajal']);
                $sheet2->setCellValue("{$cols['ranap']}{$s2RowIdx}", (int)$mValues['ranap']);
                $sheet2->setCellValue("{$cols['lainnya']}{$s2RowIdx}", (int)$mValues['lainnya']);
                $sheet2->setCellValue("{$cols['total']}{$s2RowIdx}", (int)$mValues['total']);

                $monthlySums[$ym]['rajal'] += $mValues['rajal'];
                $monthlySums[$ym]['ranap'] += $mValues['ranap'];
                $monthlySums[$ym]['lainnya'] += $mValues['lainnya'];
                $monthlySums[$ym]['total'] += $mValues['total'];
            }

            $sheet2->setCellValue("{$gt1}{$s2RowIdx}", (int)$tData['total_rajal']);
            $sheet2->setCellValue("{$gt2}{$s2RowIdx}", (int)$tData['total_ranap']);
            $sheet2->setCellValue("{$gt3}{$s2RowIdx}", (int)$tData['total_lainnya']);
            $sheet2->setCellValue("{$gt4}{$s2RowIdx}", (int)$tData['total_all']);

            $grandTotalSums['rajal'] += $tData['total_rajal'];
            $grandTotalSums['ranap'] += $tData['total_ranap'];
            $grandTotalSums['lainnya'] += $tData['total_lainnya'];
            $grandTotalSums['total'] += $tData['total_all'];

            $s2RowIdx++;
        }

        // Summary Row Sheet 2
        $sheet2->setCellValue("A{$s2RowIdx}", '');
        $sheet2->setCellValue("B{$s2RowIdx}", 'TOTAL KESELURUHAN');
        $sheet2->setCellValue("C{$s2RowIdx}", '');
        $sheet2->setCellValue("D{$s2RowIdx}", '');

        foreach ($monthList as $ym) {
            $cols = $monthColMap[$ym];
            $sheet2->setCellValue("{$cols['rajal']}{$s2RowIdx}", $monthlySums[$ym]['rajal']);
            $sheet2->setCellValue("{$cols['ranap']}{$s2RowIdx}", $monthlySums[$ym]['ranap']);
            $sheet2->setCellValue("{$cols['lainnya']}{$s2RowIdx}", $monthlySums[$ym]['lainnya']);
            $sheet2->setCellValue("{$cols['total']}{$s2RowIdx}", $monthlySums[$ym]['total']);
        }

        $sheet2->setCellValue("{$gt1}{$s2RowIdx}", $grandTotalSums['rajal']);
        $sheet2->setCellValue("{$gt2}{$s2RowIdx}", $grandTotalSums['ranap']);
        $sheet2->setCellValue("{$gt3}{$s2RowIdx}", $grandTotalSums['lainnya']);
        $sheet2->setCellValue("{$gt4}{$s2RowIdx}", $grandTotalSums['total']);

        // Styling Sheet 2 Table (2-Row Header)
        $headerRange = "A6:{$gt4}7";
        $sheet2->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
        $sheet2->getStyle($headerRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
        $sheet2->getStyle($headerRange)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        $sheet2->getRowDimension(6)->setRowHeight(24);
        $sheet2->getRowDimension(7)->setRowHeight(20);

        // Full borders
        $fullRange = "A6:{$gt4}{$s2RowIdx}";
        $sheet2->getStyle($fullRange)->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

        // Center Alignment for data rows
        $sheet2->getStyle("A8:A{$s2RowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle("C8:C{$s2RowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle("E8:{$gt4}{$s2RowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Total Row Styling
        $totalRange = "A{$s2RowIdx}:{$gt4}{$s2RowIdx}";
        $sheet2->getStyle($totalRange)->getFont()->setBold(true);
        $sheet2->getStyle($totalRange)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        $sheet2->getStyle($totalRange)->getBorders()->getBottom()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE);

        // Auto Fit Columns
        $startColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString('A');
        $endColIdx = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($gt4);
        for ($i = $startColIdx; $i <= $endColIdx; $i++) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet2->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Set Sheet 1 as active by default
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'Laporan_Jumlah_Pemeriksaan_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.xlsx';
        return ReportExcelService::streamDownload($spreadsheet, $filename);
    }
}
