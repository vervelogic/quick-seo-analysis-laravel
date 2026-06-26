<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Scan extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'workspace_id',
        'project_id',
        'uuid',
        'url',
        'normalized_url',
        'scan_mode',
        'target_keywords',
        'status',
        'error_message',
        'started_at',
        'completed_at',
        'legacy_id',
        'legacy_source',
        'legacy_client_id',
        'legacy_audit_type',
        'legacy_score',
        'legacy_created_at',
        'normalized_domain',
    ];

    protected $casts = [
        'target_keywords' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'legacy_created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Scan $scan): void {
            if (! $scan->uuid) {
                $scan->uuid = (string) Str::uuid();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function result(): HasOne
    {
        return $this->hasOne(ScanResult::class);
    }

    public function legacySnapshot(): HasOne
    {
        return $this->hasOne(LegacyReportSnapshot::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
