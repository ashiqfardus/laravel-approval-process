<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Attachments for approval requests
        Schema::create('approval_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->unsignedBigInteger('uploaded_by_user_id');
            $table->string('file_name');
            $table->string('original_file_name');
            $table->string('file_path');
            $table->string('file_type'); // mime type
            $table->string('file_extension');
            $table->unsignedBigInteger('file_size'); // bytes
            $table->string('storage_disk')->default('local'); // local, s3, etc.
            $table->string('attachment_type')->default('supporting_document'); // supporting_document, signature, template_output
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional file metadata
            $table->boolean('is_required')->default(false);
            $table->boolean('is_public')->default(false);
            $table->string('hash')->nullable(); // File hash for integrity
            $table->timestamp('scanned_at')->nullable(); // Virus scan timestamp
            $table->string('scan_status')->nullable(); // clean, infected, pending
            $table->timestamps();
            $table->softDeletes();

            $table->index(['approval_request_id', 'attachment_type']);
            $table->index(['uploaded_by_user_id', 'created_at']);
        });

        // Document templates
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // Unique identifier for template
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // contract, invoice, report, etc.
            $table->text('content'); // Template content with placeholders
            $table->string('file_type')->default('pdf'); // pdf, docx, html
            $table->json('variables')->nullable(); // Available template variables
            $table->json('settings')->nullable(); // Template-specific settings
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category', 'is_active']);
        });

        // Generated documents from templates
        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->foreignId('template_id')->constrained('document_templates')->onDelete('cascade');
            $table->unsignedBigInteger('generated_by_user_id');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type');
            $table->unsignedBigInteger('file_size');
            $table->json('template_data')->nullable(); // Data used to generate document
            $table->string('status')->default('generated'); // generated, sent, signed, archived
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['approval_request_id', 'status']);
            $table->index(['template_id', 'created_at']);
        });

        // Digital signatures
        Schema::create('approval_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->foreignId('approval_action_id')->nullable()->constrained('approval_actions')->onDelete('set null');
            $table->unsignedBigInteger('user_id');
            $table->string('signature_type'); // drawn, typed, uploaded, digital_certificate
            $table->text('signature_data'); // Base64 encoded signature or certificate data
            $table->string('signature_format')->nullable(); // png, svg, pkcs7
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('device_info')->nullable();
            $table->json('geolocation')->nullable();
            $table->string('verification_method')->nullable(); // email, sms, biometric
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('certificate_serial')->nullable(); // For digital certificates
            $table->timestamp('certificate_expires_at')->nullable();
            $table->timestamps();

            $table->index(['approval_request_id', 'user_id']);
            $table->index(['approval_action_id']);
        });

        // Document access log for audit
        Schema::create('document_access_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('document'); // approval_attachments or generated_documents
            $table->unsignedBigInteger('user_id');
            $table->string('action'); // viewed, downloaded, printed, shared
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['action']);
            $table->index(['user_id', 'created_at']);
        });

        // Document sharing (for external parties)
        Schema::create('document_shares', function (Blueprint $table) {
            $table->id();
            $table->morphs('document'); // approval_attachments or generated_documents
            $table->unsignedBigInteger('shared_by_user_id');
            $table->string('share_token')->unique();
            $table->string('recipient_email')->nullable();
            $table->string('recipient_name')->nullable();
            $table->text('message')->nullable();
            $table->boolean('requires_password')->default(false);
            $table->string('password_hash')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('max_views')->nullable();
            $table->integer('view_count')->default(0);
            $table->boolean('allow_download')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamp('first_accessed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->index(['share_token', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_shares');
        Schema::dropIfExists('document_access_logs');
        Schema::dropIfExists('approval_signatures');
        Schema::dropIfExists('generated_documents');
        Schema::dropIfExists('document_templates');
        Schema::dropIfExists('approval_attachments');
    }
};
