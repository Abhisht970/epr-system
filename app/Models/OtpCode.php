<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * OTP code row. Created/consumed by App\Services\OtpService — never use this
 * model directly from controllers.
 */
class OtpCode extends Model
{
    protected $fillable = [
        'identifier', 'channel', 'purpose', 'code_hash',
        'attempts', 'max_attempts', 'expires_at', 'consumed_at',
        'ip_address', 'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public static function issueFor(
        string $identifier,
        string $purpose,
        string $channel = 'sms',
        int $ttlMinutes = 10,
        ?string $ip = null,
        ?string $userAgent = null,
    ): array {
        $code = (string) random_int(100000, 999999);

        $row = self::create([
            'identifier' => $identifier,
            'channel' => $channel,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => Carbon::now()->addMinutes($ttlMinutes),
            'ip_address' => $ip,
            'user_agent' => $userAgent ? Str::limit($userAgent, 500, '') : null,
        ]);

        return [$row, $code];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function consume(string $code): bool
    {
        if ($this->isConsumed() || $this->isExpired()) {
            return false;
        }

        if ($this->attempts >= $this->max_attempts) {
            return false;
        }

        $this->increment('attempts');

        if (! Hash::check($code, $this->code_hash)) {
            return false;
        }

        $this->forceFill(['consumed_at' => now()])->save();

        return true;
    }
}
