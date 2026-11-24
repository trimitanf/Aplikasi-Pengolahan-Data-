<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PenandaanUserSeeder extends Seeder
{
    public function run(): void
    {
        $penandaanPassword = env('PENANDAAN_DEFAULT_PASSWORD', app()->isProduction() ? 'BPOM#Penandaan2026!' : 'password123');

        // Evaluator Penandaan BPOM
        User::updateOrCreate(
            ['username' => 'penandaan'],
            [
                'name' => 'Evaluator Penandaan BPOM',
                'password' => Hash::make($penandaanPassword),
                'role' => 'evaluator_penandaan',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['username' => 'eval_penandaan'],
            [
                'name' => 'Evaluator Penandaan OTSK',
                'password' => Hash::make($penandaanPassword),
                'role' => 'evaluator_penandaan',
                'is_active' => true,
            ]
        );
    }
}
