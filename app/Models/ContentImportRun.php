<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentImportRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'source',
        'type',
        'status',
        'dry_run',
        'summary',
        'failures',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'dry_run' => 'boolean',
            'summary' => 'array',
            'failures' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
