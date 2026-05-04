<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Persists audit_log rows for every create / update / delete on an Auditable model.
 *
 * - actor    : Auth::id() if authenticated, null otherwise (e.g. console/seeders).
 * - request  : URL + IP + user agent captured when in HTTP context.
 * - changes  : only the diffed fields are stored, never the full row.
 * - excludes : fields listed in `auditExclude()` (timestamps, password, etc.) are stripped.
 */
class AuditLogObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'created', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $original = $model->getOriginal();
        $changes = $model->getChanges();

        if ($changes === []) {
            return;
        }

        $oldValues = array_intersect_key($original, $changes);

        $this->log($model, 'updated', $oldValues, $changes);
    }

    public function deleted(Model $model): void
    {
        $event = method_exists($model, 'isForceDeleting') && $model->isForceDeleting()
            ? 'force_deleted'
            : 'deleted';

        $this->log($model, $event, $model->getOriginal(), null);
    }

    public function restored(Model $model): void
    {
        $this->log($model, 'restored', null, $model->getAttributes());
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    private function log(Model $model, string $event, ?array $old, ?array $new): void
    {
        $exclude = method_exists($model, 'auditExclude') ? $model->auditExclude() : [];
        $tags = method_exists($model, 'auditTags') ? $model->auditTags() : [];

        $strip = static function (?array $values) use ($exclude): ?array {
            if ($values === null) {
                return null;
            }

            return array_diff_key($values, array_flip($exclude));
        };

        $hasRequest = app()->bound('request') && Request::instance() !== null;

        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'old_values' => $strip($old),
            'new_values' => $strip($new),
            'tags' => $tags === [] ? null : $tags,
            'url' => $hasRequest ? substr((string) Request::fullUrl(), 0, 500) : null,
            'ip_address' => $hasRequest ? Request::ip() : null,
            'user_agent' => $hasRequest ? substr((string) Request::userAgent(), 0, 500) : null,
            'created_at' => now(),
        ]);
    }
}
