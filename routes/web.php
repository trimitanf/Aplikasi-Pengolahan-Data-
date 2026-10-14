<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatasetController;
use App\Http\Controllers\DataTersimpanController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\StandardisasiController;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Pengaturan
    Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
    Route::post('/pengaturan/target-iku', [PengaturanController::class, 'updateTargetIku'])->name('pengaturan.target-iku.update');

    // Dataset (Tahap 2)
    Route::get('/dataset', [DatasetController::class, 'index'])->name('dataset.index');
    Route::post('/dataset', [DatasetController::class, 'store'])->name('dataset.store');
    Route::get('/dataset/{dataset}', [DatasetController::class, 'show'])->name('dataset.show');
    Route::get('/dataset/{dataset}/data-mentah', [DatasetController::class, 'dataMentah'])->name('dataset.data-mentah');
    Route::get('/dataset/{dataset}/status', [DatasetController::class, 'status'])->name('dataset.status');
    Route::delete('/dataset/{dataset}', [DatasetController::class, 'destroy'])->name('dataset.destroy');

    // Standardisasi & Input Manual (Tahap 3)
    Route::get('/dataset/{dataset}/standardisasi', [StandardisasiController::class, 'show'])->name('dataset.standardisasi');
    Route::post('/dataset/{dataset}/konversi/ulang', [StandardisasiController::class, 'runConversion'])->name('dataset.konversi.ulang');
    Route::post('/dataset/{dataset}/manual-cell/{item}', [StandardisasiController::class, 'updateManualCell'])->name('dataset.manual-cell.update');
    Route::post('/dataset/{dataset}/bulk-manual', [StandardisasiController::class, 'bulkUpdateManual'])->name('dataset.bulk-manual');
    Route::post('/dataset/{dataset}/simpan-aturan-pemetaan', [StandardisasiController::class, 'saveMappingRule'])->name('dataset.mapping.save');
    Route::post('/dataset/{dataset}/simpan-final', [StandardisasiController::class, 'simpanFinal'])->name('dataset.simpan-final');
    Route::get('/dataset/{dataset}/export', [StandardisasiController::class, 'export'])->name('dataset.export');

    // Data Tersimpan (Tahap 3)
    Route::get('/data-tersimpan', [DataTersimpanController::class, 'index'])->name('data-tersimpan.index');
    Route::get('/data-tersimpan/export', [DataTersimpanController::class, 'export'])->name('data-tersimpan.export');

    // Tahap 4 & 5 placeholders
    Route::get('/rekap', function () {
        return redirect()->route('dashboard')->with('info', 'Modul Rekap aktif pada Tahap 4.');
    })->name('rekap.index');
});
