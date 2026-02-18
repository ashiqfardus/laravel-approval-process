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
        // Parallel step groups (for fork/join patterns) - create first
        Schema::create('parallel_step_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->onDelete('cascade');
            $table->string('name'); // e.g., "Finance & IT Review"
            $table->string('sync_type')->default('all'); // 'all' (wait for all), 'any' (first to complete), 'majority'
            $table->integer('required_approvals')->nullable(); // For 'majority' type
            $table->foreignId('fork_from_step_id')->nullable()->constrained('approval_steps')->onDelete('set null'); // Step that forks
            $table->foreignId('join_to_step_id')->nullable()->constrained('approval_steps')->onDelete('set null'); // Step to join to
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workflow_id', 'is_active']);
        });

        // Add parallel support to approval_steps
        Schema::table('approval_steps', function (Blueprint $table) {
            $table->string('execution_type')->default('sequential')->after('approval_type'); // 'sequential', 'parallel', 'fork', 'join'
            $table->foreignId('parallel_group_id')->nullable()->after('execution_type')->constrained('parallel_step_groups')->onDelete('set null');
            $table->integer('parallel_sequence')->nullable()->after('parallel_group_id'); // Sequence within parallel group
        });

        // Track parallel execution state for each request
        Schema::create('parallel_execution_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->foreignId('parallel_group_id')->constrained('parallel_step_groups')->onDelete('cascade');
            $table->string('status')->default('pending'); // 'pending', 'in_progress', 'completed', 'failed'
            $table->integer('completed_steps')->default(0);
            $table->integer('total_steps')->default(0);
            $table->json('step_statuses')->nullable(); // Track individual step statuses
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['approval_request_id', 'status']);
            $table->unique(['approval_request_id', 'parallel_group_id'], 'parallel_exec_request_group_unique');
        });

        // Track which steps are currently active in parallel execution
        Schema::create('active_parallel_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->foreignId('step_id')->constrained('approval_steps')->onDelete('cascade');
            $table->foreignId('parallel_group_id')->constrained('parallel_step_groups')->onDelete('cascade');
            $table->string('status')->default('pending'); // 'pending', 'in_progress', 'approved', 'rejected'
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['approval_request_id', 'status']);
            $table->index(['step_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_parallel_steps');
        Schema::dropIfExists('parallel_execution_states');
        Schema::dropIfExists('parallel_step_groups');
        
        Schema::table('approval_steps', function (Blueprint $table) {
            $table->dropForeign(['parallel_group_id']);
            $table->dropColumn(['execution_type', 'parallel_group_id', 'parallel_sequence']);
        });
    }
};
