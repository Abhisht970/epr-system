<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\User;
use App\Models\Verification;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use InvalidArgumentException;

/**
 * Standardised pending / verified / rejected / info_required workflow.
 *
 * Allowed transitions (enforced at runtime):
 *   pending          -> verified | rejected | info_required
 *   info_required    -> pending
 *   verified         -> rejected (reopen, with remarks)
 *   rejected         -> pending  (re-submission)
 *
 * Calling code MUST pass remarks for `rejected` and `info_required`.
 */
trait HasVerificationStatus
{
    public function verifications(): MorphMany
    {
        return $this->morphMany(Verification::class, 'verifiable')->latest('id');
    }

    public function currentVerification(): MorphOne
    {
        return $this->morphOne(Verification::class, 'verifiable')->latestOfMany();
    }

    public function isVerified(): bool
    {
        return $this->currentVerification?->status === Verification::STATUS_VERIFIED;
    }

    public function submitForVerification(?User $user = null, ?string $stage = null): Verification
    {
        return $this->verifications()->create([
            'status' => Verification::STATUS_PENDING,
            'stage' => $stage,
            'submitted_by' => $user?->id,
            'submitted_at' => now(),
        ]);
    }

    public function markVerified(User $reviewer, ?string $remarks = null, ?string $stage = null): Verification
    {
        $this->guardTransition(Verification::STATUS_VERIFIED);

        return $this->verifications()->create([
            'status' => Verification::STATUS_VERIFIED,
            'stage' => $stage ?? $this->currentVerification?->stage,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'remarks' => $remarks,
        ]);
    }

    public function markRejected(User $reviewer, string $remarks, ?string $stage = null): Verification
    {
        if (trim($remarks) === '') {
            throw new InvalidArgumentException('Rejection remarks are mandatory.');
        }

        $this->guardTransition(Verification::STATUS_REJECTED);

        return $this->verifications()->create([
            'status' => Verification::STATUS_REJECTED,
            'stage' => $stage ?? $this->currentVerification?->stage,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'remarks' => $remarks,
        ]);
    }

    public function requestMoreInfo(User $reviewer, string $remarks, ?string $stage = null): Verification
    {
        if (trim($remarks) === '') {
            throw new InvalidArgumentException('Remarks are mandatory when requesting more info.');
        }

        $this->guardTransition(Verification::STATUS_INFO_REQUIRED);

        return $this->verifications()->create([
            'status' => Verification::STATUS_INFO_REQUIRED,
            'stage' => $stage ?? $this->currentVerification?->stage,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'remarks' => $remarks,
        ]);
    }

    private function guardTransition(string $to): void
    {
        $current = $this->currentVerification?->status ?? Verification::STATUS_PENDING;

        $allowed = [
            Verification::STATUS_PENDING => [
                Verification::STATUS_VERIFIED,
                Verification::STATUS_REJECTED,
                Verification::STATUS_INFO_REQUIRED,
            ],
            Verification::STATUS_INFO_REQUIRED => [
                Verification::STATUS_PENDING,
            ],
            Verification::STATUS_VERIFIED => [
                Verification::STATUS_REJECTED,
            ],
            Verification::STATUS_REJECTED => [
                Verification::STATUS_PENDING,
            ],
        ];

        if (! in_array($to, $allowed[$current] ?? [], true)) {
            throw new InvalidArgumentException(
                sprintf('Illegal verification transition: %s -> %s.', $current, $to)
            );
        }
    }
}
