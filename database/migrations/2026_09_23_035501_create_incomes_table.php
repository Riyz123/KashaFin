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
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_income_id')->nullable()->constrained('incomes')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('description');
            $table->enum('type', ['fijo', 'variable']);
            $table->enum('frequency', ['semanal', 'quincenal', 'mensual'])->nullable();
            $table->date('next_occurrence_date')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'date']);
            $table->index(['type', 'next_occurrence_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
