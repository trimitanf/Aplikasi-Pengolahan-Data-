<?php

namespace Database\Seeders;

use App\Models\MasterKode;
use Illuminate\Database\Seeder;

class MasterKodeSeeder extends Seeder
{
    public function run(): void
    {
        $masters = [
            'bulan' => [
                1 => 'JANUARI',
                2 => 'FEBRUARI',
                3 => 'MARET',
                4 => 'APRIL',
                5 => 'MEI',
                6 => 'JUNI',
                7 => 'JULI',
                8 => 'AGUSTUS',
                9 => 'SEPTEMBER',
                10 => 'OKTOBER',
                11 => 'NOVEMBER',
                12 => 'DESEMBER',
            ],
            'jenis_produk' => [
                1 => 'OT',
                2 => 'SK',
                3 => 'OK',
            ],
            'asal_produk' => [
                1 => 'LOKAL',
                2 => 'IMPOR',
                3 => 'LISENSI',
            ],
            'klaim' => [
                1 => 'Membantu memelihara kesehatan tubuh, membantu memelihara daya tahan tubuh, membantu menghangatkan badan, membantu menyegarkan badan',
                2 => 'Membantu meredakan batuk, membantu melegakan tenggorokan, membantu meredakan selesma atau pilek, membantu melegakan hidung tersumbat',
                3 => 'Membantu memelihara kesehatan pria, membantu memelihara stamina pria',
                4 => 'Membantu memelihara kesehatan pencernaan, membantu meringankan gangguan lambung, membantu meredakan mual muntah, membantu meredakan rasa mulas',
                5 => 'Membantu meredakan sakit kepala, membantu meredakan pegal linu, membantu meringankan bengkak atau memar, membantu meredakan sakit gigi, sebagai parem untuk membantu meredakan pegal linu atau bengkak, membantu meredakakan demam',
                6 => 'Membantu melancarkan buang air besar, membantu memadatkan tinja, membantu mengurangi frekuensi buang air, membantu mengurangi lemak tubuh, membantu menurunkan berat badan',
                7 => 'Membantu mengurangi lemak darah, membantu mengurangi kolesterol',
                8 => 'Membantu memelihara kesehatan wanita sehabis bersalin, membantu melancarkan haid, membantu meredakan nyeri haid, memelihara kesehatan wanita, membantu meringankan gejala menopause, membantu mengurangi lendir yang berlebihan',
                9 => 'Membantu memelihara kesehatan kulit, membantu mengurangi jerawat, membantu meredakan gatal- gatal dikulit',
                10 => 'Membantu meredakan gejala panas dalam, membantu meredakan sariawan',
                11 => 'Membantu memperbaiki nafsu makan',
                12 => 'Membantu melancarkan sirkulasi darah, membantu meringankan gejala kencing manis',
                13 => 'Membantu melancarkan buang air buang air kecil, membantu meringankan tekanan darah tinggi',
                14 => 'Membantu meringankan gejala wasir',
                15 => 'Klaim lainnya (diluar klaim diatas)',
                16 => 'Membantu memelihara kesehatan penderita kanker',
                17 => 'Multivitamin dan mineral',
                18 => 'Suplemen Kesehatan untuk memelihara kesehatan',
                19 => 'Kesehatan Sendi',
                20 => 'Suplemen Stamina Pria',
                21 => 'Suplemen Kesehatan untuk diabetes',
                22 => 'Suplemen Kesehatan dalam rangka Gym',
                23 => 'Suplemen Kesehatan untuk Nafsu Makan',
                24 => 'Suplemen Kesehatan klaim pelangsing',
                25 => 'Klaim lainnya (diluar klaim diatas)',
            ],
            'kategori_media' => [
                1 => 'AUDIO',
                2 => 'VISUAL',
                3 => 'AUDIO VISUAL',
            ],
            'kategori_iklan' => [
                1 => 'E-COMMERCE',
                2 => 'WEBSITE',
                3 => 'MEDIA SOSIAL',
                4 => 'INTERNET LAIN',
                5 => 'TV NASIONAL',
                6 => 'PENYIARAN LOKAL',
                7 => 'LUAR RUANG',
                8 => 'CETAK',
            ],
            'nama_media' => [
                1 => 'SHOPEE',
                2 => 'TOKOPEDIA',
                3 => 'TIKTOK SHOP',
                4 => 'BLIBLI',
                5 => 'LAZADA',
                6 => 'INSTAGRAM',
                7 => 'FACEBOOK',
                8 => 'YOUTUBE',
                9 => 'X/TWITTER',
                10 => 'TIKTOK',
                11 => 'FACEBOOK MARKETPLACE',
                12 => 'TELEMEDISIN',
                13 => 'RCTI',
                14 => 'SCTV',
                15 => 'TRANS 7',
                16 => 'INDOSIAR',
                17 => 'GTV',
                18 => 'MNC',
                19 => 'TRANS TV',
            ],
            'pencantuman_nie' => [
                1 => 'ADA',
                2 => 'TIDAK ADA',
            ],
            'kesesuaian_nie' => [
                1 => 'SESUAI',
                2 => 'TIDAK SESUAI',
            ],
            'hasil_evaluasi' => [
                1 => 'MK',
                2 => 'TMK',
            ],
            'rincian_tmk' => [
                1 => 'Mencantumkan testimoni',
                2 => 'Memberikan hadiah',
                3 => 'Mencantumkan klaim berlebihan',
                4 => 'Mencantumkan testimoni',
                5 => 'Memberikan hadiah',
                6 => 'Mencantumkan klaim berlebihan',
                7 => 'TIE',
                8 => 'Lain-lain',
            ],
            'jenis_tmk' => [
                1 => 'KRITIS',
                2 => 'MAYOR',
                3 => 'MINOR',
            ],
            'pembuat_iklan' => [
                1 => 'PELAKU USAHA',
                2 => 'PERSEORANGAN',
            ],
            'kualitas_laporan' => [
                1 => 'LENGKAP & BENAR',
                2 => 'TIDAK LENGKAP',
            ],
            'ketepatan_waktu' => [
                1 => 'TEPAT WAKTU',
                2 => 'TIDAK TEPAT WAKTU',
            ],
            'kesesuaian_evaluasi' => [
                1 => 'SESUAI',
                2 => 'TIDAK SESUAI',
            ],
        ];

        foreach ($masters as $grup => $items) {
            foreach ($items as $kode => $nama) {
                MasterKode::updateOrCreate(
                    [
                        'grup' => $grup,
                        'kode' => $kode,
                    ],
                    [
                        'nama' => $nama,
                    ]
                );
            }
        }
    }
}
