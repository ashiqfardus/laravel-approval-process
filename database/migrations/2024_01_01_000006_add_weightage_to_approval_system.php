<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add weightage column to approval_approvers table
        Schema::table('approval_approvers', function (Blueprint $table) {
            $table->unsignedInteger('weightage')->default(100)->after('sequence')
                ->comment('Approval weightage/voting power (0-100)');
        });

        // Add minimum approval percentage to approval_steps table
        Schema::table('approval_steps', function (Blueprint $table) {
            $table->unsignedInteger('minimum_approval_percentage')->default(100)->after('allows_partial_approval')
                ->comment('Minimum percentage of weightage required to proceed (0-100)');
        });

        // Add approval percentage tracking to approval_requests table
        Schema::table('approval_requests', function (Blueprint $table) {
            $table->decimal('current_approval_percentage', 5, 2)->default(0)->after('current_step_id')
                ->comment('Current approval percentage at current step');
        });
    }

    public function down(): void
    {
        Schema::table('approval_approvers', function (Blueprint $table) {
            $table->dropColumn('weightage');
        });

        Schema::table('approval_steps', function (Blueprint $table) {
            $table->dropColumn('minimum_approval_percentage');
        });

        Schema::table('approval_requests', function (Blueprint $table) {
            $table->dropColumn('current_approval_percentage');
        });
    }
};
