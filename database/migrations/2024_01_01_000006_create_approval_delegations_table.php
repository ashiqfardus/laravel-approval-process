<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('delegated_to_user_id')->constrained('users');
            $table->foreignId('approval_step_id')->nullable()->constrained('approval_steps');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->enum('delegation_type', ['temporary', 'permanent', 'emergency'])->default('temporary');
            $table->string('module_type')->nullable();
            $table->string('role_type')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('delegated_to_user_id');
            $table->index('is_active');
            $table->index('starts_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_delegations');
    }
};
