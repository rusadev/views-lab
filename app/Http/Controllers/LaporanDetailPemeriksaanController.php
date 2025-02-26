<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanDetailPemeriksaanController extends Controller
{
    //
    public function index ()
    {
        return view ('laporan.detail-pemeriksaan.index');
    }

    public function getData(Request $request)
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date'))->startOfDay() 
            : Carbon::create(2024, 1, 1, 0, 0, 0);

        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date'))->endOfDay() 
            : Carbon::create(2024, 1, 31, 23, 59, 59);

        $rawData = DB::connection('oracle')
            ->table('ord_hdr as a')
            ->leftJoin('ord_dtl as b', 'a.oh_tno', '=', 'b.od_tno')
            ->leftJoin('test_group as c', 'b.od_test_grp', '=', 'c.tg_code')
            ->leftJoin('test_item as d', 'b.od_testcode', '=', 'd.ti_code')
            ->select([
                'a.oh_tno as transaction_number',
                'a.oh_trx_dt as transaction_date',
                'a.oh_pid as patient_id',
                'a.oh_apid as alternative_patient_id',
                'a.oh_last_name as patient_name',
                'a.oh_pataddr1 as address_1',
                'a.oh_pataddr2 as address_2',
                'a.oh_pataddr3 as address_3',
                'a.oh_pataddr4 as address_4',
                'a.oh_sex as gender',
                'a.oh_bod as date_of_birth',
                'b.od_order_ti as order_type',
            ])
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('b.od_test_grp')
            ->whereIn('b.od_order_ti', ['FBC', 'GLUS']) // Hanya filter yang dibutuhkan
            ->groupBy(
                'a.oh_tno', 'a.oh_trx_dt', 'a.oh_pid', 'a.oh_apid', 'a.oh_last_name', 
                'a.oh_pataddr1', 'a.oh_pataddr2', 'a.oh_pataddr3', 'a.oh_pataddr4', 
                'a.oh_sex', 'a.oh_bod', 'b.od_order_ti'
            )
            ->orderBy('a.oh_trx_dt', 'ASC')
            ->get();

        return response()->json($rawData);
    }
}
