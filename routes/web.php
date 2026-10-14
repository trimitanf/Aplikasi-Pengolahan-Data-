<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
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

    // Tahap selanjutnya placeholder routes to keep sidebar navigation robust
    Route::get('/dataset', function () {
        return redirect()->route('dashboard')->with('info', 'Modul Dataset aktif pada Tahap 2.');
    })->name('dataset.index');

    Route::get('/data-tersimpan', function () {
        return redirect()->route('dashboard')->with('info', 'Modul Data Tersimpan aktif pada Tahap 3.');
    })->name('data-tersimpan.index');

    Route::get('/rekap', function () {
        return redirect()->route('dashboard')->with('info', 'Modul Rekap aktif pada Tahap 4.');
    })->name('rekap.index');
});
