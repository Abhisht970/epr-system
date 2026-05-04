<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OTP codes for mobile-based login and verification.
 *
 * Embedded controls:
 *  - Compliance: only the bcrypt hash of the code is stored (never plaintext).
 *  - Validation: limited attempt count + expiry enforced at the service layer.
 *  - Audit Trail: every issuance and consumption logs IP + user agent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('identifier', 191)->index();
            $table->enum('channel', ['sms', 'email', 'whatsapp'])->default('sms');
            $table->enum('purpose', [
                'login',
                'registration',
                'kyc',
                'password_reset',
                'document_verify',
            ]);
            $table->string('code_hash');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedSmallInteger('max_attempts')->default(5);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['identifier', 'purpose', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_codes');
    }
};
