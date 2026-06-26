<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyAccount extends Model
{
    use HasFactory;

    public const STATUS_PENDING_CLAIM = 'pending_claim';
    public const STATUS_CLAIMED = 'claimed';

    protected $fillable = [
        'user_id',
        'company_id',
        'workspace_id',
        'legacy_source',
        'legacy_id',
        'name',
        'email',
        'status',
        'scan_count',
        'report_count',
        'registered_at',
        'last_activity_at',
        'claimed_at',
        'metadata',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'claimed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function isPendingClaim(): bool
    {
        return $this->status === self::STATUS_PENDING_CLAIM;
    }

    public function isClaimed(): bool
    {
        return $this->status === self::STATUS_CLAIMED;
    }
}
