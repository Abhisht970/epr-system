<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'name', 'sector', 'qp_code', 'nsqf_level', 'duration_hours',
    ];

    public function schemes(): BelongsToMany
    {
        return $this->belongsToMany(Scheme::class, 'scheme_course')
            ->withPivot(['payout_per_candidate', 'max_batch_size'])
            ->withTimestamps();
    }
}
