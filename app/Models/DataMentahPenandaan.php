<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataMentahPenandaan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'data_mentah_penandaan';

    protected $fillable = [
        'dataset_penandaan_id',
        'baris_ke',
        'upt',
        'nama_produk',
        'bentuk_sediaan',
        'nomor_izin_edar',
        'produsen',
        'pendaftar',
        'hasil_evaluasi_sipt',
        'kesimpulan_pusat',
        'payload',
        'evaluator_id',
        'kualitas_laporan',
        'ketepatan_waktu',
        'kesesuaian_evaluasi',
    ];

    protected $casts = [
        'baris_ke' => 'integer',
        'payload' => 'array',
        'evaluator_id' => 'integer',
        'kualitas_laporan' => 'integer',
        'ketepatan_waktu' => 'integer',
        'kesesuaian_evaluasi' => 'integer',
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(DatasetPenandaan::class, 'dataset_penandaan_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function dataPenandaan(): HasOne
    {
        return $this->hasOne(DataPenandaan::class, 'data_mentah_penandaan_id');
    }
}
