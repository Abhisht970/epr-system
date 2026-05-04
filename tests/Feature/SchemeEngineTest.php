<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Course;
use App\Models\Scheme;
use App\Models\SchemeEligibilityRule;
use App\Models\SchemePaymentMilestone;
use App\Services\SchemeEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SchemeEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_eligibility_passes_when_age_in_range(): void
    {
        $scheme = Scheme::factory()->create();

        SchemeEligibilityRule::create([
            'scheme_id' => $scheme->id,
            'rule_key' => 'age',
            'operator' => 'between',
            'value' => [18, 35],
            'is_required' => true,
            'error_message' => 'age out of range',
        ]);

        $candidate = Candidate::factory()->create([
            'scheme_id' => $scheme->id,
            'dob' => Carbon::now()->subYears(25)->toDateString(),
        ]);

        $result = (new SchemeEngine)->evaluateEligibility($scheme->fresh('eligibilityRules'), $candidate);

        $this->assertTrue($result['eligible']);
        $this->assertSame([], $result['errors']);
    }

    public function test_eligibility_fails_when_age_below_min(): void
    {
        $scheme = Scheme::factory()->create();

        SchemeEligibilityRule::create([
            'scheme_id' => $scheme->id,
            'rule_key' => 'age',
            'operator' => 'between',
            'value' => [18, 35],
            'is_required' => true,
            'error_message' => 'age out of range',
        ]);

        $candidate = Candidate::factory()->create([
            'scheme_id' => $scheme->id,
            'dob' => Carbon::now()->subYears(15)->toDateString(),
        ]);

        $result = (new SchemeEngine)->evaluateEligibility($scheme->fresh('eligibilityRules'), $candidate);

        $this->assertFalse($result['eligible']);
        $this->assertContains('age out of range', $result['errors']);
    }

    public function test_amount_for_milestone_uses_percentage_of_payout(): void
    {
        $scheme = Scheme::factory()->create();
        $course = Course::factory()->create();
        $scheme->courses()->attach($course->id, ['payout_per_candidate' => 20000, 'max_batch_size' => 30]);

        $milestone = SchemePaymentMilestone::create([
            'scheme_id' => $scheme->id,
            'milestone_key' => 'certification',
            'trigger_event' => 'certification',
            'percentage' => 30,
            'fixed_amount' => null,
            'payable_to' => 'vendor',
            'order' => 3,
        ]);

        $candidate = Candidate::factory()->create([
            'scheme_id' => $scheme->id,
            'course_id' => $course->id,
        ]);

        $amount = (new SchemeEngine)->amountFor($milestone, $candidate);

        $this->assertSame(6000.0, $amount);
    }

    public function test_amount_for_fixed_milestone_overrides_percentage(): void
    {
        $scheme = Scheme::factory()->create();
        $course = Course::factory()->create();

        $milestone = SchemePaymentMilestone::create([
            'scheme_id' => $scheme->id,
            'milestone_key' => 'placement',
            'trigger_event' => 'placement',
            'percentage' => null,
            'fixed_amount' => 2500,
            'payable_to' => 'vendor',
            'order' => 4,
        ]);

        $candidate = Candidate::factory()->create([
            'scheme_id' => $scheme->id,
            'course_id' => $course->id,
        ]);

        $this->assertSame(2500.0, (new SchemeEngine)->amountFor($milestone, $candidate));
    }
}
