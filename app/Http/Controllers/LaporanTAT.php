<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanTAT extends Controller
{
    //
    public function index()
    {
        return view('laporan.tat.index');
    }

    public function getData(Request $request)
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::create(2024, 1, 1, 0, 0, 0);

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::create(2024, 1, 10, 23, 59, 59);
        $oracleConnection = DB::connection('oracle');

        $averageTATByGroup = $oracleConnection
            ->table('ord_hdr as a')
            ->leftJoin('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
                    // ->where('b.od_action_flag', '=', 'R');
            })
            ->leftJoin('ord_spl as c', function ($join) {
                $join->on('b.od_tno', '=', 'c.os_tno')
                    ->on('b.od_spl_type', '=', 'c.os_spl_type');
            })
            ->leftJoin('test_group as d', 'b.od_test_grp', '=', 'd.tg_code')
            ->selectRaw("
                b.od_test_grp, 
                d.tg_name as test_group_name,
                COUNT(*) as total_tests,
                CASE 
                    WHEN FLOOR(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440) / 60) > 0 
                    THEN FLOOR(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440) / 60) || ' jam ' || MOD(ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440)), 60) || ' menit'
                    ELSE MOD(ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440)), 60) || ' menit'
                END as avg_tat_time,
                ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440), 0) as avg_tat_minutes  
            ")
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('b.od_test_grp')
            ->whereNotNull('b.od_validate_on')
            ->whereNotNull('c.os_spl_rcvdt')
            ->groupBy('b.od_test_grp', 'd.tg_name')
            ->orderBy('d.tg_name')
            ->get();

        $averageTATByTest = $oracleConnection
            ->table('ord_hdr as a')
            ->leftJoin('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->leftJoin('ord_spl as c', function ($join) {
                $join->on('b.od_tno', '=', 'c.os_tno')
                    ->on('b.od_spl_type', '=', 'c.os_spl_type');
            })
            ->leftJoin('test_group as d', 'b.od_test_grp', '=', 'd.tg_code')
            ->leftJoin('test_item as e', 'b.od_testcode', '=', 'e.ti_code') 
            ->selectRaw("
            b.od_testcode, 
            e.ti_name as test_name,
            b.od_test_grp,
            d.tg_name as test_group_name,
            COUNT(*) as total_tests,
            CASE 
                WHEN FLOOR(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440) / 60) > 0 
                THEN FLOOR(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440) / 60) || ' jam ' || MOD(ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440)), 60) || ' menit'
                ELSE MOD(ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440)), 60) || ' menit'
            END as avg_tat_time,
            ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440), 0) as avg_tat_minutes  
        ")
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('b.od_testcode')
            ->whereNotNull('b.od_validate_on')
            ->whereNotNull('c.os_spl_rcvdt')
            ->groupBy('b.od_testcode', 'e.ti_name', 'b.od_test_grp', 'd.tg_name')
            ->orderBy('e.ti_name')
            ->get();

        return response()->json([
            'averageTATByGroup' => $averageTATByGroup,
            'averageTATByTest' => $averageTATByTest
        ]);
    }
}
