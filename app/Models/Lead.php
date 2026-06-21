<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'scan_id',
        'name',
        'email',
        'phone',
        'company_name',
        'status',
        'assigned_user_id',
        'last_contacted_at',
        'source_report_uuid',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_contacted_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scan(): BelongsTo
    {
        return $this->belongsTo(Scan::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }
}
