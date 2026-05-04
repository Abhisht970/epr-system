<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class VendorKyc extends Model
{
    use Auditable, HasFactory;

    protected $table = 'vendor_kyc';

    protected $fillable = [
        'vendor_id', 'bank_name', 'account_holder',
        'account_number_encrypted', 'account_number_hash',
        'ifsc', 'branch',
        'cancelled_cheque_path', 'agreement_path',
        'status', 'verified_by', 'verified_at', 'remarks',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function auditTags(): array
    {
        return ['vendor', 'kyc', 'compliance'];
    }

    public function auditExclude(): array
    {
        return [
            'updated_at',
            'remember_token',
            'password',
            // never log encrypted bank account ciphertext to audit history
            'account_number_encrypted',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function setAccountNumberAttribute(string $accountNumber): void
    {
        $normalized = preg_replace('/\D+/', '', $accountNumber) ?? '';
        $this->attributes['account_number_encrypted'] = Crypt::encryptString($normalized);
        $this->attributes['account_number_hash'] = hash('sha256', $normalized);
    }

    public function getAccountNumberAttribute(): ?string
    {
        if (empty($this->attributes['account_number_encrypted'])) {
            return null;
        }

        return Crypt::decryptString($this->attributes['account_number_encrypted']);
    }
}
