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

        $oracleConnection = DB::connection('oracle');

        $rawData = $oracleConnection
            ->table('ord_hdr as a')
            ->leftJoin('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->leftJoin('test_group as c', 'b.od_test_grp', '=', 'c.tg_code')
            ->leftJoin('test_item as d', 'b.od_testcode', '=', 'd.ti_code')
            ->select(
                DB::raw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM') as year_month"),
                'b.od_test_grp as test_group_code',
                DB::raw("COALESCE(c.tg_name, 'Tidak Diketahui') as test_group_name"), // Hindari NULL
                'b.od_testcode as test_code',
                DB::raw("COALESCE(d.ti_name, 'Tidak Diketahui') as test_name"), // Hindari NULL
                DB::raw('COUNT(*) as total')
            )
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('b.od_test_grp')
            ->groupBy(DB::raw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM')"), 'b.od_test_grp', DB::raw("COALESCE(c.tg_name, 'Tidak Diketahui')"), 'b.od_testcode', DB::raw("COALESCE(d.ti_name, 'Tidak Diketahui')"))
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
        $groupTotals = [];

        foreach ($rawData as $item) {
            $yearMonth = $item->year_month;
            $groupName = $item->test_group_name ?? 'Tidak Diketahui';
            $testName = $item->test_name ?? 'Tidak Diketahui';

            // Inisialisasi grup jika belum ada
            if (!isset($tableData[$groupName])) {
                $tableData[$groupName] = [
                    'name' => $groupName,
                    'totals' => array_fill_keys($months->toArray(), 0),
                    'tests' => []
                ];
                $tableData[$groupName]['totals']['total'] = 0;
            }

            // Inisialisasi pemeriksaan dalam grup jika belum ada
            if (!isset($tableData[$groupName]['tests'][$testName])) {
                $tableData[$groupName]['tests'][$testName] = [
                    'name' => $testName,
                    'totals' => array_fill_keys($months->toArray(), 0)
                ];
                $tableData[$groupName]['tests'][$testName]['totals']['total'] = 0;
            }

            // Tambahkan jumlah per bulan
            $tableData[$groupName]['totals'][$yearMonth] += $item->total;
            $tableData[$groupName]['tests'][$testName]['totals'][$yearMonth] += $item->total;

            // Hitung total keseluruhan per grup
            $tableData[$groupName]['totals']['total'] += $item->total;
            $tableData[$groupName]['tests'][$testName]['totals']['total'] += $item->total;

            // Hitung total keseluruhan per bulan
            $grandTotal[$yearMonth] += $item->total;
            $grandTotal['total'] += $item->total;
        }

        // Hitung total per grup
        foreach ($tableData as $groupName => $group) {
            $groupTotals[$groupName] = [
                'name' => $groupName,
                'totals' => $group['totals']
            ];
        }

        // Debugging: Periksa apakah data hilang
        if (empty($tableData)) {
            Log::error('Data kelompok pemeriksaan hilang setelah proses pengolahan.');
        } else {
            Log::info('Data kelompok pemeriksaan setelah proses:', $tableData);
        }

        // Pastikan grup masih ada setelah sorting
        uksort($tableData, function ($a, $b) {
            return strcmp($a, $b);
        });

        foreach ($tableData as $groupName => &$group) {
            if (!isset($group['tests']) || empty($group['tests'])) {
                Log::warning("Kelompok pemeriksaan $groupName tidak memiliki data pemeriksaan.");
                continue;
            }
            uksort($group['tests'], function ($a, $b) {
                return strcmp($a, $b);
            });
        }
        unset($group);

        // Buat response JSON
        $response = [
            'months' => $months,
            'table' => $tableData,
            'group_totals' => array_values($groupTotals),
            'grand_total' => $grandTotal
        ];

        return response()->json($response);
    }
}
