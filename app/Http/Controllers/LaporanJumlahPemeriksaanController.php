<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LaporanJumlahPemeriksaanController extends Controller
{
    //
    public function index()
    {
        return view('laporan.jumlah-pemeriksaan.index');
    }


    public function getData(Request $request)
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::create(2024, 1, 1, 0, 0, 0);
    
        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::create(2024, 3, 31, 23, 59, 59);
    
        // Menghubungkan ke database Oracle
        $oracleConnection = DB::connection('oracle');
    
        // Query untuk mengambil data
        $rawData = $oracleConnection
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
                DB::raw("COALESCE(c.tg_name, 'Tidak Diketahui') as test_group_name"),
                'b.od_testcode as test_code',
                DB::raw("COALESCE(d.ti_name, 'Tidak Diketahui') as test_name"),
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
                DB::raw("COALESCE(c.tg_name, 'Tidak Diketahui')"),
                'b.od_testcode',
                DB::raw("COALESCE(d.ti_name, 'Tidak Diketahui')")
            )
            ->orderBy(DB::raw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM')"), 'ASC')
            ->orderBy(DB::raw("COALESCE(c.tg_name, 'Tidak Diketahui')"), 'ASC')
            ->orderBy(DB::raw("COALESCE(d.ti_name, 'Tidak Diketahui')"), 'ASC')
            ->get();
    
        // Ambil bulan unik
        $months = collect($rawData)->pluck('year_month')->unique()->sort()->values();
    
        // Inisialisasi struktur pivot
        $pivotData = [];
        $allJenisRawat = ['Rawat Jalan', 'Rawat Inap', 'Lainnya'];
    
        foreach ($rawData as $item) {
            $groupName = $item->test_group_name ?? 'Tidak Diketahui';
            $testName = $item->test_name ?? 'Tidak Diketahui';
            $yearMonth = $item->year_month;
            $jenisRawat = $item->jenis_rawat ?? 'Lainnya';
    
            // Unique key untuk kombinasi group + test
            $key = md5($groupName . '||' . $testName);
    
            if (!isset($pivotData[$key])) {
                $pivotData[$key] = [
                    'group_name' => $groupName,
                    'test_name' => $testName,
                    'data' => []
                ];
    
                foreach ($months as $month) {
                    $pivotData[$key]['data'][$month] = array_fill_keys($allJenisRawat, 0);
                }
    
                $pivotData[$key]['data']['total'] = array_fill_keys($allJenisRawat, 0);
            }
    
            // Tambahkan nilai
            $pivotData[$key]['data'][$yearMonth][$jenisRawat] += $item->total;
            $pivotData[$key]['data']['total'][$jenisRawat] += $item->total;
        }
    
        // Sortir berdasarkan nama group dan test
        $pivotData = collect($pivotData)->sortBy([
            ['group_name', 'asc'],
            ['test_name', 'asc']
        ])->values()->toArray();
    
        // Hitung grand total
        $grandTotal = [];
        foreach ($months as $month) {
            $grandTotal[$month] = array_fill_keys($allJenisRawat, 0);
        }
        $grandTotal['total'] = array_fill_keys($allJenisRawat, 0);
    
        foreach ($pivotData as $data) {
            foreach ($months as $month) {
                foreach ($allJenisRawat as $jenis) {
                    $grandTotal[$month][$jenis] += $data['data'][$month][$jenis];
                    $grandTotal['total'][$jenis] += $data['data'][$month][$jenis];
                }
            }
        }
    
        // Buat response JSON
        $response = [
            'months' => $months,
            'pivot' => $pivotData,
            'grand_total' => $grandTotal
        ];
    
        return response()->json($response);
    }
    
}
