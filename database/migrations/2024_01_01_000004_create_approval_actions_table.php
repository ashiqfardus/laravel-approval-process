<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->cascadeOnDelete();
            $table->foreignId('approval_step_id')->constrained('approval_steps');
            $table->foreignId('user_id')->constrained('users');
            $table->string('action');
            $table->text('remarks')->nullable();
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('device_info')->nullable();
            $table->timestamp('action_at')->nullable();
            $table->timestamps();

            $table->index('approval_request_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_actions');
    }
};
