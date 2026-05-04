<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates Indian PAN format: AAAAA9999A.
 */
final class PanRule implements ValidationRule
{
    private const PATTERN = '/^[A-Z]{5}[0-9]{4}[A-Z]$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match(self::PATTERN, $value)) {
            $fail('The :attribute must be a valid uppercase PAN (e.g. ABCDE1234F).');
        }
    }
}
