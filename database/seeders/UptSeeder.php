<?php

namespace Database\Seeders;

use App\Models\Upt;
use Illuminate\Database\Seeder;

class UptSeeder extends Seeder
{
    public function run(): void
    {
        $upts = [
            1 => 'Balai Besar POM di Banda Aceh',
            2 => 'Loka POM di Kab. Aceh Tengah',
            3 => 'Loka POM di Kab. Aceh Selatan',
            4 => 'Balai Besar POM di Medan',
            5 => 'Balai POM di Tanjung Balai',
            6 => 'Loka POM di Kab. Toba Samosir',
            7 => 'Balai Besar POM di Padang',
            8 => 'Loka POM di Kab. Sijunjung',
            9 => 'Balai POM di Payakumbuh',
            10 => 'Balai Besar POM di Pekanbaru',
            11 => 'Balai POM di Dumai',
            12 => 'Balai POM di Indragiri Hulu',
            13 => 'Balai Besar POM di Palembang',
            14 => 'Loka POM di Kota Lubuklinggau',
            15 => 'Balai Besar POM di Bandar Lampung',
            16 => 'Balai POM di Tulang Bawang',
            17 => 'Balai POM di Jambi',
            18 => 'Loka POM di Kab. Bungo',
            19 => 'Balai POM di Bengkulu',
            20 => 'Loka POM di Kab. Rejang Lebong',
            21 => 'Balai POM di Pangkal Pinang',
            22 => 'Loka POM di Kab. Belitung',
            23 => 'Balai POM di Batam',
            24 => 'Loka POM di Kota Tanjung pinang',
            25 => 'Balai Besar POM di Jakarta',
            26 => 'Balai Besar POM di Bandung',
            27 => 'Balai POM di Tasikmalaya',
            28 => 'Balai POM di Bogor',
            29 => 'Balai Besar POM di Semarang',
            30 => 'Balai POM di Banyumas',
            31 => 'Balai POM di Surakarta',
            32 => 'Balai Besar POM di Yogyakarta',
            33 => 'Balai Besar POM di Surabaya',
            34 => 'Balai POM di Kediri',
            35 => 'Balai POM di Jember',
            36 => 'Balai Besar POM di Serang',
            37 => 'Balai POM di Tangerang',
            38 => 'Balai Besar POM di Denpasar',
            39 => 'Loka POM di Kab. Buleleng',
            40 => 'Balai Besar POM di Mataram',
            41 => 'Balai POM di Bima',
            42 => 'Balai POM di Kupang',
            43 => 'Balai POM di Ende',
            44 => 'Loka POM di Kab. Manggarai Barat',
            45 => 'Loka POM di Kab. Belu',
            46 => 'Loka POM di Kab. Sumba Timur',
            47 => 'Balai Besar POM di Pontianak',
            48 => 'Loka POM di Kab. Sanggau',
            49 => 'Loka POM di Kab. Sambas',
            50 => 'Balai Besar POM di Banjarbaru',
            51 => 'Balai POM di Tabalong',
            52 => 'Loka POM di Kab. Tanah Bumbu',
            53 => 'Balai Besar POM di Samarinda',
            54 => 'Balai POM di Balikpapan',
            55 => 'Balai POM di Tarakan',
            56 => 'Balai Besar POM di Palangkaraya',
            57 => 'Loka POM di Kab. Kotawaringin Barat',
            58 => 'Balai Besar POM di Manado',
            59 => 'Loka POM di Kab. Kepulauan Sangihe',
            60 => 'Balai Besar POM di Makassar',
            61 => 'Balai POM di Palopo',
            62 => 'Balai POM di Palu',
            63 => 'Loka POM di Kab. Banggai',
            64 => 'Balai POM di Kendari',
            65 => 'Balai POM di Baubau',
            66 => 'Balai POM di Ambon',
            67 => 'Loka POM di Kepulauan Tanimbar',
            68 => 'Balai POM di Gorontalo',
            69 => 'Balai Besar POM di Jayapura',
            70 => 'Loka POM di Kab. Merauke',
            71 => 'Loka POM di Kab. Mimika',
            72 => 'Balai POM di Sofifi',
            73 => 'Loka POM di Kab. Pulau Morotai',
            74 => 'Balai POM di Manokwari',
            75 => 'Loka POM di Kab. Sorong',
            76 => 'Balai POM di Mamuju',
            77 => 'Loka POM di Kota Gunungsitoli',
            78 => 'Loka POM di Kabupaten Karawang',
            79 => 'Loka POM di Kabupaten Indramayu',
            80 => 'Loka POM di Kabupaten Tegal',
            81 => 'Loka POM di Kabupaten Grobogan',
            82 => 'Loka POM di Kabupaten Madiun',
            83 => 'Loka POM di Kabupaten Bone',
        ];

        foreach ($upts as $kode => $nama) {
            Upt::updateOrCreate(
                ['kode' => $kode],
                ['nama' => $nama]
            );
        }
    }
}
