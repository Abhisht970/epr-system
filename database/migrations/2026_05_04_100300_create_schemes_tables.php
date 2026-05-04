<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scheme Logic Engine — the brain of the system.
 *
 * `schemes`            — top-level scheme (e.g. PMKVY-4, DDU-GKY)
 * `scheme_eligibility_rules` — declarative rules (min_age, min_education, ...)
 *                              evaluated against candidate attributes
 * `scheme_payment_milestones` — payable events (registration, training_complete,
 *                                certification, placement) with percentage / fixed amount
 * `courses`            — qualification packs (NSQF level + duration)
 * `scheme_course`      — pivot: which courses a scheme funds + per-candidate payout
 *
 * Embedded controls:
 *  - Scheme Logic: declarative rules + milestones drive eligibility and payout.
 *  - Validation: rule evaluator validates operators + value types.
 *  - Audit Trail: scheme edits logged.
 *  - RBAC: only admin role may CRUD schemes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schemes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('sponsor', 64)->nullable();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->json('default_rules')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->string('name');
            $table->string('sector', 128)->nullable();
            $table->string('qp_code', 64)->nullable()->index();
            $table->unsignedTinyInteger('nsqf_level')->nullable();
            $table->unsignedSmallInteger('duration_hours')->nullable();
            $table->timestamps();
        });

        Schema::create('scheme_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->decimal('payout_per_candidate', 12, 2)->default(0);
            $table->unsignedSmallInteger('max_batch_size')->default(30);
            $table->timestamps();

            $table->unique(['scheme_id', 'course_id']);
        });

        Schema::create('scheme_eligibility_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained()->cascadeOnDelete();
            $table->string('rule_key', 64);
            $table->enum('operator', ['eq', 'neq', 'gt', 'gte', 'lt', 'lte', 'in', 'not_in', 'between']);
            $table->json('value');
            $table->boolean('is_required')->default(true);
            $table->string('error_message', 255)->nullable();
            $table->timestamps();

            $table->index(['scheme_id', 'rule_key']);
        });

        Schema::create('scheme_payment_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained()->cascadeOnDelete();
            $table->string('milestone_key', 64);
            $table->string('trigger_event', 64);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->decimal('fixed_amount', 12, 2)->nullable();
            $table->enum('payable_to', ['vendor', 'trainer', 'candidate'])->default('vendor');
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->unique(['scheme_id', 'milestone_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheme_payment_milestones');
        Schema::dropIfExists('scheme_eligibility_rules');
        Schema::dropIfExists('scheme_course');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('schemes');
    }
};
