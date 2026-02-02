<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('model_type');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->json('config')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['name', 'deleted_at']);
            $table->index('model_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_workflows');
    }
};
