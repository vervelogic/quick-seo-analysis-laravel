<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScanResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'scan_id',
        'http_status',
        'is_reachable',
        'uses_https',
        'title',
        'title_length',
        'meta_description',
        'meta_description_length',
        'h1_count',
        'canonical',
        'robots_meta',
        'page_size_bytes',
        'response_time_ms',
        'internal_links_count',
        'external_links_count',
        'images_count',
        'images_missing_alt_count',
        'score',
        'checks',
        'recommendations',
        'technical_data',
        'on_page_data',
        'content_data',
        'performance_data',
        'security_data',
        'social_data',
        'structured_data',
        'ai_readiness_data',
        'score_breakdown',
        'raw',
    ];

    protected $casts = [
        'is_reachable' => 'boolean',
        'uses_https' => 'boolean',
        'checks' => 'array',
        'recommendations' => 'array',
        'technical_data' => 'array',
        'on_page_data' => 'array',
        'content_data' => 'array',
        'performance_data' => 'array',
        'security_data' => 'array',
        'social_data' => 'array',
        'structured_data' => 'array',
        'ai_readiness_data' => 'array',
        'score_breakdown' => 'array',
        'raw' => 'array',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }
}
