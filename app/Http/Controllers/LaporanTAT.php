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
        
        // Helper function to create query per status and care type
        $queryByStatusAndCareType = function ($status) use ($oracleConnection, $startDate, $endDate) {
            // Query by Group with Care Type (Rawat Inap / Rawat Jalan)
            $byGroup = $oracleConnection
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
                ->select(
                    'a.oh_pri as priority',
                    'b.od_test_grp',
                    'd.tg_name as test_group_name',
                    DB::raw("COUNT(*) as total_tests"),
                    DB::raw("CASE 
                        WHEN FLOOR(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440) / 60) > 0 
                        THEN FLOOR(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440) / 60) || ' jam ' || MOD(ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440)), 60) || ' menit'
                        ELSE MOD(ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440)), 60) || ' menit'
                    END as avg_tat_time"),
                    DB::raw("ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440), 0) as avg_tat_minutes"),
                    DB::raw("CASE 
                        WHEN a.oh_ptype = 'IN' THEN 'Rawat Inap' 
                        WHEN a.oh_ptype = 'OP' THEN 'Rawat Jalan' 
                        ELSE 'Lainnya' 
                    END AS jenis_rawat")
                )
                ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
                ->where('a.oh_pri', $status)
                ->whereNotNull('b.od_test_grp')
                ->whereNotNull('b.od_validate_on')
                ->whereNotNull('c.os_spl_rcvdt')
                ->groupBy('a.oh_pri', 'b.od_test_grp', 'd.tg_name', 'a.oh_ptype')
                ->orderBy('d.tg_name')
                ->get();
        
            // Query by Test with Care Type (Rawat Inap / Rawat Jalan)
            $byTest = $oracleConnection
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
                ->select(
                    'a.oh_pri as priority',
                    'b.od_testcode',
                    'e.ti_name as test_name',
                    'b.od_test_grp',
                    'd.tg_name as test_group_name',
                    DB::raw("COUNT(*) as total_tests"),
                    DB::raw("CASE 
                        WHEN FLOOR(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440) / 60) > 0 
                        THEN FLOOR(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440) / 60) || ' jam ' || MOD(ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440)), 60) || ' menit'
                        ELSE MOD(ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440)), 60) || ' menit'
                    END as avg_tat_time"),
                    DB::raw("ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440), 0) as avg_tat_minutes"),
                    DB::raw("CASE 
                        WHEN a.oh_ptype = 'IN' THEN 'Rawat Inap' 
                        WHEN a.oh_ptype = 'OP' THEN 'Rawat Jalan' 
                        ELSE 'Lainnya' 
                    END AS jenis_rawat")
                )
                ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
                ->where('a.oh_pri', $status)
                ->whereNotNull('b.od_testcode')
                ->whereNotNull('b.od_validate_on')
                ->whereNotNull('c.os_spl_rcvdt')
                ->groupBy('a.oh_pri', 'b.od_testcode', 'e.ti_name', 'b.od_test_grp', 'd.tg_name', 'a.oh_ptype')
                ->orderBy('e.ti_name')
                ->get();
        
            return [
                'by_group' => $byGroup,
                'by_test' => $byTest
            ];
        };
        
        // Query for Cito (Urgent) and Non-Cito (Routine) statuses
        $citoData = $queryByStatusAndCareType('U'); // Cito/Urgent
        $nonCitoData = $queryByStatusAndCareType('R'); // Non-Cito/Routine
        
        // Group by Care Type for Cito and Non-Cito
        $groupByCareType = function ($data) {
            $groupedData = [
                'rawat_inap' => [
                    'by_group' => [],
                    'by_test' => []
                ],
                'rawat_jalan' => [
                    'by_group' => [],
                    'by_test' => []
                ]
            ];
    
            foreach ($data['by_group'] as $item) {
                if ($item->jenis_rawat === 'Rawat Inap') {
                    $groupedData['rawat_inap']['by_group'][] = $item;
                } else {
                    $groupedData['rawat_jalan']['by_group'][] = $item;
                }
            }
    
            foreach ($data['by_test'] as $item) {
                if ($item->jenis_rawat === 'Rawat Inap') {
                    $groupedData['rawat_inap']['by_test'][] = $item;
                } else {
                    $groupedData['rawat_jalan']['by_test'][] = $item;
                }
            }
    
            return $groupedData;
        };
    
        // Group data by care type
        $groupedCitoData = $groupByCareType($citoData);
        $groupedNonCitoData = $groupByCareType($nonCitoData);
    
        return response()->json([
            'cito' => $groupedCitoData,
            'non_cito' => $groupedNonCitoData
        ]);
    }
    
    
}
