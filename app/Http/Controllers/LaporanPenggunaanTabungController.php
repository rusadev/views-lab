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

            // 1 Query Terpadu Cepat (Tingkat Harian per Spesimen & Layanan)
            $rawRows = $oracle
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
                    COALESCE(e.st_name, d.os_spl_type) as sample_name,
                    COUNT(DISTINCT CASE WHEN a.oh_ptype = 'OP' THEN d.os_tno END) as rajal,
                    COUNT(DISTINCT CASE WHEN a.oh_ptype = 'IN' THEN d.os_tno END) as ranap,
                    COUNT(DISTINCT CASE WHEN a.oh_ptype NOT IN ('OP', 'IN') OR a.oh_ptype IS NULL THEN d.os_tno END) as lainnya,
                    COUNT(DISTINCT d.os_tno) as total
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

            // Inisialisasi struktur ringkasan bulanan
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

            $summaryMap = [];
            $tubeMatrix = [];
            $monthlyRawMap = [];
            $dailyDates = [];
            $dailySamplesMap = [];
            $formattedDaily = [];

            $kpiTotal = 0;
            $kpiRajal = 0;
            $kpiRanap = 0;
            $kpiLainnya = 0;

            foreach ($rawRows as $row) {
                $date = $row->trx_date;
                $ym = substr($date, 0, 7);
                $code = $row->sample_code;
                $name = $row->sample_name;
                $r = (int)$row->rajal;
                $in = (int)$row->ranap;
                $l = (int)$row->lainnya;
                $tot = (int)$row->total;

                // 1. KPI Akumulasi
                $kpiTotal += $tot;
                $kpiRajal += $r;
                $kpiRanap += $in;
                $kpiLainnya += $l;

                // 2. Summary per Sampel (untuk Rekapitulasi Layanan)
                if (!isset($summaryMap[$code])) {
                    $summaryMap[$code] = [
                        'sample_code' => $code,
                        'sample_name' => $name,
                        'total_rajal' => 0,
                        'total_ranap' => 0,
                        'total_lainnya' => 0,
                        'total_keseluruhan' => 0,
                    ];
                }
                $summaryMap[$code]['total_rajal'] += $r;
                $summaryMap[$code]['total_ranap'] += $in;
                $summaryMap[$code]['total_lainnya'] += $l;
                $summaryMap[$code]['total_keseluruhan'] += $tot;

                // 3. Matriks Bulanan Tabung
                if (!isset($tubeMatrix[$code])) {
                    $tubeMatrix[$code] = [
                        'code' => $code,
                        'name' => $name,
                        'months' => [],
                        'total_rajal' => 0,
                        'total_ranap' => 0,
                        'total_lainnya' => 0,
                        'total_all' => 0,
                    ];
                }
                if (!isset($tubeMatrix[$code]['months'][$ym])) {
                    $tubeMatrix[$code]['months'][$ym] = [
                        'rajal' => 0,
                        'ranap' => 0,
                        'lainnya' => 0,
                        'total' => 0,
                    ];
                }
                $tubeMatrix[$code]['months'][$ym]['rajal'] += $r;
                $tubeMatrix[$code]['months'][$ym]['ranap'] += $in;
                $tubeMatrix[$code]['months'][$ym]['lainnya'] += $l;
                $tubeMatrix[$code]['months'][$ym]['total'] += $tot;

                $tubeMatrix[$code]['total_rajal'] += $r;
                $tubeMatrix[$code]['total_ranap'] += $in;
                $tubeMatrix[$code]['total_lainnya'] += $l;
                $tubeMatrix[$code]['total_all'] += $tot;

                // 4. Ringkasan Tren Bulanan (Grafik)
                if (isset($monthlySummary[$ym])) {
                    $monthlySummary[$ym]['rajal'] += $r;
                    $monthlySummary[$ym]['ranap'] += $in;
                    $monthlySummary[$ym]['lainnya'] += $l;
                    $monthlySummary[$ym]['total'] += $tot;
                    if (!isset($monthlySummary[$ym]['samples'][$name])) {
                        $monthlySummary[$ym]['samples'][$name] = 0;
                    }
                    $monthlySummary[$ym]['samples'][$name] += $tot;
                }

                // 5. Monthly Raw Map (untuk kesesuaian data)
                $mRawKey = "{$code}|{$ym}";
                if (!isset($monthlyRawMap[$mRawKey])) {
                    $monthlyRawMap[$mRawKey] = (object)[
                        'sample_code' => $code,
                        'sample_name' => $name,
                        'year_month' => $ym,
                        'rajal' => 0,
                        'ranap' => 0,
                        'lainnya' => 0,
                        'total' => 0,
                    ];
                }
                $monthlyRawMap[$mRawKey]->rajal += $r;
                $monthlyRawMap[$mRawKey]->ranap += $in;
                $monthlyRawMap[$mRawKey]->lainnya += $l;
                $monthlyRawMap[$mRawKey]->total += $tot;

                // 6. Data Harian
                $dailyDates[$date] = true;
                $dailySamplesMap[$name] = true;
                if (!isset($formattedDaily[$date])) {
                    $formattedDaily[$date] = [
                        'tanggal' => $date,
                        'total' => 0,
                    ];
                }
                if (!isset($formattedDaily[$date][$name])) {
                    $formattedDaily[$date][$name] = 0;
                }
                $formattedDaily[$date][$name] += $tot;
                $formattedDaily[$date]['total'] += $tot;
            }

            // Urutkan summary & matriks berdasarkan nama tabung/spesimen
            uasort($summaryMap, fn($a, $b) => strcmp($a['sample_name'], $b['sample_name']));
            uasort($tubeMatrix, fn($a, $b) => strcmp($a['name'], $b['name']));

            // Konversi summaryMap menjadi array of objects agar kompatibel
            $summaryRaw = array_map(fn($item) => (object)$item, array_values($summaryMap));

            // Format daily samples
            $dailySamples = array_keys($dailySamplesMap);
            sort($dailySamples);
            ksort($formattedDaily);
            foreach ($formattedDaily as $dKey => &$dVal) {
                foreach ($dailySamples as $sName) {
                    if (!isset($dVal[$sName])) {
                        $dVal[$sName] = 0;
                    }
                }
            }
            unset($dVal);

            return [
                'kpi' => [
                    'total' => $kpiTotal,
                    'rajal' => $kpiRajal,
                    'ranap' => $kpiRanap,
                    'lainnya' => $kpiLainnya,
                ],
                'summary_tabung' => $summaryRaw,
                'monthly_raw' => array_values($monthlyRawMap),
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

    /**
     * Palet Warna Standar Tabung Spesimen Medis (ISO 6710 / BD Vacutainer)
     */
    private function getTubeColorInfo($name, $code): array
    {
        $n = strtolower(trim((string)$name));
        $c = trim((string)$code);

        // 1. EDTA (Ungu / Lavender / Purple - Hematologi)
        if (str_contains($n, 'edta') || $c === '30') {
            return ['bg' => '8B5CF6', 'font' => 'FFFFFF', 'label' => 'Ungu'];
        }
        // 2. Serum / Clot Activator (Merah / Red - Kimia Darah / Serologi)
        if (str_contains($n, 'serum') || $c === '10' || $c === '16' || str_contains($n, 'clot')) {
            return ['bg' => 'EF4444', 'font' => 'FFFFFF', 'label' => 'Merah'];
        }
        // 3. Sitrat / Citrate (Biru Muda / Light Blue - Koagulasi PT/APTT)
        if (str_contains($n, 'sitrat') || str_contains($n, 'citrate') || $c === '40') {
            return ['bg' => '0EA5E9', 'font' => 'FFFFFF', 'label' => 'Biru Muda'];
        }
        // 4. Arteri / Heparin (Hijau / Green - Analisa Gas Darah)
        if (str_contains($n, 'arteri') || str_contains($n, 'heparin') || $c === '73') {
            return ['bg' => '10B981', 'font' => 'FFFFFF', 'label' => 'Hijau'];
        }
        // 5. Urin / Urine (Kuning / Yellow - Wadah Urin)
        if (str_contains($n, 'urin') || str_contains($n, 'urine') || in_array($c, ['20', '28', '224'])) {
            return ['bg' => 'F59E0B', 'font' => '000000', 'label' => 'Kuning'];
        }
        // 6. Faeces / Feses (Cokelat / Brown - Wadah Faeces)
        if (str_contains($n, 'faeces') || str_contains($n, 'feses') || str_contains($n, 'stool') || $c === '85') {
            return ['bg' => '854D0E', 'font' => 'FFFFFF', 'label' => 'Cokelat'];
        }
        // 7. Cairan Tubuh / Pleura / Ascites / Sendi (Cyan / Teal - Non-blood Body Fluids)
        if (str_contains($n, 'cairan') || str_contains($n, 'c.') || in_array($c, ['69', '935', '921', '68'])) {
            return ['bg' => '06B6D4', 'font' => 'FFFFFF', 'label' => 'Cyan'];
        }
        // 8. Darah Lengkap / Whole Blood (Merah Gelap)
        if (str_contains($n, 'darah') || $c === '901') {
            return ['bg' => 'B91C1C', 'font' => 'FFFFFF', 'label' => 'Merah Tua'];
        }
        // 9. Glukosa / Fluoride (Abu-abu / Gray)
        if (str_contains($n, 'glukosa') || str_contains($n, 'fluoride') || str_contains($n, 'oxalate')) {
            return ['bg' => '6B7280', 'font' => 'FFFFFF', 'label' => 'Abu-abu'];
        }
        // 10. LED / ESR (Hitam / Black)
        if (str_contains($n, 'led') || str_contains($n, 'esr')) {
            return ['bg' => '1E293B', 'font' => 'FFFFFF', 'label' => 'Hitam'];
        }
        // 11. VTM / Swab (Pink)
        if (str_contains($n, 'vtm') || str_contains($n, 'swab')) {
            return ['bg' => 'EC4899', 'font' => 'FFFFFF', 'label' => 'Pink'];
        }

        return ['bg' => '94A3B8', 'font' => 'FFFFFF', 'label' => 'Standar'];
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

        // Query Terpadu Cepat
        $rawRows = $oracle
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
                COALESCE(e.st_name, d.os_spl_type) as sample_name,
                COUNT(DISTINCT CASE WHEN a.oh_ptype = 'OP' THEN d.os_tno END) as rajal,
                COUNT(DISTINCT CASE WHEN a.oh_ptype = 'IN' THEN d.os_tno END) as ranap,
                COUNT(DISTINCT CASE WHEN a.oh_ptype NOT IN ('OP', 'IN') OR a.oh_ptype IS NULL THEN d.os_tno END) as lainnya,
                COUNT(DISTINCT d.os_tno) as total
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
            $monthList[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        // Susun Agregasi untuk Sheet 1 (Summary) & Sheet 2 (Matriks Bulanan)
        $summaryData = [];
        $tubeMatrix = [];

        foreach ($rawRows as $row) {
            $ym = substr($row->trx_date, 0, 7);
            $code = $row->sample_code;
            $name = $row->sample_name;
            $r = (int)$row->rajal;
            $in = (int)$row->ranap;
            $l = (int)$row->lainnya;
            $tot = (int)$row->total;

            // Sheet 1 Data
            if (!isset($summaryData[$code])) {
                $summaryData[$code] = [
                    'sample_code' => $code,
                    'sample_name' => $name,
                    'total_rajal' => 0,
                    'total_ranap' => 0,
                    'total_lainnya' => 0,
                    'total_keseluruhan' => 0,
                ];
            }
            $summaryData[$code]['total_rajal'] += $r;
            $summaryData[$code]['total_ranap'] += $in;
            $summaryData[$code]['total_lainnya'] += $l;
            $summaryData[$code]['total_keseluruhan'] += $tot;

            // Sheet 2 Data
            if (!isset($tubeMatrix[$code])) {
                $tubeMatrix[$code] = [
                    'code' => $code,
                    'name' => $name,
                    'months' => [],
                    'total_rajal' => 0,
                    'total_ranap' => 0,
                    'total_lainnya' => 0,
                    'total_all' => 0,
                ];
            }
            if (!isset($tubeMatrix[$code]['months'][$ym])) {
                $tubeMatrix[$code]['months'][$ym] = [
                    'rajal' => 0,
                    'ranap' => 0,
                    'lainnya' => 0,
                    'total' => 0,
                ];
            }
            $tubeMatrix[$code]['months'][$ym]['rajal'] += $r;
            $tubeMatrix[$code]['months'][$ym]['ranap'] += $in;
            $tubeMatrix[$code]['months'][$ym]['lainnya'] += $l;
            $tubeMatrix[$code]['months'][$ym]['total'] += $tot;

            $tubeMatrix[$code]['total_rajal'] += $r;
            $tubeMatrix[$code]['total_ranap'] += $in;
            $tubeMatrix[$code]['total_lainnya'] += $l;
            $tubeMatrix[$code]['total_all'] += $tot;
        }

        uasort($summaryData, fn($a, $b) => strcmp($a['sample_name'], $b['sample_name']));
        uasort($tubeMatrix, fn($a, $b) => strcmp($a['name'], $b['name']));

        $periodStr = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        $spreadsheet = ReportExcelService::createSpreadsheet('Laporan Penggunaan Tabung & Spesimen Laboratorium', $periodStr);
        
        // ================= 1. REKAPITULASI LAYANAN (SHEET 1) =================
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Rekapitulasi Layanan');

        $sheet1->setCellValue('A6', 'No');
        $sheet1->setCellValue('B6', 'Warna Tabung');
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

        foreach ($summaryData as $row) {
            $colorInfo = $this->getTubeColorInfo($row['sample_name'], $row['sample_code']);

            $sheet1->setCellValue("A{$rowIdx}", $no++);
            $sheet1->setCellValue("B{$rowIdx}", $colorInfo['label']);
            $sheet1->setCellValue("C{$rowIdx}", $row['sample_name']);
            $sheet1->setCellValue("D{$rowIdx}", (int)$row['total_rajal']);
            $sheet1->setCellValue("E{$rowIdx}", (int)$row['total_ranap']);
            $sheet1->setCellValue("F{$rowIdx}", (int)$row['total_lainnya']);
            $sheet1->setCellValue("G{$rowIdx}", (int)$row['total_keseluruhan']);

            // Beri warna latar belakang pada sel Warna Tabung
            $sheet1->getStyle("B{$rowIdx}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($colorInfo['bg']);
            $sheet1->getStyle("B{$rowIdx}")->getFont()
                ->setBold(true)
                ->getColor()->setRGB($colorInfo['font']);

            $sumRajal += (int)$row['total_rajal'];
            $sumRanap += (int)$row['total_ranap'];
            $sumLainnya += (int)$row['total_lainnya'];
            $sumTotal += (int)$row['total_keseluruhan'];
            $rowIdx++;
        }

        // Summary row Sheet 1
        $sheet1->setCellValue("A{$rowIdx}", '');
        $sheet1->mergeCells("B{$rowIdx}:C{$rowIdx}");
        $sheet1->setCellValue("B{$rowIdx}", 'TOTAL KESELURUHAN');
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

        // Susun Header 2 Baris (Baris 6 & 7)
        $sheet2->mergeCells('A6:A7');
        $sheet2->mergeCells('B6:B7');
        $sheet2->mergeCells('C6:C7');

        $sheet2->setCellValue('A6', 'No');
        $sheet2->setCellValue('B6', 'Warna Tabung');
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
            $colorInfo = $this->getTubeColorInfo($tData['name'], $tData['code']);

            $sheet2->setCellValue("A{$s2RowIdx}", $s2No++);
            $sheet2->setCellValue("B{$s2RowIdx}", $colorInfo['label']);
            $sheet2->setCellValue("C{$s2RowIdx}", $tData['name']);

            // Beri warna latar belakang pada sel Warna Tabung
            $sheet2->getStyle("B{$s2RowIdx}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB($colorInfo['bg']);
            $sheet2->getStyle("B{$s2RowIdx}")->getFont()
                ->setBold(true)
                ->getColor()->setRGB($colorInfo['font']);

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
        $sheet2->mergeCells("B{$s2RowIdx}:C{$s2RowIdx}");
        $sheet2->setCellValue("B{$s2RowIdx}", 'TOTAL PENGGUNAAN');

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
