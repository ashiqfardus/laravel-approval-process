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
        Schema::create('approval_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->foreignId('from_user_id')->nullable()->constrained('users')->onDelete('set null')->comment('Original approver');
            $table->foreignId('to_user_id')->constrained('users')->onDelete('cascade')->comment('Escalated to');
            $table->integer('from_level')->comment('Original approval level');
            $table->integer('to_level')->comment('Escalated to level');
            $table->string('reason')->comment('sla_timeout, manual, auto');
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('approval_request_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_escalations');
    }
};
