<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Observers\AuditLogObserver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trait that wires every CRUD event on a model into the audit_logs table.
 *
 * Usage:
 *   class Vendor extends Model {
 *       use Auditable;
 *   }
 *
 * Models can override `auditExclude` to skip noisy fields (e.g. timestamps),
 * and `auditTags` to attach searchable tags (e.g. ['finance']).
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        $observer = new AuditLogObserver;

        static::created(static fn (Model $model) => $observer->created($model));
        static::updated(static fn (Model $model) => $observer->updated($model));
        static::deleted(static fn (Model $model) => $observer->deleted($model));

        if (in_array('Illuminate\\Database\\Eloquent\\SoftDeletes', class_uses_recursive(static::class), true)) {
            static::restored(static fn (Model $model) => $observer->restored($model));
        }
    }

    public function audits(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * @return array<int, string>
     */
    public function auditExclude(): array
    {
        return ['updated_at', 'remember_token', 'password'];
    }

    /**
     * @return array<int, string>
     */
    public function auditTags(): array
    {
        return [];
    }
}
