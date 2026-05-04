<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasVerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trainer extends Model
{
    use Auditable, HasFactory, HasVerificationStatus, SoftDeletes;

    protected $fillable = [
        'user_id', 'full_name', 'dob', 'gender',
        'qualification', 'experience_years',
        'tot_certificate_no', 'tot_issued_by', 'tot_valid_until',
        'sectors', 'status', 'rejection_reason',
    ];

    protected $casts = [
        'dob' => 'date',
        'tot_valid_until' => 'date',
        'sectors' => 'array',
        'experience_years' => 'integer',
    ];

    public function auditTags(): array
    {
        return ['trainer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TrainerDocument::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TrainerAssignment::class);
    }
}
