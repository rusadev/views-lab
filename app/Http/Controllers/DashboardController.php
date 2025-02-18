<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }

    public function dashboardData (Request $request) 
    {

        $startDate = $request->input('start_date') 
        ? Carbon::parse($request->input('start_date'))->startOfDay() 
        : Carbon::today()->startOfDay();
    
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date'))->endOfDay() 
            : Carbon::today()->endOfDay();

        $oracleConnection = DB::connection('oracle');

        $kunjunganData = $this->kunjunganData($oracleConnection, $startDate, $endDate);
        $permintaanRawatJalanInap = $this->permintaanRawatJalanInap($oracleConnection, $startDate, $endDate);
        $averageTAT = $this->averageTAT($oracleConnection, $startDate, $endDate);
        $distribusiKunjunganPasien = $this->distribusiKunjunganPasien($oracleConnection, $startDate, $endDate);
        $distribusiPemeriksaan = $this->distribusiPemeriksaan($oracleConnection, $startDate, $endDate);
        $distribusiSpesimen = $this->distribusiSpesimen($oracleConnection, $startDate, $endDate);
        $permintaanPemeriksaan = $this->permintaanPemeriksaan($oracleConnection, $startDate, $endDate);
        $permintaanPerWaktu = $this->permintaanPerWaktu($oracleConnection, $startDate, $endDate);
        $nilaiKritis = $this->nilaiKritis($oracleConnection, $startDate, $endDate);
        $statusPemeriksaan = $this->statusPemeriksaan($oracleConnection, $startDate, $endDate);

        // Mengembalikan respons JSON
        return response()->json([
            'kunjunganData' => $kunjunganData,
            'permintaanRawatJalanInap' => $permintaanRawatJalanInap,
            'averageTAT' => $averageTAT,
            'distribusiKunjunganPasien' => $distribusiKunjunganPasien,
            'distribusiPemeriksaan' => $distribusiPemeriksaan,
            'distribusiSpesimen' => $distribusiSpesimen,
            'permintaanPemeriksaan' => $permintaanPemeriksaan,
            'permintaanPerWaktu' => $permintaanPerWaktu,
            'nilaiKritis' => $nilaiKritis,
            'statusPemeriksaan' => $statusPemeriksaan
        ]);
    }

    private function kunjunganData($oracleConnection, $startDate, $endDate)
    {
        $kunjunganData = $oracleConnection
            ->table('ord_hdr')
            ->select(
                DB::raw('COUNT(DISTINCT oh_pid) AS kunjungan_pasien'),
                DB::raw('COUNT(oh_tno) AS jumlah_permintaan'),
                DB::raw('COUNT(CASE WHEN oh_ord_status NOT IN (1, 9) AND oh_completed_dt IS NULL THEN 1 END) AS pemeriksaan_di_proses'),
                DB::raw('COUNT(CASE WHEN oh_ord_status = 9 AND oh_completed_dt IS NOT NULL THEN 1 END) AS pemeriksaan_selesai')
            )
            ->whereBetween('oh_trx_dt', [$startDate, $endDate])
            ->first();

        return $kunjunganData;
    }

    private function statusPemeriksaan($oracleConnection, $startDate, $endDate)
    {

        $permintaanPemeriksaan = $oracleConnection
            ->table('ord_hdr as a')
            ->leftJoin('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->leftJoin('ord_spl as d', function ($join) {
                $join->on('b.od_tno', '=', 'd.os_tno')
                    ->on('b.od_spl_type', '=', 'd.os_spl_type');
            })
            ->select(
                DB::raw("count(*) as total_pemeriksaan"),
                DB::raw("count(CASE WHEN b.od_validate_on IS NOT NULL AND b.od_action_flag = 'R' THEN 1 END) as total_selesai"),
                DB::raw("count(CASE WHEN d.os_spl_rj_dt IS NOT NULL THEN 1 END) as total_pending"),
                DB::raw("count(CASE WHEN d.os_spl_rcvdt IS NOT NULL AND b.od_action_flag = 'N' THEN 1 END) as total_diproses"),
                DB::raw("count(CASE WHEN d.os_spl_rcvdt IS NULL AND b.od_action_flag = 'N' THEN 1 END) as total_belum_dikerjakan") // Tambahan kondisi
            )
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->first();
        
        return [
            'total_pemeriksaan' => $permintaanPemeriksaan->total_pemeriksaan,
            'total_selesai' => $permintaanPemeriksaan->total_selesai,
            'total_pending' => $permintaanPemeriksaan->total_pending,
            'total_diproses' => $permintaanPemeriksaan->total_diproses,
            'total_belum_dikerjakan' => $permintaanPemeriksaan->total_belum_dikerjakan, // Tambahan return
        ];
    }
    
    private function permintaanRawatJalanInap($oracleConnection, $startDate, $endDate)
    {
        $rawatJalanInapData = $oracleConnection
            ->table('ord_hdr')
            ->select(
                DB::raw("
                    CASE 
                        WHEN oh_ptype = 'IN' THEN 'ranap' 
                        WHEN oh_ptype = 'OP' THEN 'rajal' 
                        ELSE 'lainnya' 
                    END AS jenis_rawat
                "),
                DB::raw('COUNT(DISTINCT oh_pid) AS jumlah_pasien'),  
                DB::raw('COUNT(CASE WHEN oh_completed_dt IS NOT NULL THEN 1 END) AS pemeriksaan_selesai'),
                DB::raw('COUNT(CASE WHEN oh_completed_dt IS NULL THEN 1 END) AS pemeriksaan_belum_selesai'),
                DB::raw('COUNT(*) AS total_pemeriksaan')
            )
            ->whereBetween('oh_trx_dt', [$startDate, $endDate])
            ->groupBy('oh_ptype')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->jenis_rawat => $item];
            });
        
        return $rawatJalanInapData;
    

    }

    private function averageTAT($oracleConnection, $startDate, $endDate)
    {
        $averageTAT = $oracleConnection
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
            ->whereIn('b.od_test_grp', ['HM', 'KM', 'IM', 'SR', 'UR'])
            ->groupBy('b.od_test_grp', 'd.tg_name')
            ->orderBy('tg_name')
            ->get();
            
        return $averageTAT;
    }
    
    private function distribusiKunjunganPasien($oracleConnection, $startDate, $endDate)
    {
        $distribusiRanapRajal = $oracleConnection
            ->table('ord_hdr')
            ->select(
                DB::raw("CASE 
                            WHEN oh_ptype = 'IN' THEN 'Rawat Inap' 
                            WHEN oh_ptype = 'OP' THEN 'Rawat Jalan' 
                            ELSE 'Lainnya' 
                        END AS jenis_rawat"),
                DB::raw('COUNT(DISTINCT oh_pid) AS jumlah_pasien')
            )
            ->whereBetween('oh_trx_dt', [$startDate, $endDate])
            ->groupBy(DB::raw("CASE 
                    WHEN oh_ptype = 'IN' THEN 'Rawat Inap' 
                    WHEN oh_ptype = 'OP' THEN 'Rawat Jalan' 
                    ELSE 'Lainnya' 
                END"))
            ->get();
        
        return $distribusiRanapRajal;
    
    }

    private function distribusiPemeriksaan($oracleConnection, $startDate, $endDate)
    {
        $distribusiPemeriksaan = $oracleConnection
            ->table('ord_hdr as a')
            ->leftJoin('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->leftJoin('test_group as c', 'b.od_test_grp', '=', 'c.tg_code')
            ->select(
                'b.od_test_grp as test_group_code',
                'c.tg_name as test_group_name',
                DB::raw('count(*) as total'),
                DB::raw('round((count(*) / sum(count(*)) over()) * 100, 2) as percentage') 
            )
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('b.od_test_grp')
            ->groupBy('b.od_test_grp', 'c.tg_name')
            ->orderByDesc(DB::raw('total'))
            ->take(5)
            ->get();
        
        $distribusiPemeriksaan->each(function ($item) {
            $item->percentage = number_format($item->percentage, 2) . '%'; 
        });
        
        return $distribusiPemeriksaan;
    }

    private function distribusiSpesimen($oracleConnection, $startDate, $endDate)
    {
        $distribusiSpesimen = $oracleConnection
            ->table('ord_hdr as a')
            ->leftJoin('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->leftJoin('ord_spl as d', function ($join) {
                $join->on('b.od_tno', '=', 'd.os_tno')
                    ->on('b.od_spl_type', '=', 'd.os_spl_type');
            })
            ->leftJoin('sample_type as e', 'd.os_spl_type', '=', 'e.st_code')
            ->selectRaw('
                COUNT(DISTINCT d.os_tno) as total, 
                d.os_spl_type as specimen_type, 
                e.st_name as sample
            ')
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->groupBy('d.os_spl_type', 'e.st_name')
            ->orderBy('total', 'desc')
            ->take(7)
            ->get();

        return $distribusiSpesimen;
    }

    private function permintaanPemeriksaan($oracleConnection, $startDate, $endDate)
    {
        $permintaanPemeriksaan = $oracleConnection
            ->table('ord_hdr as a')
            ->leftJoin('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->leftJoin('test_item as c', 'b.od_testcode','=','c.ti_code')
            ->select(
                'c.ti_name as pemeriksaan',
                DB::raw('count(*) as total'), 
                DB::raw('round((count(*) / sum(count(*)) over()) * 100, 2) as percentage'),
                DB::raw('count(CASE WHEN b.od_validate_on IS NULL THEN 1 END) as pemeriksaan_belum_selesai'), 
                DB::raw('count(CASE WHEN b.od_validate_on IS NOT NULL THEN 1 END) as pemeriksaan_selesai') 
            )
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->groupBy('c.ti_name')
            ->orderByDesc(DB::raw('total')) 
            ->take(10) 
            ->get();

        return $permintaanPemeriksaan;

    }

    private function permintaanPerwaktu($oracleConnection, $startDate, $endDate)
    {
        $permintaanPerwaktu = $oracleConnection
            ->table('ord_hdr')
            ->selectRaw("
                TO_CHAR(oh_trx_dt, 'HH24') || ':00' AS hour,  -- Format to HH24:00
                COUNT(CASE WHEN oh_ptype = 'OP' THEN 1 END) AS rajal,
                COUNT(CASE WHEN oh_ptype = 'IN' THEN 1 END) AS ranap,
                COUNT(*) AS total_keseluruhan
            ")
            ->whereBetween('oh_trx_dt', [$startDate, $endDate])
            ->groupByRaw("TO_CHAR(oh_trx_dt, 'HH24') || ':00'")
            ->orderByRaw("TO_CHAR(oh_trx_dt, 'HH24') || ':00'")
            ->get();
        
        return $permintaanPerwaktu;
    
    }

    private function nilaiKritis($oracleConnection, $startDate, $endDate)
    {
        $nilaiKritis = $oracleConnection
        ->table('ord_dtl as od')
        ->join('ord_hdr as oh', 'od.od_tno', '=', 'oh.oh_tno')
        ->leftJoin('hfclinic as hc', 'oh.oh_clinic_code', '=', 'hc.clinic_code')
        ->leftJoin('test_item as ti', 'od.od_testcode', '=', 'ti.ti_code')
        ->select(
            'oh.oh_update_on',
            'oh.oh_tno',
            'oh.oh_pid',
            'oh.oh_last_name',
            'od.od_tr_val',
            'od.od_tr_flag',
            'ti.ti_name'
        )
        ->whereIn('od.od_tr_flag', ['LL', 'HH'])
        ->whereBetween('oh.oh_trx_dt', [$startDate, $endDate])
        ->orderBy('oh.oh_update_on', 'desc')
        ->get();

        return $nilaiKritis;
    
    }
}
