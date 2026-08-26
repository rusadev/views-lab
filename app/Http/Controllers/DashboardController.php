<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard.index');
    }

    public function dashboardData(Request $request)
    {
        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');
        $forceRefresh = $request->boolean('refresh', false);

        $startDate = $startDateInput 
            ? Carbon::parse($startDateInput)->startOfDay() 
            : Carbon::today()->startOfDay();

        $endDate = $endDateInput 
            ? Carbon::parse($endDateInput)->endOfDay() 
            : Carbon::today()->endOfDay();

        $cacheKey = 'dashboard_data_' . md5($startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d'));

        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        // Cache response for 5 minutes (300 seconds)
        $data = Cache::remember($cacheKey, 300, function () use ($startDate, $endDate) {
            $oracle = DB::connection('oracle');

            $kunjunganData = $this->kunjunganData($oracle, $startDate, $endDate);
            $permintaanRawatJalanInap = $this->permintaanRawatJalanInap($oracle, $startDate, $endDate);
            $averageTAT = $this->averageTAT($oracle, $startDate, $endDate);
            $distribusiKunjunganPasien = $this->distribusiKunjunganPasien($oracle, $startDate, $endDate);
            $distribusiPemeriksaan = $this->distribusiPemeriksaan($oracle, $startDate, $endDate);
            $distribusiSpesimen = $this->distribusiSpesimen($oracle, $startDate, $endDate);
            $permintaanPemeriksaan = $this->permintaanPemeriksaan($oracle, $startDate, $endDate);
            $permintaanPerWaktu = $this->permintaanPerWaktu($oracle, $startDate, $endDate);
            $nilaiKritis = $this->nilaiKritis($oracle, $startDate, $endDate);
            $statusPemeriksaan = $this->statusPemeriksaan($oracle, $startDate, $endDate);

            return [
                'kunjunganData' => $kunjunganData,
                'permintaanRawatJalanInap' => $permintaanRawatJalanInap,
                'averageTAT' => $averageTAT,
                'distribusiKunjunganPasien' => $distribusiKunjunganPasien,
                'distribusiPemeriksaan' => $distribusiPemeriksaan,
                'distribusiSpesimen' => $distribusiSpesimen,
                'permintaanPemeriksaan' => $permintaanPemeriksaan,
                'permintaanPerWaktu' => $permintaanPerWaktu,
                'nilaiKritis' => $nilaiKritis,
                'statusPemeriksaan' => $statusPemeriksaan,
                'cached_at' => now()->format('H:i:s'),
            ];
        });

        return response()->json($data);
    }

    private function kunjunganData($oracle, $startDate, $endDate)
    {
        return $oracle
            ->table('ord_hdr')
            ->select(
                DB::raw('COUNT(DISTINCT oh_pid) AS kunjungan_pasien'),
                DB::raw('COUNT(oh_tno) AS jumlah_permintaan'),
                DB::raw('COUNT(CASE WHEN oh_ord_status NOT IN (1, 9) AND oh_completed_dt IS NULL THEN 1 END) AS pemeriksaan_di_proses'),
                DB::raw('COUNT(CASE WHEN oh_ord_status = 9 AND oh_completed_dt IS NOT NULL THEN 1 END) AS pemeriksaan_selesai')
            )
            ->whereBetween('oh_trx_dt', [$startDate, $endDate])
            ->first();
    }

    private function statusPemeriksaan($oracle, $startDate, $endDate)
    {
        $permintaan = $oracle
            ->table('ord_hdr as a')
            ->leftJoin('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->select(
                DB::raw('count(*) as total_pemeriksaan'),
                DB::raw("count(CASE WHEN b.od_validate_on IS NOT NULL AND b.od_action_flag = 'R' THEN 1 END) as total_selesai"),
                DB::raw("count(CASE WHEN b.od_action_flag = 'N' AND b.od_validate_on IS NULL THEN 1 END) as total_diproses"),
                DB::raw("count(CASE WHEN b.od_action_flag IS NULL OR b.od_action_flag = ' ' THEN 1 END) as total_belum_dikerjakan")
            )
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->first();

        return [
            'total_pemeriksaan' => $permintaan->total_pemeriksaan ?? 0,
            'total_selesai' => $permintaan->total_selesai ?? 0,
            'total_pending' => 0,
            'total_diproses' => $permintaan->total_diproses ?? 0,
            'total_belum_dikerjakan' => $permintaan->total_belum_dikerjakan ?? 0,
        ];
    }

    private function permintaanRawatJalanInap($oracle, $startDate, $endDate)
    {
        return $oracle
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
    }

    private function averageTAT($oracle, $startDate, $endDate)
    {
        return $oracle
            ->table('ord_hdr as a')
            ->join('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->join('ord_spl as c', function ($join) {
                $join->on('b.od_tno', '=', 'c.os_tno')
                    ->on('b.od_spl_type', '=', 'c.os_spl_type');
            })
            ->leftJoin('test_group as d', 'b.od_test_grp', '=', 'd.tg_code')
            ->selectRaw("
                b.od_test_grp, 
                COALESCE(d.tg_name, b.od_test_grp) as test_group_name,
                COUNT(*) as total_tests,
                ROUND(AVG((b.od_validate_on - c.os_spl_rcvdt) * 1440), 0) as avg_tat_minutes  
            ")
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('b.od_test_grp')
            ->whereNotNull('b.od_validate_on')
            ->whereNotNull('c.os_spl_rcvdt')
            ->whereIn('b.od_test_grp', ['HM', 'KM', 'IM', 'SR', 'UR'])
            ->groupBy('b.od_test_grp', 'd.tg_name')
            ->orderBy('test_group_name')
            ->get();
    }

    private function distribusiKunjunganPasien($oracle, $startDate, $endDate)
    {
        return $oracle
            ->table('ord_hdr')
            ->select(
                DB::raw("CASE 
                            WHEN oh_ptype = 'IN' THEN 'Rawat Inap' 
                            WHEN oh_ptype = 'OP' THEN 'Rawat Jalan' 
                            ELSE 'Lainnya / UGD' 
                        END AS jenis_rawat"),
                DB::raw('COUNT(DISTINCT oh_pid) AS jumlah_pasien')
            )
            ->whereBetween('oh_trx_dt', [$startDate, $endDate])
            ->groupBy(DB::raw("CASE 
                    WHEN oh_ptype = 'IN' THEN 'Rawat Inap' 
                    WHEN oh_ptype = 'OP' THEN 'Rawat Jalan' 
                    ELSE 'Lainnya / UGD' 
                END"))
            ->get();
    }

    private function distribusiPemeriksaan($oracle, $startDate, $endDate)
    {
        $distribusi = $oracle
            ->table('ord_hdr as a')
            ->join('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->leftJoin('test_group as c', 'b.od_test_grp', '=', 'c.tg_code')
            ->select(
                'b.od_test_grp as test_group_code',
                DB::raw('COALESCE(c.tg_name, b.od_test_grp) as test_group_name'),
                DB::raw('count(*) as total')
            )
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('b.od_test_grp')
            ->groupBy('b.od_test_grp', 'c.tg_name')
            ->orderByDesc(DB::raw('count(*)'))
            ->take(6)
            ->get();

        $totalSum = $distribusi->sum('total') ?: 1;
        $distribusi->each(function ($item) use ($totalSum) {
            $item->percentage = number_format(($item->total / $totalSum) * 100, 1) . '%';
        });

        return $distribusi;
    }

    private function distribusiSpesimen($oracle, $startDate, $endDate)
    {
        return $oracle
            ->table('ord_hdr as a')
            ->join('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->join('ord_spl as d', function ($join) {
                $join->on('b.od_tno', '=', 'd.os_tno')
                    ->on('b.od_spl_type', '=', 'd.os_spl_type');
            })
            ->leftJoin('sample_type as e', 'd.os_spl_type', '=', 'e.st_code')
            ->selectRaw('
                COUNT(DISTINCT d.os_tno) as total, 
                d.os_spl_type as specimen_type, 
                COALESCE(e.st_name, d.os_spl_type) as sample
            ')
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->groupBy('d.os_spl_type', 'e.st_name')
            ->orderByDesc('total')
            ->take(6)
            ->get();
    }

    private function permintaanPemeriksaan($oracle, $startDate, $endDate)
    {
        return $oracle
            ->table('ord_hdr as a')
            ->join('ord_dtl as b', function ($join) {
                $join->on('a.oh_tno', '=', 'b.od_tno')
                    ->where('b.od_order_item', '=', 'Y');
            })
            ->leftJoin('test_item as c', 'b.od_testcode', '=', 'c.ti_code')
            ->select(
                DB::raw('COALESCE(c.ti_name, b.od_testcode) as pemeriksaan'),
                DB::raw('count(*) as total'), 
                DB::raw('count(CASE WHEN b.od_validate_on IS NULL THEN 1 END) as pemeriksaan_belum_selesai'), 
                DB::raw('count(CASE WHEN b.od_validate_on IS NOT NULL THEN 1 END) as pemeriksaan_selesai') 
            )
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->groupBy('c.ti_name', 'b.od_testcode')
            ->orderByDesc(DB::raw('count(*)')) 
            ->take(8) 
            ->get();
    }

    private function permintaanPerWaktu($oracle, $startDate, $endDate)
    {
        return $oracle
            ->table('ord_hdr')
            ->selectRaw("
                TO_CHAR(oh_trx_dt, 'HH24') || ':00' AS hour,
                COUNT(CASE WHEN oh_ptype = 'OP' THEN 1 END) AS rajal,
                COUNT(CASE WHEN oh_ptype = 'IN' THEN 1 END) AS ranap,
                COUNT(*) AS total_keseluruhan
            ")
            ->whereBetween('oh_trx_dt', [$startDate, $endDate])
            ->groupByRaw("TO_CHAR(oh_trx_dt, 'HH24') || ':00'")
            ->orderByRaw("TO_CHAR(oh_trx_dt, 'HH24') || ':00'")
            ->get();
    }

    private function nilaiKritis($oracle, $startDate, $endDate)
    {
        $rows = $oracle
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
                'ti.ti_name',
                'hc.clinic_desc'
            )
            ->whereIn('od.od_tr_flag', ['LL', 'HH'])
            ->whereBetween('oh.oh_trx_dt', [$startDate, $endDate])
            ->orderBy('oh.oh_update_on', 'desc')
            ->take(30)
            ->get();

        foreach ($rows as $row) {
            $row->detail_url = route('klinik.detail', ['labno' => Hashids::encode($row->oh_tno)]);
        }

        return $rows;
    }
}
