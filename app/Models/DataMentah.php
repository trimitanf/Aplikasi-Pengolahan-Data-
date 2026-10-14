<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataMentah extends Model
{
    use HasFactory;

    protected $table = 'data_mentah';

    protected $fillable = [
        'dataset_id',
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
    ];

    protected function casts(): array
    {
        return [
            'nomor_baris' => 'integer',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }
}
