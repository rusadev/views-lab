<?php

namespace App\Http\Controllers;

use App\Services\ReportExcelService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanDetailPemeriksaanController extends Controller
{
    public function index()
    {
        return view('laporan.detail-pemeriksaan.index');
    }

    public function getData(Request $request)
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        $testGroup = $request->input('test_group');

        $query = DB::connection('oracle')
            ->table('ord_hdr as a')
            ->leftJoin('ord_dtl as b', 'a.oh_tno', '=', 'b.od_tno')
            ->leftJoin('test_group as c', 'b.od_test_grp', '=', 'c.tg_code')
            ->leftJoin('test_item as d', 'b.od_testcode', '=', 'd.ti_code')
            ->leftJoin('hfclinic as e', 'a.oh_clinic_code', '=', 'e.clinic_code')
            ->select([
                'a.oh_tno as transaction_number',
                'a.oh_trx_dt as transaction_date',
                'a.oh_pid as patient_id',
                'a.oh_last_name as patient_name',
                DB::raw("CASE WHEN a.oh_sex = '1' THEN 'L' WHEN a.oh_sex = '2' THEN 'P' ELSE '-' END as gender"),
                'a.oh_age_yy as age',
                'e.clinic_desc as clinic_name',
                'b.od_test_grp as test_group',
                DB::raw("COALESCE(c.tg_name, b.od_test_grp) as group_name"),
                DB::raw("COALESCE(d.ti_name, b.od_testcode) as test_name"),
                'b.od_tr_val as result_value',
                'b.od_tr_flag as flag',
                'b.od_validate_on as validate_date'
            ])
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('b.od_test_grp');

        if ($testGroup) {
            $query->where('b.od_test_grp', $testGroup);
        }

        $rawData = $query->orderBy('a.oh_trx_dt', 'desc')->take(500)->get();

        return response()->json($rawData);
    }

    public function exportToExcel(Request $request)
    {
        $startDate = $request->input('start_date')
            ? Carbon::parse($request->input('start_date'))->startOfDay()
            : Carbon::now()->startOfMonth()->startOfDay();

        $endDate = $request->input('end_date')
            ? Carbon::parse($request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        $testGroup = $request->input('test_group');

        $query = DB::connection('oracle')
            ->table('ord_hdr as a')
            ->leftJoin('ord_dtl as b', 'a.oh_tno', '=', 'b.od_tno')
            ->leftJoin('test_group as c', 'b.od_test_grp', '=', 'c.tg_code')
            ->leftJoin('test_item as d', 'b.od_testcode', '=', 'd.ti_code')
            ->leftJoin('hfclinic as e', 'a.oh_clinic_code', '=', 'e.clinic_code')
            ->select([
                'a.oh_tno as transaction_number',
                'a.oh_trx_dt as transaction_date',
                'a.oh_pid as patient_id',
                'a.oh_last_name as patient_name',
                DB::raw("CASE WHEN a.oh_sex = '1' THEN 'L' WHEN a.oh_sex = '2' THEN 'P' ELSE '-' END as gender"),
                'a.oh_age_yy as age',
                'e.clinic_desc as clinic_name',
                DB::raw("COALESCE(c.tg_name, b.od_test_grp) as group_name"),
                DB::raw("COALESCE(d.ti_name, b.od_testcode) as test_name"),
                'b.od_tr_val as result_value',
                'b.od_tr_flag as flag',
                'b.od_validate_on as validate_date'
            ])
            ->whereBetween('a.oh_trx_dt', [$startDate, $endDate])
            ->whereNotNull('b.od_test_grp');

        if ($testGroup) {
            $query->where('b.od_test_grp', $testGroup);
        }

        $rawData = $query->orderBy('a.oh_trx_dt', 'asc')->take(2000)->get();

        $periodStr = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        $spreadsheet = ReportExcelService::createSpreadsheet('Laporan Detail Hasil Pemeriksaan Laboratorium', $periodStr);
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail Pemeriksaan');

        $sheet->setCellValue('A6', 'No');
        $sheet->setCellValue('B6', 'Tanggal');
        $sheet->setCellValue('C6', 'No. Order');
        $sheet->setCellValue('D6', 'No. RM');
        $sheet->setCellValue('E6', 'Nama Pasien');
        $sheet->setCellValue('F6', 'JK');
        $sheet->setCellValue('G6', 'Umur');
        $sheet->setCellValue('H6', 'Ruangan');
        $sheet->setCellValue('I6', 'Kelompok Uji');
        $sheet->setCellValue('J6', 'Nama Pemeriksaan');
        $sheet->setCellValue('K6', 'Hasil');
        $sheet->setCellValue('L6', 'Flag');

        $rowIdx = 7;
        $no = 1;

        foreach ($rawData as $row) {
            $sheet->setCellValue("A{$rowIdx}", $no++);
            $sheet->setCellValue("B{$rowIdx}", $row->transaction_date ? date('d/m/Y', strtotime($row->transaction_date)) : '-');
            $sheet->setCellValue("C{$rowIdx}", $row->transaction_number);
            $sheet->setCellValue("D{$rowIdx}", $row->patient_id);
            $sheet->setCellValue("E{$rowIdx}", $row->patient_name);
            $sheet->setCellValue("F{$rowIdx}", $row->gender);
            $sheet->setCellValue("G{$rowIdx}", $row->age);
            $sheet->setCellValue("H{$rowIdx}", $row->clinic_name ?: '-');
            $sheet->setCellValue("I{$rowIdx}", $row->group_name ?: '-');
            $sheet->setCellValue("J{$rowIdx}", $row->test_name ?: '-');
            $sheet->setCellValue("K{$rowIdx}", $row->result_value ?: '-');
            $sheet->setCellValue("L{$rowIdx}", $row->flag ?: '-');
            $rowIdx++;
        }

        ReportExcelService::formatTable($sheet, 6, $rowIdx - 1, 'A', 'L', false);

        $filename = 'Laporan_Detail_Pemeriksaan_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.xlsx';
        return ReportExcelService::streamDownload($spreadsheet, $filename);
    }
}
