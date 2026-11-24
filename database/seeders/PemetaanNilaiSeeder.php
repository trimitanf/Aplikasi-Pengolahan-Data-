<?php

namespace Database\Seeders;

use App\Models\PemetaanNilai;
use Illuminate\Database\Seeder;

class PemetaanNilaiSeeder extends Seeder
{
    public function run(): void
    {
        $pemetaan = [
            // Jenis Produk (kolom_sumber: JENIS PRODUK)
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OBAT BAHAN ALAM', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OBAT BAHAN ALAM (OBA)', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OBA', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'BAHAN ALAM', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OBAT TRADISIONAL', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OBAT TRADISIONAL (OT)', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OT', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'TRADISIONAL', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'JAMU', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'HERBAL', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'FITOFARMAKA', 'kode' => 1],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'SUPLEMEN KESEHATAN', 'kode' => 2],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'SK', 'kode' => 2],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'SUPLEMEN', 'kode' => 2],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OBAT KUASI', 'kode' => 3],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'OK', 'kode' => 3],
            ['grup' => 'jenis_produk', 'kolom_sumber' => 'JENIS PRODUK', 'nilai_sipt' => 'KUASI', 'kode' => 3],

            // Asal Produk (kolom_sumber: ASAL PRODUK)
            ['grup' => 'asal_produk', 'kolom_sumber' => 'ASAL PRODUK', 'nilai_sipt' => 'LOKAL', 'kode' => 1],
            ['grup' => 'asal_produk', 'kolom_sumber' => 'ASAL PRODUK', 'nilai_sipt' => 'DALAM NEGERI', 'kode' => 1],
            ['grup' => 'asal_produk', 'kolom_sumber' => 'ASAL PRODUK', 'nilai_sipt' => 'IMPOR', 'kode' => 2],
            ['grup' => 'asal_produk', 'kolom_sumber' => 'ASAL PRODUK', 'nilai_sipt' => 'IMPORT', 'kode' => 2],
            ['grup' => 'asal_produk', 'kolom_sumber' => 'ASAL PRODUK', 'nilai_sipt' => 'LISENSI', 'kode' => 3],

            // Kategori Media (kolom_sumber: KATEGORI MEDIA)
            ['grup' => 'kategori_media', 'kolom_sumber' => 'KATEGORI MEDIA', 'nilai_sipt' => 'AUDIO', 'kode' => 1],
            ['grup' => 'kategori_media', 'kolom_sumber' => 'KATEGORI MEDIA', 'nilai_sipt' => 'VISUAL', 'kode' => 2],
            ['grup' => 'kategori_media', 'kolom_sumber' => 'KATEGORI MEDIA', 'nilai_sipt' => 'AUDIO VISUAL', 'kode' => 3],
            ['grup' => 'kategori_media', 'kolom_sumber' => 'KATEGORI MEDIA', 'nilai_sipt' => 'AUDIOVISUAL', 'kode' => 3],

            // Kategori Iklan (kolom_sumber: MEDIA IKLAN)
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'E-COMMERCE', 'kode' => 1],
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'ECOMMERCE', 'kode' => 1],
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'MARKETPLACE', 'kode' => 1],
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'WEBSITE', 'kode' => 2],
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'MEDIA SOSIAL', 'kode' => 3],
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'MEDIASOSIAL', 'kode' => 3],
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'INTERNET LAIN', 'kode' => 4],
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'INTERNET LAINNYA', 'kode' => 4],
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'INTERNETLAIN', 'kode' => 4],
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'TV NASIONAL', 'kode' => 5],
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'TVNASIONAL', 'kode' => 5],
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'PENYIARAN LOKAL', 'kode' => 6],
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'PENYIARANLOKAL', 'kode' => 6],
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'LUAR RUANG', 'kode' => 7],
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'LUARRUANG', 'kode' => 7],
            ['grup' => 'kategori_iklan', 'kolom_sumber' => 'MEDIA IKLAN', 'nilai_sipt' => 'CETAK', 'kode' => 8],

            // Pembuat Iklan (kolom_sumber: PEMBUAT IKLAN)
            ['grup' => 'pembuat_iklan', 'kolom_sumber' => 'PEMBUAT IKLAN', 'nilai_sipt' => 'PELAKU USAHA', 'kode' => 1],
            ['grup' => 'pembuat_iklan', 'kolom_sumber' => 'PEMBUAT IKLAN', 'nilai_sipt' => 'PERSEORANGAN', 'kode' => 2],
            ['grup' => 'pembuat_iklan', 'kolom_sumber' => 'PEMBUAT IKLAN', 'nilai_sipt' => 'PERORANGAN', 'kode' => 2],

            // Kesesuaian Evaluasi (kolom_sumber: KESESUAIAN HASIL EVALUASI)
            ['grup' => 'kesesuaian_evaluasi', 'kolom_sumber' => 'KESESUAIAN HASIL EVALUASI', 'nilai_sipt' => 'SESUAI', 'kode' => 1],
            ['grup' => 'kesesuaian_evaluasi', 'kolom_sumber' => 'KESESUAIAN HASIL EVALUASI', 'nilai_sipt' => 'TIDAK SESUAI', 'kode' => 2],

            // Nama Media (kolom_sumber: TEMPAT PENGAMBILAN IKLAN)
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'SHOPEE', 'kode' => 1],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'TOKOPEDIA', 'kode' => 2],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'TIKTOK SHOP', 'kode' => 3],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'TIKTOKSHOP', 'kode' => 3],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'BLIBLI', 'kode' => 4],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'LAZADA', 'kode' => 5],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'INSTAGRAM', 'kode' => 6],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'FACEBOOK', 'kode' => 7],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'YOUTUBE', 'kode' => 8],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'X/TWITTER', 'kode' => 9],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'TWITTER', 'kode' => 9],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'TIKTOK', 'kode' => 10],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'FACEBOOK MARKETPLACE', 'kode' => 11],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'FB MARKETPLACE', 'kode' => 11],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'TELEMEDISIN', 'kode' => 12],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'HALODOC', 'kode' => 12],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'ALODOKTER', 'kode' => 12],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'RCTI', 'kode' => 13],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'SCTV', 'kode' => 14],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'TRANS 7', 'kode' => 15],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'TRANS7', 'kode' => 15],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'INDOSIAR', 'kode' => 16],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'GTV', 'kode' => 17],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'MNC', 'kode' => 18],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'MNCTV', 'kode' => 18],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'TRANS TV', 'kode' => 19],
            ['grup' => 'nama_media', 'kolom_sumber' => 'TEMPAT PENGAMBILAN IKLAN', 'nilai_sipt' => 'TRANSTV', 'kode' => 19],
        ];

        foreach ($pemetaan as $item) {
            PemetaanNilai::updateOrCreate(
                [
                    'kolom_sumber' => $item['kolom_sumber'],
                    'nilai_sipt' => $item['nilai_sipt'],
                ],
                [
                    'grup' => $item['grup'],
                    'kode' => $item['kode'],
                    'is_active' => true,
                ]
            );
        }
    }
}
