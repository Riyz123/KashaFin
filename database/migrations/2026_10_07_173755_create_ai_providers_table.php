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
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('driver', ['openai_compatible', 'gemini']);
            $table->string('base_url');
            $table->string('model');
            $table->text('api_key');
            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_exhausted')->default(false);
            $table->unsignedInteger('requests_used')->default(0);
            $table->unsignedInteger('quota_limit')->nullable();
            $table->unsignedInteger('quota_period_days')->default(1);
            $table->timestamp('period_reset_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
