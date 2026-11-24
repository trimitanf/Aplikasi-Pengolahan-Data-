<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataPenandaan extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'data_penandaan';

    protected $fillable = [
        'dataset_penandaan_id',
        'data_mentah_penandaan_id',
        'evaluator_id',
        'no',
        'upt',
        'periode_bulan',
        'periode_tahun',
        'jenis_produk',
        'asal_produk',
        'hasil_evaluasi',
        'rincian_tmk',
        'jenis_tmk',
        'kesesuaian_evaluasi',
        'kualitas_laporan',
        'ketepatan_waktu',
        'status_standardisasi',
        'catatan_tidak_terpetakan',
        'is_final',
    ];

    protected $casts = [
        'dataset_penandaan_id' => 'integer',
        'data_mentah_penandaan_id' => 'integer',
        'evaluator_id' => 'integer',
        'no' => 'integer',
        'upt' => 'integer',
        'periode_bulan' => 'integer',
        'periode_tahun' => 'integer',
        'jenis_produk' => 'integer',
        'asal_produk' => 'integer',
        'hasil_evaluasi' => 'integer',
        'rincian_tmk' => 'integer',
        'jenis_tmk' => 'integer',
        'kesesuaian_evaluasi' => 'integer',
        'kualitas_laporan' => 'integer',
        'ketepatan_waktu' => 'integer',
        'catatan_tidak_terpetakan' => 'array',
        'is_final' => 'boolean',
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(DatasetPenandaan::class, 'dataset_penandaan_id');
    }

    public function dataMentah(): BelongsTo
    {
        return $this->belongsTo(DataMentahPenandaan::class, 'data_mentah_penandaan_id');
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function uptMaster(): BelongsTo
    {
        return $this->belongsTo(Upt::class, 'upt', 'kode');
    }
}
