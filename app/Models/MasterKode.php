<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterKode extends Model
{
    use HasFactory;

    protected $table = 'master_kodes';

    protected $fillable = [
        'grup',
        'kode',
        'nama',
        'kelompok_rekap',
    ];

    protected function casts(): array
    {
        return [
            'kode' => 'integer',
        ];
    }
}
