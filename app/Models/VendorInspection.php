<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorInspection extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'vendor_id', 'vendor_center_id', 'scheme_id', 'inspector_id',
        'inspection_type', 'inspection_date',
        'checklist', 'score', 'max_score',
        'status', 'recommendation', 'report_path', 'remarks',
    ];

    protected $casts = [
        'inspection_date' => 'date',
        'checklist' => 'array',
        'score' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

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

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
