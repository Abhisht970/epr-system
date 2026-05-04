<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates an Indian mobile number: 10 digits starting with 6-9,
 * optionally prefixed with +91 / 0.
 */
final class MobileRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        $normalized = preg_replace('/\D+/', '', $value) ?? '';

        if (strlen($normalized) === 12 && str_starts_with($normalized, '91')) {
            $normalized = substr($normalized, 2);
        } elseif (strlen($normalized) === 11 && str_starts_with($normalized, '0')) {
            $normalized = substr($normalized, 1);
        }

        if (! preg_match('/^[6-9][0-9]{9}$/', $normalized)) {
            $fail('The :attribute must be a valid 10-digit Indian mobile number.');
        }
    }
}
