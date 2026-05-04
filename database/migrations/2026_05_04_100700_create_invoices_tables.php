<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Finance: invoices, invoice items, payments.
 *
 * Embedded controls:
 *  - Validation/Dedup: unique invoice_no globally; unique (invoice_id, candidate_id, milestone_id)
 *    on items prevents duplicate billing per candidate-milestone.
 *  - Scheme Logic: invoice items reference scheme_payment_milestones — amounts
 *    auto-calculated from scheme rules (no free-text amounts allowed).
 *  - Verification Workflow: invoice status transitions
 *      draft → submitted → under_review → approved → paid (or rejected)
 *  - Audit Trail: every status transition and amount edit logged. Critical for
 *    audit-readiness.
 *  - RBAC: only vendor owner can submit; only admin/finance can approve/pay.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no', 64)->unique();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scheme_id')->constrained();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedInteger('candidates_count')->default(0);
            $table->decimal('gross_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('deductions', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2)->default(0);
            $table->enum('status', [
                'draft', 'submitted', 'under_review', 'approved', 'paid', 'rejected',
            ])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'status']);
            $table->index(['scheme_id', 'period_start']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('milestone_id')->nullable()
                ->constrained('scheme_payment_milestones')->nullOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('amount', 14, 2);
            $table->timestamps();

            $table->unique(['invoice_id', 'candidate_id', 'milestone_id'], 'inv_items_unique_billing');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_ref', 64)->unique();
            $table->decimal('amount', 14, 2);
            $table->enum('mode', ['neft', 'rtgs', 'imps', 'upi', 'cheque', 'manual'])->default('neft');
            $table->string('utr_no', 64)->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
