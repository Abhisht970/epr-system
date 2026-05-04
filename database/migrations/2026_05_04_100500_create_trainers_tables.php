<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trainer module.
 *
 * Embedded controls:
 *  - Validation: mandatory qualification + TOT (Train-of-Trainer) certificate.
 *  - Verification Workflow: trainer profile must be verified before assignment.
 *  - RBAC: only admin can assign trainers.
 *  - Scheme Logic: trainer assignment validated against scheme eligibility
 *    (e.g. NSQF level / sector).
 *  - Audit Trail: assignments logged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('full_name');
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('qualification', 128);
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->string('tot_certificate_no', 64)->nullable()->unique();
            $table->string('tot_issued_by', 128)->nullable();
            $table->date('tot_valid_until')->nullable();
            $table->json('sectors')->nullable();
            $table->enum('status', ['draft', 'pending_verification', 'verified', 'rejected', 'active', 'inactive'])
                ->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('trainer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained()->cascadeOnDelete();
            $table->enum('document_type', ['pan_card', 'aadhaar', 'qualification', 'tot_certificate', 'experience_letter', 'other']);
            $table->string('file_path', 500);
            $table->string('original_name');
            $table->string('hash', 64)->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('trainer_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_center_id')->constrained()->cascadeOnDelete();
            $table->foreignId('scheme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('batch_no', 64);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['scheduled', 'active', 'completed', 'cancelled'])->default('scheduled');
            $table->foreignId('assigned_by')->constrained('users');
            $table->timestamps();

            $table->index(['trainer_id', 'status']);
            $table->index(['vendor_center_id', 'batch_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_assignments');
        Schema::dropIfExists('trainer_documents');
        Schema::dropIfExists('trainers');
    }
};
