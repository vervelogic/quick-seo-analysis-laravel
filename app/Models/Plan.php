<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_cents',
        'interval',
        'features',
        'limits',
        'monthly_scans',
        'team_members',
        'storage_mb',
        'allows_white_label_reports',
        'allows_pdf_exports',
        'allows_ai_reports',
        'allows_api_access',
        'allows_competitor_tracking',
        'allows_scheduled_scans',
        'allows_projects',
        'allows_custom_branding',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'features' => 'array',
        'limits' => 'array',
        'monthly_scans' => 'integer',
        'team_members' => 'integer',
        'storage_mb' => 'integer',
        'allows_white_label_reports' => 'boolean',
        'allows_pdf_exports' => 'boolean',
        'allows_ai_reports' => 'boolean',
        'allows_api_access' => 'boolean',
        'allows_competitor_tracking' => 'boolean',
        'allows_scheduled_scans' => 'boolean',
        'allows_projects' => 'boolean',
        'allows_custom_branding' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function featureEnabled(string $feature): bool
    {
        $column = 'allows_'.$feature;

        if (array_key_exists($column, $this->attributes)) {
            return (bool) $this->{$column};
        }

        return (bool) ($this->features[$feature] ?? false);
    }

    public function limit(string $limit): mixed
    {
        if (array_key_exists($limit, $this->attributes)) {
            return $this->{$limit};
        }

        return $this->limits[$limit] ?? null;
    }
}
