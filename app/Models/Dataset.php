<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dataset extends Model
{
    use HasFactory;

    protected $table = 'datasets';

    protected $fillable = [
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

    public function issues(): HasMany
    {
        return $this->hasMany(DatasetIssue::class);
    }

    public function dataMentah(): HasMany
    {
        return $this->hasMany(DataMentah::class);
    }

    public function isFinal(): bool
    {
        return $this->status === 'Final';
    }

    public function canBeDeleted(): bool
    {
        return ! $this->isFinal();
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
            'OT' => 'Obat Tradisional (OT)',
            'OK' => 'Obat Kuasi (OK)',
            'SK' => 'Suplemen Kesehatan (SK)',
            default => $this->komoditi,
        };
    }
}
