<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\AadhaarVault;
use Database\Factories\CandidateFactory;
use RuntimeException;
use Tests\TestCase;

class AadhaarVaultTest extends TestCase
{
    public function test_normalize_strips_separators(): void
    {
        $this->assertSame('123456789012', AadhaarVault::normalize('1234-5678-9012'));
        $this->assertSame('123456789012', AadhaarVault::normalize('1234 5678 9012'));
    }

    public function test_normalize_rejects_non_12_digit_input(): void
    {
        $this->expectException(RuntimeException::class);
        AadhaarVault::normalize('12345');
    }

    public function test_encrypt_and_decrypt_roundtrip(): void
    {
        $aadhaar = '234112233445'; // not necessarily valid checksum, but vault doesn't enforce it
        $cipher = AadhaarVault::encrypt($aadhaar);

        $this->assertNotSame($aadhaar, $cipher);
        $this->assertSame($aadhaar, AadhaarVault::decrypt($cipher));
    }

    public function test_hash_is_deterministic_for_same_input(): void
    {
        $a = AadhaarVault::hash('123456789012');
        $b = AadhaarVault::hash('1234-5678-9012');

        $this->assertSame($a, $b);
        $this->assertNotSame(AadhaarVault::hash('123456789012'), AadhaarVault::hash('123456789013'));
    }

    public function test_mask_renders_only_last_four_digits(): void
    {
        $this->assertSame('XXXX-XXXX-9012', AadhaarVault::mask('1234-5678-9012'));
    }

    public function test_verhoeff_accepts_valid_and_rejects_invalid(): void
    {
        // Build a known-good Verhoeff number using the same algo as the factory.
        $valid = $this->buildValidAadhaar();
        $this->assertTrue(AadhaarVault::isValidVerhoeff($valid), "Generated number $valid should pass Verhoeff");

        // Mutating the last digit must always invalidate.
        $invalidLast = (string) ((((int) substr($valid, -1)) + 1) % 10);
        $this->assertFalse(AadhaarVault::isValidVerhoeff(substr($valid, 0, 11).$invalidLast));
    }

    private function buildValidAadhaar(): string
    {
        $factory = new CandidateFactory;
        $reflection = new \ReflectionClass($factory);
        $method = $reflection->getMethod('generateValidAadhaar');
        $method->setAccessible(true);

        return $method->invoke($factory);
    }
}
