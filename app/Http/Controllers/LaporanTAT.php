<?php

namespace App\Http\Controllers;

use App\Services\ReportExcelService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LaporanTAT extends Controller
{
    public const DEFAULT_CITO_SLA_MINS = 120;     // 2 Jam (Request dr. Ika)
    public const DEFAULT_NON_CITO_SLA_MINS = 240; // 4 Jam (Request dr. Ika)

    /**
     * Ambil konfigurasi standar SLA TAT aktif (tersimpan di storage atau fallback default)
     */
    public function getSettings(): array
    {
        $path = storage_path('app/tat_settings.json');
        $data = [];

        if (file_exists($path)) {
            $decoded = json_decode(@file_get_contents($path), true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        $citoMins = isset($data['cito_sla_mins']) && (int) $data['cito_sla_mins'] >= 15
            ? (int) $data['cito_sla_mins']
            : self::DEFAULT_CITO_SLA_MINS;

        $nonCitoMins = isset($data['non_cito_sla_mins']) && (int) $data['non_cito_sla_mins'] >= 15
            ? (int) $data['non_cito_sla_mins']
            : self::DEFAULT_NON_CITO_SLA_MINS;

        return [
            'cito_sla_mins' => $citoMins,
            'non_cito_sla_mins' => $nonCitoMins,
            'cito_sla_label' => $this->formatSlaLabel($citoMins, false),
            'cito_sla_short' => $this->formatSlaLabel($citoMins, true),
            'non_cito_sla_label' => $this->formatSlaLabel($nonCitoMins, false),
            'non_cito_sla_short' => $this->formatSlaLabel($nonCitoMins, true),
            'updated_at' => $data['updated_at'] ?? null,
        ];
    }

    /**
     * Simpan perubahan standar target TAT secara permanen
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'cito_sla_mins' => 'required|integer|min:15|max:1440',
            'non_cito_sla_mins' => 'required|integer|min:15|max:2880',
        ]);

        $payload = [
            'cito_sla_mins' => (int) $validated['cito_sla_mins'],
            'non_cito_sla_mins' => (int) $validated['non_cito_sla_mins'],
            'updated_at' => now()->format('d/m/Y H:i:s'),
        ];

        $path = storage_path('app/tat_settings.json');
        @file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT));

        return response()->json([
            'status' => 'success',
            'message' => 'Standar target TAT berhasil disimpan sebagai pengaturan default.',
            'settings' => $this->getSettings(),
        ]);
    }

    public function index()
    {
        $settings = $this->getSettings();
        return view('laporan.tat.index', compact('settings'));
    }

    /**
     * Format durasi menit menjadi teks jam & menit yang mudah dibaca
     */
    private function formatTime($mins): string
    {
        if ($mins === null || $mins === '' || (int) $mins <= 0) {
            return '-';
        }
        $mins = (int) round($mins);
        $hours = intdiv($mins, 60);
        $rem = $mins % 60;

        if ($hours > 0 && $rem > 0) {
            return "{$hours} jam {$rem} m";
        }
        if ($hours > 0) {
            return "{$hours} jam";
        }
        return "{$mins} m";
    }

    /**
     * Format label target SLA (contoh: "2 Jam (120 Menit)" atau "2 Jam (120m)")
     */
    private function formatSlaLabel(int $mins, bool $short = false): string
    {
        $hours = intdiv($mins, 60);
        $rem = $mins % 60;
        $unit = $short ? 'm' : ' Menit';

        if ($hours > 0 && $rem === 0) {
            return "{$hours} Jam ({$mins}{$unit})";
        }
        if ($hours > 0) {
            return "{$hours} Jam {$rem}m ({$mins}{$unit})";
        }
        return "{$mins}{$unit}";
    }

    /**
     * Query & agregasi utama laporan TAT (dishare antara web JSON dan Export Excel)
     */
    private function buildReportData(
        Carbon $startDate,
        Carbon $endDate,
        int $citoSlaMins,
        int $nonCitoSlaMins,
        bool $forceRefresh = false
    ): array {
        $cacheKey = sprintf(
            'laporan_tat_v4_%s_%d_%d',
            md5($startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d')),
            $citoSlaMins,
            $nonCitoSlaMins
        );

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        // Dynamic Cache TTL
        $diffDays = $startDate->diffInDays($endDate);
        if ($endDate->lt(Carbon::now()->startOfYear())) {
            $ttl = 86400; // 24 jam untuk tahun lalu
        } elseif ($diffDays >= 90) {
            $ttl = 7200;  // 2 jam untuk rentang >= 3 bulan
        } else {
            $ttl = 900;   // 15 menit untuk harian/bulanan
        }

        return Cache::remember($cacheKey, $ttl, function () use ($startDate, $endDate, $citoSlaMins, $nonCitoSlaMins) {
            $oracle = DB::connection('oracle');

            // Kategorisasi prioritas langsung di level SQL:
            // Menghindari bug SQL `NULL NOT IN ('U','C','S')` dan mencegah duplikasi baris per kode pemeriksaan
            $priorityGroupExpr = "CASE WHEN TRIM(UPPER(a.oh_pri)) IN ('U','C','S') THEN 'CITO' ELSE 'NON_CITO' END";
            $tatMinutesExpr = "((b.od_validate_on - c.os_spl_rcvdt) * 1440)";

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
                    {$priorityGroupExpr} as priority_group,
                    b.od_testcode as code,
                    COUNT(CASE WHEN a.oh_ptype = 'OP' THEN 1 END) as rajal_count,
                    ROUND(AVG(CASE WHEN a.oh_ptype = 'OP' THEN {$tatMinutesExpr} END), 0) as rajal_avg_mins,
                    COUNT(CASE WHEN a.oh_ptype = 'IN' THEN 1 END) as ranap_count,
                    ROUND(AVG(CASE WHEN a.oh_ptype = 'IN' THEN {$tatMinutesExpr} END), 0) as ranap_avg_mins,
                    COUNT(CASE WHEN a.oh_ptype NOT IN ('OP', 'IN') OR a.oh_ptype IS NULL THEN 1 END) as lainnya_count,
                    ROUND(AVG(CASE WHEN a.oh_ptype NOT IN ('OP', 'IN') OR a.oh_ptype IS NULL THEN {$tatMinutesExpr} END), 0) as lainnya_avg_mins,
                    COUNT(*) as total_count,
                    ROUND(AVG({$tatMinutesExpr}), 0) as overall_avg_mins,
                    COUNT(CASE 
                        WHEN TRIM(UPPER(a.oh_pri)) IN ('U','C','S') AND {$tatMinutesExpr} <= {$citoSlaMins} THEN 1 
                        WHEN (TRIM(UPPER(a.oh_pri)) NOT IN ('U','C','S') OR a.oh_pri IS NULL) AND {$tatMinutesExpr} <= {$nonCitoSlaMins} THEN 1 
                    END) as on_time_count
                ")
                ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
                ->whereNotNull('b.od_validate_on')
                ->whereNotNull('c.os_spl_rcvdt')
                ->whereRaw('b.od_validate_on >= c.os_spl_rcvdt')
                ->groupByRaw("{$priorityGroupExpr}, b.od_testcode")
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

            $citoList = [];
            $nonCitoList = [];

            $stats = [
                'CITO' => [
                    'total' => 0,
                    'on_time' => 0,
                    'rajal_count' => 0,
                    'rajal_weighted' => 0,
                    'ranap_count' => 0,
                    'ranap_weighted' => 0,
                    'lainnya_count' => 0,
                    'lainnya_weighted' => 0,
                    'overall_weighted' => 0,
                ],
                'NON_CITO' => [
                    'total' => 0,
                    'on_time' => 0,
                    'rajal_count' => 0,
                    'rajal_weighted' => 0,
                    'ranap_count' => 0,
                    'ranap_weighted' => 0,
                    'lainnya_count' => 0,
                    'lainnya_weighted' => 0,
                    'overall_weighted' => 0,
                ],
            ];

            foreach ($raw as $item) {
                $group = $item->priority_group === 'CITO' ? 'CITO' : 'NON_CITO';
                $targetMins = $group === 'CITO' ? $citoSlaMins : $nonCitoSlaMins;

                $tot = (int) $item->total_count;
                $onTime = (int) $item->on_time_count;
                $overSla = max(0, $tot - $onTime);
                $slaPct = $tot > 0 ? round(($onTime / $tot) * 100, 1) : 0;

                $rajalCount = (int) $item->rajal_count;
                $rajalMins = (int) $item->rajal_avg_mins;
                $ranapCount = (int) $item->ranap_count;
                $ranapMins = (int) $item->ranap_avg_mins;
                $lainnyaCount = (int) $item->lainnya_count;
                $lainnyaMins = (int) $item->lainnya_avg_mins;
                $overallMins = (int) $item->overall_avg_mins;

                $testName = $testNames[$item->code] ?? $item->code;

                $formatted = [
                    'code' => $item->code,
                    'name' => $testName,
                    'rajal_count' => $rajalCount,
                    'rajal_mins' => $rajalMins,
                    'rajal_tat_formatted' => $this->formatTime($rajalMins),
                    'ranap_count' => $ranapCount,
                    'ranap_mins' => $ranapMins,
                    'ranap_tat_formatted' => $this->formatTime($ranapMins),
                    'lainnya_count' => $lainnyaCount,
                    'lainnya_mins' => $lainnyaMins,
                    'lainnya_tat_formatted' => $this->formatTime($lainnyaMins),
                    'total_count' => $tot,
                    'overall_mins' => $overallMins,
                    'overall_tat_formatted' => $this->formatTime($overallMins),
                    'on_time_count' => $onTime,
                    'over_sla_count' => $overSla,
                    'sla_percent' => $slaPct,
                    'is_avg_compliant' => $overallMins <= $targetMins,
                ];

                if ($group === 'CITO') {
                    $citoList[] = $formatted;
                } else {
                    $nonCitoList[] = $formatted;
                }

                $stats[$group]['total'] += $tot;
                $stats[$group]['on_time'] += $onTime;
                $stats[$group]['rajal_count'] += $rajalCount;
                $stats[$group]['rajal_weighted'] += ($rajalMins * $rajalCount);
                $stats[$group]['ranap_count'] += $ranapCount;
                $stats[$group]['ranap_weighted'] += ($ranapMins * $ranapCount);
                $stats[$group]['lainnya_count'] += $lainnyaCount;
                $stats[$group]['lainnya_weighted'] += ($lainnyaMins * $lainnyaCount);
                $stats[$group]['overall_weighted'] += ($overallMins * $tot);
            }

            // Urutkan berdasarkan nama pemeriksaan alfabetis
            usort($citoList, fn($a, $b) => strcmp($a['name'], $b['name']));
            usort($nonCitoList, fn($a, $b) => strcmp($a['name'], $b['name']));

            $buildSummaryGroup = function (array $s) {
                $avgOverall = $s['total'] > 0 ? (int) round($s['overall_weighted'] / $s['total']) : 0;
                $avgRajal = $s['rajal_count'] > 0 ? (int) round($s['rajal_weighted'] / $s['rajal_count']) : 0;
                $avgRanap = $s['ranap_count'] > 0 ? (int) round($s['ranap_weighted'] / $s['ranap_count']) : 0;
                $avgLainnya = $s['lainnya_count'] > 0 ? (int) round($s['lainnya_weighted'] / $s['lainnya_count']) : 0;
                $slaOverall = $s['total'] > 0 ? round(($s['on_time'] / $s['total']) * 100, 1) : 0;

                return [
                    'total' => $s['total'],
                    'on_time' => $s['on_time'],
                    'over_sla' => max(0, $s['total'] - $s['on_time']),
                    'avg_mins' => $avgOverall,
                    'avg_tat' => $this->formatTime($avgOverall),
                    'rajal_avg_mins' => $avgRajal,
                    'rajal_avg_tat' => $this->formatTime($avgRajal),
                    'ranap_avg_mins' => $avgRanap,
                    'ranap_avg_tat' => $this->formatTime($avgRanap),
                    'lainnya_avg_mins' => $avgLainnya,
                    'lainnya_avg_tat' => $this->formatTime($avgLainnya),
                    'sla_percent' => $slaOverall,
                ];
            };

            $citoSum = $buildSummaryGroup($stats['CITO']);
            $nonCitoSum = $buildSummaryGroup($stats['NON_CITO']);

            return [
                'cito' => $citoList,
                'non_cito' => $nonCitoList,
                'summary' => [
                    'cito_total' => $citoSum['total'],
                    'cito_on_time' => $citoSum['on_time'],
                    'cito_over_sla' => $citoSum['over_sla'],
                    'cito_avg_mins' => $citoSum['avg_mins'],
                    'cito_avg_tat' => $citoSum['avg_tat'],
                    'cito_rajal_avg_tat' => $citoSum['rajal_avg_tat'],
                    'cito_ranap_avg_tat' => $citoSum['ranap_avg_tat'],
                    'cito_lainnya_avg_tat' => $citoSum['lainnya_avg_tat'],
                    'cito_sla_percent' => $citoSum['sla_percent'],

                    'non_cito_total' => $nonCitoSum['total'],
                    'non_cito_on_time' => $nonCitoSum['on_time'],
                    'non_cito_over_sla' => $nonCitoSum['over_sla'],
                    'non_cito_avg_mins' => $nonCitoSum['avg_mins'],
                    'non_cito_avg_tat' => $nonCitoSum['avg_tat'],
                    'non_cito_rajal_avg_tat' => $nonCitoSum['rajal_avg_tat'],
                    'non_cito_ranap_avg_tat' => $nonCitoSum['ranap_avg_tat'],
                    'non_cito_lainnya_avg_tat' => $nonCitoSum['lainnya_avg_tat'],
                    'non_cito_sla_percent' => $nonCitoSum['sla_percent'],
                ],
                'sla_config' => [
                    'cito_sla_mins' => $citoSlaMins,
                    'cito_sla_label' => $this->formatSlaLabel($citoSlaMins, false),
                    'cito_sla_short' => $this->formatSlaLabel($citoSlaMins, true),
                    'non_cito_sla_mins' => $nonCitoSlaMins,
                    'non_cito_sla_label' => $this->formatSlaLabel($nonCitoSlaMins, false),
                    'non_cito_sla_short' => $this->formatSlaLabel($nonCitoSlaMins, true),
                ],
                'cached_at' => now()->format('d/m/Y H:i:s'),
            ];
        });
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

        $savedSettings = $this->getSettings();
        $citoSlaMins = (int) $request->input('cito_sla', $savedSettings['cito_sla_mins']);
        $nonCitoSlaMins = (int) $request->input('non_cito_sla', $savedSettings['non_cito_sla_mins']);

        if ($citoSlaMins < 15) {
            $citoSlaMins = self::DEFAULT_CITO_SLA_MINS;
        }
        if ($nonCitoSlaMins < 15) {
            $nonCitoSlaMins = self::DEFAULT_NON_CITO_SLA_MINS;
        }

        $forceRefresh = $request->boolean('refresh', false);

        $result = $this->buildReportData($startDate, $endDate, $citoSlaMins, $nonCitoSlaMins, $forceRefresh);

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

        $savedSettings = $this->getSettings();
        $citoSlaMins = (int) $request->input('cito_sla', $savedSettings['cito_sla_mins']);
        $nonCitoSlaMins = (int) $request->input('non_cito_sla', $savedSettings['non_cito_sla_mins']);

        if ($citoSlaMins < 15) {
            $citoSlaMins = self::DEFAULT_CITO_SLA_MINS;
        }
        if ($nonCitoSlaMins < 15) {
            $nonCitoSlaMins = self::DEFAULT_NON_CITO_SLA_MINS;
        }

        $citoLabelShort = $this->formatSlaLabel($citoSlaMins, true);
        $nonCitoLabelShort = $this->formatSlaLabel($nonCitoSlaMins, true);

        // Gunakan agregasi yang sama dengan tampilan web (memanfaatkan cache jika sudah dimuat)
        $reportData = $this->buildReportData($startDate, $endDate, $citoSlaMins, $nonCitoSlaMins, false);

        $periodStr = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        $spreadsheet = ReportExcelService::createSpreadsheet(
            "Laporan Turn Around Time (TAT) CITO (≤ {$citoLabelShort})",
            $periodStr
        );

        $populateSheet = function ($sheet, $title, array $items, int $slaTargetMins, string $slaLabel) {
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
            $sheet->setCellValue('L6', "Tepat Waktu (≤ {$slaLabel})");
            $sheet->setCellValue('M6', "Kepatuhan Target (≤ {$slaLabel})");

            $rowIdx = 7;
            $no = 1;
            $totRajal = 0;
            $totRanap = 0;
            $totLainnya = 0;
            $totGrand = 0;
            $totOnTime = 0;

            $wRajal = 0;
            $wRanap = 0;
            $wLainnya = 0;
            $wGrand = 0;

            foreach ($items as $item) {
                $tot = (int) $item['total_count'];
                $onTime = (int) $item['on_time_count'];
                $slaPct = $item['sla_percent'] . '%';

                $rCount = (int) $item['rajal_count'];
                $rMins = (int) $item['rajal_mins'];
                $inCount = (int) $item['ranap_count'];
                $inMins = (int) $item['ranap_mins'];
                $otCount = (int) $item['lainnya_count'];
                $otMins = (int) $item['lainnya_mins'];
                $ovMins = (int) $item['overall_mins'];

                $sheet->setCellValue("A{$rowIdx}", $no++);
                $sheet->setCellValue("B{$rowIdx}", $item['code']);
                $sheet->setCellValue("C{$rowIdx}", $item['name']);
                $sheet->setCellValue("D{$rowIdx}", $rMins ?: '-');
                $sheet->setCellValue("E{$rowIdx}", $rCount);
                $sheet->setCellValue("F{$rowIdx}", $inMins ?: '-');
                $sheet->setCellValue("G{$rowIdx}", $inCount);
                $sheet->setCellValue("H{$rowIdx}", $otMins ?: '-');
                $sheet->setCellValue("I{$rowIdx}", $otCount);
                $sheet->setCellValue("J{$rowIdx}", $ovMins);
                $sheet->setCellValue("K{$rowIdx}", $tot);
                $sheet->setCellValue("L{$rowIdx}", $onTime);
                $sheet->setCellValue("M{$rowIdx}", $slaPct);

                $totRajal += $rCount;
                $totRanap += $inCount;
                $totLainnya += $otCount;
                $totGrand += $tot;
                $totOnTime += $onTime;

                $wRajal += ($rMins * $rCount);
                $wRanap += ($inMins * $inCount);
                $wLainnya += ($otMins * $otCount);
                $wGrand += ($ovMins * $tot);

                $rowIdx++;
            }

            // Summary Row dengan Weighted Average TAT
            $avgRajal = $totRajal > 0 ? (int) round($wRajal / $totRajal) : '-';
            $avgRanap = $totRanap > 0 ? (int) round($wRanap / $totRanap) : '-';
            $avgLainnya = $totLainnya > 0 ? (int) round($wLainnya / $totLainnya) : '-';
            $avgGrand = $totGrand > 0 ? (int) round($wGrand / $totGrand) : '-';
            $overallSlaPct = $totGrand > 0 ? round(($totOnTime / $totGrand) * 100, 1) . '%' : '0%';

            $sheet->setCellValue("A{$rowIdx}", '');
            $sheet->setCellValue("B{$rowIdx}", 'TOTAL KESELURUHAN');
            $sheet->setCellValue("C{$rowIdx}", '');
            $sheet->setCellValue("D{$rowIdx}", $avgRajal);
            $sheet->setCellValue("E{$rowIdx}", $totRajal);
            $sheet->setCellValue("F{$rowIdx}", $avgRanap);
            $sheet->setCellValue("G{$rowIdx}", $totRanap);
            $sheet->setCellValue("H{$rowIdx}", $avgLainnya);
            $sheet->setCellValue("I{$rowIdx}", $totLainnya);
            $sheet->setCellValue("J{$rowIdx}", $avgGrand);
            $sheet->setCellValue("K{$rowIdx}", $totGrand);
            $sheet->setCellValue("L{$rowIdx}", $totOnTime);
            $sheet->setCellValue("M{$rowIdx}", $overallSlaPct);

            ReportExcelService::formatTable($sheet, 6, $rowIdx, 'A', 'M', true);
            $sheet->getStyle("A7:B{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D7:M{$rowIdx}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        };

        // Tab 1: CITO
        $sheet1 = $spreadsheet->getActiveSheet();
        $populateSheet($sheet1, "TAT CITO ({$citoSlaMins}m)", $reportData['cito'], $citoSlaMins, $citoLabelShort);

        // Tab 2: NON-CITO
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setCellValue('A1', 'RUMAH SAKIT UMUM DAERAH');
        $sheet2->setCellValue('A2', 'LABORATORIUM PATOLOGI KLINIK');
        $sheet2->setCellValue('A3', "LAPORAN TURN AROUND TIME (TAT) NON-CITO (TARGET ≤ " . strtoupper($nonCitoLabelShort) . ")");
        $sheet2->setCellValue('A4', 'Periode: ' . $periodStr . ' | Dicetak: ' . date('d/m/Y H:i:s'));
        $sheet2->getStyle('A1:A3')->getFont()->setBold(true);

        $populateSheet($sheet2, "TAT Non-CITO ({$nonCitoSlaMins}m)", $reportData['non_cito'], $nonCitoSlaMins, $nonCitoLabelShort);

        // Tab 3: Daftar Transaksi Detail Pemeriksaan Pasien (Order, Check-in, Validasi)
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Daftar Pasien & Check-in');

        $sheet3->setCellValue('A1', 'RUMAH SAKIT UMUM DAERAH');
        $sheet3->setCellValue('A2', 'LABORATORIUM PATOLOGI KLINIK');
        $sheet3->setCellValue('A3', 'DAFTAR TRANSAKSI PEMERIKSAAN PASIEN (ORDER, SPESIMEN CHECK-IN, VALIDASI)');
        $sheet3->setCellValue('A4', "Periode: {$periodStr} | Target SLA: CITO ≤ {$citoLabelShort}, Non-CITO ≤ {$nonCitoLabelShort}");
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
        $sheet3->setCellValue('N6', 'Target SLA');
        $sheet3->setCellValue('O6', 'Status Kepatuhan Target');
        $sheet3->setCellValue('P6', 'Validator');

        $oracle = DB::connection('oracle');
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
            ->whereRaw('b.od_validate_on >= c.os_spl_rcvdt')
            ->orderBy('a.oh_trx_dt', 'asc')
            ->take(5000)
            ->get();

        $rIdx3 = 7;
        $no3 = 1;

        foreach ($detailPasien as $p) {
            $isCito = in_array(trim(strtoupper((string) $p->prioritas)), ['U', 'C', 'S']);
            $targetMins = $isCito ? $citoSlaMins : $nonCitoSlaMins;
            $targetLabel = $isCito ? $citoLabelShort : $nonCitoLabelShort;
            $tatMins = (int) $p->tat_menit;
            $isCompliant = $tatMins <= $targetMins;
            $jenisRawatStr = $p->jenis_rawat === 'IN' ? 'Rawat Inap' : ($p->jenis_rawat === 'OP' ? 'Rawat Jalan' : 'Lainnya');

            $sheet3->setCellValue("A{$rIdx3}", $no3++);
            $sheet3->setCellValue("B{$rIdx3}", $p->tgl_order ? date('d/m/Y', strtotime($p->tgl_order)) : '-');
            $sheet3->setCellValue("C{$rIdx3}", $p->no_order);
            $sheet3->setCellValue("D{$rIdx3}", $p->no_rm);
            $sheet3->setCellValue("E{$rIdx3}", $p->nama_pasien);
            $sheet3->setCellValue("F{$rIdx3}", $jenisRawatStr);
            $sheet3->setCellValue("G{$rIdx3}", $isCito ? 'CITO' : 'Non-CITO');
            $sheet3->setCellValue("H{$rIdx3}", $p->ruangan ?: '-');
            $sheet3->setCellValue("I{$rIdx3}", $p->nama_uji);
            $sheet3->setCellValue("J{$rIdx3}", $p->tgl_order ? date('d/m/Y H:i:s', strtotime($p->tgl_order)) : '-');
            $sheet3->setCellValue("K{$rIdx3}", $p->waktu_checkin ? date('d/m/Y H:i:s', strtotime($p->waktu_checkin)) : '-');
            $sheet3->setCellValue("L{$rIdx3}", $p->waktu_validasi ? date('d/m/Y H:i:s', strtotime($p->waktu_validasi)) : '-');
            $sheet3->setCellValue("M{$rIdx3}", $tatMins);
            $sheet3->setCellValue("N{$rIdx3}", "≤ {$targetLabel}");
            $sheet3->setCellValue("O{$rIdx3}", $isCompliant ? 'Tepat Waktu (Memenuhi Target)' : 'Melewati Target SLA');
            $sheet3->setCellValue("P{$rIdx3}", $p->validator ?: '-');

            $rIdx3++;
        }

        ReportExcelService::formatTable($sheet3, 6, max(6, $rIdx3 - 1), 'A', 'P', false);

        $filename = 'Laporan_TAT_Lab_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.xlsx';
        return ReportExcelService::streamDownload($spreadsheet, $filename);
    }
}
