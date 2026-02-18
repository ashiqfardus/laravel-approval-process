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
        Schema::create('approval_approvable_entities', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'model' or 'table'
            $table->string('name'); // Model class name or table name
            $table->string('label'); // Display name
            $table->string('connection')->nullable(); // Database connection name
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable(); // Additional config
            $table->timestamps();
            
            $table->unique(['type', 'name', 'connection']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_approvable_entities');
    }
};
