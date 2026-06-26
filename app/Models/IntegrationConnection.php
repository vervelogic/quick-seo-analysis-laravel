<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'provider',
        'status',
        'scopes',
        'metadata',
        'connected_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'metadata' => 'array',
        'connected_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
