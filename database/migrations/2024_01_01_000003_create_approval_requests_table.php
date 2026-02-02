<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('approval_workflows');
            $table->foreignId('current_step_id')->nullable()->constrained('approval_steps');
            $table->string('requestable_type');
            $table->unsignedBigInteger('requestable_id');
            $table->foreignId('requested_by_user_id')->constrained(
                config('auth.providers.users.model') . 's'
            );
            $table->enum('status', [
                'draft',
                'submitted',
                'in-review',
                'pending',
                'approved',
                'rejected',
                'cancelled',
                'archived'
            ])->default('draft');
            $table->json('data_snapshot')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['requestable_type', 'requestable_id']);
            $table->index('status');
            $table->index('requested_by_user_id');
            $table->index('workflow_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_requests');
    }
};
