<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Scheme extends Model
{
    use Auditable, HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'sponsor', 'description',
        'start_date', 'end_date', 'status', 'default_rules',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'default_rules' => 'array',
    ];

    public function eligibilityRules(): HasMany
    {
        return $this->hasMany(SchemeEligibilityRule::class);
    }

    public function paymentMilestones(): HasMany
    {
        return $this->hasMany(SchemePaymentMilestone::class)->orderBy('order');
    }

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'scheme_course')
            ->withPivot(['payout_per_candidate', 'max_batch_size'])
            ->withTimestamps();
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }
}
