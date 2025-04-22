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
    
        $oracle = DB::connection('oracle');
    
        $buildQuery = function ($status, $type) use ($oracle, $startDate, $endDate) {
            $select = [
                'a.oh_pri as priority',
                'a.oh_ptype as jenis_rawat',
                DB::raw("CASE 
                    WHEN a.oh_ptype = 'IN' THEN 'Rawat Inap' 
                    WHEN a.oh_ptype = 'OP' THEN 'Rawat Jalan' 
                    ELSE 'Lainnya' 
                END as jenis_rawat_nama"),
                DB::raw("ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440), 0) as avg_tat_minutes"),
                DB::raw("CASE 
                    WHEN FLOOR(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440) / 60) > 0 
                    THEN FLOOR(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440) / 60) || ' jam ' || MOD(ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440)), 60) || ' menit'
                    ELSE MOD(ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440)), 60) || ' menit'
                END as avg_tat_time"),
                DB::raw("COUNT(*) as total_tests")
            ];
    
            if ($type === 'group') {
                $select[] = 'b.od_test_grp as code';
                $select[] = 'd.tg_name as name';
                $groupBy = ['a.oh_pri', 'a.oh_ptype', 'b.od_test_grp', 'd.tg_name'];
            } else {
                $select[] = 'b.od_testcode as code';
                $select[] = 'e.ti_name as name';
                $groupBy = ['a.oh_pri', 'a.oh_ptype', 'b.od_testcode', 'e.ti_name'];
            }
    
            $query = $oracle
                ->table('ord_hdr as a')
                ->leftJoin('ord_dtl as b', function ($join) {
                    $join->on('a.oh_tno', '=', 'b.od_tno')
                        ->where('b.od_order_item', '=', 'Y');
                })
                ->leftJoin('ord_spl as c', function ($join) {
                    $join->on('b.od_tno', '=', 'c.os_tno')
                        ->on('b.od_spl_type', '=', 'c.os_spl_type');
                });
    
            if ($type === 'group') {
                $query->leftJoin('test_group as d', 'b.od_test_grp', '=', 'd.tg_code');
            } else {
                $query->leftJoin('test_group as d', 'b.od_test_grp', '=', 'd.tg_code');
                $query->leftJoin('test_item as e', 'b.od_testcode', '=', 'e.ti_code');
            }
    
            return $query
                ->select($select)
                ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
                ->where('a.oh_pri', $status)
                ->whereNotNull($type === 'group' ? 'b.od_test_grp' : 'b.od_testcode')
                ->whereNotNull('b.od_validate_on')
                ->whereNotNull('c.os_spl_rcvdt')
                ->groupBy($groupBy)
                ->get();
        };
    
        // Ambil data Cito dan Non-Cito
        $data = [
            'cito' => [
                'by_group' => $buildQuery('U', 'group'),
                'by_test' => $buildQuery('U', 'test'),
            ],
            'non_cito' => [
                'by_group' => $buildQuery('R', 'group'),
                'by_test' => $buildQuery('R', 'test'),
            ]
        ];
    
        // Fungsi untuk menggabungkan data dan menghindari duplikasi nama
        $pivotData = function ($dataset) {
            $pivot = [];
    
            foreach ($dataset as $row) {
                $key = $row->code;
    
                if (!isset($pivot[$key])) {
                    $pivot[$key] = [
                        'code' => $row->code,
                        'name' => $row->name,
                        'rawat_inap' => null,
                        'rawat_jalan' => null,
                    ];
                }
    
                // Pastikan nama konsisten untuk setiap kode
                if ($pivot[$key]['name'] !== $row->name) {
                    // Jika tidak konsisten, overwrite untuk menjaga akurasi
                    $pivot[$key]['name'] = $row->name;
                }
    
                if ($row->jenis_rawat === 'IN') {
                    $pivot[$key]['rawat_inap'] = [
                        'tat_minutes' => (int)$row->avg_tat_minutes,
                        'tat_formatted' => $row->avg_tat_time,
                        'total_tests' => $row->total_tests,
                    ];
                } elseif ($row->jenis_rawat === 'OP') {
                    $pivot[$key]['rawat_jalan'] = [
                        'tat_minutes' => (int)$row->avg_tat_minutes,
                        'tat_formatted' => $row->avg_tat_time,
                        'total_tests' => $row->total_tests,
                    ];
                }
            }
    
            return array_values($pivot); // reset index
        };
    
        return response()->json([
            'cito' => [
                'by_group' => $pivotData($data['cito']['by_group']),
                'by_test' => $pivotData($data['cito']['by_test']),
            ],
            'non_cito' => [
                'by_group' => $pivotData($data['non_cito']['by_group']),
                'by_test' => $pivotData($data['non_cito']['by_test']),
            ]
        ]);
    }
    
    
    
    
}
