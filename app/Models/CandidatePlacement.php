<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidatePlacement extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'candidate_id', 'employer_name', 'employer_address',
        'designation', 'monthly_salary', 'joining_date',
        'placement_proof_path', 'status',
        'verified_by', 'verified_at',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'verified_at' => 'datetime',
        'monthly_salary' => 'decimal:2',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
