<?php

namespace App\Http\Controllers;

use App\Services\ReportExcelService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LaporanTAT extends Controller
{
    public function index()
    {
        return view('laporan.tat.index');
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
        $cacheKey = 'laporan_tat_kemenkes_v3_' . md5($startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d'));

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        // Dynamic Cache TTL:
        // - Data masa lalu (tahun sebelumnya): cache 24 jam (86400 detik)
        // - Rentang data > 90 hari (skala tahunan): cache 2 jam (7200 detik)
        // - Rentang harian / bulanan: cache 15 menit (900 detik)
        $diffDays = $startDate->diffInDays($endDate);
        if ($endDate->lt(Carbon::now()->startOfYear())) {
            $ttl = 86400; // 24 jam
        } elseif ($diffDays >= 90) {
            $ttl = 7200;  // 2 jam
        } else {
            $ttl = 900;   // 15 menit
        }

        $result = Cache::remember($cacheKey, $ttl, function () use ($startDate, $endDate) {
            $oracle = DB::connection('oracle');

            // Standar Indikator Nasional Mutu (INM) Kemenkes RI:
            // - CITO (UGD / Cepat / Urgent): <= 60 Menit
            // - Rutin / Non-CITO: <= 120 Menit
            // Query Agregasi Cepat (Group by minimal kolom tanpa overhead join teks test_item pada ratusan ribu baris)
            $raw = $oracle
                ->table('ord_hdr as a')
                ->join('ord_dtl as b', function ($join) {
                    $join->on('a.oh_tno', '=', 'b.od_tno')
                        ->where('b.od_order_item', '=', 'Y');
                })
                ->join('ord_spl as c', function ($join) {
                    $join->on('b.od_tno', '=', 'c.os_tno')
                        ->on('b.od_spl_type', '=', 'c.os_spl_type');
                })
                ->selectRaw("
                    a.oh_pri as priority,
                    b.od_testcode as code,
                    COUNT(CASE WHEN a.oh_ptype = 'OP' THEN 1 END) as rajal_count,
                    ROUND(AVG(CASE WHEN a.oh_ptype = 'OP' THEN (b.od_validate_on - c.os_spl_rcvdt) * 1440 END), 0) as rajal_avg_mins,
                    COUNT(CASE WHEN a.oh_ptype = 'IN' THEN 1 END) as ranap_count,
                    ROUND(AVG(CASE WHEN a.oh_ptype = 'IN' THEN (b.od_validate_on - c.os_spl_rcvdt) * 1440 END), 0) as ranap_avg_mins,
                    COUNT(CASE WHEN a.oh_ptype NOT IN ('OP', 'IN') OR a.oh_ptype IS NULL THEN 1 END) as lainnya_count,
                    ROUND(AVG(CASE WHEN a.oh_ptype NOT IN ('OP', 'IN') OR a.oh_ptype IS NULL THEN (b.od_validate_on - c.os_spl_rcvdt) * 1440 END), 0) as lainnya_avg_mins,
                    COUNT(*) as total_count,
                    ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440), 0) as overall_avg_mins,
                    COUNT(CASE 
                        WHEN a.oh_pri IN ('U','C','S') AND (b.od_validate_on - c.os_spl_rcvdt) * 1440 <= 60 THEN 1 
                        WHEN a.oh_pri NOT IN ('U','C','S') AND (b.od_validate_on - c.os_spl_rcvdt) * 1440 <= 120 THEN 1 
                    END) as on_time_count
                ")
                ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
                ->whereNotNull('b.od_validate_on')
                ->whereNotNull('c.os_spl_rcvdt')
                ->groupBy('a.oh_pri', 'b.od_testcode')
                ->get();

            // Ambil mapping nama tes (test_item) sekaligus dari database
            $testCodes = $raw->pluck('code')->unique()->filter()->values()->all();
            $testNames = [];
            if (!empty($testCodes)) {
                $testNames = $oracle->table('test_item')
                    ->whereIn('ti_code', $testCodes)
                    ->pluck('ti_name', 'ti_code')
                    ->toArray();
            }

            $formatTime = function ($mins) {
                if (!$mins || $mins <= 0) return '-';
                $mins = (int)$mins;
                $hours = floor($mins / 60);
                $rem = $mins % 60;
                if ($hours > 0) {
                    return "{$hours} jam {$rem} m";
                }
                return "{$mins} m";
            };

            $citoList = [];
            $nonCitoList = [];

            $citoTotalTests = 0;
            $citoOnTimeTests = 0;
            $citoWeightedMins = 0;

            $nonCitoTotalTests = 0;
            $nonCitoOnTimeTests = 0;
            $nonCitoWeightedMins = 0;

            foreach ($raw as $item) {
                $tot = (int)$item->total_count;
                $onTime = (int)$item->on_time_count;
                $slaPct = $tot > 0 ? round(($onTime / $tot) * 100, 1) : 0;
                $overallMins = (int)$item->overall_avg_mins;
                $testName = $testNames[$item->code] ?? $item->code;

                $formatted = [
                    'code' => $item->code,
                    'name' => $testName,
                    'rajal_count' => (int)$item->rajal_count,
                    'rajal_mins' => (int)$item->rajal_avg_mins,
                    'rajal_tat_formatted' => $formatTime($item->rajal_avg_mins),
                    'ranap_count' => (int)$item->ranap_count,
                    'ranap_mins' => (int)$item->ranap_avg_mins,
                    'ranap_tat_formatted' => $formatTime($item->ranap_avg_mins),
                    'lainnya_count' => (int)$item->lainnya_count,
                    'lainnya_mins' => (int)$item->lainnya_avg_mins,
                    'lainnya_tat_formatted' => $formatTime($item->lainnya_avg_mins),
                    'total_count' => $tot,
                    'overall_mins' => $overallMins,
                    'overall_tat_formatted' => $formatTime($overallMins),
                    'on_time_count' => $onTime,
                    'sla_percent' => $slaPct,
                ];

                if (in_array(strtoupper($item->priority), ['U', 'C', 'S'])) {
                    $citoList[] = $formatted;
                    $citoTotalTests += $tot;
                    $citoOnTimeTests += $onTime;
                    $citoWeightedMins += ($overallMins * $tot);
                } else {
                    $nonCitoList[] = $formatted;
                    $nonCitoTotalTests += $tot;
                    $nonCitoOnTimeTests += $onTime;
                    $nonCitoWeightedMins += ($overallMins * $tot);
                }
            }

            // Urutkan berdasarkan nama pemeriksaan alfabetis
            usort($citoList, fn($a, $b) => strcmp($a['name'], $b['name']));
            usort($nonCitoList, fn($a, $b) => strcmp($a['name'], $b['name']));

            $avgCitoMins = $citoTotalTests > 0 ? round($citoWeightedMins / $citoTotalTests) : 0;
            $avgNonCitoMins = $nonCitoTotalTests > 0 ? round($nonCitoWeightedMins / $nonCitoTotalTests) : 0;

            $citoSlaOverall = $citoTotalTests > 0 ? round(($citoOnTimeTests / $citoTotalTests) * 100, 1) : 0;
            $nonCitoSlaOverall = $nonCitoTotalTests > 0 ? round(($nonCitoOnTimeTests / $nonCitoTotalTests) * 100, 1) : 0;

            return [
                'cito' => $citoList,
                'non_cito' => $nonCitoList,
                'summary' => [
                    'cito_total' => $citoTotalTests,
                    'cito_avg_tat' => $formatTime($avgCitoMins),
                    'cito_sla_percent' => $citoSlaOverall,
                    'non_cito_total' => $nonCitoTotalTests,
                    'non_cito_avg_tat' => $formatTime($avgNonCitoMins),
                    'non_cito_sla_percent' => $nonCitoSlaOverall,
                ],
                'cached_at' => now()->format('d/m/Y H:i:s'),
            ];
        });

        return response()->json($result);
    }

    public function exportToExcel(Request $request)
    {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');

        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        $oracle = DB::connection('oracle');

        // 1. Agregasi Rekap CITO & Non-CITO
        $raw = $oracle
            ->table('ord_hdr as a')
            ->join('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->join('ord_spl as c', function ($join) {
                $join->on('b.od_tno', '=', 'c.os_tno')
                    ->on('b.od_spl_type', '=', 'c.os_spl_type');
            })
            ->selectRaw("
                a.oh_pri as priority,
                b.od_testcode as code,
                COUNT(CASE WHEN a.oh_ptype = 'OP' THEN 1 END) as rajal_count,
                ROUND(AVG(CASE WHEN a.oh_ptype = 'OP' THEN (b.od_validate_on - c.os_spl_rcvdt) * 1440 END), 0) as rajal_avg_mins,
                COUNT(CASE WHEN a.oh_ptype = 'IN' THEN 1 END) as ranap_count,
                ROUND(AVG(CASE WHEN a.oh_ptype = 'IN' THEN (b.od_validate_on - c.os_spl_rcvdt) * 1440 END), 0) as ranap_avg_mins,
                COUNT(CASE WHEN a.oh_ptype NOT IN ('OP', 'IN') OR a.oh_ptype IS NULL THEN 1 END) as lainnya_count,
                ROUND(AVG(CASE WHEN a.oh_ptype NOT IN ('OP', 'IN') OR a.oh_ptype IS NULL THEN (b.od_validate_on - c.os_spl_rcvdt) * 1440 END), 0) as lainnya_avg_mins,
                COUNT(*) as total_count,
                ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440), 0) as overall_avg_mins,
                COUNT(CASE 
                    WHEN a.oh_pri IN ('U','C','S') AND (b.od_validate_on - c.os_spl_rcvdt) * 1440 <= 60 THEN 1 
                    WHEN a.oh_pri NOT IN ('U','C','S') AND (b.od_validate_on - c.os_spl_rcvdt) * 1440 <= 120 THEN 1 
                END) as on_time_count
            ")
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('b.od_validate_on')
            ->whereNotNull('c.os_spl_rcvdt')
            ->groupBy('a.oh_pri', 'b.od_testcode')
            ->get();

        $testCodes = $raw->pluck('code')->unique()->filter()->values()->all();
        $testNames = [];
        if (!empty($testCodes)) {
            $testNames = $oracle->table('test_item')
                ->whereIn('ti_code', $testCodes)
                ->pluck('ti_name', 'ti_code')
                ->toArray();
        }

        foreach ($raw as $item) {
            $item->name = $testNames[$item->code] ?? $item->code;
        }

        $periodStr = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        $spreadsheet = ReportExcelService::createSpreadsheet('Laporan Turn Around Time (TAT) Laboratorium', $periodStr);

        $populateSheet = function ($sheet, $title, $items, $slaTargetMins) {
            $sheet->setTitle(substr($title, 0, 30));

            $sheet->setCellValue('A6', 'No');
            $sheet->setCellValue('B6', 'Kode');
            $sheet->setCellValue('C6', 'Nama Pemeriksaan');
            $sheet->setCellValue('D6', 'TAT Rajal (Menit)');
            $sheet->setCellValue('E6', 'Total Rajal');
            $sheet->setCellValue('F6', 'TAT Ranap (Menit)');
            $sheet->setCellValue('G6', 'Total Ranap');
            $sheet->setCellValue('H6', 'TAT Lainnya (Menit)');
            $sheet->setCellValue('I6', 'Total Lainnya');
            $sheet->setCellValue('J6', 'Rata-rata TAT (Menit)');
            $sheet->setCellValue('K6', 'Total Keseluruhan');
            $sheet->setCellValue('L6', 'Kepatuhan Standar Kemenkes (≤ ' . $slaTargetMins . 'm)');

            $rowIdx = 7;
            $no = 1;
            $totRajal = 0;
            $totRanap = 0;
            $totLainnya = 0;
            $totGrand = 0;
            $totOnTime = 0;

            // Sort items by name
            $sortedItems = $items->sortBy('name');

            foreach ($sortedItems as $item) {
                $tot = (int)$item->total_count;
                $onTime = (int)$item->on_time_count;
                $slaPct = $tot > 0 ? round(($onTime / $tot) * 100, 1) . '%' : '0%';

                $sheet->setCellValue("A{$rowIdx}", $no++);
                $sheet->setCellValue("B{$rowIdx}", $item->code);
                $sheet->setCellValue("C{$rowIdx}", $item->name);
                $sheet->setCellValue("D{$rowIdx}", (int)$item->rajal_avg_mins ?: '-');
                $sheet->setCellValue("E{$rowIdx}", (int)$item->rajal_count);
                $sheet->setCellValue("F{$rowIdx}", (int)$item->ranap_avg_mins ?: '-');
                $sheet->setCellValue("G{$rowIdx}", (int)$item->ranap_count);
                $sheet->setCellValue("H{$rowIdx}", (int)$item->lainnya_avg_mins ?: '-');
                $sheet->setCellValue("I{$rowIdx}", (int)$item->lainnya_count);
                $sheet->setCellValue("J{$rowIdx}", (int)$item->overall_avg_mins);
                $sheet->setCellValue("K{$rowIdx}", $tot);
                $sheet->setCellValue("L{$rowIdx}", $slaPct);

                $totRajal += (int)$item->rajal_count;
                $totRanap += (int)$item->ranap_count;
                $totLainnya += (int)$item->lainnya_count;
                $totGrand += $tot;
                $totOnTime += $onTime;
                $rowIdx++;
            }

            // Summary Row
            $overallSlaPct = $totGrand > 0 ? round(($totOnTime / $totGrand) * 100, 1) . '%' : '0%';
            $sheet->setCellValue("A{$rowIdx}", '');
            $sheet->setCellValue("B{$rowIdx}", 'TOTAL KESELURUHAN');
            $sheet->setCellValue("C{$rowIdx}", '');
            $sheet->setCellValue("D{$rowIdx}", '');
            $sheet->setCellValue("E{$rowIdx}", $totRajal);
            $sheet->setCellValue("F{$rowIdx}", '');
            $sheet->setCellValue("G{$rowIdx}", $totRanap);
            $sheet->setCellValue("H{$rowIdx}", '');
            $sheet->setCellValue("I{$rowIdx}", $totLainnya);
            $sheet->setCellValue("J{$rowIdx}", '');
            $sheet->setCellValue("K{$rowIdx}", $totGrand);
            $sheet->setCellValue("L{$rowIdx}", $overallSlaPct);

            ReportExcelService::formatTable($sheet, 6, $rowIdx, 'A', 'L', true);
        };

        // Tab 1: CITO (Standar Kemenkes <= 60m)
        $citoItems = $raw->filter(fn($i) => in_array(strtoupper($i->priority), ['U', 'C', 'S']));
        $sheet1 = $spreadsheet->getActiveSheet();
        $populateSheet($sheet1, 'TAT CITO (Kemenkes 60m)', $citoItems, 60);

        // Tab 2: NON-CITO (Standar Kemenkes <= 120m)
        $nonCitoItems = $raw->filter(fn($i) => !in_array(strtoupper($i->priority), ['U', 'C', 'S']));
        $sheet2 = $spreadsheet->createSheet();
        
        $sheet2->setCellValue('A1', 'RUMAH SAKIT UMUM DAERAH');
        $sheet2->setCellValue('A2', 'LABORATORIUM PATOLOGI KLINIK');
        $sheet2->setCellValue('A3', 'LAPORAN TURN AROUND TIME (TAT) NON-CITO (STANDAR KEMENKES ≤ 120 MENIT)');
        $sheet2->setCellValue('A4', 'Periode: ' . $periodStr);
        $sheet2->getStyle('A1:A3')->getFont()->setBold(true);

        $populateSheet($sheet2, 'TAT Non-CITO (Kemenkes 120m)', $nonCitoItems, 120);

        // Tab 3: Daftar Transaksi Detail Pemeriksaan Pasien (Order, Check-in, Validasi)
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Daftar Pasien & Check-in');

        $sheet3->setCellValue('A1', 'RUMAH SAKIT UMUM DAERAH');
        $sheet3->setCellValue('A2', 'LABORATORIUM PATOLOGI KLINIK');
        $sheet3->setCellValue('A3', 'DAFTAR TRANSAKSI PEMERIKSAAN PASIEN (ORDER, SPESIMEN CHECK-IN, VALIDASI)');
        $sheet3->setCellValue('A4', 'Periode: ' . $periodStr . ' | Standar Kemenkes: CITO ≤ 60m, Non-CITO ≤ 120m');
        $sheet3->getStyle('A1:A3')->getFont()->setBold(true);

        $sheet3->setCellValue('A6', 'No');
        $sheet3->setCellValue('B6', 'Tanggal');
        $sheet3->setCellValue('C6', 'No. Order / Lab No');
        $sheet3->setCellValue('D6', 'No. RM');
        $sheet3->setCellValue('E6', 'Nama Pasien');
        $sheet3->setCellValue('F6', 'Jenis Layanan');
        $sheet3->setCellValue('G6', 'Prioritas');
        $sheet3->setCellValue('H6', 'Ruangan / Poliklinik');
        $sheet3->setCellValue('I6', 'Nama Pemeriksaan');
        $sheet3->setCellValue('J6', 'Waktu Order');
        $sheet3->setCellValue('K6', 'Waktu Check-In Spesimen');
        $sheet3->setCellValue('L6', 'Waktu Validasi');
        $sheet3->setCellValue('M6', 'TAT (Menit)');
        $sheet3->setCellValue('N6', 'Target Kemenkes');
        $sheet3->setCellValue('O6', 'Status Kepatuhan Kemenkes');
        $sheet3->setCellValue('P6', 'Validator');

        // Query Detail Pasien (Maksimal 5000 baris untuk kenyamanan performa Excel)
        $detailPasien = $oracle
            ->table('ord_hdr as a')
            ->join('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->join('ord_spl as c', function ($join) {
                $join->on('b.od_tno', '=', 'c.os_tno')
                    ->on('b.od_spl_type', '=', 'c.os_spl_type');
            })
            ->leftJoin('test_item as e', 'b.od_testcode', '=', 'e.ti_code')
            ->leftJoin('hfclinic as f', 'a.oh_clinic_code', '=', 'f.clinic_code')
            ->select([
                'a.oh_trx_dt as tgl_order',
                'a.oh_tno as no_order',
                'a.oh_pid as no_rm',
                'a.oh_last_name as nama_pasien',
                'a.oh_ptype as jenis_rawat',
                'a.oh_pri as prioritas',
                'f.clinic_desc as ruangan',
                DB::raw('COALESCE(e.ti_name, b.od_testcode) as nama_uji'),
                'c.os_spl_rcvdt as waktu_checkin',
                'b.od_validate_on as waktu_validasi',
                'b.od_validate_by as validator',
                DB::raw('ROUND((b.od_validate_on - c.os_spl_rcvdt) * 1440, 0) as tat_menit')
            ])
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('b.od_validate_on')
            ->whereNotNull('c.os_spl_rcvdt')
            ->orderBy('a.oh_trx_dt', 'asc')
            ->take(5000)
            ->get();

        $rIdx3 = 7;
        $no3 = 1;

        foreach ($detailPasien as $p) {
            $isCito = in_array(strtoupper($p->prioritas), ['U', 'C', 'S']);
            $targetMins = $isCito ? 60 : 120;
            $tatMins = (int)$p->tat_menit;
            $isCompliant = $tatMins <= $targetMins;
            $jenisRawatStr = $p->jenis_rawat === 'IN' ? 'Rawat Inap' : ($p->jenis_rawat === 'OP' ? 'Rawat Jalan' : 'Lainnya');

            $sheet3->setCellValue("A{$rIdx3}", $no3++);
            $sheet3->setCellValue("B{$rIdx3}", $p->tgl_order ? date('d/m/Y', strtotime($p->tgl_order)) : '-');
            $sheet3->setCellValue("C{$rIdx3}", $p->no_order);
            $sheet3->setCellValue("D{$rIdx3}", $p->no_rm);
            $sheet3->setCellValue("E{$rIdx3}", $p->nama_pasien);
            $sheet3->setCellValue("F{$rIdx3}", $jenisRawatStr);
            $sheet3->setCellValue("G{$rIdx3}", $isCito ? 'CITO' : 'Rutin');
            $sheet3->setCellValue("H{$rIdx3}", $p->ruangan ?: '-');
            $sheet3->setCellValue("I{$rIdx3}", $p->nama_uji);
            $sheet3->setCellValue("J{$rIdx3}", $p->tgl_order ? date('d/m/Y H:i:s', strtotime($p->tgl_order)) : '-');
            $sheet3->setCellValue("K{$rIdx3}", $p->waktu_checkin ? date('d/m/Y H:i:s', strtotime($p->waktu_checkin)) : '-');
            $sheet3->setCellValue("L{$rIdx3}", $p->waktu_validasi ? date('d/m/Y H:i:s', strtotime($p->waktu_validasi)) : '-');
            $sheet3->setCellValue("M{$rIdx3}", $tatMins);
            $sheet3->setCellValue("N{$rIdx3}", "≤ {$targetMins} Menit");
            $sheet3->setCellValue("O{$rIdx3}", $isCompliant ? 'Tepat Waktu (Memenuhi Standar)' : 'Melewati Standar Kemenkes');
            $sheet3->setCellValue("P{$rIdx3}", $p->validator ?: '-');

            $rIdx3++;
        }

        ReportExcelService::formatTable($sheet3, 6, $rIdx3 - 1, 'A', 'P', false);

        $filename = 'Laporan_TAT_Kemenkes_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.xlsx';
        return ReportExcelService::streamDownload($spreadsheet, $filename);
    }
}
