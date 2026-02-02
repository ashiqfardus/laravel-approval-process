<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sequence');
            $table->enum('approval_type', ['serial', 'parallel', 'any-one'])->default('serial');
            $table->boolean('is_active')->default(true);
            $table->json('condition_config')->nullable();
            $table->unsignedInteger('sla_hours')->nullable();
            $table->string('escalation_strategy')->nullable();
            $table->boolean('allows_delegation')->default(true);
            $table->boolean('allows_partial_approval')->default(false);
            $table->timestamps();

            $table->unique(['workflow_id', 'sequence']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_steps');
    }
};
