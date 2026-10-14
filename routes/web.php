<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatasetController;
use App\Http\Controllers\PengaturanController;
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

    // Tahap selanjutnya placeholder routes
    Route::get('/data-tersimpan', function () {
        return redirect()->route('dashboard')->with('info', 'Modul Data Tersimpan aktif pada Tahap 3.');
    })->name('data-tersimpan.index');

    Route::get('/rekap', function () {
        return redirect()->route('dashboard')->with('info', 'Modul Rekap aktif pada Tahap 4.');
    })->name('rekap.index');
});
