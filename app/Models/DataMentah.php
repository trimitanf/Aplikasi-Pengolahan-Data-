<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class DataMentah extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'data_mentah';

    protected $fillable = [
        'dataset_id',
        'evaluator_id',
        'nomor_baris',
        'no',
        'upt',
        'tanggal_pengambilan_iklan',
        'tgl_kirim_ka_upt_ke_pusat',
        'tanggal_evaluasi_pusat',
        'jenis_produk',
        'nama_produk',
        'nomor_izin_edar',
        'khasiat_indikasi',
        'pendaftar',
        'asal_produk',
        'kategori_media',
        'media_iklan',
        'tempat_pengambilan_iklan',
        'pencantuman_nomor_izin_edar',
        'link_url_iklan',
        'klaim_dalam_promosi_iklan',
        'klasifikasi_klaim',
        'pembuat_iklan',
        'hasil_penilaian_iklan_upt',
        'kesimpulan_upt',
        'kesimpulan_pusat',
        'kesesuaian_hasil_evaluasi',
        'kategori_tmk',
        'rincian_tmk',
        'tindak_lanjut',
        'kesesuaian_evaluasi',
        'kualitas_laporan',
        'ketepatan_waktu',
    ];

    protected function casts(): array
    {
        return [
            'nomor_baris' => 'integer',
            'kesesuaian_evaluasi' => 'integer',
            'kualitas_laporan' => 'integer',
            'ketepatan_waktu' => 'integer',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function dataIklan(): HasOne
    {
        return $this->hasOne(DataIklan::class, 'data_mentah_id');
    }

    public function isManualComplete(): bool
    {
        return ! is_null($this->kualitas_laporan)
            && ! is_null($this->ketepatan_waktu);
    }

    public function isMenungguEvaluasi(): bool
    {
        return empty($this->kesimpulan_pusat) || $this->kesimpulan_pusat === '-';
    }
}
