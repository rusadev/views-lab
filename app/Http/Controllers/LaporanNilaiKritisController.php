<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanNilaiKritisController extends Controller
{

    public function index ()
    {
        return view ('laporan.nilai-kritis.index');
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
    
        $nilaiKritis = $oracleConnection
        ->table('ord_dtl as od')
        ->join('ord_hdr as oh', 'od.od_tno', '=', 'oh.oh_tno')
        ->leftJoin('hfclinic as hc', 'oh.oh_clinic_code', '=', 'hc.clinic_code')
        ->leftJoin('test_item as ti', 'od.od_testcode', '=', 'ti.ti_code')
        ->select(
            'oh.oh_trx_dt',
            'oh.oh_tno',
            'oh.oh_pid',
            'oh.oh_apid',
            'oh.oh_last_name',
            'oh.oh_dname',
            'hc.clinic_desc',
            'od.od_tr_val',
            'od.od_tr_flag',
            'od.od_update_on',
            'ti.ti_name'
        )
        ->whereIn('od.od_tr_flag', ['LL', 'HH'])
        ->whereBetween('oh.oh_trx_dt', [$startDate, $endDate])
        ->orderBy('oh.oh_trx_dt', 'asc')
        ->get();

        return $nilaiKritis;
    }
}
