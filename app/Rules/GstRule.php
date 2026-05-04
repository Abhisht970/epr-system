<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates Indian GSTIN: 15 chars, structured as
 *   <2 state code><10 PAN><1 entity><1 'Z'><1 checksum>.
 */
final class GstRule implements ValidationRule
{
    private const PATTERN = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z][1-9A-Z]Z[0-9A-Z]$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match(self::PATTERN, $value)) {
            $fail('The :attribute must be a valid uppercase GSTIN.');
        }
    }
}
