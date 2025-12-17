<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\TblWidth; // This might not be strictly necessary for basic table styling

class LaporanController extends Controller
{
    //
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
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::create(2024, 1, 1)->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::create(2024, 1, 31)->endOfDay();

        $oracleConnection = DB::connection('oracle');
        $distribusiRuangan = $this->getDistribusiTipeRuangan($oracleConnection, $startDate, $endDate);
        $getDistribusiPerRuangan = $this->getDistribusiPerRuangan($oracleConnection, $startDate, $endDate);
        return response()->json([
            'distribusi_ruangan' => $distribusiRuangan,
            'getDistribusiPerRuangan' => $getDistribusiPerRuangan,
        ]);
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

        // Calculate totals for "Total" row within each type
        foreach ($pivotData as $tipeRuangan => &$ruangan) {
            // Initialize the 'Total' array for this $tipeRuangan
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
            // Calculate the grand total for the 'Total' row
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

    public function exportToWord(Request $request)
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::create(2024, 1, 1)->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::create(2024, 1, 31)->endOfDay();

        $oracleConnection = DB::connection('oracle');

        $distribusiRuanganData = $this->getDistribusiTipeRuangan($oracleConnection, $startDate, $endDate);
        $distribusiPerRuanganData = $this->getDistribusiPerRuangan($oracleConnection, $startDate, $endDate);

        // --- IMPORTANT: REMOVE ALL DEBUGGING LOGS HERE ---
        // Log::info('DEBUG: Data for Distribusi Tipe Ruangan:', $distribusiRuanganData); // DELETE THIS LINE
        // Log::info('DEBUG: Data for Distribusi Per Ruangan:', $distribusiPerRuanganData); // DELETE THIS LINE
        // --- END OF DEBUGGING LOGS ---

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Styles for table
        $tableStyle = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 80, 'width' => 100 * 50, 'unit' => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT];
        $fontStyle = ['name' => 'Arial', 'size' => 9];
        $headerFontStyle = ['name' => 'Arial', 'size' => 9, 'bold' => true]; // For header text
        $cellStyle = ['valign' => 'center'];
        $headerCellStyle = ['valign' => 'center', 'bgColor' => 'E0E0E0']; // Light grey background for headers
        $totalRowStyle = ['valign' => 'center', 'bgColor' => 'D3D3D3']; // Slightly darker grey for total row

        // Add header
        $section->addText('Laporan Jumlah Pasien', ['bold' => true, 'size' => 16, 'name' => 'Arial'], ['align' => 'center']);
        $section->addText('Periode: ' . $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'), ['size' => 10], ['align' => 'center']);
        $section->addTextBreak(1);

        // --- Rekapitulasi Kunjungan Pasien per Jenis Pelayanan ---
        $section->addText('Rekapitulasi Kunjungan Pasien per Jenis Pelayanan', ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);

        $dataDistribusiTipeRuangan = $distribusiRuanganData['Tipe Ruangan'];
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
        $table->addCell(2000, $headerCellStyle)->addText('Jenis Pelayanan', $headerFontStyle, ['align' => 'center']);
        foreach ($months as $month) {
            $table->addCell(1500, $headerCellStyle)->addText($month, $headerFontStyle, ['align' => 'center']);
        }
        $table->addCell(1500, $headerCellStyle)->addText('Total', $headerFontStyle, ['align' => 'center']);

        $rows = array_filter($dataDistribusiTipeRuangan, fn($key) => $key !== "Total Per Bulan", ARRAY_FILTER_USE_KEY);

        foreach ($rows as $key => $row) {
            $table->addRow();
            $table->addCell(2000, $cellStyle)->addText($key, $fontStyle);
            foreach ($months as $month) {
                $table->addCell(1500, $cellStyle)->addText($row[$month] ?? 0, $fontStyle, ['align' => 'center']);
            }
            $table->addCell(1500, $cellStyle)->addText($row['Total'] ?? 0, ['bold' => true, 'size' => 9], ['align' => 'center']);
        }

        if (isset($dataDistribusiTipeRuangan['Total Per Bulan'])) {
            $totalRowData = $dataDistribusiTipeRuangan['Total Per Bulan'];
            $table->addRow();
            $table->addCell(2000, $totalRowStyle)->addText('Total', ['bold' => true, 'size' => 9]);
            foreach ($months as $month) {
                $table->addCell(1500, $totalRowStyle)->addText($totalRowData[$month] ?? 0, ['bold' => true, 'size' => 9], ['align' => 'center']);
            }
            $table->addCell(1500, $totalRowStyle)->addText($totalRowData['Total'] ?? 0, ['bold' => true, 'size' => 9], ['align' => 'center']);
        }

        $section->addTextBreak(1);


        // --- Rekapitulasi Kunjungan Pasien per Ruangan ---
        $section->addText('Rekapitulasi Kunjungan Pasien per Ruangan', ['bold' => true, 'size' => 14]);
        $section->addTextBreak(1);

        $dataDistribusiPerRuanganActual = $distribusiPerRuanganData['data'];
        $monthsDistribusi = $distribusiPerRuanganData['months'];
        sort($monthsDistribusi);

        $table2 = $section->addTable($tableStyle);
        $table2->addRow();
        $table2->addCell(2000, $headerCellStyle)->addText('Tipe Ruangan', $headerFontStyle, ['align' => 'center']);
        $table2->addCell(3000, $headerCellStyle)->addText('Nama Ruangan', $headerFontStyle, ['align' => 'center']);
        foreach ($monthsDistribusi as $month) {
            $table2->addCell(1500, $headerCellStyle)->addText($month, $headerFontStyle, ['align' => 'center']);
        }
        $table2->addCell(1500, $headerCellStyle)->addText('Total', $headerFontStyle, ['align' => 'center']);

        foreach ($dataDistribusiPerRuanganActual as $tipe => $ruanganData) {
            $ruanganKeys = array_keys(array_filter($ruanganData, function($val, $key) {
                return $key !== 'Total';
            }, ARRAY_FILTER_USE_BOTH));
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
                $table2->addCell(2000, $totalRowStyle)->addText("Total {$tipe}", ['bold' => true, 'size' => 9], ['align' => 'center']);
                $table2->addCell(3000, $totalRowStyle);
                foreach ($monthsDistribusi as $month) {
                    $table2->addCell(1500, $totalRowStyle)->addText($ruanganData['Total'][$month] ?? 0, ['bold' => true, 'size' => 9], ['align' => 'center']);
                }
                $table2->addCell(1500, $totalRowStyle)->addText($ruanganData['Total']['Total'] ?? 0, ['bold' => true, 'size' => 9], ['align' => 'center']);
            }
        }

        // Save the Word document
        $fileName = 'Laporan_Jumlah_Pasien_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.docx';
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');

        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');
        $objWriter->save('php://output');
        exit;
    }
}