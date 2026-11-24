<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DatasetIssue extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dataset_issues';

    protected $fillable = [
        'dataset_id',
        'nomor_baris',
        'kolom',
        'tingkat',
        'pesan',
    ];

    protected function casts(): array
    {
        return [
            'nomor_baris' => 'integer',
        ];
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(Dataset::class);
    }
}
