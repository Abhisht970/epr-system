<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use App\Models\Verification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class VerificationStateMachineTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_to_verified_is_allowed(): void
    {
        $vendor = Vendor::factory()->create();
        $vendor->submitForVerification();

        $reviewer = User::factory()->admin()->create();
        $verification = $vendor->markVerified($reviewer, 'Looks good');

        $this->assertSame(Verification::STATUS_VERIFIED, $verification->status);
        $this->assertTrue($vendor->fresh()->isVerified());
    }

    public function test_rejection_without_remarks_throws(): void
    {
        $vendor = Vendor::factory()->create();
        $vendor->submitForVerification();

        $reviewer = User::factory()->admin()->create();

        $this->expectException(InvalidArgumentException::class);
        $vendor->markRejected($reviewer, '   ');
    }

    public function test_rejection_with_remarks_succeeds(): void
    {
        $vendor = Vendor::factory()->create();
        $vendor->submitForVerification();

        $reviewer = User::factory()->admin()->create();
        $v = $vendor->markRejected($reviewer, 'PAN mismatch');

        $this->assertSame(Verification::STATUS_REJECTED, $v->status);
        $this->assertSame('PAN mismatch', $v->remarks);
    }

    public function test_illegal_transition_is_blocked(): void
    {
        $vendor = Vendor::factory()->create();
        $vendor->submitForVerification();

        $reviewer = User::factory()->admin()->create();
        $vendor->markVerified($reviewer);

        // verified -> verified is not allowed
        $this->expectException(InvalidArgumentException::class);
        $vendor->fresh()->markVerified($reviewer);
    }
}
