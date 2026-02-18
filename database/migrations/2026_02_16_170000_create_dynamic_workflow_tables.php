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
        // Workflow versions for tracking changes
        Schema::create('workflow_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->onDelete('cascade');
            $table->integer('version_number');
            $table->json('workflow_snapshot'); // Complete workflow state at this version
            $table->json('steps_snapshot'); // Steps configuration at this version
            $table->string('change_type'); // 'created', 'step_added', 'step_removed', 'step_modified', 'approver_changed'
            $table->text('change_description')->nullable();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_active')->default(false); // Only one version is active
            $table->timestamps();

            $table->index(['workflow_id', 'version_number']);
            $table->index(['workflow_id', 'is_active']);
            $table->unique(['workflow_id', 'version_number']);
        });

        // Track dynamic step modifications during active requests
        Schema::create('dynamic_step_modifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->foreignId('step_id')->nullable()->constrained('approval_steps')->onDelete('set null');
            $table->string('modification_type'); // 'added', 'removed', 'modified', 'skipped', 'reordered'
            $table->json('old_data')->nullable(); // State before modification
            $table->json('new_data')->nullable(); // State after modification
            $table->text('reason')->nullable();
            $table->foreignId('modified_by_user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_applied')->default(false);
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index(['approval_request_id', 'modification_type'], 'dynamic_mods_request_type_idx');
            $table->index(['step_id', 'is_applied'], 'dynamic_mods_step_applied_idx');
        });

        // Dynamic approver assignments (runtime approver changes)
        Schema::create('dynamic_approver_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->foreignId('step_id')->constrained('approval_steps')->onDelete('cascade');
            $table->foreignId('original_approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('new_approver_id')->constrained('users')->onDelete('cascade');
            $table->string('assignment_type'); // 'replacement', 'addition', 'temporary'
            $table->text('reason')->nullable();
            $table->foreignId('assigned_by_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['approval_request_id', 'is_active']);
            $table->index(['step_id', 'is_active']);
        });

        // Workflow modification rules (what can be changed and when)
        Schema::create('workflow_modification_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->onDelete('cascade');
            $table->string('rule_type'); // 'allow_step_addition', 'allow_step_removal', 'allow_approver_change', 'allow_reordering'
            $table->json('conditions')->nullable(); // When this rule applies
            $table->json('restrictions')->nullable(); // Limitations on modifications
            $table->boolean('requires_approval')->default(false); // Does the modification itself need approval?
            $table->unsignedBigInteger('approval_required_from_user_id')->nullable();
            $table->foreign('approval_required_from_user_id', 'wf_mod_approval_user_fk')->references('id')->on('users')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workflow_id', 'rule_type', 'is_active'], 'wf_mod_rules_wf_type_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_modification_rules');
        Schema::dropIfExists('dynamic_approver_assignments');
        Schema::dropIfExists('dynamic_step_modifications');
        Schema::dropIfExists('workflow_versions');
    }
};
