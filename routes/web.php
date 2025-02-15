<?php

use App\Http\Controllers\LaboratoriumKlinikController;
use App\Http\Controllers\LaboratoriumMikrobiologiKlinikController;
use App\Http\Controllers\LaboratoriumPatologiAnatomiController;
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
        Route::get('/patologi-klinik', [LaboratoriumKlinikController::class, 'index'])->name('klinik.index');
        Route::get('/patologi-klinik/get-order', [LaboratoriumKlinikController::class, 'getOrder'])->name('klinik.order');
        Route::get('/patologi-klinik/get-order/flag', [LaboratoriumKlinikController::class, 'getOrderFlag'])->name('klinik.order.flag');
        Route::get('/patologi-klinik/detail/{labno}', [LaboratoriumKlinikController::class, 'detailResult'])->name('klinik.detail');

        Route::get('/mikrobiologi-klinik', [LaboratoriumMikrobiologiKlinikController::class, 'index'])->name('mikro.index');
        Route::get('/patologi-anatomi', [LaboratoriumPatologiAnatomiController::class, 'index'])->name('pa.index');
    });

});

require __DIR__.'/auth.php';
