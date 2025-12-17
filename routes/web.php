<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LaboratoriumKlinikController;
use App\Http\Controllers\LaboratoriumMikrobiologiKlinikController;
use App\Http\Controllers\LaboratoriumPatologiAnatomiController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LaporanDetailPemeriksaanController;
use App\Http\Controllers\LaporanJumlahPemeriksaanController;
use App\Http\Controllers\LaporanNilaiKritisController;
use App\Http\Controllers\LaporanPenggunaanTabungController;
use App\Http\Controllers\LaporanTAT;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});


Route::get('/dashboard', function () {
    return redirect()->route('klinik.index');
})->middleware(['auth', 'verified']);


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('laboratorium')->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
        Route::get('/dashboard/data', [DashboardController::class, 'dashboardData'])->name('dashboard.data');
        Route::get('/patologi-klinik', [LaboratoriumKlinikController::class, 'index'])->name('klinik.index');
        Route::get('/patologi-klinik/get-order', [LaboratoriumKlinikController::class, 'getOrder'])->name('klinik.order');
        Route::get('/patologi-klinik/get-order/flag', [LaboratoriumKlinikController::class, 'getOrderFlag'])->name('klinik.order.flag');
        Route::get('/patologi-klinik/detail/{labno}', [LaboratoriumKlinikController::class, 'detailResult'])->name('klinik.detail');

        Route::get('/mikrobiologi-klinik', [LaboratoriumMikrobiologiKlinikController::class, 'index'])->name('mikro.index');
        Route::get('/patologi-anatomi', [LaboratoriumPatologiAnatomiController::class, 'index'])->name('pa.index');

        Route::prefix('laporan')->group(function() {
            Route::get('/', [LaporanController::class, 'index'])->name('laporan.index');

            Route::get('/jumlah-pasien', [LaporanController::class, 'indexJumlahPasien'])->name('laporan.jumlah-pasien.index');
            Route::get('/jumlah-pasien/data', [LaporanController::class, 'getData'])->name('laporan.jumlah-pasien.data');
            Route::get('/jumlah-pasien/export-word', [LaporanController::class, 'exportToWord'])->name('laporan.jumlah-pasien.export-word');


            Route::get('/jumlah-pemeriksaan', [LaporanJumlahPemeriksaanController::class, 'index'])->name('laporan.jumlah-pemeriksaan.index');
            Route::get('/jumlah-pemeriksaan/data', [LaporanJumlahPemeriksaanController::class, 'getData'])->name('laporan.jumlah-pemeriksaan.data');

            Route::get('/detail-pemeriksaan', [LaporanDetailPemeriksaanController::class, 'index'])->name('laporan.detail-pemeriksaan.index');
            Route::get('/detail-pemeriksaan/data', [LaporanDetailPemeriksaanController::class, 'getData'])->name('laporan.detail-pemeriksaan.data');

            Route::get('/penggunaan-tabung', [LaporanPenggunaanTabungController::class, 'index'])->name('laporan.penggunaan-tabung.index');
            Route::get('/penggunaan-tabung/data', [LaporanPenggunaanTabungController::class, 'getData'])->name('laporan.penggunaan-tabung.data');

            Route::get('/nilai-kritis', [LaporanNilaiKritisController::class, 'index'])->name('laporan.nilai-kritis.index');
            Route::get('/nilai-kritis/data', [LaporanNilaiKritisController::class, 'getData'])->name('laporan.nilai-kritis.data');

            
            Route::get('/tat', [LaporanTAT::class, 'index'])->name('laporan.tat.index');
            Route::get('/tat/data', [LaporanTAT::class, 'getData'])->name('laporan.tat.data');

        });

    });

});

require __DIR__.'/auth.php';
