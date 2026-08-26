<?php

namespace App\Http\Controllers;

use App\Services\ReportExcelService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanJumlahPemeriksaanController extends Controller
{
    public function index()
    {
        return view('laporan.jumlah-pemeriksaan.index');
    }

    public function getData(Request $request)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

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
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

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
                'b.od_test_grp as test_group_code',
                DB::raw("COALESCE(c.tg_name, b.od_test_grp, 'Lain-lain') as test_group_name"),
                'b.od_testcode as test_code',
                DB::raw("COALESCE(d.ti_name, b.od_testcode, 'Pemeriksaan') as test_name"),
                DB::raw("COUNT(CASE WHEN a.oh_ptype = 'OP' THEN 1 END) as total_rajal"),
                DB::raw("COUNT(CASE WHEN a.oh_ptype = 'IN' THEN 1 END) as total_ranap"),
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
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Jumlah Pemeriksaan');

        $sheet->setCellValue('A6', 'No');
        $sheet->setCellValue('B6', 'Kelompok Pemeriksaan');
        $sheet->setCellValue('C6', 'Kode Uji');
        $sheet->setCellValue('D6', 'Nama Pemeriksaan');
        $sheet->setCellValue('E6', 'Rawat Jalan');
        $sheet->setCellValue('F6', 'Rawat Inap');
        $sheet->setCellValue('G6', 'Total Jumlah');

        $rowIdx = 7;
        $no = 1;
        $sumRajal = 0;
        $sumRanap = 0;
        $sumTotal = 0;

        foreach ($rawData as $row) {
            $sheet->setCellValue("A{$rowIdx}", $no++);
            $sheet->setCellValue("B{$rowIdx}", $row->test_group_name);
            $sheet->setCellValue("C{$rowIdx}", $row->test_code);
            $sheet->setCellValue("D{$rowIdx}", $row->test_name);
            $sheet->setCellValue("E{$rowIdx}", (int)$row->total_rajal);
            $sheet->setCellValue("F{$rowIdx}", (int)$row->total_ranap);
            $sheet->setCellValue("G{$rowIdx}", (int)$row->total_keseluruhan);

            $sumRajal += (int)$row->total_rajal;
            $sumRanap += (int)$row->total_ranap;
            $sumTotal += (int)$row->total_keseluruhan;
            $rowIdx++;
        }

        // Summary row
        $sheet->setCellValue("A{$rowIdx}", '');
        $sheet->setCellValue("B{$rowIdx}", 'TOTAL KESELURUHAN');
        $sheet->setCellValue("C{$rowIdx}", '');
        $sheet->setCellValue("D{$rowIdx}", '');
        $sheet->setCellValue("E{$rowIdx}", $sumRajal);
        $sheet->setCellValue("F{$rowIdx}", $sumRanap);
        $sheet->setCellValue("G{$rowIdx}", $sumTotal);

        ReportExcelService::formatTable($sheet, 6, $rowIdx, 'A', 'G', true);

        $filename = 'Laporan_Jumlah_Pemeriksaan_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.xlsx';
        return ReportExcelService::streamDownload($spreadsheet, $filename);
    }
}
