<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasVerificationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

class Vendor extends Model
{
    use Auditable, HasFactory, HasVerificationStatus, SoftDeletes;

    protected $fillable = [
        'user_id', 'application_no', 'org_name', 'org_type',
        'pan', 'gst', 'tan', 'registration_no',
        'registered_address', 'communication_address',
        'contact_person', 'contact_email', 'contact_mobile',
        'status', 'rejection_reason',
        'submitted_at', 'approved_at', 'approved_by',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Vendor $vendor): void {
            if ($vendor->status === 'active') {
                $kyc = $vendor->kyc;
                if (! $kyc || $kyc->status !== 'verified') {
                    throw new RuntimeException(
                        'Vendor cannot be activated: KYC must be verified first.'
                    );
                }
            }
        });
    }

    public function auditTags(): array
    {
        return ['vendor'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function centers(): HasMany
    {
        return $this->hasMany(VendorCenter::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VendorDocument::class);
    }

    public function kyc(): HasOne
    {
        return $this->hasOne(VendorKyc::class);
    }

    public function inspections(): HasMany
    {
        return $this->hasMany(VendorInspection::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeOwnedBy(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }
}
