<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataIklan extends Model
{
    use HasFactory;

    protected $table = 'data_iklan';

    protected $fillable = [
        'dataset_id',
        'data_mentah_id',
        'no',
        'upt',
        'periode_bulan',
        'periode_tahun',
        'jenis_produk',
        'asal_produk',
        'klaim',
        'kategori_media',
        'kategori_iklan',
        'nama_media',
        'pencantuman_nie',
        'kesesuaian_nie',
        'hasil_evaluasi',
        'rincian_tmk',
        'jenis_tmk',
        'pembuat_iklan',
        'kesesuaian_evaluasi',
        'kualitas_laporan',
        'ketepatan_waktu',
        'status_standardisasi',
        'catatan_tidak_terpetakan',
        'is_final',
    ];

    protected function casts(): array
    {
        return [
            'no' => 'integer',
            'upt' => 'integer',
            'periode_bulan' => 'integer',
            'periode_tahun' => 'integer',
            'jenis_produk' => 'integer',
            'asal_produk' => 'integer',
            'klaim' => 'integer',
            'kategori_media' => 'integer',
            'kategori_iklan' => 'integer',
            'nama_media' => 'integer',
            'pencantuman_nie' => 'integer',
            'kesesuaian_nie' => 'integer',
            'hasil_evaluasi' => 'integer',
            'rincian_tmk' => 'integer',
            'jenis_tmk' => 'integer',
            'pembuat_iklan' => 'integer',
            'kesesuaian_evaluasi' => 'integer',
            'kualitas_laporan' => 'integer',
            'ketepatan_waktu' => 'integer',
            'catatan_tidak_terpetakan' => 'array',
            'is_final' => 'boolean',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function dataMentah(): BelongsTo
    {
        return $this->belongsTo(DataMentah::class);
    }

    public function isManualComplete(): bool
    {
        return ! is_null($this->kesesuaian_evaluasi)
            && ! is_null($this->kualitas_laporan)
            && ! is_null($this->ketepatan_waktu);
    }

    public function isMenungguEvaluasi(): bool
    {
        return is_null($this->hasil_evaluasi);
    }
}
