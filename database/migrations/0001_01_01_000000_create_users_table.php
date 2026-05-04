<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identity table.
 *
 * Embedded controls:
 *  - RBAC: roles assigned via spatie/laravel-permission (see create_permission_tables migration).
 *  - Validation/Dedup: unique index on email and mobile; mobile is the primary login identifier
 *    for OTP-based flows.
 *  - Audit Trail: `last_login_at`, `last_login_ip` captured on every login. Full row-level
 *    auditing handled by the `audit_logs` table.
 *  - Compliance: passwords hashed via bcrypt; soft deletes preserve history for audit.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('mobile', 15)->unique();
            $table->timestamp('mobile_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'pending'])
                ->default('pending');
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
