<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetIku extends Model
{
    use HasFactory;

    protected $table = 'target_iku';

    protected $fillable = [
        'tahun',
        'kelompok',
        'target',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'target' => 'float',
        ];
    }
}
