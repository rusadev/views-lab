<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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

        foreach ($pivotData as $tipeRuangan => &$ruangan) {
            foreach ($months as $bulanTahun => $_) {
                $ruangan["Total"][$bulanTahun] = array_sum(array_column($ruangan, $bulanTahun));
            }
            $ruangan["Total"]["Total"] = array_sum(array_column($ruangan, 'Total'));
        }

        return [
            'data' => $pivotData,
            'months' => array_keys($months)
        ];
    }
}
