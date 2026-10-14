<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'evaluator'],
            [
                'name' => 'Evaluator BPOM',
                'password' => Hash::make('password123'),
                'role' => 'evaluator',
                'is_active' => true,
            ]
        );
    }
}
