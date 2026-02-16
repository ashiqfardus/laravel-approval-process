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
        // Analytics snapshots for performance tracking
        Schema::create('approval_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('metric_type'); // request_count, approval_time, rejection_rate, etc.
            $table->string('dimension')->nullable(); // workflow, user, department, etc.
            $table->unsignedBigInteger('dimension_id')->nullable();
            $table->string('period'); // daily, weekly, monthly, yearly
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('value', 15, 2);
            $table->json('metadata')->nullable(); // Additional context
            $table->timestamps();

            $table->index(['metric_type', 'period', 'period_start']);
            $table->index(['dimension', 'dimension_id']);
        });

        // Custom reports configuration
        Schema::create('custom_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('report_type'); // summary, detailed, comparison, trend
            $table->json('filters')->nullable(); // Report filters
            $table->json('columns')->nullable(); // Columns to include
            $table->json('grouping')->nullable(); // Group by fields
            $table->json('sorting')->nullable(); // Sort order
            $table->json('chart_config')->nullable(); // Chart configuration
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency')->nullable(); // daily, weekly, monthly
            $table->json('schedule_recipients')->nullable(); // Email recipients
            $table->boolean('is_public')->default(false);
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['report_type', 'is_public']);
            $table->index(['is_scheduled', 'schedule_frequency']);
        });

        // Report execution history
        Schema::create('report_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained('custom_reports')->onDelete('cascade');
            $table->unsignedBigInteger('executed_by_user_id')->nullable();
            $table->string('status'); // pending, running, completed, failed
            $table->integer('record_count')->nullable();
            $table->text('file_path')->nullable(); // Path to generated report file
            $table->string('file_format')->nullable(); // pdf, excel, csv
            $table->integer('execution_time_ms')->nullable();
            $table->text('error_message')->nullable();
            $table->json('parameters')->nullable(); // Parameters used for execution
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['report_id', 'status']);
            $table->index(['executed_by_user_id', 'created_at']);
        });

        // Workflow performance metrics
        Schema::create('workflow_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->onDelete('cascade');
            $table->date('metric_date');
            $table->integer('total_requests')->default(0);
            $table->integer('approved_requests')->default(0);
            $table->integer('rejected_requests')->default(0);
            $table->integer('pending_requests')->default(0);
            $table->integer('cancelled_requests')->default(0);
            $table->decimal('avg_approval_time_hours', 10, 2)->nullable();
            $table->decimal('avg_steps_completed', 5, 2)->nullable();
            $table->decimal('approval_rate', 5, 2)->nullable(); // Percentage
            $table->decimal('rejection_rate', 5, 2)->nullable(); // Percentage
            $table->decimal('sla_compliance_rate', 5, 2)->nullable(); // Percentage
            $table->timestamps();

            $table->unique(['workflow_id', 'metric_date']);
            $table->index(['metric_date']);
        });

        // User performance metrics
        Schema::create('user_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('metric_date');
            $table->integer('requests_submitted')->default(0);
            $table->integer('approvals_given')->default(0);
            $table->integer('rejections_given')->default(0);
            $table->integer('pending_approvals')->default(0);
            $table->decimal('avg_response_time_hours', 10, 2)->nullable();
            $table->integer('overdue_approvals')->default(0);
            $table->integer('delegations_created')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'metric_date']);
            $table->index(['metric_date']);
        });

        // Approval bottlenecks tracking
        Schema::create('approval_bottlenecks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->onDelete('cascade');
            $table->foreignId('step_id')->constrained('approval_steps')->onDelete('cascade');
            $table->date('detected_date');
            $table->integer('pending_count');
            $table->decimal('avg_wait_time_hours', 10, 2);
            $table->string('severity'); // low, medium, high, critical
            $table->text('recommendation')->nullable();
            $table->boolean('is_resolved')->default(false);
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['workflow_id', 'detected_date']);
            $table->index(['severity', 'is_resolved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_bottlenecks');
        Schema::dropIfExists('user_metrics');
        Schema::dropIfExists('workflow_metrics');
        Schema::dropIfExists('report_executions');
        Schema::dropIfExists('custom_reports');
        Schema::dropIfExists('approval_analytics');
    }
};
