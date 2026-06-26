<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'brand_settings',
        'settings',
        'plan_id',
        'subscription_status',
        'subscription_renews_at',
        'plan_overrides',
        'logo_path',
        'primary_color',
        'secondary_color',
        'accent_color',
        'contact_name',
        'contact_email',
        'contact_phone',
        'website_url',
        'billing_email',
        'white_label_enabled',
        'white_label_settings',
        'feature_flags',
        'usage_limits',
        'usage_counters',
        'legacy_company_logo',
        'legacy_pdf_logo',
        'legacy_company_description',
        'legacy_metadata',
    ];

    protected $casts = [
        'brand_settings' => 'array',
        'settings' => 'array',
        'subscription_renews_at' => 'datetime',
        'plan_overrides' => 'array',
        'white_label_enabled' => 'boolean',
        'white_label_settings' => 'array',
        'feature_flags' => 'array',
        'usage_limits' => 'array',
        'usage_counters' => 'array',
        'legacy_metadata' => 'array',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function reportTemplates(): HasMany
    {
        return $this->hasMany(ReportTemplate::class);
    }

    public function widgetKeys(): HasMany
    {
        return $this->hasMany(WidgetKey::class);
    }

    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    public function reportUsages(): HasMany
    {
        return $this->hasMany(ReportUsage::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function workspaces(): HasMany
    {
        return $this->hasMany(Workspace::class);
    }

    public function legacyAccounts(): HasMany
    {
        return $this->hasMany(LegacyAccount::class);
    }

    public function integrationConnections(): HasMany
    {
        return $this->hasMany(IntegrationConnection::class);
    }

    public function featureEnabled(string $feature): bool
    {
        $overrides = $this->plan_overrides['features'] ?? [];

        if (array_key_exists($feature, $overrides)) {
            return (bool) $overrides[$feature];
        }

        if (array_key_exists($feature, $this->feature_flags ?? [])) {
            return (bool) $this->feature_flags[$feature];
        }

        return (bool) ($this->plan?->featureEnabled($feature) ?? false);
    }

    public function planLimit(string $limit): mixed
    {
        $overrides = $this->plan_overrides['limits'] ?? [];

        if (array_key_exists($limit, $overrides)) {
            return $overrides[$limit];
        }

        if (array_key_exists($limit, $this->usage_limits ?? [])) {
            return $this->usage_limits[$limit];
        }

        return $this->plan?->limit($limit);
    }
}
