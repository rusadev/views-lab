<?php

namespace App\Http\Controllers;

use App\Services\ReportExcelService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanPenggunaanTabungController extends Controller
{
    public function index()
    {
        return view('laporan.penggunaan-tabung.index');
    }

    public function getData(Request $request)
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        $oracle = DB::connection('oracle');

        $tubeUsage = $oracle
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
                COALESCE(e.st_name, d.os_spl_type) as sample, 
                COUNT(DISTINCT d.os_tno) as total_usage
            ")
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('d.os_spl_type')
            ->groupByRaw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM-DD'), COALESCE(e.st_name, d.os_spl_type)")
            ->orderBy('trx_date', 'asc')
            ->get();

        $dates = $tubeUsage->pluck('trx_date')->unique()->sort()->values()->all();
        $samples = $tubeUsage->pluck('sample')->unique()->sort()->values()->all();

        $formattedData = [];
        foreach ($dates as $date) {
            $formattedData[$date] = [
                'tanggal' => $date,
                'total' => 0,
            ];
            foreach ($samples as $sample) {
                $formattedData[$date][$sample] = 0;
            }
        }

        foreach ($tubeUsage as $item) {
            $formattedData[$item->trx_date][$item->sample] = (int)$item->total_usage;
            $formattedData[$item->trx_date]['total'] += (int)$item->total_usage;
        }

        return response()->json([
            'samples' => $samples,
            'data' => array_values($formattedData),
            'total_keseluruhan' => $tubeUsage->sum('total_usage')
        ]);
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

        $tubeUsage = $oracle
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
                COALESCE(e.st_name, d.os_spl_type) as sample, 
                COUNT(DISTINCT d.os_tno) as total_usage
            ")
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('d.os_spl_type')
            ->groupByRaw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM-DD'), COALESCE(e.st_name, d.os_spl_type)")
            ->orderBy('trx_date', 'asc')
            ->get();

        $dates = $tubeUsage->pluck('trx_date')->unique()->sort()->values()->all();
        $samples = $tubeUsage->pluck('sample')->unique()->sort()->values()->all();

        $periodStr = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        $spreadsheet = ReportExcelService::createSpreadsheet('Laporan Penggunaan Tabung & Spesimen Laboratorium', $periodStr);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Penggunaan Tabung');

        $sheet->setCellValue('A6', 'No');
        $sheet->setCellValue('B6', 'Tanggal');

        $colIdx = 3;
        foreach ($samples as $sample) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue("{$colLetter}6", $sample);
            $colIdx++;
        }
        $totCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue("{$totCol}6", 'Total Tabung');

        $formattedData = [];
        foreach ($dates as $date) {
            $formattedData[$date] = [
                'tanggal' => $date,
                'total' => 0,
            ];
            foreach ($samples as $sample) {
                $formattedData[$date][$sample] = 0;
            }
        }

        foreach ($tubeUsage as $item) {
            $formattedData[$item->trx_date][$item->sample] = (int)$item->total_usage;
            $formattedData[$item->trx_date]['total'] += (int)$item->total_usage;
        }

        $rowIdx = 7;
        $no = 1;
        $sampleSums = array_fill_keys($samples, 0);
        $grandTotal = 0;

        foreach ($formattedData as $date => $row) {
            $sheet->setCellValue("A{$rowIdx}", $no++);
            $sheet->setCellValue("B{$rowIdx}", date('d/m/Y', strtotime($date)));

            $c = 3;
            foreach ($samples as $sample) {
                $cLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $val = $row[$sample] ?? 0;
                $sheet->setCellValue("{$cLetter}{$rowIdx}", $val);
                $sampleSums[$sample] += $val;
                $c++;
            }

            $sheet->setCellValue("{$totCol}{$rowIdx}", $row['total']);
            $grandTotal += $row['total'];
            $rowIdx++;
        }

        // Summary Row
        $sheet->setCellValue("A{$rowIdx}", '');
        $sheet->setCellValue("B{$rowIdx}", 'TOTAL PENGGUNAAN');
        $c = 3;
        foreach ($samples as $sample) {
            $cLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sheet->setCellValue("{$cLetter}{$rowIdx}", $sampleSums[$sample]);
            $c++;
        }
        $sheet->setCellValue("{$totCol}{$rowIdx}", $grandTotal);

        ReportExcelService::formatTable($sheet, 6, $rowIdx, 'A', $totCol, true);

        $filename = 'Laporan_Penggunaan_Tabung_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.xlsx';
        return ReportExcelService::streamDownload($spreadsheet, $filename);
    }
}
