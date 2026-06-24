<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyReportSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_id',
        'user_id',
        'legacy_source',
        'legacy_table',
        'legacy_id',
        'legacy_client_id',
        'source_url',
        'payload',
        'payload_hash',
        'metadata',
        'legacy_created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'legacy_created_at' => 'datetime',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
