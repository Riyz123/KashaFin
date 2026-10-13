<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shared_expense_participants', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('shared_expense_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pendiente', 'aceptado', 'rechazado'])->default('pendiente')->after('amount_owed');
            $table->decimal('daily_commitment', 10, 2)->nullable()->after('status');
            $table->timestamp('committed_at')->nullable()->after('daily_commitment');
            $table->dropColumn('is_paid');
        });

        Schema::create('shared_expense_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('participant_id')->constrained('shared_expense_participants')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_expense_contributions');

        Schema::table('shared_expense_participants', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['status', 'daily_commitment', 'committed_at']);
            $table->boolean('is_paid')->default(false);
        });
    }
};
