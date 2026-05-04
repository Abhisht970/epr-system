<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchemePaymentMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'scheme_id', 'milestone_key', 'trigger_event',
        'percentage', 'fixed_amount', 'payable_to', 'order',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'order' => 'integer',
    ];

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }
}
