<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_approvers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_step_id')->constrained('approval_steps')->cascadeOnDelete();
            $table->string('approver_type'); // user, role, manager, department_head, position, custom
            $table->string('approver_id')->nullable(); // Role name, position, custom class
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approval_at')->nullable();
            $table->unsignedInteger('sequence')->default(1);
            $table->timestamps();

            $table->index('approval_step_id');
            $table->index('approver_type');
            $table->index('is_approved');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_approvers');
    }
};
