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
        Schema::create('query_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')->constrained('approval_requests')->onDelete('cascade');
            $table->string('query_type'); // 'sql', 'view', 'builder', 'api'
            $table->json('query_definition'); // Query details (SQL, view name, etc.)
            $table->json('result_snapshot'); // Snapshot of query result at submission
            $table->string('identifier')->index(); // Unique identifier for the data
            $table->text('description')->nullable(); // Human-readable description
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('query_approval_requests');
    }
};
