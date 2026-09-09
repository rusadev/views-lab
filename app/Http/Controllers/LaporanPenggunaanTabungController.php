<?php

namespace App\Http\Controllers;

use App\Services\ReportExcelService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class LaporanPenggunaanTabungController extends Controller
{
    public function index()
    {
        return view('laporan.penggunaan-tabung.index');
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
        $cacheKey = 'laporan_tabung_' . md5($startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d'));

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        $result = Cache::remember($cacheKey, 600, function () use ($startDate, $endDate) {
            $oracle = DB::connection('oracle');

            // 1. Data Rekapitulasi per Tabung & Spesimen (Rajal, Ranap, Lainnya, Total)
            $summaryRaw = $oracle
                ->table('ord_hdr as a')
                ->leftJoin('ord_dtl as b', function ($join) {
                    $join->on('a.oh_tno', '=', 'b.od_tno')
                        ->where('b.od_order_item', '=', 'Y');
                })
                ->join('ord_spl as d', function ($join) {
                    $join->on('b.od_tno', '=', 'd.os_tno')
                        ->on('b.od_spl_type', '=', 'd.os_spl_type');
                })
                ->leftJoin('sample_type as e', 'd.os_spl_type', '=', 'e.st_code')
                ->select(
                    'd.os_spl_type as sample_code',
                    DB::raw("COALESCE(e.st_name, d.os_spl_type) as sample_name"),
                    DB::raw("COUNT(DISTINCT CASE WHEN a.oh_ptype = 'OP' THEN d.os_tno END) as total_rajal"),
                    DB::raw("COUNT(DISTINCT CASE WHEN a.oh_ptype = 'IN' THEN d.os_tno END) as total_ranap"),
                    DB::raw("COUNT(DISTINCT CASE WHEN a.oh_ptype NOT IN ('OP', 'IN') OR a.oh_ptype IS NULL THEN d.os_tno END) as total_lainnya"),
                    DB::raw("COUNT(DISTINCT d.os_tno) as total_keseluruhan")
                )
                ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
                ->whereNotNull('d.os_spl_type')
                ->groupBy('d.os_spl_type', 'e.st_name')
                ->orderBy('sample_name', 'ASC')
                ->get();

            // 2. Data Rincian Bulanan per Spesimen
            $monthlyRaw = $oracle
                ->table('ord_hdr as a')
                ->leftJoin('ord_dtl as b', function ($join) {
                    $join->on('a.oh_tno', '=', 'b.od_tno')
                        ->where('b.od_order_item', '=', 'Y');
                })
                ->join('ord_spl as d', function ($join) {
                    $join->on('b.od_tno', '=', 'd.os_tno')
                        ->on('b.od_spl_type', '=', 'd.os_spl_type');
                })
                ->leftJoin('sample_type as e', 'd.os_spl_type', '=', 'e.st_code')
                ->select(
                    'd.os_spl_type as sample_code',
                    DB::raw("COALESCE(e.st_name, d.os_spl_type) as sample_name"),
                    DB::raw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM') as year_month"),
                    DB::raw("COUNT(DISTINCT CASE WHEN a.oh_ptype = 'OP' THEN d.os_tno END) as rajal"),
                    DB::raw("COUNT(DISTINCT CASE WHEN a.oh_ptype = 'IN' THEN d.os_tno END) as ranap"),
                    DB::raw("COUNT(DISTINCT CASE WHEN a.oh_ptype NOT IN ('OP', 'IN') OR a.oh_ptype IS NULL THEN d.os_tno END) as lainnya"),
                    DB::raw("COUNT(DISTINCT d.os_tno) as total")
                )
                ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
                ->whereNotNull('d.os_spl_type')
                ->groupBy('d.os_spl_type', 'e.st_name', DB::raw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM')"))
                ->orderBy(DB::raw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM')"), 'ASC')
                ->orderBy('sample_name', 'ASC')
                ->get();

            // 3. Data Harian (untuk mode Tampilan Harian)
            $dailyRaw = $oracle
                ->table('ord_hdr as a')
                ->leftJoin('ord_dtl as b', function ($join) {
                    $join->on('a.oh_tno', '=', 'b.od_tno')
                        ->where('b.od_order_item', '=', 'Y');
                })
                ->join('ord_spl as d', function ($join) {
                    $join->on('b.od_tno', '=', 'd.os_tno')
                        ->on('b.od_spl_type', '=', 'd.os_spl_type');
                })
                ->leftJoin('sample_type as e', 'd.os_spl_type', '=', 'e.st_code')
                ->selectRaw("
                    TO_CHAR(a.oh_trx_dt, 'YYYY-MM-DD') as trx_date, 
                    d.os_spl_type as sample_code,
                    COALESCE(e.st_name, d.os_spl_type) as sample, 
                    COUNT(DISTINCT d.os_tno) as total_usage
                ")
                ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
                ->whereNotNull('d.os_spl_type')
                ->groupByRaw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM-DD'), d.os_spl_type, COALESCE(e.st_name, d.os_spl_type)")
                ->orderBy('trx_date', 'asc')
                ->get();

            // Bangun daftar semua bulan dalam rentang tanggal
            $monthList = [];
            $cursor = $startDate->copy()->startOfMonth();
            $endCursor = $endDate->copy()->startOfMonth();
            while ($cursor->lte($endCursor)) {
                $monthList[] = [
                    'key' => $cursor->format('Y-m'),
                    'label' => $cursor->translatedFormat('F Y'),
                ];
                $cursor->addMonth();
            }

            // Hitung akumulasi KPI
            $kpiTotal = 0;
            $kpiRajal = 0;
            $kpiRanap = 0;
            $kpiLainnya = 0;
            foreach ($summaryRaw as $s) {
                $kpiTotal += (int)$s->total_keseluruhan;
                $kpiRajal += (int)$s->total_rajal;
                $kpiRanap += (int)$s->total_ranap;
                $kpiLainnya += (int)$s->total_lainnya;
            }

            // Struktur Matriks Bulanan untuk Tabung
            // rows: specimens, cols: months (with rajal, ranap, lainnya, total)
            $tubeMatrix = [];
            foreach ($monthlyRaw as $mRow) {
                $code = $mRow->sample_code;
                if (!isset($tubeMatrix[$code])) {
                    $tubeMatrix[$code] = [
                        'code' => $code,
                        'name' => $mRow->sample_name,
                        'months' => [],
                        'total_rajal' => 0,
                        'total_ranap' => 0,
                        'total_lainnya' => 0,
                        'total_all' => 0,
                    ];
                }
                $r = (int)$mRow->rajal;
                $in = (int)$mRow->ranap;
                $l = (int)$mRow->lainnya;
                $tot = (int)$mRow->total;

                $tubeMatrix[$code]['months'][$mRow->year_month] = [
                    'rajal' => $r,
                    'ranap' => $in,
                    'lainnya' => $l,
                    'total' => $tot,
                ];
                $tubeMatrix[$code]['total_rajal'] += $r;
                $tubeMatrix[$code]['total_ranap'] += $in;
                $tubeMatrix[$code]['total_lainnya'] += $l;
                $tubeMatrix[$code]['total_all'] += $tot;
            }

            // Struktur Ringkasan Bulanan (per bulan: total, rajal, ranap, lainnya, per_sample)
            $monthlySummary = [];
            foreach ($monthList as $mItem) {
                $ym = $mItem['key'];
                $monthlySummary[$ym] = [
                    'year_month' => $ym,
                    'label' => $mItem['label'],
                    'rajal' => 0,
                    'ranap' => 0,
                    'lainnya' => 0,
                    'total' => 0,
                    'samples' => [],
                ];
            }
            foreach ($monthlyRaw as $mRow) {
                $ym = $mRow->year_month;
                if (isset($monthlySummary[$ym])) {
                    $monthlySummary[$ym]['rajal'] += (int)$mRow->rajal;
                    $monthlySummary[$ym]['ranap'] += (int)$mRow->ranap;
                    $monthlySummary[$ym]['lainnya'] += (int)$mRow->lainnya;
                    $monthlySummary[$ym]['total'] += (int)$mRow->total;
                    $monthlySummary[$ym]['samples'][$mRow->sample_name] = (int)$mRow->total;
                }
            }

            // Daily formatting
            $dailyDates = $dailyRaw->pluck('trx_date')->unique()->sort()->values()->all();
            $dailySamples = $dailyRaw->pluck('sample')->unique()->sort()->values()->all();
            $formattedDaily = [];
            foreach ($dailyDates as $date) {
                $formattedDaily[$date] = [
                    'tanggal' => $date,
                    'total' => 0,
                ];
                foreach ($dailySamples as $sample) {
                    $formattedDaily[$date][$sample] = 0;
                }
            }
            foreach ($dailyRaw as $item) {
                $formattedDaily[$item->trx_date][$item->sample] = (int)$item->total_usage;
                $formattedDaily[$item->trx_date]['total'] += (int)$item->total_usage;
            }

            return [
                'kpi' => [
                    'total' => $kpiTotal,
                    'rajal' => $kpiRajal,
                    'ranap' => $kpiRanap,
                    'lainnya' => $kpiLainnya,
                ],
                'summary_tabung' => $summaryRaw,
                'monthly_raw' => $monthlyRaw,
                'monthly_matrix' => array_values($tubeMatrix),
                'monthly_summary' => array_values($monthlySummary),
                'month_list' => $monthList,
                'daily' => [
                    'samples' => $dailySamples,
                    'data' => array_values($formattedDaily),
                ],
                // Backwards compatibility
                'samples' => $dailySamples,
                'data' => array_values($formattedDaily),
                'total_keseluruhan' => $kpiTotal,
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
            ->join('ord_spl as d', function ($join) {
                $join->on('b.od_tno', '=', 'd.os_tno')
                    ->on('b.od_spl_type', '=', 'd.os_spl_type');
            })
            ->leftJoin('sample_type as e', 'd.os_spl_type', '=', 'e.st_code')
            ->select(
                'd.os_spl_type as sample_code',
                DB::raw("COALESCE(e.st_name, d.os_spl_type) as sample_name"),
                DB::raw("COUNT(DISTINCT CASE WHEN a.oh_ptype = 'OP' THEN d.os_tno END) as total_rajal"),
                DB::raw("COUNT(DISTINCT CASE WHEN a.oh_ptype = 'IN' THEN d.os_tno END) as total_ranap"),
                DB::raw("COUNT(DISTINCT CASE WHEN a.oh_ptype NOT IN ('OP', 'IN') OR a.oh_ptype IS NULL THEN d.os_tno END) as total_lainnya"),
                DB::raw("COUNT(DISTINCT d.os_tno) as total_keseluruhan")
            )
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('d.os_spl_type')
            ->groupBy('d.os_spl_type', 'e.st_name')
            ->orderBy('sample_name', 'ASC')
            ->get();

        $periodStr = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        $spreadsheet = ReportExcelService::createSpreadsheet('Laporan Penggunaan Tabung & Spesimen Laboratorium', $periodStr);
        
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Rekapitulasi Layanan');

        $sheet1->setCellValue('A6', 'No');
        $sheet1->setCellValue('B6', 'Kode Spesimen');
        $sheet1->setCellValue('C6', 'Jenis Tabung / Spesimen');
        $sheet1->setCellValue('D6', 'Rawat Jalan');
        $sheet1->setCellValue('E6', 'Rawat Inap');
        $sheet1->setCellValue('F6', 'Lainnya');
        $sheet1->setCellValue('G6', 'Total Tabung');

        $rowIdx = 7;
        $no = 1;
        $sumRajal = 0;
        $sumRanap = 0;
        $sumLainnya = 0;
        $sumTotal = 0;

        foreach ($rawData as $row) {
            $sheet1->setCellValue("A{$rowIdx}", $no++);
            $sheet1->setCellValue("B{$rowIdx}", $row->sample_code);
            $sheet1->setCellValue("C{$rowIdx}", $row->sample_name);
            $sheet1->setCellValue("D{$rowIdx}", (int)$row->total_rajal);
            $sheet1->setCellValue("E{$rowIdx}", (int)$row->total_ranap);
            $sheet1->setCellValue("F{$rowIdx}", (int)$row->total_lainnya);
            $sheet1->setCellValue("G{$rowIdx}", (int)$row->total_keseluruhan);

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
        $sheet1->setCellValue("D{$rowIdx}", $sumRajal);
        $sheet1->setCellValue("E{$rowIdx}", $sumRanap);
        $sheet1->setCellValue("F{$rowIdx}", $sumLainnya);
        $sheet1->setCellValue("G{$rowIdx}", $sumTotal);

        ReportExcelService::formatTable($sheet1, 6, $rowIdx, 'A', 'G', true);
        $sheet1->getStyle("A7:A{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle("B7:B{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet1->getStyle("D7:G{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ================= 2. RINCIAN BULANAN (SHEET 2) =================
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Rincian Bulanan');

        // Header Sheet 2
        $sheet2->setCellValue('A1', 'RUMAH SAKIT UMUM DAERAH');
        $sheet2->setCellValue('A2', 'LABORATORIUM PATOLOGI KLINIK');
        $sheet2->setCellValue('A3', 'RINCIAN PENGGUNAAN TABUNG & SPESIMEN PER BULAN & JENIS PELAYANAN');
        $sheet2->setCellValue('A4', 'Periode: ' . $periodStr . ' | Dicetak: ' . date('d/m/Y H:i:s'));
        $sheet2->getStyle('A1:A3')->getFont()->setBold(true);
        $sheet2->getStyle('A1')->getFont()->setSize(13);
        $sheet2->getStyle('A2')->getFont()->setSize(11);
        $sheet2->getStyle('A3')->getFont()->setSize(11);
        $sheet2->getStyle('A4')->getFont()->setSize(9)->setItalic(true);

        // Query Data Bulanan dengan rincian Rajal, Ranap, Lainnya
        $monthlyRaw = $oracle
            ->table('ord_hdr as a')
            ->leftJoin('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->join('ord_spl as d', function ($join) {
                $join->on('b.od_tno', '=', 'd.os_tno')
                    ->on('b.od_spl_type', '=', 'd.os_spl_type');
            })
            ->leftJoin('sample_type as e', 'd.os_spl_type', '=', 'e.st_code')
            ->select(
                'd.os_spl_type as sample_code',
                DB::raw("COALESCE(e.st_name, d.os_spl_type) as sample_name"),
                DB::raw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM') as year_month"),
                DB::raw("COUNT(DISTINCT CASE WHEN a.oh_ptype = 'OP' THEN d.os_tno END) as rajal"),
                DB::raw("COUNT(DISTINCT CASE WHEN a.oh_ptype = 'IN' THEN d.os_tno END) as ranap"),
                DB::raw("COUNT(DISTINCT CASE WHEN a.oh_ptype NOT IN ('OP', 'IN') OR a.oh_ptype IS NULL THEN d.os_tno END) as lainnya"),
                DB::raw("COUNT(DISTINCT d.os_tno) as total")
            )
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('d.os_spl_type')
            ->groupBy('d.os_spl_type', 'e.st_name', DB::raw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM')"))
            ->orderBy('sample_name', 'ASC')
            ->get();

        // Bangun daftar semua bulan dalam rentang tanggal
        $monthList = [];
        $cursor = $startDate->copy()->startOfMonth();
        $endCursor = $endDate->copy()->startOfMonth();
        while ($cursor->lte($endCursor)) {
            $monthList[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        // Susun matriks tabung per bulan & per layanan
        $tubeMatrix = [];
        foreach ($monthlyRaw as $mRow) {
            $code = $mRow->sample_code;
            if (!isset($tubeMatrix[$code])) {
                $tubeMatrix[$code] = [
                    'code' => $code,
                    'name' => $mRow->sample_name,
                    'months' => [],
                    'total_rajal' => 0,
                    'total_ranap' => 0,
                    'total_lainnya' => 0,
                    'total_all' => 0,
                ];
            }
            $r = (int)$mRow->rajal;
            $in = (int)$mRow->ranap;
            $l = (int)$mRow->lainnya;
            $tot = (int)$mRow->total;

            $tubeMatrix[$code]['months'][$mRow->year_month] = [
                'rajal' => $r,
                'ranap' => $in,
                'lainnya' => $l,
                'total' => $tot,
            ];
            $tubeMatrix[$code]['total_rajal'] += $r;
            $tubeMatrix[$code]['total_ranap'] += $in;
            $tubeMatrix[$code]['total_lainnya'] += $l;
            $tubeMatrix[$code]['total_all'] += $tot;
        }

        // Susun Header 2 Baris (Baris 6 & 7)
        $sheet2->mergeCells('A6:A7');
        $sheet2->mergeCells('B6:B7');
        $sheet2->mergeCells('C6:C7');

        $sheet2->setCellValue('A6', 'No');
        $sheet2->setCellValue('B6', 'Kode Spesimen');
        $sheet2->setCellValue('C6', 'Jenis Tabung / Spesimen');

        $colIdx = 4;
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

        foreach ($tubeMatrix as $tData) {
            $sheet2->setCellValue("A{$s2RowIdx}", $s2No++);
            $sheet2->setCellValue("B{$s2RowIdx}", $tData['code']);
            $sheet2->setCellValue("C{$s2RowIdx}", $tData['name']);

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
        $sheet2->setCellValue("B{$s2RowIdx}", 'TOTAL PENGGUNAAN');
        $sheet2->setCellValue("C{$s2RowIdx}", '');

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
        $sheet2->getStyle($headerRange)->getFont()->setBold(true)->setColor(new Color('FFFFFF'));
        $sheet2->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('2563EB');
        $sheet2->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet2->getRowDimension(6)->setRowHeight(24);
        $sheet2->getRowDimension(7)->setRowHeight(20);

        // Full borders
        $fullRange = "A6:{$gt4}{$s2RowIdx}";
        $sheet2->getStyle($fullRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CBD5E1');

        // Center Alignment for data rows
        $sheet2->getStyle("A8:A{$s2RowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle("B8:B{$s2RowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet2->getStyle("D8:{$gt4}{$s2RowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Total Row Styling
        $totalRange = "A{$s2RowIdx}:{$gt4}{$s2RowIdx}";
        $sheet2->getStyle($totalRange)->getFont()->setBold(true);
        $sheet2->getStyle($totalRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F1F5F9');
        $sheet2->getStyle($totalRange)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);

        // Auto Fit Columns
        $startColIdx = Coordinate::columnIndexFromString('A');
        $endColIdx = Coordinate::columnIndexFromString($gt4);
        for ($i = $startColIdx; $i <= $endColIdx; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex($i);
            $sheet2->getColumnDimension($colLetter)->setAutoSize(true);
        }

        // Set Sheet 1 as active by default
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'Laporan_Penggunaan_Tabung_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.xlsx';
        return ReportExcelService::streamDownload($spreadsheet, $filename);
    }
}
