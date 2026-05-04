<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Candidate;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CandidateAadhaarDedupTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_aadhaar_is_rejected_by_unique_index(): void
    {
        $first = Candidate::factory()->create();
        $aadhaar = $first->aadhaar;

        $this->assertNotNull($aadhaar);
        $this->assertSame($aadhaar, Candidate::find($first->id)->aadhaar);

        $this->expectException(QueryException::class);

        Candidate::factory()->create([
            'aadhaar' => $aadhaar,
        ]);
    }

    public function test_aadhaar_is_stored_encrypted_and_masked_in_array(): void
    {
        $candidate = Candidate::factory()->create();
        $row = \DB::table('candidates')->where('id', $candidate->id)->first();

        $this->assertNotNull($row);
        $this->assertNotEmpty($row->aadhaar_encrypted);
        $this->assertNotSame($candidate->aadhaar, $row->aadhaar_encrypted);
        $this->assertNotEquals($candidate->aadhaar, $row->aadhaar_hash);
        $this->assertSame(4, strlen($row->aadhaar_last4));
        $this->assertStringStartsWith('XXXX-XXXX-', $candidate->aadhaar_masked);
    }

    public function test_hidden_attributes_are_not_serialized(): void
    {
        $candidate = Candidate::factory()->create();
        $array = $candidate->toArray();

        $this->assertArrayNotHasKey('aadhaar_encrypted', $array);
        $this->assertArrayNotHasKey('aadhaar_hash', $array);
    }
}
