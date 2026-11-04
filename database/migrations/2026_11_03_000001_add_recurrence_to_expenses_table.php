<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors the "fijo"/"variable" split that incomes already have
     * (see 2026_09_23_035501_create_incomes_table.php) — expenses never
     * had it, so recurring expenses (rent, subscriptions) landed as
     * one-off rows the student had to re-enter every period, and the
     * liquidity projection smoothed 100% of expense history into a daily
     * rate instead of placing fixed ones on their real due dates.
     *
     * `type` defaults to 'variable' (unlike incomes, where it's required)
     * so every existing expense row stays valid without a backfill.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('parent_expense_id')->nullable()->after('category_id')->constrained('expenses')->nullOnDelete();
            $table->enum('type', ['fijo', 'variable'])->default('variable')->after('amount');
            $table->enum('frequency', ['semanal', 'quincenal', 'mensual'])->nullable()->after('type');
            $table->date('next_occurrence_date')->nullable()->after('frequency');
            $table->index(['type', 'next_occurrence_date']);
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['type', 'next_occurrence_date']);
            $table->dropConstrainedForeignId('parent_expense_id');
            $table->dropColumn(['type', 'frequency', 'next_occurrence_date']);
        });
    }
};
