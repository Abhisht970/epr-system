<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateOjt extends Model
{
    use Auditable, HasFactory;

    protected $table = 'candidate_ojt';

    protected $fillable = [
        'candidate_id', 'employer_name', 'employer_address',
        'ojt_start', 'ojt_end', 'hours_completed', 'status',
    ];

    protected $casts = [
        'ojt_start' => 'date',
        'ojt_end' => 'date',
        'hours_completed' => 'integer',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}
