<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Candidate;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Scheme;
use App\Models\SchemeEligibilityRule;
use App\Models\SchemePaymentMilestone;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * SchemeEngine — declarative eligibility + payout calculator.
 *
 *  - evaluateEligibility(scheme, candidate): returns ['eligible' => bool, 'errors' => string[]]
 *  - amountFor(milestone, candidate): returns the rupee amount payable for a single
 *    candidate hitting a milestone, given the scheme's per-candidate payout.
 *
 * The engine is stateless and pure. It is the single source of truth for
 * "is this candidate eligible?" and "how much do we pay?". Controllers and jobs
 * MUST go through this service rather than computing values themselves.
 */
class SchemeEngine
{
    /**
     * @return array{eligible: bool, errors: list<string>}
     */
    public function evaluateEligibility(Scheme $scheme, Candidate $candidate): array
    {
        $errors = [];

        foreach ($scheme->eligibilityRules as $rule) {
            $value = $this->resolveAttribute($candidate, $rule->rule_key);

            if (! $this->matches($rule, $value)) {
                if ($rule->is_required) {
                    $errors[] = $rule->error_message
                        ?? sprintf('Candidate fails rule "%s" (%s).', $rule->rule_key, $rule->operator);
                }
            }
        }

        return [
            'eligible' => $errors === [],
            'errors' => $errors,
        ];
    }

    public function amountFor(SchemePaymentMilestone $milestone, Candidate $candidate): float
    {
        if ($milestone->fixed_amount !== null) {
            return (float) $milestone->fixed_amount;
        }

        if ($milestone->percentage === null) {
            return 0.0;
        }

        $payout = $this->payoutPerCandidate($milestone->scheme_id, $candidate->course_id);

        return round($payout * ((float) $milestone->percentage) / 100.0, 2);
    }

    public function payoutPerCandidate(int $schemeId, int $courseId): float
    {
        $row = \DB::table('scheme_course')
            ->where('scheme_id', $schemeId)
            ->where('course_id', $courseId)
            ->first(['payout_per_candidate']);

        if ($row === null) {
            throw new RuntimeException('Course is not enrolled under this scheme.');
        }

        return (float) $row->payout_per_candidate;
    }

    /**
     * Build a draft invoice for a vendor for a given period, automatically
     * generating one item per (candidate, milestone) pair that is currently
     * billable but not previously billed.
     */
    public function buildDraftInvoice(
        Scheme $scheme,
        int $vendorId,
        Carbon $periodStart,
        Carbon $periodEnd,
    ): Invoice {
        $candidates = Candidate::query()
            ->where('vendor_id', $vendorId)
            ->where('scheme_id', $scheme->id)
            ->whereBetween('status_changed_at', [$periodStart, $periodEnd])
            ->get();

        $invoice = Invoice::create([
            'invoice_no' => 'INV-'.strtoupper(uniqid()),
            'vendor_id' => $vendorId,
            'scheme_id' => $scheme->id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'candidates_count' => $candidates->count(),
            'gross_amount' => 0,
            'tax_amount' => 0,
            'deductions' => 0,
            'net_amount' => 0,
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $gross = 0.0;

        foreach ($candidates as $candidate) {
            foreach ($scheme->paymentMilestones as $milestone) {
                if (! $this->milestoneTriggered($milestone, $candidate)) {
                    continue;
                }

                if ($this->alreadyBilled($candidate->id, $milestone->id, $invoice->id)) {
                    continue;
                }

                $amount = $this->amountFor($milestone, $candidate);

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'candidate_id' => $candidate->id,
                    'milestone_id' => $milestone->id,
                    'description' => sprintf('%s — %s', $milestone->milestone_key, $candidate->full_name),
                    'quantity' => 1,
                    'unit_price' => $amount,
                    'amount' => $amount,
                ]);

                $gross += $amount;
            }
        }

        $invoice->update([
            'gross_amount' => $gross,
            'net_amount' => $gross,
        ]);

        return $invoice->refresh();
    }

    /**
     * @param  mixed  $value
     */
    private function matches(SchemeEligibilityRule $rule, $value): bool
    {
        $expected = $rule->value;

        return match ($rule->operator) {
            'eq' => $value == $this->scalar($expected),
            'neq' => $value != $this->scalar($expected),
            'gt' => is_numeric($value) && $value > $this->scalar($expected),
            'gte' => is_numeric($value) && $value >= $this->scalar($expected),
            'lt' => is_numeric($value) && $value < $this->scalar($expected),
            'lte' => is_numeric($value) && $value <= $this->scalar($expected),
            'in' => is_array($expected) && in_array($value, $expected, false),
            'not_in' => is_array($expected) && ! in_array($value, $expected, false),
            'between' => is_array($expected) && count($expected) === 2
                && is_numeric($value) && $value >= $expected[0] && $value <= $expected[1],
            default => false,
        };
    }

    private function scalar(mixed $value): mixed
    {
        if (is_array($value)) {
            return $value[0] ?? null;
        }

        return $value;
    }

    private function resolveAttribute(Candidate $candidate, string $key): mixed
    {
        if ($key === 'age') {
            return $candidate->dob ? Carbon::parse($candidate->dob)->age : null;
        }

        return $candidate->getAttribute($key);
    }

    private function milestoneTriggered(SchemePaymentMilestone $milestone, Candidate $candidate): bool
    {
        return match ($milestone->trigger_event) {
            'registration' => $candidate->status !== null,
            'training_complete' => in_array($candidate->status, [
                Candidate::STATUS_TRAINING, Candidate::STATUS_ASSESSED,
                Candidate::STATUS_CERTIFIED, Candidate::STATUS_PLACED,
            ], true),
            'assessment' => in_array($candidate->status, [
                Candidate::STATUS_ASSESSED, Candidate::STATUS_CERTIFIED, Candidate::STATUS_PLACED,
            ], true),
            'certification' => in_array($candidate->status, [
                Candidate::STATUS_CERTIFIED, Candidate::STATUS_PLACED,
            ], true),
            'placement' => $candidate->status === Candidate::STATUS_PLACED,
            default => false,
        };
    }

    private function alreadyBilled(int $candidateId, int $milestoneId, int $excludeInvoiceId): bool
    {
        return InvoiceItem::query()
            ->where('candidate_id', $candidateId)
            ->where('milestone_id', $milestoneId)
            ->where('invoice_id', '!=', $excludeInvoiceId)
            ->exists();
    }
}
