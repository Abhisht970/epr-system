<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateAssessment extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'candidate_id', 'scheme_id', 'course_id',
        'assessment_agency', 'assessment_date',
        'score', 'max_score', 'result',
        'certification_no', 'certification_date', 'certificate_path',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'certification_date' => 'date',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
