<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\AadhaarVault;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that the value is a syntactically correct Aadhaar number,
 * checking length, digit-only, and the Verhoeff checksum used by UIDAI.
 */
final class AadhaarRule implements ValidationRule
{
    public function __construct(private bool $strictChecksum = true) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string of digits.');

            return;
        }

        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (strlen($digits) !== 12) {
            $fail('The :attribute must be exactly 12 digits.');

            return;
        }

        if ($this->strictChecksum && ! AadhaarVault::isValidVerhoeff($digits)) {
            $fail('The :attribute is not a valid Aadhaar number (checksum failed).');
        }
    }
}
