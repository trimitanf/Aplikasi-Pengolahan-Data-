<?php

namespace Database\Seeders;

use App\Models\TargetIku;
use Illuminate\Database\Seeder;

class TargetIkuSeeder extends Seeder
{
    public function run(): void
    {
        $targets = [
            ['tahun' => 2026, 'kelompok' => 'OBA', 'target' => 67.00],
            ['tahun' => 2026, 'kelompok' => 'SK', 'target' => 83.00],
        ];

        foreach ($targets as $item) {
            TargetIku::updateOrCreate(
                [
                    'tahun' => $item['tahun'],
                    'kelompok' => $item['kelompok'],
                ],
                [
                    'target' => $item['target'],
                ]
            );
        }
    }
}
