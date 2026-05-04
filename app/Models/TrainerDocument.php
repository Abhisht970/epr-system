<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerDocument extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'trainer_id', 'document_type', 'file_path',
        'original_name', 'hash', 'status', 'remarks',
    ];

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }
}
