<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Vinkla\Hashids\Facades\Hashids;
use Yajra\DataTables\DataTables;

class LaboratoriumKlinikController extends Controller
{
    //

    public function index()
    {
        $ruangans = DB::connection('oracle')->table('hfclinic')->where('CD_CTL_FLAG5', 'N')->get();
        return view('result-lab.klinik.index', compact('ruangans'));
    }

    public function getOrder(Request $request)
    {
        $searchType = $request->input('search_type');
        $rmNumber = $request->input('rm_number');
        $ruangan = $request->input('ruangan');
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;

        $query = DB::connection('oracle')
            ->table('ord_hdr as a')
            ->leftJoin('hfclinic as b', 'a.oh_clinic_code', '=', 'b.clinic_code')
            ->leftJoin('ord_dtl as c', function ($join) {
                $join->on('a.oh_tno', '=', 'c.od_tno')
                    ->where('c.od_order_item', '=', 'Y');
            })
            ->select(
                'a.oh_trx_dt',
                'a.oh_tno',
                'a.oh_ono',
                'a.oh_pid',
                'a.oh_last_name',
                'a.oh_dname',
                'b.clinic_code',
                'b.clinic_desc',
                'a.oh_ord_status',
                'a.oh_completed_dt',
                'c.od_testcode',
                'c.od_validate_on'
            )
            ->orderBy('a.oh_trx_dt', 'desc');

        if ($searchType === 'rm' && !empty($rmNumber)) {
            $query->where('a.oh_pid', $rmNumber);
        } else {
            if (!empty($ruangan)) {
                $query->where('b.clinic_code', 'LIKE', $ruangan);
            }
            if (!empty($startDate) && !empty($endDate)) {
                $query->whereBetween('a.oh_trx_dt', [$startDate, $endDate]);
            }
        }

        $results = $query->get();

        $groupedData = [];
        foreach ($results as $row) {
            $tno = $row->oh_tno;
            if (!isset($groupedData[$tno])) {
                $groupedData[$tno] = [
                    'oh_trx_dt'      => $row->oh_trx_dt,
                    'oh_tno'         => $tno,
                    'oh_ono'         => $row->oh_ono,
                    'oh_pid'         => $row->oh_pid,
                    'oh_last_name'   => $row->oh_last_name,
                    'oh_dname'       => $row->oh_dname,
                    'clinic_code'    => $row->clinic_code,
                    'clinic_desc'    => $row->clinic_desc,
                    'oh_ord_status'  => $row->oh_ord_status,
                    'oh_completed_dt' => $row->oh_completed_dt,
                    'details'        => [],
                    'final_status'   => 'Belum tersedia'
                ];
            }

            if (!empty($row->od_testcode)) {
                $groupedData[$tno]['details'][] = [
                    'od_testcode'   => $row->od_testcode,
                    'od_validate_on' => $row->od_validate_on
                ];
            }
        }

        foreach ($groupedData as &$data) {
            $details = $data['details'];

            if (!empty($details)) {
                $validatedCount = count(array_filter($details, fn($detail) => !empty($detail['od_validate_on'])));
                $totalDetails = count($details);

                if ($validatedCount === 0) {
                    $data['final_status'] = 'Belum tersedia';
                } elseif ($validatedCount < $totalDetails) {
                    $data['final_status'] = 'Hasil Sebagian';
                } else {
                    $data['final_status'] = 'Selesai';
                }
            }
        }

        return DataTables::of(collect(array_values($groupedData)))
            ->addColumn('oh_ord_status', function ($row) {
                switch ($row['final_status']) {
                    case 'Belum tersedia':
                        return '<button class="px-3 font-medium py-1 bg-gradient-to-r from-gray-400 to-gray-600 text-xs text-white rounded-md shadow-sm opacity-50 cursor-not-allowed" disabled>
                            <i class="fas fa-times-circle mr-1"></i> Belum Tersedia
                        </button>';
                    case 'Hasil Sebagian':
                        return '<a href="' . route('klinik.detail', ['labno' => Hashids::encode($row['oh_tno'])]) . '" target="_blank"
                            class="px-3 font-medium py-1 bg-gradient-to-r from-yellow-400 to-yellow-600 text-xs text-white rounded-md shadow-sm hover:from-yellow-500 hover:to-yellow-700 transition-all duration-300">
                            <i class="fas fa-hourglass-half mr-1"></i> Hasil Sebagian
                        </a>';
                    case 'Selesai':
                        return '<a href="' . route('klinik.detail', ['labno' => Hashids::encode($row['oh_tno'])]) . '" target="_blank"
                            class="px-3 font-medium py-1 bg-gradient-to-r from-green-500 to-green-700 text-xs text-white rounded-md shadow-sm hover:from-green-600 hover:to-green-800 transition-all duration-300">
                            <i class="fas fa-check-circle mr-1"></i> Selesai
                        </a>';
                    default:
                        return '<span class="px-3 font-medium py-1 bg-gradient-to-r from-gray-200 to-gray-400 text-gray-700 text-sm rounded-md shadow-sm">
                            Tidak Diketahui
                        </span>';
                }
            })
            
            ->rawColumns(['oh_ord_status'])
            ->make(true);
    }

    public function getOrderFlag(Request $request)
    {
        $searchType = $request->input('search_type');
        $rmNumber = $request->input('rm_number');
        $ruangan = $request->input('ruangan');
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : null;

        $query = DB::connection('oracle')
            ->table('ord_dtl as c')
            ->join('ord_hdr as a', 'c.od_tno', '=', 'a.oh_tno')
            ->leftJoin('hfclinic as b', 'a.oh_clinic_code', '=', 'b.clinic_code')
            ->leftJoin('test_item as d', 'c.od_testcode', '=', 'd.ti_code')
            ->select(
                'a.oh_tno',
                'a.oh_pid',
                'a.oh_last_name',
                'c.od_testcode',
                'c.od_tr_val',
                'c.od_validate_on',
                'c.od_tr_flag',
                'c.od_action_flag',
                'd.ti_name'
            )
            ->whereNotNull('c.od_validate_on')
            ->whereIn('c.od_tr_flag', ['LL', 'HH'])
            ->where('c.od_action_flag', '!=', 'N')
            ->orderBy('a.oh_trx_dt', 'desc');

        if ($searchType === 'rm' && !empty($rmNumber)) {
            $query->where('a.oh_pid', $rmNumber);
        } else {
            if (!empty($ruangan)) {
                $query->where('b.clinic_code', 'LIKE', $ruangan);
            }
            if (!empty($startDate) && !empty($endDate)) {
                $query->whereBetween('a.oh_trx_dt', [$startDate, $endDate]);
            }
        }

        $results = $query->get();

        $groupedData = [];
        foreach ($results as $row) {
            $tno = $row->oh_tno;
            if (!isset($groupedData[$tno])) {
                $groupedData[$tno] = [
                    'oh_tno'         => $tno,
                    'oh_pid'         => $row->oh_pid,
                    'oh_last_name'   => $row->oh_last_name,
                    'details'        => [],
                ];
            }

            $groupedData[$tno]['details'][] = [
                'od_testcode'   => $row->od_testcode,
                'od_validate_on' => $row->od_validate_on,
                'od_tr_flag' => $row->od_tr_flag,
                'od_tr_val' => $row->od_tr_val,
                'od_action_flag' => $row->od_action_flag,
                'ti_name' => $row->ti_name
            ];
        }

        return DataTables::of(collect(array_values($groupedData)))
            ->addColumn('patient_info', function ($row) {
                return '<a href="' . route('klinik.detail', ['labno' => Hashids::encode($row['oh_tno'])]) . '" target="_blank" class="font-medium text-black no-underline">'
                        . $row['oh_last_name'] . 
                        '</a>';
            })
            ->addColumn('test_name', function ($row) {
                return implode(' ', array_map(fn($d) => '<span class="block">' . $d['ti_name'] . '</span>', $row['details']));
            })
            ->addColumn('result', function ($row) {
                return implode(' ', array_map(fn($d) => '<span class="block">' . $d['od_tr_val'] . '</span>', $row['details']));
            })
            ->addColumn('critical_status', function ($row) {
                return implode(' ', array_map(fn($d) => '<span class="block font-bold text-red-500">' . $d['od_tr_flag'] . '</span>', $row['details']));
            })
            ->rawColumns(['test_name', 'result', 'critical_status', 'patient_info'])
            ->make(true);
    }



    public function detailResult($laborder)
    {
        $tno = Hashids::decode($laborder)[0] ?? null;

        $orderHeader = DB::connection('oracle')
            ->table('ord_hdr as oh')
            ->leftJoin('hfclinic as hc', 'hc.clinic_code', '=', 'oh.oh_clinic_code')
            ->leftJoin('ord_spl as os', 'os.os_tno', '=', 'oh.oh_tno')
            ->leftJoin('ord_dtl as od', 'oh.oh_tno', '=', 'od.od_tno')
            ->where('oh.oh_tno', $tno)
            ->selectRaw('
                oh.oh_trx_dt as order_date,
                oh.oh_ono as ono,
                oh.oh_bod as bod,
                oh.oh_tno as tno,
                oh.oh_pid as pid,
                oh.oh_apid as nik,
                oh.oh_age_yy as age_year,
                oh.oh_age_mm as age_month,
                oh.oh_age_dd as age_day,
                oh.oh_sex as gender,
                oh.oh_last_name as name,
                oh.oh_visitno as register_number,
                oh.oh_completed_dt as complete_date,
                oh.oh_ord_status as status,
                oh.oh_pataddr1 as addr1,
                oh.oh_pataddr2 as addr2,
                oh.oh_pataddr3 as addr3,
                oh.oh_pataddr4 as addr4,
                oh.oh_dname as clinician,
                oh.oh_diag1 as diag1,
                hc.clinic_desc as room_desc,
                COALESCE(MAX(os.os_spl_rcvdt), NULL) as spl_rcvdt,
                COALESCE(MAX(od.od_validate_on), NULL) as validate_on

            ')
            ->groupBy([
                'oh.oh_trx_dt',
                'oh.oh_ono',
                'oh.oh_bod',
                'oh.oh_tno',
                'oh.oh_pid',
                'oh.oh_apid',
                'oh.oh_age_yy',
                'oh.oh_age_mm',
                'oh.oh_age_dd',
                'oh.oh_sex',
                'oh.oh_last_name',
                'oh.oh_visitno',
                'oh.oh_completed_dt',
                'oh.oh_ord_status',
                'oh.oh_pataddr1',
                'oh.oh_pataddr2',
                'oh.oh_pataddr3',
                'oh.oh_pataddr4',
                'oh.oh_dname',
                'hc.clinic_desc',
                'oh.oh_diag1'
            ])
            ->first();

        if ($orderHeader) {
            $orderHeader->gender = match ($orderHeader->gender ?? '') {
                '1' => 'Laki-laki',
                '2' => 'Perempuan',
                default => 'Unknown',
            };

            $orderHeader->bod = $orderHeader->bod
                ? Carbon::parse($orderHeader->bod)->format('d-m-Y')
                : 'Unknown';

            $orderHeader->calculated_age = ($orderHeader->age_year !== null ? $orderHeader->age_year . ' tahun' : '') .
                ($orderHeader->age_month !== null ? ', ' . $orderHeader->age_month . ' bulan' : '') .
                ($orderHeader->age_day !== null ? ', ' . $orderHeader->age_day . ' hari' : '');

            if (empty(trim($orderHeader->calculated_age))) {
                $orderHeader->calculated_age = 'Unknown';
            }
        }

        // Query untuk order details
        $orderDetails = DB::connection('oracle')
            ->table('ord_hdr as a')
            ->leftJoin('ord_dtl as b', 'a.oh_tno', '=', 'b.od_tno')
            ->leftJoin('test_group as c', 'b.od_test_grp', '=', 'c.tg_code')
            ->leftJoin('test_item as d', 'b.od_order_ti', '=', 'd.ti_code')
            ->leftJoin('hord_dtl as e', function ($join) {
                $join->on('b.od_tno', '=', 'e.od_tno')
                    ->on('b.od_item_parent', '=', 'e.od_testcode');
            })
            ->leftJoin('hord_ftr as f', function ($join) {
                $join->on('b.od_tno', '=', 'f.of_tno')
                    ->on('b.od_testcode', '=', 'f.of_testcode');
            })
            ->join('test_item as g', 'b.od_testcode', '=', 'g.ti_code')
            ->leftJoin('textvalue as h', 'b.od_tr_val', '=', 'h.tv_code')
            ->leftJoin('hfsex as i', 'a.oh_sex', '=', 'i.sex_code')
            ->leftJoin('pmi_para as j', function ($join) {
                $join->on('j.pp_type', '=', DB::raw("'10'"))
                    ->on('a.oh_ptype', '=', 'j.pp_code');
            })
            ->leftJoin('hfclinic as k', 'a.oh_clinic_code', '=', 'k.clinic_code')
            ->leftJoin('hfresource as l', function ($join) {
                $join->on('l.resource_type', '=', DB::raw("'01'"))
                    ->on('a.oh_dcode', '=', 'l.resource_code');
            })
            ->leftJoin('ord_hdr as m', 'a.oh_tno', '=', 'm.oh_tno')
            ->leftJoin('ord_spl as n', function ($join) {
                $join->on('b.od_tno', '=', 'n.os_tno')
                    ->on('b.od_spl_type', '=', 'n.os_spl_type');
            })
            ->select([
                'a.oh_tno as labno',
                'b.od_update_by',
                'b.od_update_on',
                'b.od_validate_on',
                'b.od_validate_by',
                'b.od_testcode as test_code',
                DB::raw("CASE
                    WHEN b.od_item_type = 'U' AND b.od_item_parent <> '000000' AND b.od_item_parent <> b.od_order_ti THEN g.ti_name
                    WHEN b.od_item_parent = b.od_order_ti THEN g.ti_name
                    ELSE g.ti_name
                END as test_name"),
                'b.od_tr_unit as test_unit',
                DB::raw("CASE
                    WHEN b.od_action_flag = 'N' THEN 'Belum Tersedia'
                    WHEN b.od_data_type = 'T' AND h.tv_desc IS NOT NULL THEN h.tv_desc
                    WHEN b.od_data_type = 'W' THEN f.of_text
                    ELSE b.od_tr_val
                END as test_value"),
                DB::raw("CASE
                    WHEN b.od_item_type = 'U' AND b.od_data_type = 'N' AND b.od_tr_val IS NOT NULL AND b.od_tr_flag IS NULL THEN 'N'
                    ELSE b.od_tr_flag
                END as abnormal_flag"),
                'b.od_tr_range as ref_range',
                DB::raw("CASE
                    WHEN b.od_tr_range = 'MRR' THEN b.od_mrr_desc
                    ELSE b.od_tr_comment || '~' || b.od_attached_cmt
                END as test_comment"),
                'f.of_text as free_text_1',
                'f.of_text1 as free_text_2',
                DB::raw("c.tg_ls_code || LPAD(d.ti_disp_seq,3,'0') || 
                         LPAD(
                             CASE 
                                 WHEN b.od_item_parent = '000000' OR b.od_item_parent = b.od_order_ti THEN b.od_seq_no 
                                 ELSE COALESCE(e.od_seq_no, 0) 
                             END, 3, '0') || 
                         LPAD(
                             CASE 
                                 WHEN b.od_item_parent = '000000' OR b.od_item_parent = b.od_order_ti THEN 0 
                                 ELSE b.od_seq_no 
                             END, 3, '0') as seq"),
                'b.od_item_type',
                DB::raw("b.od_order_ti || '^' || d.ti_name as od_order_ti"),
                'c.tg_name',
                'b.od_data_type',
                'n.os_spl_rcvdt'
            ])
            ->where('a.oh_tno', $tno)
            ->orderBy('seq', 'asc')
            ->get();

        if ($orderDetails->isNotEmpty()) {
            $orderDetails = $orderDetails->map(function ($item) {
                $comments = explode('~', $item->test_comment);
                $item->test_comment = $comments[0] ?? null;
                $item->attached_comment = $comments[1] ?? null;
                return $item;
            });
        }

        // Grouping order details by test group name
        $groupedOrderDetails = $orderDetails->groupBy('tg_name')->map(function ($group) {
            return $group->sortBy('seq')->values();
        });

        if (!$orderHeader) {
            abort(404, 'Data order tidak ditemukan.');
        }

        return view('result-lab.klinik.detail', compact('orderHeader', 'groupedOrderDetails'));

        return response()->json([
            'order_header' => $orderHeader,
            'order_detail' => $groupedOrderDetails
        ]);
    }
}
