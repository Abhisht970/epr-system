<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\CandidateStatusHistory;
use App\Models\User;
use App\Services\CandidateLifecycle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CandidateLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_legal_transition_records_history(): void
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_REGISTERED]);
        $admin = User::factory()->create();

        $candidate = (new CandidateLifecycle)->transition(
            $candidate, Candidate::STATUS_TRAINING, $admin, 'Batch started'
        );

        $this->assertSame(Candidate::STATUS_TRAINING, $candidate->status);
        $this->assertNotNull($candidate->status_changed_at);

        $history = CandidateStatusHistory::where('candidate_id', $candidate->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertSame('registered', $history->from_status);
        $this->assertSame('training', $history->to_status);
        $this->assertSame($admin->id, $history->changed_by);
    }

    public function test_skipping_stage_is_blocked(): void
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_REGISTERED]);

        $this->expectException(InvalidArgumentException::class);
        (new CandidateLifecycle)->transition($candidate, Candidate::STATUS_CERTIFIED);
    }

    public function test_full_lifecycle_progresses(): void
    {
        $candidate = Candidate::factory()->create(['status' => Candidate::STATUS_REGISTERED]);
        $svc = new CandidateLifecycle;

        $svc->transition($candidate, Candidate::STATUS_TRAINING);
        $svc->transition($candidate->fresh(), Candidate::STATUS_ASSESSED);
        $svc->transition($candidate->fresh(), Candidate::STATUS_CERTIFIED);
        $svc->transition($candidate->fresh(), Candidate::STATUS_PLACED);

        $this->assertSame(Candidate::STATUS_PLACED, $candidate->fresh()->status);
        $this->assertSame(4, CandidateStatusHistory::where('candidate_id', $candidate->id)->count());
    }
}
