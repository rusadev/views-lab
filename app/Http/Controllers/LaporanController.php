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

    public function getJumlahPasien(Request $request)
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::create(2024, 1, 1)->startOfDay(); 

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::create(2024, 12, 1)->endOfDay(); 

        $oracleConnection = DB::connection('oracle');


        $distribusiRanapRajal = $oracleConnection
            ->table('ord_hdr')
            ->select(
                DB::raw("CASE 
                    WHEN oh_ptype = 'IN' THEN 'Rawat Inap' 
                    WHEN oh_ptype = 'OP' THEN 'Rawat Jalan' 
                    ELSE 'Lainnya' 
                END AS jenis_rawat"),
                DB::raw("TO_CHAR(oh_trx_dt, 'YYYY-MM') AS bulan_tahun"),
                DB::raw('COUNT(DISTINCT oh_pid) AS jumlah_pasien')
            )
            ->whereBetween('oh_trx_dt', [$startDate, $endDate])
            ->groupBy(
                DB::raw("CASE 
                    WHEN oh_ptype = 'IN' THEN 'Rawat Inap' 
                    WHEN oh_ptype = 'OP' THEN 'Rawat Jalan' 
                    ELSE 'Lainnya' 
                END"),
                DB::raw("TO_CHAR(oh_trx_dt, 'YYYY-MM')")
            )
            ->orderBy(DB::raw("TO_CHAR(oh_trx_dt, 'YYYY-MM')"))
            ->get();

        $groupedData = $distribusiRanapRajal->groupBy('jenis_rawat');

        $pivotedData = [];
        $monthTotals = [];
        $grandTotal = 0;

        // Proses pivot data dan hitung total per baris & per bulan
        foreach ($groupedData as $jenisRawat => $records) {
            $rowTotal = 0;
            foreach ($records as $record) {
                $bulan = $record->bulan_tahun;
                $jumlah = $record->jumlah_pasien;

                $pivotedData[$jenisRawat][$bulan] = $jumlah;
                $rowTotal += $jumlah;

                // Akumulasi total per bulan
                if (!isset($monthTotals[$bulan])) {
                    $monthTotals[$bulan] = 0;
                }
                $monthTotals[$bulan] += $jumlah;
            }
            // Tambahkan total untuk masing-masing baris (jenis rawat)
            $pivotedData[$jenisRawat]['total'] = $rowTotal;
            $grandTotal += $rowTotal;
        }

        // Urutkan key bulan jika diperlukan
        ksort($monthTotals);

        // Tambahkan baris total (total per bulan dan grand total)
        $pivotedData['Total'] = $monthTotals;
        $pivotedData['Total']['total'] = $grandTotal;

        return response()->json($pivotedData);
    }
}
