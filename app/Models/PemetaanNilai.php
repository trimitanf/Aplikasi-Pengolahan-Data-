<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemetaanNilai extends Model
{
    use HasFactory;

    protected $table = 'pemetaan_nilai';

    protected $fillable = [
        'grup',
        'kolom_sumber',
        'nilai_sipt',
        'kode',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'kode' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
