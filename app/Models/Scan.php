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
        'uuid',
        'url',
        'normalized_url',
        'status',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
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

    public function result(): HasOne
    {
        return $this->hasOne(ScanResult::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
