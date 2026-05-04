<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Rules\AadhaarRule;
use App\Rules\GstRule;
use App\Rules\MobileRule;
use App\Rules\PanRule;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RulesTest extends TestCase
{
    public function test_pan_rule_accepts_valid_and_rejects_invalid(): void
    {
        $this->assertTrue(Validator::make(['x' => 'ABCDE1234F'], ['x' => new PanRule])->passes());
        $this->assertFalse(Validator::make(['x' => 'ABCD1234F'], ['x' => new PanRule])->passes());
        $this->assertFalse(Validator::make(['x' => 'abcde1234f'], ['x' => new PanRule])->passes()); // we uppercase
    }

    public function test_gst_rule_accepts_valid_and_rejects_invalid(): void
    {
        $this->assertTrue(Validator::make(['x' => '27ABCDE1234F1Z5'], ['x' => new GstRule])->passes());
        $this->assertFalse(Validator::make(['x' => '27ABCDE1234F1A5'], ['x' => new GstRule])->passes());
    }

    public function test_mobile_rule_handles_prefixes(): void
    {
        $this->assertTrue(Validator::make(['x' => '9876543210'], ['x' => new MobileRule])->passes());
        $this->assertTrue(Validator::make(['x' => '+919876543210'], ['x' => new MobileRule])->passes());
        $this->assertTrue(Validator::make(['x' => '09876543210'], ['x' => new MobileRule])->passes());

        $this->assertFalse(Validator::make(['x' => '1234567890'], ['x' => new MobileRule])->passes());
        $this->assertFalse(Validator::make(['x' => '5876543210'], ['x' => new MobileRule])->passes());
    }

    public function test_aadhaar_rule_rejects_short_input_without_strict_mode(): void
    {
        $rule = new AadhaarRule(strictChecksum: false);
        $this->assertFalse(Validator::make(['x' => '123'], ['x' => $rule])->passes());
        $this->assertTrue(Validator::make(['x' => '123456789012'], ['x' => $rule])->passes());
    }
}
