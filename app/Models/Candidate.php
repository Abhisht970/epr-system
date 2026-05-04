<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasVerificationStatus;
use App\Support\AadhaarVault;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * Candidate.
 *
 * Aadhaar handling:
 *   - Set via $candidate->aadhaar = '123456789012';
 *     Mutator stores the encrypted ciphertext, HMAC hash and last-4 digits.
 *   - Read via $candidate->aadhaar (decrypts), or $candidate->aadhaar_masked.
 *   - Dedup: unique index on `aadhaar_hash`.
 *
 * Status transitions are enforced via candidate_status_history (see service layer).
 */
class Candidate extends Model
{
    use Auditable, HasFactory, HasVerificationStatus, SoftDeletes;

    public const STATUS_REGISTERED = 'registered';

    public const STATUS_TRAINING = 'training';

    public const STATUS_ASSESSED = 'assessed';

    public const STATUS_CERTIFIED = 'certified';

    public const STATUS_PLACED = 'placed';

    public const STATUS_DROPOUT = 'dropout';

    /**
     * Allowed forward transitions. Backward / skipping transitions are blocked
     * by App\Services\CandidateLifecycle.
     *
     * @var array<string, list<string>>
     */
    public const ALLOWED_TRANSITIONS = [
        self::STATUS_REGISTERED => [self::STATUS_TRAINING, self::STATUS_DROPOUT],
        self::STATUS_TRAINING => [self::STATUS_ASSESSED, self::STATUS_DROPOUT],
        self::STATUS_ASSESSED => [self::STATUS_CERTIFIED, self::STATUS_DROPOUT],
        self::STATUS_CERTIFIED => [self::STATUS_PLACED],
        self::STATUS_PLACED => [],
        self::STATUS_DROPOUT => [],
    ];

    protected $fillable = [
        'application_id',
        'aadhaar_encrypted', 'aadhaar_hash', 'aadhaar_last4',
        'full_name', 'dob', 'gender', 'mobile', 'email',
        'address', 'district', 'state', 'pincode',
        'education', 'category', 'pwd', 'bpl',
        'vendor_id', 'vendor_center_id', 'scheme_id', 'course_id',
        'batch_no', 'status',
        'enrolled_at', 'status_changed_at',
    ];

    protected $hidden = ['aadhaar_encrypted', 'aadhaar_hash'];

    protected $casts = [
        'dob' => 'date',
        'pwd' => 'boolean',
        'bpl' => 'boolean',
        'enrolled_at' => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    public function auditTags(): array
    {
        return ['candidate'];
    }

    public function auditExclude(): array
    {
        return [
            'updated_at', 'remember_token', 'password',
            // never reveal encrypted Aadhaar in audit diffs
            'aadhaar_encrypted', 'aadhaar_hash',
        ];
    }

    public function setAadhaarAttribute(string $aadhaar): void
    {
        $normalized = AadhaarVault::normalize($aadhaar);
        $this->attributes['aadhaar_encrypted'] = AadhaarVault::encrypt($normalized);
        $this->attributes['aadhaar_hash'] = AadhaarVault::hash($normalized);
        $this->attributes['aadhaar_last4'] = AadhaarVault::last4($normalized);
    }

    public function getAadhaarAttribute(): ?string
    {
        if (empty($this->attributes['aadhaar_encrypted'])) {
            return null;
        }

        return AadhaarVault::decrypt($this->attributes['aadhaar_encrypted']);
    }

    public function getAadhaarMaskedAttribute(): string
    {
        $last4 = $this->aadhaar_last4 ?? '----';

        return 'XXXX-XXXX-'.$last4;
    }

    public function canTransitionTo(string $to): bool
    {
        $allowed = self::ALLOWED_TRANSITIONS[$this->status] ?? [];

        return in_array($to, $allowed, true);
    }

    public function assertCanTransitionTo(string $to): void
    {
        if (! $this->canTransitionTo($to)) {
            throw new InvalidArgumentException(
                sprintf('Illegal candidate status transition: %s -> %s', $this->status, $to)
            );
        }
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function center(): BelongsTo
    {
        return $this->belongsTo(VendorCenter::class, 'vendor_center_id');
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(CandidateDocument::class);
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(CandidateAssessment::class);
    }

    public function ojt(): HasMany
    {
        return $this->hasMany(CandidateOjt::class);
    }

    public function placements(): HasMany
    {
        return $this->hasMany(CandidatePlacement::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(CandidateStatusHistory::class)->orderBy('id');
    }

    public function scopeForVendor(Builder $query, Vendor $vendor): Builder
    {
        return $query->where('vendor_id', $vendor->id);
    }
}
