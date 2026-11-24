<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DatasetPenandaan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'datasets_penandaan';

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
        'uploaded_by',
        'uploaded_at',
        'finalized_at',
    ];

    protected $casts = [
        'periode_tahun' => 'integer',
        'periode_bulan' => 'integer',
        'periode_mulai' => 'date',
        'periode_akhir' => 'date',
        'jumlah_baris' => 'integer',
        'jumlah_menunggu_evaluasi' => 'integer',
        'uploaded_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function dataMentah(): HasMany
    {
        return $this->hasMany(DataMentahPenandaan::class, 'dataset_penandaan_id');
    }

    public function dataPenandaan(): HasMany
    {
        return $this->hasMany(DataPenandaan::class, 'dataset_penandaan_id');
    }

    public function isFinal(): bool
    {
        return $this->status === 'Final';
    }

    public function canBeEditedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $user->isEvaluatorPenandaan() && (int) $this->uploaded_by === (int) $user->id;
    }
}
