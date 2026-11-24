<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = env('ADMIN_DEFAULT_PASSWORD', app()->isProduction() ? 'BPOM#Admin2026!' : 'password123');
        $evaluatorPassword = env('EVALUATOR_DEFAULT_PASSWORD', app()->isProduction() ? 'BPOM#Evaluator2026!' : 'password123');

        // Administrator Utama
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator BPOM',
                'password' => Hash::make($adminPassword),
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Evaluator Utama (Kompatibel dengan pengujian yang sudah ada)
        User::updateOrCreate(
            ['username' => 'evaluator'],
            [
                'name' => 'Evaluator BPOM 1',
                'password' => Hash::make($evaluatorPassword),
                'role' => 'evaluator',
                'is_active' => true,
            ]
        );

        // Evaluator 2 s.d. 6
        for ($i = 2; $i <= 6; $i++) {
            User::updateOrCreate(
                ['username' => "evaluator{$i}"],
                [
                    'name' => "Evaluator BPOM {$i}",
                    'password' => Hash::make($evaluatorPassword),
                    'role' => 'evaluator',
                    'is_active' => true,
                ]
            );
        }
    }
}
