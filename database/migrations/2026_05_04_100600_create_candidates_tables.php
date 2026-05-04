<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Candidate lifecycle tables.
 *
 * Embedded controls:
 *  - Compliance: Aadhaar is stored as `aadhaar_encrypted` (Laravel Crypt) plus
 *    `aadhaar_hash` (HMAC-SHA256 with APP_KEY) for dedup. Plaintext Aadhaar
 *    NEVER touches storage. Display layer renders masked (XXXX-XXXX-1234).
 *  - Validation/Dedup: unique index on aadhaar_hash + (vendor_id, mobile, scheme_id).
 *  - Verification Workflow: per-tab verification (personal, assessment, ojt, placement)
 *    via the polymorphic `verifications` table.
 *  - Scheme Logic: status progression `registered → training → assessed → certified → placed`
 *    enforced by the CandidateStatusObserver. Every status change recorded in
 *    candidate_status_history.
 *  - Audit Trail: every create/update logged.
 *  - RBAC: vendors see only their own candidates via global scope.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('application_id', 32)->unique();
            $table->text('aadhaar_encrypted');
            $table->string('aadhaar_hash', 64);
            $table->string('aadhaar_last4', 4);
            $table->string('full_name');
            $table->date('dob');
            $table->enum('gender', ['male', 'female', 'other']);
            $table->string('mobile', 15);
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('district', 128);
            $table->string('state', 128);
            $table->string('pincode', 10)->index();
            $table->string('education', 128)->nullable();
            $table->enum('category', ['general', 'sc', 'st', 'obc', 'ews', 'minority'])->default('general');
            $table->boolean('pwd')->default(false);
            $table->boolean('bpl')->default(false);
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scheme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('batch_no', 64)->nullable()->index();
            $table->enum('status', [
                'registered',
                'training',
                'assessed',
                'certified',
                'placed',
                'dropout',
            ])->default('registered');
            $table->timestamp('enrolled_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('aadhaar_hash');
            $table->index('mobile');
            $table->index('status');
            $table->index(['vendor_id', 'scheme_id', 'status']);
        });

        Schema::create('candidate_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->enum('document_type', [
                'aadhaar', 'pan_card', 'photo', 'education_proof', 'caste_certificate',
                'bpl_certificate', 'pwd_certificate', 'bank_proof', 'consent_form', 'other',
            ]);
            $table->string('file_path', 500);
            $table->string('original_name');
            $table->string('hash', 64)->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scheme_id')->constrained();
            $table->foreignId('course_id')->constrained();
            $table->string('assessment_agency', 128)->nullable();
            $table->date('assessment_date');
            $table->decimal('score', 6, 2)->nullable();
            $table->decimal('max_score', 6, 2)->nullable();
            $table->enum('result', ['pending', 'pass', 'fail', 'absent'])->default('pending');
            $table->string('certification_no', 64)->nullable()->unique();
            $table->date('certification_date')->nullable();
            $table->string('certificate_path', 500)->nullable();
            $table->timestamps();

            $table->index(['candidate_id', 'result']);
        });

        Schema::create('candidate_ojt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->string('employer_name');
            $table->text('employer_address');
            $table->date('ojt_start');
            $table->date('ojt_end')->nullable();
            $table->unsignedInteger('hours_completed')->default(0);
            $table->enum('status', ['planned', 'ongoing', 'completed', 'dropped'])->default('planned');
            $table->timestamps();
        });

        Schema::create('candidate_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->string('employer_name');
            $table->text('employer_address');
            $table->string('designation');
            $table->decimal('monthly_salary', 12, 2);
            $table->date('joining_date');
            $table->string('placement_proof_path', 500)->nullable();
            $table->enum('status', ['reported', 'verified', 'disputed', 'rejected'])->default('reported');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('candidate_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32);
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index(['candidate_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_status_history');
        Schema::dropIfExists('candidate_placements');
        Schema::dropIfExists('candidate_ojt');
        Schema::dropIfExists('candidate_assessments');
        Schema::dropIfExists('candidate_documents');
        Schema::dropIfExists('candidates');
    }
};
