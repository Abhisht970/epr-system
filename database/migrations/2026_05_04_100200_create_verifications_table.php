<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Standardised verification workflow. Any model can attach verifications via the
 * HasVerificationStatus trait. The state machine enforces:
 *   pending -> verified  (with optional remarks)
 *   pending -> rejected  (remarks REQUIRED)
 *   pending -> info_required (remarks REQUIRED)
 *   info_required -> pending (when subject re-submits)
 *
 * Embedded controls:
 *  - Verification Workflow: enum status + transition rules in the trait.
 *  - RBAC: only users with verification permissions may transition to verified/rejected.
 *  - Audit Trail: each transition emits an audit log entry.
 *  - Validation: rejection_reason mandatory for `rejected` and `info_required` states.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->morphs('verifiable');
            $table->enum('status', ['pending', 'verified', 'rejected', 'info_required'])
                ->default('pending');
            $table->string('stage', 64)->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('remarks')->nullable();
            $table->json('checklist')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['verifiable_type', 'verifiable_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
