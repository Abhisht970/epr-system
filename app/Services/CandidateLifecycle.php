<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Candidate;
use App\Models\CandidateStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * CandidateLifecycle — guarded status transitions.
 *
 * Use this service (never raw `$candidate->update(['status' => ...])`) to ensure
 * that:
 *   1. The transition is allowed by Candidate::ALLOWED_TRANSITIONS.
 *   2. A candidate_status_history row is written atomically.
 *   3. status_changed_at is bumped for invoice period filtering.
 */
class CandidateLifecycle
{
    public function transition(
        Candidate $candidate,
        string $to,
        ?User $actor = null,
        ?string $reason = null,
        array $metadata = [],
    ): Candidate {
        $candidate->assertCanTransitionTo($to);
        $from = $candidate->status;

        return DB::transaction(function () use ($candidate, $to, $from, $actor, $reason, $metadata) {
            $candidate->forceFill([
                'status' => $to,
                'status_changed_at' => now(),
            ])->save();

            CandidateStatusHistory::create([
                'candidate_id' => $candidate->id,
                'from_status' => $from,
                'to_status' => $to,
                'changed_by' => $actor?->id,
                'reason' => $reason,
                'metadata' => $metadata === [] ? null : $metadata,
                'changed_at' => now(),
            ]);

            return $candidate->refresh();
        });
    }
}
