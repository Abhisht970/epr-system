<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vendor (Training Partner) lifecycle tables.
 *
 * Embedded controls:
 *  - Validation/Dedup: unique indexes on PAN, GST and application_no prevent
 *    duplicate registrations.
 *  - Verification Workflow: `status` enum tracks lifecycle; transitions are
 *    enforced via the HasVerificationStatus trait + Verification rows.
 *  - Audit Trail: every change captured by the global Auditable trait.
 *  - Compliance: bank account stored as encrypted ciphertext + SHA-256 hash;
 *    KYC must be `verified` before vendor can be `active` (enforced in the model boot).
 *  - RBAC: vendor users see only their own vendors via global scope.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('application_no', 32)->unique();
            $table->string('org_name');
            $table->enum('org_type', ['proprietorship', 'partnership', 'llp', 'pvt_ltd', 'public_ltd', 'society', 'trust', 'section_8'])
                ->default('proprietorship');
            $table->string('pan', 10)->unique();
            $table->string('gst', 15)->nullable()->unique();
            $table->string('tan', 15)->nullable();
            $table->string('registration_no', 64)->nullable();
            $table->text('registered_address');
            $table->text('communication_address')->nullable();
            $table->string('contact_person');
            $table->string('contact_email');
            $table->string('contact_mobile', 15);
            $table->enum('status', [
                'draft',
                'submitted',
                'pending_verification',
                'inspection_scheduled',
                'inspection_completed',
                'approved',
                'rejected',
                'kyc_pending',
                'active',
                'inactive',
                'suspended',
            ])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });

        Schema::create('vendor_centers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('center_code', 32)->unique();
            $table->string('name');
            $table->text('address');
            $table->string('district', 128);
            $table->string('state', 128);
            $table->string('pincode', 10)->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('capacity')->default(0);
            $table->enum('status', ['draft', 'pending_verification', 'active', 'inactive', 'rejected'])
                ->default('draft');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vendor_id', 'status']);
        });

        Schema::create('vendor_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->enum('document_type', [
                'pan_card',
                'gst_certificate',
                'incorporation',
                'address_proof',
                'authorisation_letter',
                'bank_statement',
                'cancelled_cheque',
                'agreement',
                'other',
            ]);
            $table->string('file_path', 500);
            $table->string('original_name');
            $table->string('mime_type', 128)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('hash', 64)->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vendor_id', 'document_type']);
            $table->index('hash');
        });

        Schema::create('vendor_kyc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('account_holder');
            $table->text('account_number_encrypted');
            $table->string('account_number_hash', 64)->index();
            $table->string('ifsc', 11);
            $table->string('branch')->nullable();
            $table->string('cancelled_cheque_path', 500)->nullable();
            $table->string('agreement_path', 500)->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_center_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('scheme_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('inspection_type', ['physical', 'virtual']);
            $table->date('inspection_date');
            $table->json('checklist')->nullable();
            $table->decimal('score', 6, 2)->nullable();
            $table->decimal('max_score', 6, 2)->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])
                ->default('scheduled');
            $table->enum('recommendation', ['approve', 'reject', 'reinspect'])->nullable();
            $table->string('report_path', 500)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_inspections');
        Schema::dropIfExists('vendor_kyc');
        Schema::dropIfExists('vendor_documents');
        Schema::dropIfExists('vendor_centers');
        Schema::dropIfExists('vendors');
    }
};
