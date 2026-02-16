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
        Schema::create('workflow_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->onDelete('cascade');
            $table->foreignId('from_step_id')->constrained('approval_steps')->onDelete('cascade');
            $table->foreignId('to_step_id')->nullable()->constrained('approval_steps')->onDelete('cascade');
            $table->string('name')->nullable(); // Descriptive name for the condition
            $table->string('field'); // Field to evaluate (e.g., 'amount', 'department_id')
            $table->string('operator'); // Comparison operator (e.g., '>', '<', '=', '!=', 'in', 'not_in', 'between')
            $table->json('value'); // Value(s) to compare against
            $table->string('logic_operator')->default('and'); // 'and' or 'or' for multiple conditions
            $table->integer('priority')->default(0); // Evaluation order (higher priority first)
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes
            $table->index(['workflow_id', 'from_step_id']);
            $table->index(['is_active', 'priority']);
        });

        Schema::create('workflow_condition_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->onDelete('cascade');
            $table->string('name'); // Group name (e.g., "High Value Route", "Department A Route")
            $table->string('logic_operator')->default('and'); // 'and' or 'or' for conditions in this group
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workflow_id', 'is_active']);
        });

        Schema::create('workflow_condition_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('workflow_condition_groups')->onDelete('cascade');
            $table->foreignId('condition_id')->constrained('workflow_conditions')->onDelete('cascade');
            $table->integer('sequence')->default(0);
            $table->timestamps();

            $table->index(['group_id', 'sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_condition_group_items');
        Schema::dropIfExists('workflow_condition_groups');
        Schema::dropIfExists('workflow_conditions');
    }
};
