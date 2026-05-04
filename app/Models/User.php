<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * EPR User.
 *
 * Roles (assigned via spatie/laravel-permission): admin, vendor, trainer, candidate, finance, inspector.
 *
 * The `mobile` column is the primary identifier for OTP-based login. `email`
 * is optional and only used for admin-style accounts.
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, HasRoles, Notifiable, SoftDeletes;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_VENDOR = 'vendor';

    public const ROLE_TRAINER = 'trainer';

    public const ROLE_CANDIDATE = 'candidate';

    public const ROLE_FINANCE = 'finance';

    public const ROLE_INSPECTOR = 'inspector';

    protected $fillable = [
        'name', 'email', 'mobile',
        'email_verified_at', 'mobile_verified_at',
        'password', 'status',
        'last_login_at', 'last_login_ip',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    public function trainer(): HasOne
    {
        return $this->hasOne(Trainer::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }
}
