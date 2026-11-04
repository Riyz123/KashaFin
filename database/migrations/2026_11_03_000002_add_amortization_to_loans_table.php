<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `paid_amount` tracks how much of `amount` has actually been paid off
     * so far — separate from `status` (which still only flips to "pagado"
     * once paid_amount reaches amount) so a debt can be paid in several
     * partial installments ("amortizaciones") instead of only all-at-once.
     *
     * `last_overdue_notified_at` throttles the "tu deuda está vencida"
     * reminder to once every 3 days (see Console\Commands\NotifyOverdueLoans)
     * without needing a separate notifications-sent table.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->decimal('paid_amount', 10, 2)->default(0)->after('amount');
            $table->timestamp('last_overdue_notified_at')->nullable()->after('due_date');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'last_overdue_notified_at']);
        });
    }
};
