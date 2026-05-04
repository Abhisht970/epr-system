<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchemeEligibilityRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheme_id', 'rule_key', 'operator', 'value', 'is_required', 'error_message',
    ];

    protected $casts = [
        'value' => 'array',
        'is_required' => 'boolean',
    ];

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }
}
