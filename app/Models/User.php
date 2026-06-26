<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use Notifiable;

    public const COMPANY_ROLE_OWNER = 'owner';
    public const COMPANY_ROLE_ADMIN = 'admin';
    public const COMPANY_ROLE_MANAGER = 'manager';
    public const COMPANY_ROLE_VIEWER = 'viewer';

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'role',
        'company_role',
        'permissions',
        'last_active_at',
        'is_admin',
        'legacy_id',
        'legacy_source',
        'legacy_imported_at',
        'legacy_login_provider',
        'invite_required',
        'legacy_metadata',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'last_active_at' => 'datetime',
            'is_admin' => 'boolean',
            'legacy_imported_at' => 'datetime',
            'invite_required' => 'boolean',
            'legacy_metadata' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scans(): HasMany
    {
        return $this->hasMany(Scan::class);
    }

    public function legacyAccounts(): HasMany
    {
        return $this->hasMany(LegacyAccount::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

    public function isCompanyOwner(): bool
    {
        return $this->company_role === self::COMPANY_ROLE_OWNER;
    }

    public function canManageCompany(): bool
    {
        return in_array($this->company_role, [
            self::COMPANY_ROLE_OWNER,
            self::COMPANY_ROLE_ADMIN,
        ], true);
    }

    public function canManageScans(): bool
    {
        return in_array($this->company_role, [
            self::COMPANY_ROLE_OWNER,
            self::COMPANY_ROLE_ADMIN,
            self::COMPANY_ROLE_MANAGER,
        ], true);
    }
}
