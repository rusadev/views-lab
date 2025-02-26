<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanPenggunaanTabungController extends Controller
{
    //
    public function index ()
    {
        return view ('laporan.penggunaan-tabung.index');
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

        // Ambil data dari database, kecualikan sample yang NULL
        $tubeUsage = $oracleConnection
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
            ->selectRaw("
                TO_CHAR(a.oh_trx_dt, 'YYYY-MM-DD') as trx_date, 
                e.st_name as sample, 
                COUNT(DISTINCT d.os_tno) as total_usage
            ")
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('e.st_name') // Kecualikan yang NULL
            ->groupByRaw("TO_CHAR(a.oh_trx_dt, 'YYYY-MM-DD'), e.st_name")
            ->get();

        // Ambil semua tanggal unik dan urutkan dari paling awal ke paling akhir
        $dates = $tubeUsage->pluck('trx_date')->unique()->sort()->values()->all();

        // Ambil semua sample unik dan urutkan A-Z
        $samples = $tubeUsage->pluck('sample')->unique()->sort()->values()->all();

        // Struktur hasil akhir dengan tanggal sebagai index utama
        $formattedData = [];

        // Inisialisasi array kosong untuk setiap tanggal
        foreach ($dates as $date) {
            $formattedData[$date] = ['date' => $date];

            // Set setiap sample default ke 0
            foreach ($samples as $sample) {
                $formattedData[$date][$sample] = 0;
            }
        }

        // Masukkan data yang ada ke dalam array pivot
        foreach ($tubeUsage as $data) {
            $formattedData[$data->trx_date][$data->sample] = $data->total_usage;
        }

        return response()->json([
            'data' => array_values($formattedData), // Reset indeks array
            'samples' => $samples, // Untuk header tabel
        ]);
    }

    

}
