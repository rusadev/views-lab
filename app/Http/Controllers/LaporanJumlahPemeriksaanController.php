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

        // Inisialisasi array data
        $tableData = [];
        $grandTotal = array_fill_keys($months->toArray(), 0);
        $grandTotal['total'] = 0;

        foreach ($rawData as $item) {
            $jenisRawat = $item->jenis_rawat ?? 'Lainnya';
            $yearMonth = $item->year_month;
            $groupName = $item->test_group_name ?? 'Tidak Diketahui';
            $testName = $item->test_name ?? 'Tidak Diketahui';

            // Inisialisasi jenis_rawat jika belum ada
            if (!isset($tableData[$jenisRawat])) {
                $tableData[$jenisRawat] = [
                    'name' => $jenisRawat,
                    'totals' => array_fill_keys($months->toArray(), 0),
                    'groups' => [],
                    'total_group' => 0 // Total untuk semua grup di dalam jenis rawat
                ];
                $tableData[$jenisRawat]['totals']['total'] = 0;
            }

            // Inisialisasi grup jika belum ada
            if (!isset($tableData[$jenisRawat]['groups'][$groupName])) {
                $tableData[$jenisRawat]['groups'][$groupName] = [
                    'name' => $groupName,
                    'totals' => array_fill_keys($months->toArray(), 0),
                    'tests' => [],
                    'total_group' => 0 // Total untuk grup ini
                ];
                $tableData[$jenisRawat]['groups'][$groupName]['totals']['total'] = 0;
            }

            // Inisialisasi pemeriksaan dalam grup jika belum ada
            if (!isset($tableData[$jenisRawat]['groups'][$groupName]['tests'][$testName])) {
                $tableData[$jenisRawat]['groups'][$groupName]['tests'][$testName] = [
                    'name' => $testName,
                    'totals' => array_fill_keys($months->toArray(), 0)
                ];
                $tableData[$jenisRawat]['groups'][$groupName]['tests'][$testName]['totals']['total'] = 0;
            }

            // Tambahkan jumlah per bulan
            $tableData[$jenisRawat]['totals'][$yearMonth] += $item->total;
            $tableData[$jenisRawat]['groups'][$groupName]['totals'][$yearMonth] += $item->total;
            $tableData[$jenisRawat]['groups'][$groupName]['tests'][$testName]['totals'][$yearMonth] += $item->total;

            // Hitung total keseluruhan
            $tableData[$jenisRawat]['totals']['total'] += $item->total;
            $tableData[$jenisRawat]['groups'][$groupName]['totals']['total'] += $item->total;
            $tableData[$jenisRawat]['groups'][$groupName]['tests'][$testName]['totals']['total'] += $item->total;

            // Hitung total keseluruhan per bulan
            $grandTotal[$yearMonth] += $item->total;
            $grandTotal['total'] += $item->total;

            // Calculate total of groups (adding all group totals) into each jenis_rawat's total_group
            $tableData[$jenisRawat]['total_group'] += $item->total;
            $tableData[$jenisRawat]['groups'][$groupName]['total_group'] += $item->total;
        }

        // Susunan manual untuk jenis rawat
        $customOrder = ['Rawat Jalan', 'Rawat Inap', 'Lainnya'];
        $tableData = collect($tableData)->sortBy(function ($value, $key) use ($customOrder) {
            return array_search($key, $customOrder);
        })->toArray();

        // Struktur output dengan sorting
        foreach ($tableData as $jenisRawat => &$jenisData) {
            if (!isset($jenisData['groups']) || empty($jenisData['groups'])) {
                Log::warning("Jenis Rawat $jenisRawat tidak memiliki grup data.");
                continue;
            }

            uksort($jenisData['groups'], function ($a, $b) {
                return strcmp($a, $b);
            });

            foreach ($jenisData['groups'] as $groupName => &$groupData) {
                if (!isset($groupData['tests']) || empty($groupData['tests'])) {
                    Log::warning("Grup $groupName pada jenis rawat $jenisRawat tidak memiliki data pemeriksaan.");
                    continue;
                }

                uksort($groupData['tests'], function ($a, $b) {
                    return strcmp($a, $b);
                });
            }
        }

        // Buat response JSON
        $response = [
            'months' => $months,
            'table' => $tableData,
            'grand_total' => $grandTotal
        ];

        return response()->json($response);
    }

    
    
    
}
