<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shared_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('total_amount', 10, 2);
            $table->decimal('my_share', 10, 2)->default(0);
            $table->date('date');
            $table->timestamps();
        });

        Schema::create('shared_expense_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shared_expense_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('amount_owed', 10, 2);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_expense_participants');
        Schema::dropIfExists('shared_expenses');
    }
};
