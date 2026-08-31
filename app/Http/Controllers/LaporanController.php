<?php

namespace App\Http\Controllers;

use App\Services\ReportExcelService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class LaporanController extends Controller
{
    public function index()
    {
        return view('laporan.index');
    }

    public function indexJumlahPasien()
    {
        return view('laporan.jumlah-pasien.index');
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
        $cacheKey = 'laporan_pasien_' . md5($startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d'));

        if ($forceRefresh) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
        }

        $data = \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () use ($startDate, $endDate) {
            $oracleConnection = DB::connection('oracle');
            $distribusiRuangan = $this->getDistribusiTipeRuangan($oracleConnection, $startDate, $endDate);
            $getDistribusiPerRuangan = $this->getDistribusiPerRuangan($oracleConnection, $startDate, $endDate);

            return [
                'distribusi_ruangan' => $distribusiRuangan,
                'getDistribusiPerRuangan' => $getDistribusiPerRuangan,
                'cached_at' => now()->format('d/m/Y H:i:s'),
            ];
        });

        return response()->json($data);
    }

    private function getDistribusiTipeRuangan($oracleConnection, $startDate, $endDate)
    {
        $distribusiData = $oracleConnection
            ->table('ord_hdr')
            ->select(
                DB::raw("
                    CASE
                        WHEN oh_ptype = 'IN' THEN 'Rawat Inap'
                        WHEN oh_ptype = 'OP' THEN 'Rawat Jalan'
                        ELSE 'Lainnya'
                    END AS jenis_rawat
                "),
                DB::raw("TO_CHAR(oh_trx_dt, 'YYYY-MM') AS bulan_tahun"),
                DB::raw("COUNT(DISTINCT CONCAT(oh_pid, TO_CHAR(oh_trx_dt, 'YYYY-MM'))) AS total_pasien"),
                DB::raw("
                    CASE
                        WHEN oh_age_yy < 18 THEN '<18'
                        WHEN oh_age_yy BETWEEN 18 AND 30 THEN '18-30'
                        WHEN oh_age_yy BETWEEN 31 AND 45 THEN '31-45'
                        WHEN oh_age_yy BETWEEN 46 AND 60 THEN '46-60'
                        ELSE '>60'
                    END AS kelompok_usia
                "),
                DB::raw("COUNT(DISTINCT CONCAT(oh_pid, TO_CHAR(oh_trx_dt, 'YYYY-MM'))) AS jumlah_per_usia"),
                DB::raw("
                    CASE
                        WHEN oh_sex = '1' THEN 'Laki-laki'
                        WHEN oh_sex = '2' THEN 'Perempuan'
                        ELSE 'Lainnya'
                    END AS gender
                "),
                DB::raw("COUNT(DISTINCT CONCAT(oh_pid, TO_CHAR(oh_trx_dt, 'YYYY-MM'))) AS jumlah_per_gender")
            )
            ->whereBetween('oh_trx_dt', [$startDate, $endDate])
            ->groupBy(
                DB::raw("
                    CASE
                        WHEN oh_ptype = 'IN' THEN 'Rawat Inap'
                        WHEN oh_ptype = 'OP' THEN 'Rawat Jalan'
                        ELSE 'Lainnya'
                    END
                "),
                DB::raw("TO_CHAR(oh_trx_dt, 'YYYY-MM')"),
                DB::raw("
                    CASE
                        WHEN oh_age_yy < 18 THEN '<18'
                        WHEN oh_age_yy BETWEEN 18 AND 30 THEN '18-30'
                        WHEN oh_age_yy BETWEEN 31 AND 45 THEN '31-45'
                        WHEN oh_age_yy BETWEEN 46 AND 60 THEN '46-60'
                        ELSE '>60'
                    END
                "),
                DB::raw("
                    CASE
                        WHEN oh_sex = '1' THEN 'Laki-laki'
                        WHEN oh_sex = '2' THEN 'Perempuan'
                        ELSE 'Lainnya'
                    END
                ")
            )
            ->orderBy(DB::raw("TO_CHAR(oh_trx_dt, 'YYYY-MM')"))
            ->get();

        $pivotedData = [
            'Tipe Ruangan' => [],
            'Usia' => [],
            'Gender' => [],
            'Grand Total' => 0
        ];

        $monthTotals = [];
        $totalPerTipeRuangan = [];

        foreach ($distribusiData as $record) {
            $bulan = $record->bulan_tahun;
            $jenisRawat = $record->jenis_rawat;
            $kelompokUsia = $record->kelompok_usia;
            $gender = $record->gender;
            $jumlahPasien = $record->total_pasien;
            $jumlahUsia = $record->jumlah_per_usia;
            $jumlahGender = $record->jumlah_per_gender;

            if (!isset($pivotedData['Tipe Ruangan'][$jenisRawat][$bulan])) {
                $pivotedData['Tipe Ruangan'][$jenisRawat][$bulan] = 0;
            }
            $pivotedData['Tipe Ruangan'][$jenisRawat][$bulan] += $jumlahPasien;

            if (!isset($totalPerTipeRuangan[$jenisRawat])) {
                $totalPerTipeRuangan[$jenisRawat] = 0;
            }
            $totalPerTipeRuangan[$jenisRawat] += $jumlahPasien;

            if (!isset($monthTotals[$bulan])) {
                $monthTotals[$bulan] = 0;
            }
            $monthTotals[$bulan] += $jumlahPasien;

            if (!isset($pivotedData['Usia'][$kelompokUsia])) {
                $pivotedData['Usia'][$kelompokUsia] = 0;
            }
            $pivotedData['Usia'][$kelompokUsia] += $jumlahUsia;

            if (!isset($pivotedData['Gender'][$gender])) {
                $pivotedData['Gender'][$gender] = 0;
            }
            $pivotedData['Gender'][$gender] += $jumlahGender;

            $pivotedData['Grand Total'] += $jumlahPasien;
        }

        foreach ($totalPerTipeRuangan as $jenisRawat => $total) {
            $pivotedData['Tipe Ruangan'][$jenisRawat]['Total'] = $total;
        }
        ksort($monthTotals);
        $monthTotals['Total'] = array_sum($monthTotals);
        $pivotedData['Tipe Ruangan']['Total Per Bulan'] = $monthTotals;

        return $pivotedData;
    }

    private function getDistribusiPerRuangan($oracleConnection, $startDate, $endDate)
    {
        $distribusiRuangan = $oracleConnection
            ->table('ord_hdr')
            ->join('hfclinic', 'ord_hdr.oh_clinic_code', '=', 'hfclinic.clinic_code')
            ->select(
                'hfclinic.clinic_desc AS nama_ruangan',
                DB::raw("CASE
                    WHEN oh_ptype = 'IN' THEN 'Rawat Inap'
                    WHEN oh_ptype = 'OP' THEN 'Rawat Jalan'
                    ELSE 'Lainnya'
                END AS tipe_ruangan"),
                DB::raw("TO_CHAR(oh_trx_dt, 'YYYY-MM') AS bulan_tahun"),
                DB::raw("COUNT(DISTINCT oh_pid) AS jumlah_pasien")
            )
            ->whereIn('oh_ptype', ['IN', 'OP'])
            ->whereBetween('oh_trx_dt', [$startDate, $endDate])
            ->groupBy(
                'hfclinic.clinic_desc',
                DB::raw("CASE
                    WHEN oh_ptype = 'IN' THEN 'Rawat Inap'
                    WHEN oh_ptype = 'OP' THEN 'Rawat Jalan'
                    ELSE 'Lainnya'
                END"),
                DB::raw("TO_CHAR(oh_trx_dt, 'YYYY-MM')")
            )
            ->orderBy('hfclinic.clinic_desc')
            ->orderBy(DB::raw("TO_CHAR(oh_trx_dt, 'YYYY-MM')"))
            ->get();

        $pivotData = [];
        $months = [];
        $totalPerRuangan = [];

        foreach ($distribusiRuangan as $record) {
            $tipeRuangan = $record->tipe_ruangan;
            $namaRuangan = $record->nama_ruangan;
            $bulanTahun = $record->bulan_tahun;
            $jumlahPasien = $record->jumlah_pasien;

            $months[$bulanTahun] = true;

            if (!isset($pivotData[$tipeRuangan][$namaRuangan])) {
                $pivotData[$tipeRuangan][$namaRuangan] = [];
            }

            $pivotData[$tipeRuangan][$namaRuangan][$bulanTahun] = $jumlahPasien;

            if (!isset($totalPerRuangan[$tipeRuangan][$namaRuangan])) {
                $totalPerRuangan[$tipeRuangan][$namaRuangan] = 0;
            }
            $totalPerRuangan[$tipeRuangan][$namaRuangan] += $jumlahPasien;
        }

        foreach ($totalPerRuangan as $tipeRuangan => $ruangan) {
            foreach ($ruangan as $namaRuangan => $total) {
                $pivotData[$tipeRuangan][$namaRuangan]['Total'] = $total;
            }
        }

        foreach ($pivotData as $tipeRuangan => &$ruangan) {
            if (!isset($ruangan["Total"])) {
                $ruangan["Total"] = [];
            }

            foreach (array_keys($months) as $monthTahun) {
                $monthlyTotal = 0;
                foreach ($ruangan as $namaRuangan => $dataPerBulan) {
                    if ($namaRuangan !== "Total" && isset($dataPerBulan[$monthTahun])) {
                        $monthlyTotal += $dataPerBulan[$monthTahun];
                    }
                }
                $ruangan["Total"][$monthTahun] = $monthlyTotal;
            }

            $grandTotalForTipe = 0;
            foreach ($ruangan as $namaRuangan => $dataPerBulan) {
                if ($namaRuangan !== "Total" && isset($dataPerBulan['Total'])) {
                    $grandTotalForTipe += $dataPerBulan['Total'];
                }
            }
            $ruangan["Total"]["Total"] = $grandTotalForTipe;
        }

        return [
            'data' => $pivotData,
            'months' => array_keys($months)
        ];
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
        $distribusiRuanganData = $this->getDistribusiTipeRuangan($oracle, $startDate, $endDate);
        $distribusiPerRuanganData = $this->getDistribusiPerRuangan($oracle, $startDate, $endDate);

        $periodStr = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        $spreadsheet = ReportExcelService::createSpreadsheet('Laporan Jumlah Pasien Laboratorium', $periodStr);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Jenis Pelayanan');

        // Table 1: Jenis Pelayanan
        $sheet->setCellValue('A6', 'No');
        $sheet->setCellValue('B6', 'Jenis Pelayanan');

        $dataDistribusiTipeRuangan = $distribusiRuanganData['Tipe Ruangan'] ?? [];
        $months = [];
        foreach ($dataDistribusiTipeRuangan as $key => $row) {
            if ($key !== "Total Per Bulan" && is_array($row)) {
                foreach ($row as $col => $val) {
                    if ($col !== "Total") {
                        $months[$col] = true;
                    }
                }
            }
        }
        $monthList = array_keys($months);
        sort($monthList);

        $colIdx = 3;
        foreach ($monthList as $m) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
            $sheet->setCellValue("{$colLetter}6", $m);
            $colIdx++;
        }
        $totalColLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx);
        $sheet->setCellValue("{$totalColLetter}6", 'Total Pasien');

        $rowIdx = 7;
        $no = 1;
        foreach ($dataDistribusiTipeRuangan as $key => $row) {
            if ($key === "Total Per Bulan" || !is_array($row)) continue;

            $sheet->setCellValue("A{$rowIdx}", $no++);
            $sheet->setCellValue("B{$rowIdx}", $key);

            $c = 3;
            foreach ($monthList as $m) {
                $cLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $sheet->setCellValue("{$cLetter}{$rowIdx}", $row[$m] ?? 0);
                $c++;
            }
            $sheet->setCellValue("{$totalColLetter}{$rowIdx}", $row['Total'] ?? 0);
            $rowIdx++;
        }

        // Total Row
        if (isset($dataDistribusiTipeRuangan['Total Per Bulan'])) {
            $sheet->setCellValue("A{$rowIdx}", '');
            $sheet->setCellValue("B{$rowIdx}", 'TOTAL KESELURUHAN');
            $c = 3;
            foreach ($monthList as $m) {
                $cLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                $sheet->setCellValue("{$cLetter}{$rowIdx}", $dataDistribusiTipeRuangan['Total Per Bulan'][$m] ?? 0);
                $c++;
            }
            $sheet->setCellValue("{$totalColLetter}{$rowIdx}", $dataDistribusiTipeRuangan['Total Per Bulan']['Total'] ?? 0);
            ReportExcelService::formatTable($sheet, 6, $rowIdx, 'A', $totalColLetter, true);
        } else {
            ReportExcelService::formatTable($sheet, 6, $rowIdx - 1, 'A', $totalColLetter, false);
        }

        // Sheet 2: Distribusi Per Ruangan
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Per Ruangan');
        
        $sheet2->setCellValue('A1', 'RUMAH SAKIT UMUM DAERAH');
        $sheet2->setCellValue('A2', 'LABORATORIUM PATOLOGI KLINIK');
        $sheet2->setCellValue('A3', 'REKAPITULASI PASIEN PER RUANGAN');
        $sheet2->setCellValue('A4', 'Periode: ' . $periodStr);
        $sheet2->getStyle('A1:A3')->getFont()->setBold(true);

        $sheet2->setCellValue('A6', 'No');
        $sheet2->setCellValue('B6', 'Tipe Layanan');
        $sheet2->setCellValue('C6', 'Nama Ruangan / Poliklinik');

        $monthsDistribusi = $distribusiPerRuanganData['months'] ?? [];
        sort($monthsDistribusi);

        $cIdx2 = 4;
        foreach ($monthsDistribusi as $m) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIdx2);
            $sheet2->setCellValue("{$colLetter}6", $m);
            $cIdx2++;
        }
        $totCol2 = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cIdx2);
        $sheet2->setCellValue("{$totCol2}6", 'Total');

        $rIdx2 = 7;
        $no2 = 1;
        $dataRuanganActual = $distribusiPerRuanganData['data'] ?? [];

        foreach ($dataRuanganActual as $tipe => $ruanganData) {
            $ruanganKeys = array_keys(array_filter($ruanganData, fn($val, $key) => $key !== 'Total', ARRAY_FILTER_USE_BOTH));
            sort($ruanganKeys);

            foreach ($ruanganKeys as $ruangan) {
                $sheet2->setCellValue("A{$rIdx2}", $no2++);
                $sheet2->setCellValue("B{$rIdx2}", $tipe);
                $sheet2->setCellValue("C{$rIdx2}", $ruangan);

                $c = 4;
                foreach ($monthsDistribusi as $m) {
                    $cLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    $sheet2->setCellValue("{$cLetter}{$rIdx2}", $ruanganData[$ruangan][$m] ?? 0);
                    $c++;
                }
                $sheet2->setCellValue("{$totCol2}{$rIdx2}", $ruanganData[$ruangan]['Total'] ?? 0);
                $rIdx2++;
            }

            if (isset($ruanganData['Total'])) {
                $sheet2->setCellValue("A{$rIdx2}", '');
                $sheet2->setCellValue("B{$rIdx2}", "SUBTOTAL {$tipe}");
                $sheet2->setCellValue("C{$rIdx2}", '');
                $c = 4;
                foreach ($monthsDistribusi as $m) {
                    $cLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
                    $sheet2->setCellValue("{$cLetter}{$rIdx2}", $ruanganData['Total'][$m] ?? 0);
                    $c++;
                }
                $sheet2->setCellValue("{$totCol2}{$rIdx2}", $ruanganData['Total']['Total'] ?? 0);
                $sheet2->getStyle("A{$rIdx2}:{$totCol2}{$rIdx2}")->getFont()->setBold(true);
                $rIdx2++;
            }
        }

        ReportExcelService::formatTable($sheet2, 6, $rIdx2 - 1, 'A', $totCol2, true);

        $filename = 'Laporan_Jumlah_Pasien_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.xlsx';
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

        $oracleConnection = DB::connection('oracle');
        $distribusiRuanganData = $this->getDistribusiTipeRuangan($oracleConnection, $startDate, $endDate);
        $distribusiPerRuanganData = $this->getDistribusiPerRuangan($oracleConnection, $startDate, $endDate);

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $tableStyle = ['borderSize' => 6, 'borderColor' => '94A3B8', 'cellMargin' => 80, 'width' => 100 * 50, 'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT];
        $fontStyle = ['name' => 'Plus Jakarta Sans', 'size' => 9];
        $headerFontStyle = ['name' => 'Plus Jakarta Sans', 'size' => 9, 'bold' => true, 'color' => 'FFFFFF'];
        $cellStyle = ['valign' => 'center'];
        $headerCellStyle = ['valign' => 'center', 'bgColor' => '2563EB'];
        $totalRowStyle = ['valign' => 'center', 'bgColor' => 'F1F5F9'];

        $section->addText('RUMAH SAKIT UMUM DAERAH', ['bold' => true, 'size' => 14, 'name' => 'Plus Jakarta Sans'], ['align' => 'center']);
        $section->addText('LABORATORIUM PATOLOGI KLINIK', ['bold' => true, 'size' => 12, 'name' => 'Plus Jakarta Sans'], ['align' => 'center']);
        $section->addText('LAPORAN JUMLAH PASIEN', ['bold' => true, 'size' => 11, 'name' => 'Plus Jakarta Sans'], ['align' => 'center']);
        $section->addText('Periode: ' . $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'), ['size' => 9, 'italic' => true], ['align' => 'center']);
        $section->addTextBreak(1);

        $section->addText('1. Rekapitulasi Pasien per Jenis Pelayanan', ['bold' => true, 'size' => 11]);
        $section->addTextBreak(1);

        $dataDistribusiTipeRuangan = $distribusiRuanganData['Tipe Ruangan'] ?? [];
        $monthSet = [];
        foreach ($dataDistribusiTipeRuangan as $key => $row) {
            if ($key !== "Total Per Bulan" && is_array($row)) {
                foreach ($row as $col => $val) {
                    if ($col !== "Total") {
                        $monthSet[$col] = true;
                    }
                }
            }
        }
        $months = array_keys($monthSet);
        sort($months);

        $table = $section->addTable($tableStyle);
        $table->addRow();
        $table->addCell(3000, $headerCellStyle)->addText('Jenis Pelayanan', $headerFontStyle, ['align' => 'center']);
        foreach ($months as $month) {
            $table->addCell(1500, $headerCellStyle)->addText($month, $headerFontStyle, ['align' => 'center']);
        }
        $table->addCell(1500, $headerCellStyle)->addText('Total', $headerFontStyle, ['align' => 'center']);

        $rows = array_filter($dataDistribusiTipeRuangan, fn($key) => $key !== "Total Per Bulan", ARRAY_FILTER_USE_KEY);

        foreach ($rows as $key => $row) {
            $table->addRow();
            $table->addCell(3000, $cellStyle)->addText($key, $fontStyle);
            foreach ($months as $month) {
                $table->addCell(1500, $cellStyle)->addText($row[$month] ?? 0, $fontStyle, ['align' => 'center']);
            }
            $table->addCell(1500, $cellStyle)->addText($row['Total'] ?? 0, ['bold' => true, 'size' => 9], ['align' => 'center']);
        }

        if (isset($dataDistribusiTipeRuangan['Total Per Bulan'])) {
            $totalRowData = $dataDistribusiTipeRuangan['Total Per Bulan'];
            $table->addRow();
            $table->addCell(3000, $totalRowStyle)->addText('TOTAL', ['bold' => true, 'size' => 9]);
            foreach ($months as $month) {
                $table->addCell(1500, $totalRowStyle)->addText($totalRowData[$month] ?? 0, ['bold' => true, 'size' => 9], ['align' => 'center']);
            }
            $table->addCell(1500, $totalRowStyle)->addText($totalRowData['Total'] ?? 0, ['bold' => true, 'size' => 9], ['align' => 'center']);
        }

        $section->addTextBreak(1);
        $section->addText('2. Rekapitulasi Pasien per Ruangan', ['bold' => true, 'size' => 11]);
        $section->addTextBreak(1);

        $dataDistribusiPerRuanganActual = $distribusiPerRuanganData['data'] ?? [];
        $monthsDistribusi = $distribusiPerRuanganData['months'] ?? [];
        sort($monthsDistribusi);

        $table2 = $section->addTable($tableStyle);
        $table2->addRow();
        $table2->addCell(2000, $headerCellStyle)->addText('Tipe', $headerFontStyle, ['align' => 'center']);
        $table2->addCell(3000, $headerCellStyle)->addText('Nama Ruangan', $headerFontStyle, ['align' => 'center']);
        foreach ($monthsDistribusi as $month) {
            $table2->addCell(1500, $headerCellStyle)->addText($month, $headerFontStyle, ['align' => 'center']);
        }
        $table2->addCell(1500, $headerCellStyle)->addText('Total', $headerFontStyle, ['align' => 'center']);

        foreach ($dataDistribusiPerRuanganActual as $tipe => $ruanganData) {
            $ruanganKeys = array_keys(array_filter($ruanganData, fn($val, $key) => $key !== 'Total', ARRAY_FILTER_USE_BOTH));
            sort($ruanganKeys);

            foreach ($ruanganKeys as $ruangan) {
                $table2->addRow();
                $table2->addCell(2000, $cellStyle)->addText($tipe, $fontStyle, ['align' => 'center']);
                $table2->addCell(3000, $cellStyle)->addText($ruangan, $fontStyle);
                foreach ($monthsDistribusi as $month) {
                    $table2->addCell(1500, $cellStyle)->addText($ruanganData[$ruangan][$month] ?? 0, $fontStyle, ['align' => 'center']);
                }
                $table2->addCell(1500, $cellStyle)->addText($ruanganData[$ruangan]['Total'] ?? 0, ['bold' => true, 'size' => 9], ['align' => 'center']);
            }

            if (isset($ruanganData['Total'])) {
                $table2->addRow();
                $table2->addCell(2000, $totalRowStyle)->addText("Subtotal {$tipe}", ['bold' => true, 'size' => 9], ['align' => 'center']);
                $table2->addCell(3000, $totalRowStyle);
                foreach ($monthsDistribusi as $month) {
                    $table2->addCell(1500, $totalRowStyle)->addText($ruanganData['Total'][$month] ?? 0, ['bold' => true, 'size' => 9], ['align' => 'center']);
                }
                $table2->addCell(1500, $totalRowStyle)->addText($ruanganData['Total']['Total'] ?? 0, ['bold' => true, 'size' => 9], ['align' => 'center']);
            }
        }

        $fileName = 'Laporan_Jumlah_Pasien_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.docx';
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');

        return response()->streamDownload(function() use ($objWriter) {
            $objWriter->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ]);
    }
}