<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Dataset extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'datasets';

    protected $fillable = [
        'uploaded_by',
        'nama_file_asli',
        'path_file',
        'komoditi',
        'periode_tahun',
        'periode_bulan',
        'periode_mulai',
        'periode_akhir',
        'jumlah_baris',
        'jumlah_menunggu_evaluasi',
        'status',
        'catatan',
        'uploaded_at',
        'finalized_at',
    ];

    protected function casts(): array
    {
        return [
            'periode_tahun' => 'integer',
            'periode_bulan' => 'integer',
            'periode_mulai' => 'date',
            'periode_akhir' => 'date',
            'jumlah_baris' => 'integer',
            'jumlah_menunggu_evaluasi' => 'integer',
            'uploaded_at' => 'datetime',
            'finalized_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Dataset $dataset) {
            if ($dataset->isForceDeleting()) {
                $dataset->deletePhysicalFile();
                $dataset->dataMentah()->withTrashed()->forceDelete();
                $dataset->dataIklan()->withTrashed()->forceDelete();
                $dataset->issues()->withTrashed()->forceDelete();
            } else {
                $dataset->dataMentah()->delete();
                $dataset->dataIklan()->delete();
                $dataset->issues()->delete();
            }
        });

        static::restoring(function (Dataset $dataset) {
            $dataset->dataMentah()->withTrashed()->restore();
            $dataset->dataIklan()->withTrashed()->restore();
            $dataset->issues()->withTrashed()->restore();
        });
    }

    /**
     * Hapus file fisik dataset (.xlsx/.xls/.csv) dari storage disk.
     */
    public function deletePhysicalFile(): bool
    {
        if (empty($this->path_file)) {
            return false;
        }

        $cleanPath = str_replace(['../', '..\\', 'private/'], '', (string) $this->path_file);
        $cleanPath = ltrim($cleanPath, '/\\');

        $deleted = false;

        // 1. Cek pada disk 'private' (storage/app/private/datasets/...)
        try {
            if (Storage::disk('private')->exists($cleanPath)) {
                Storage::disk('private')->delete($cleanPath);
                $deleted = true;
            }
        } catch (\Throwable) {
            // Abaikan kegagalan I/O non-kritis
        }

        // 2. Cek pada default local disk (storage/app/private atau storage/app/...)
        try {
            if (Storage::disk('local')->exists($cleanPath)) {
                Storage::disk('local')->delete($cleanPath);
                $deleted = true;
            }
        } catch (\Throwable) {
            // Abaikan kegagalan I/O non-kritis
        }

        // 3. Fallback direct path storage/app/datasets/... dan storage/app/private/...
        $directAppPath = storage_path('app/' . $cleanPath);
        if (file_exists($directAppPath) && is_file($directAppPath)) {
            @unlink($directAppPath);
            $deleted = true;
        }

        $directPrivatePath = storage_path('app/private/' . $cleanPath);
        if (file_exists($directPrivatePath) && is_file($directPrivatePath)) {
            @unlink($directPrivatePath);
            $deleted = true;
        }

        return $deleted;
    }

    /**
     * Accessor untuk file_path agar kompatibel dengan pemanggilan $dataset->file_path.
     */
    public function getFilePathAttribute(): ?string
    {
        return $this->attributes['file_path'] ?? $this->attributes['path_file'] ?? $this->path_file;
    }

    /**
     * Mutator untuk file_path.
     */
    public function setFilePathAttribute(?string $value): void
    {
        $this->attributes['path_file'] = $value;
    }

    /**
     * Evaluator (atau admin) yang mengunggah dataset ini sekaligus pemilik seluruh barisnya.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Hak akses pengelolaan berkas dataset (Unggah, hapus, dan atur berkas).
     * Khusus Administrator atau PIC/Pengunggah berkas tersebut.
     */
    public function canBeManagedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this->uploaded_by !== null && (int) $this->uploaded_by === (int) $user->id;
    }

    /**
     * Hak akses evaluasi baris data (Skema Kolaboratif):
     * - Administrator dapat mengedit seluruh dataset kapan saja (selama belum Final).
     * - Evaluator aktif dapat berkolaborasi pada dataset bersama yang belum Final.
     *   (Penguncian tingkat baris / UPT ditangani pada level row-level permission).
     */
    public function canBeEditedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isFinal()) {
            return $user->isAdmin();
        }

        if ($user->isAdmin()) {
            return true;
        }

        // Jika dataset diunggah oleh evaluator tertentu, hanya pemilik tersebut yang berwenang mengedit
        if ($this->uploaded_by !== null && ! $this->uploader?->isAdmin()) {
            return (int) $this->uploaded_by === (int) $user->id;
        }

        return (bool) ($user->is_active ?? true);
    }

    public function issues(): HasMany
    {
        return $this->hasMany(DatasetIssue::class);
    }

    public function dataMentah(): HasMany
    {
        return $this->hasMany(DataMentah::class);
    }

    public function dataIklan(): HasMany
    {
        return $this->hasMany(DataIklan::class);
    }

    public function items(): HasMany
    {
        return $this->dataIklan();
    }

    public function isFinal(): bool
    {
        return in_array($this->status, ['Final', 'Sudah Diproses'], true);
    }

    public function canBeDeleted(): bool
    {
        return true;
    }

    public function getNamaBulanAttribute(): string
    {
        $bulanMap = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return $bulanMap[$this->periode_bulan] ?? (string) $this->periode_bulan;
    }

    public function getNamaKomoditiAttribute(): string
    {
        return match ($this->komoditi) {
            'OBA', 'OT' => 'Obat Bahan Alam (OBA)',
            'OK' => 'Obat Kuasi (OK)',
            'SK' => 'Suplemen Kesehatan (SK)',
            default => $this->komoditi,
        };
    }

    public function setKomoditiAttribute($value): void
    {
        $this->attributes['komoditi'] = strtoupper(trim((string) $value));
    }

    /**
     * Kunci pengelompokan dataset identik lintas sesi evaluator (Komoditi + Tahun + Bulan + Nama File).
     */
    public function getGroupKeyAttribute(): string
    {
        $normKomoditi = in_array(strtoupper((string) $this->komoditi), ['OBA', 'OT'], true)
            ? 'OBA'
            : strtoupper((string) $this->komoditi);
        $rawName = mb_strtolower(trim((string) $this->nama_file_asli));
        $normFile = preg_replace('/\s*\(\d+\)(?=\.[^.]+$)/', '', $rawName) ?: $rawName;

        return "{$normKomoditi}|{$this->periode_tahun}|{$this->periode_bulan}|{$normFile}";
    }

    /**
     * Mengambil seluruh sesi dataset (termasuk milik evaluator lain) yang berasal dari berkas & periode yang sama.
     */
    public function getSiblingSessions(): \Illuminate\Support\Collection
    {
        $normKomoditi = in_array(strtoupper((string) $this->komoditi), ['OBA', 'OT'], true)
            ? ['OBA', 'OT']
            : [strtoupper((string) $this->komoditi)];
        $targetKey = $this->group_key;

        return static::with('uploader')
            ->whereIn('komoditi', $normKomoditi)
            ->where('periode_tahun', $this->periode_tahun)
            ->where('periode_bulan', $this->periode_bulan)
            ->orderBy('id', 'desc')
            ->get()
            ->filter(fn (self $ds) => $ds->group_key === $targetKey)
            ->values();
    }

    /**
     * Ringkasan progres input 2 kolom manual milik pemilik dataset.
     * Baris "Menunggu Evaluasi" tidak dihitung karena tidak wajib diisi.
     */
    public function progressStats(): array
    {
        $totalRows = $this->dataMentah()->count();
        $wajibIsi = max(0, $totalRows - (int) $this->jumlah_menunggu_evaluasi);
        $completed = $this->dataMentah()
            ->whereNotNull('kualitas_laporan')
            ->whereNotNull('ketepatan_waktu')
            ->count();
        $completed = min($completed, $wajibIsi);

        return [
            'total' => $totalRows,
            'total_rows' => $totalRows,
            'wajib_isi' => $wajibIsi,
            'completed' => $completed,
            'pending' => $wajibIsi - $completed,
            'percentage' => $wajibIsi > 0 ? round(($completed / $wajibIsi) * 100, 1) : 0.0,
            'is_complete' => $wajibIsi > 0 && $completed >= $wajibIsi,
        ];
    }

    /**
     * Mengalihkan kepemilikan dataset beserta seluruh barisnya ke pengguna lain (khusus admin).
     */
    public function transferOwnership(User $user): int
    {
        $this->update(['uploaded_by' => $user->id]);

        $affected = DataMentah::where('dataset_id', $this->id)->update(['evaluator_id' => $user->id]);
        DataIklan::where('dataset_id', $this->id)->update(['evaluator_id' => $user->id]);

        return $affected;
    }

    /**
     * Mendapatkan daftar peringatan kode master yang belum terstandarisasi (misal: UPT baru yang belum terdaftar di Master Data).
     */
    public function getUnstandardizedWarnings(?array $datasetIds = null): array
    {
        $datasetIds = $datasetIds ?: [$this->id];
        $warnings = [];

        // 1. Periksa kolom UPT pada Data Mentah
        $rawUpts = DataMentah::whereIn('dataset_id', $datasetIds)
            ->whereNotNull('upt')
            ->where('upt', '!=', '')
            ->where('upt', '!=', '-')
            ->distinct()
            ->pluck('upt');

        $stdService = app(\App\Services\Iklan\StandardizationService::class);
        $unregisteredUpts = [];

        if ($rawUpts->isNotEmpty()) {
            foreach ($rawUpts as $rawUpt) {
                $trimmed = trim($rawUpt);
                if ($trimmed === '' || $trimmed === '-') {
                    continue;
                }
                if ($stdService->resolveUpt($trimmed) === null) {
                    $unregisteredUpts[] = $trimmed;
                }
            }
        } else {
            $issueUpts = DatasetIssue::whereIn('dataset_id', $datasetIds)
                ->where('kolom', 'UPT')
                ->where('tingkat', 'warning')
                ->pluck('pesan')
                ->map(function ($pesan) {
                    if (preg_match("/Unit Pelaksana Teknis '([^']+)'/", $pesan, $m)) {
                        return $m[1];
                    }
                    return null;
                })
                ->filter()
                ->all();
            $unregisteredUpts = array_merge($unregisteredUpts, $issueUpts);
        }

        $unregisteredUpts = array_values(array_unique(array_filter($unregisteredUpts)));
        if (! empty($unregisteredUpts)) {
            $warnings['upt'] = $unregisteredUpts;
        }

        return $warnings;
    }
}
