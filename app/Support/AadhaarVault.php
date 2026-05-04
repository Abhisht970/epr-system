<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use RuntimeException;

/**
 * AadhaarVault — compliance helper for handling Aadhaar numbers.
 *
 *  - encrypt(): Laravel Crypt (AES-256-CBC + HMAC) ciphertext for at-rest storage.
 *  - hash():    HMAC-SHA256 keyed with APP_KEY for deterministic dedup lookups.
 *  - mask():    rendering helper — returns "XXXX-XXXX-1234".
 *
 * Plaintext Aadhaar numbers must NEVER be persisted, logged, or returned over
 * non-admin APIs.
 */
final class AadhaarVault
{
    public static function normalize(string $aadhaar): string
    {
        $digits = preg_replace('/\D+/', '', $aadhaar) ?? '';

        if (strlen($digits) !== 12) {
            throw new RuntimeException('Aadhaar must be exactly 12 digits.');
        }

        return $digits;
    }

    public static function encrypt(string $aadhaar): string
    {
        return Crypt::encryptString(self::normalize($aadhaar));
    }

    public static function decrypt(string $ciphertext): string
    {
        return Crypt::decryptString($ciphertext);
    }

    public static function hash(string $aadhaar): string
    {
        $key = config('app.key') ?? '';

        if ($key === '') {
            throw new RuntimeException('APP_KEY is not configured.');
        }

        // Strip "base64:" prefix if present so we always hash with raw bytes.
        if (str_starts_with($key, 'base64:')) {
            $key = base64_decode(substr($key, 7), true) ?: $key;
        }

        return hash_hmac('sha256', self::normalize($aadhaar), $key);
    }

    public static function last4(string $aadhaar): string
    {
        return substr(self::normalize($aadhaar), -4);
    }

    public static function mask(string $aadhaar): string
    {
        $normalized = self::normalize($aadhaar);

        return 'XXXX-XXXX-'.substr($normalized, -4);
    }

    /**
     * Verhoeff checksum used by UIDAI.
     *
     * @see https://en.wikipedia.org/wiki/Verhoeff_algorithm
     */
    public static function isValidVerhoeff(string $aadhaar): bool
    {
        $digits = preg_replace('/\D+/', '', $aadhaar) ?? '';

        if (strlen($digits) !== 12) {
            return false;
        }

        $d = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
            [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
            [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
            [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
            [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
            [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
            [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
            [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
            [9, 8, 7, 6, 5, 4, 3, 2, 1, 0],
        ];

        $p = [
            [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
            [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
            [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
            [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
            [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
            [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
            [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
            [7, 0, 4, 6, 9, 1, 3, 2, 5, 8],
        ];

        $c = 0;
        $reversed = array_reverse(str_split($digits));

        foreach ($reversed as $i => $digit) {
            $c = $d[$c][$p[$i % 8][(int) $digit]];
        }

        return $c === 0;
    }
}
