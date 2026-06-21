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
        'visibility_data',
        'ai_visibility_data',
        'geo_data',
        'aeo_data',
        'topic_intelligence_data',
        'ranking_potential_data',
        'prompt_intelligence_data',
        'content_coverage_data',
        'ai_citation_readiness_data',
        'keyword_targeting_data',
        'opportunity_data',
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
        'visibility_data' => 'array',
        'ai_visibility_data' => 'array',
        'geo_data' => 'array',
        'aeo_data' => 'array',
        'topic_intelligence_data' => 'array',
        'ranking_potential_data' => 'array',
        'prompt_intelligence_data' => 'array',
        'content_coverage_data' => 'array',
        'ai_citation_readiness_data' => 'array',
        'keyword_targeting_data' => 'array',
        'opportunity_data' => 'array',
        'score_breakdown' => 'array',
        'raw' => 'array',
    ];

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }
}
