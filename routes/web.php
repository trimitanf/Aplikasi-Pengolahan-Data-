<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Iklan\AturanKonversiController;
use App\Http\Controllers\Iklan\AturanPemetaanController;
use App\Http\Controllers\Iklan\DashboardController;
use App\Http\Controllers\Iklan\DatasetController;
use App\Http\Controllers\Iklan\DatasetEvaluationController;
use App\Http\Controllers\Iklan\DatasetExportController;
use App\Http\Controllers\Iklan\DatasetTrashController;
use App\Http\Controllers\Iklan\DataTersimpanController;
use App\Http\Controllers\Iklan\MasterKodeController;
use App\Http\Controllers\Iklan\PengaturanController;
use App\Http\Controllers\Iklan\PenggunaController;
use App\Http\Controllers\Iklan\RekapController;
use App\Http\Controllers\Iklan\ReportEvaluatorController;
use App\Http\Controllers\Iklan\StandardisasiController;
use App\Http\Controllers\Iklan\TargetIkuController;
use App\Http\Controllers\Penandaan\PenandaanDashboardController;
use App\Http\Controllers\Penandaan\PenandaanDatasetController;
use App\Http\Controllers\Penandaan\PenandaanRekapController;
use App\Http\Controllers\Penandaan\PenandaanStandardisasiController;
use Illuminate\Support\Facades\Route;

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:20,1')
        ->name('login.post');
});

// Authenticated Routes
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        if (auth()->check() && auth()->user()->isEvaluatorPenandaan()) {
            return redirect()->route('penandaan.dashboard');
        }

        return redirect()->route('dashboard');
    });

    // Modul Pengawasan Iklan (Khusus Admin & Evaluator Iklan)
    Route::middleware('access.iklan')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/sync/versi', [DashboardController::class, 'syncVersion'])->name('sync.versi');

        // Dataset (Tahap 2)
        Route::get('/dataset', [DatasetController::class, 'index'])->name('dataset.index');
        Route::post('/dataset', [DatasetController::class, 'store'])->name('dataset.store');

        // CRUD Sampah (Recycle Bin) Dataset
        Route::get('/dataset/trash', [DatasetTrashController::class, 'trash'])->name('dataset.trash');
        Route::post('/dataset/trash/empty', [DatasetTrashController::class, 'emptyTrash'])->name('dataset.trash.empty');
        Route::post('/dataset/trash/restore-all', [DatasetTrashController::class, 'restoreAll'])->name('dataset.trash.restore-all');
        Route::post('/dataset/{id}/restore', [DatasetTrashController::class, 'restore'])->name('dataset.restore');
        Route::delete('/dataset/{id}/force', [DatasetTrashController::class, 'forceDelete'])->name('dataset.force-delete');

        // Export Excel Daftar Dataset
        Route::get('/dataset/export-list', [DatasetExportController::class, 'exportList'])->name('dataset.export-list');

        Route::get('/dataset/{dataset}', [DatasetController::class, 'show'])->name('dataset.show');
        Route::get('/dataset/{dataset}/export-mentah', [DatasetExportController::class, 'exportMentah'])->name('dataset.export-mentah');
        Route::get('/dataset/{dataset}/data-mentah', [DatasetController::class, 'dataMentah'])->name('dataset.data-mentah');
        Route::post('/dataset/{dataset}/data-mentah/manual-cell/{item}', [DatasetEvaluationController::class, 'updateManualCell'])->name('dataset.mentah-cell.update');
        Route::post('/dataset/{dataset}/data-mentah/bulk-manual', [DatasetEvaluationController::class, 'bulkUpdateManual'])->name('dataset.mentah-bulk-manual');
        Route::post('/dataset/{dataset}/assign-upt', [DatasetEvaluationController::class, 'assignUpt'])->name('dataset.assign-upt');
        Route::post('/dataset/{dataset}/distribute-evaluators', [DatasetEvaluationController::class, 'redistributeEvaluator'])->name('dataset.distribute-evaluators');
        Route::post('/dataset/{dataset}/reassign-evaluator', [DatasetEvaluationController::class, 'reassignEvaluatorRows'])->name('dataset.reassign-evaluator');
        Route::post('/dataset/{dataset}/transfer-ownership', [DatasetEvaluationController::class, 'reassignEvaluatorRows'])->name('dataset.transfer-ownership');
        Route::get('/dataset/{dataset}/status', [DatasetController::class, 'status'])->name('dataset.status');
        Route::get('/dataset/{dataset}/download', [DatasetController::class, 'download'])->name('dataset.download');
        Route::delete('/dataset/{dataset}', [DatasetController::class, 'destroy'])->name('dataset.destroy');

        // Standarisasi Data (Menu Khusus Hasil Standarisasi)
        Route::get('/standarisasi/export-all', [StandardisasiController::class, 'exportAll'])->name('standarisasi.export-all');
        Route::get('/standarisasi/{dataset?}', [StandardisasiController::class, 'index'])->name('standarisasi.index');
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

        // Rekap (Tahap 4)
        Route::get('/rekap', [RekapController::class, 'index'])->name('rekap.index');
        Route::get('/rekap/export', [RekapController::class, 'export'])->name('rekap.export');
    });

    // Modul Pengawasan Penandaan (Khusus Admin & Evaluator Penandaan)
    Route::prefix('penandaan')->name('penandaan.')->middleware('access.penandaan')->group(function () {
        Route::get('/dashboard', [PenandaanDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dataset', [PenandaanDatasetController::class, 'index'])->name('dataset.index');
        Route::get('/standarisasi', [PenandaanStandardisasiController::class, 'index'])->name('standarisasi.index');
        Route::get('/rekap', [PenandaanRekapController::class, 'index'])->name('rekap.index');
    });

    // Menu Khusus Administrator
    Route::middleware('admin')->group(function () {
        // Report Evaluator (Laporan Pembagian Tugas UPT & Tanggal Evaluasi)
        Route::get('/report-evaluator', [ReportEvaluatorController::class, 'index'])->name('report-evaluator.index');
        Route::get('/report-evaluator/export', [ReportEvaluatorController::class, 'export'])->name('report-evaluator.export');

        // 1. Kamus Kode Standar & Master Data UPT (Master Data Backend)
        Route::get('/kamus-kode', [MasterKodeController::class, 'index'])->name('kamus-kode.index');
        Route::post('/kamus-kode', [MasterKodeController::class, 'store'])->name('kamus-kode.store');
        Route::put('/kamus-kode/upt/{upt}', [MasterKodeController::class, 'updateUpt'])->name('kamus-kode.upt.update');
        Route::delete('/kamus-kode/upt/{upt}', [MasterKodeController::class, 'destroyUpt'])->name('kamus-kode.upt.destroy');
        Route::put('/kamus-kode/{masterKode}', [MasterKodeController::class, 'update'])->name('kamus-kode.update');
        Route::delete('/kamus-kode/{masterKode}', [MasterKodeController::class, 'destroy'])->name('kamus-kode.destroy');

        // 2. Aturan Konversi SIPT
        Route::get('/aturan-konversi', [AturanKonversiController::class, 'index'])->name('aturan-konversi.index');
        Route::post('/aturan-konversi', [AturanKonversiController::class, 'store'])->name('aturan-konversi.store');
        Route::put('/aturan-konversi/{aturanKonversi}', [AturanKonversiController::class, 'update'])->name('aturan-konversi.update');
        Route::delete('/aturan-konversi/{aturanKonversi}', [AturanKonversiController::class, 'destroy'])->name('aturan-konversi.destroy');

        // 3. Target IKU
        Route::get('/target-iku', [TargetIkuController::class, 'index'])->name('target-iku.index');
        Route::post('/target-iku', [TargetIkuController::class, 'store'])->name('target-iku.store');
        Route::put('/target-iku/{targetIku}', [TargetIkuController::class, 'update'])->name('target-iku.update');
        Route::delete('/target-iku/{targetIku}', [TargetIkuController::class, 'destroy'])->name('target-iku.destroy');

        // 4. Kelola Pengguna
        Route::get('/pengguna', [PenggunaController::class, 'index'])->name('pengguna.index');
        Route::post('/pengguna', [PenggunaController::class, 'store'])->name('pengguna.store');
        Route::put('/pengguna', [PenggunaController::class, 'updateFallback'])->name('pengguna.update.fallback');
        Route::put('/pengguna/{pengguna}', [PenggunaController::class, 'update'])->name('pengguna.update');
        Route::delete('/pengguna', [PenggunaController::class, 'destroyFallback'])->name('pengguna.destroy.fallback');
        Route::delete('/pengguna/{pengguna}', [PenggunaController::class, 'destroy'])->name('pengguna.destroy');

        // Kompatibilitas Rute Lama
        Route::get('/aturan-pemetaan', [AturanPemetaanController::class, 'index'])->name('aturan-pemetaan.index');
        Route::post('/aturan-pemetaan', [AturanPemetaanController::class, 'store'])->name('aturan-pemetaan.store');
        Route::put('/aturan-pemetaan/{aturanPemetaan}', [AturanPemetaanController::class, 'update'])->name('aturan-pemetaan.update');
        Route::delete('/aturan-pemetaan/{aturanPemetaan}', [AturanPemetaanController::class, 'destroy'])->name('aturan-pemetaan.destroy');

        Route::get('/pengaturan', [PengaturanController::class, 'index'])->name('pengaturan.index');
        Route::post('/pengaturan/target-iku', [PengaturanController::class, 'updateTargetIku'])->name('pengaturan.target-iku.update');
    });
});
