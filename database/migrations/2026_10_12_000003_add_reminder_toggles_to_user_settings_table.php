<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->boolean('notify_inactivity_by_email')->default(true)->after('notify_low_liquidity_by_email');
            $table->boolean('notify_income_confirmation_by_email')->default(true)->after('notify_inactivity_by_email');
        });
    }

    public function down(): void
    {
        Schema::table('user_settings', function (Blueprint $table) {
            $table->dropColumn(['notify_inactivity_by_email', 'notify_income_confirmation_by_email']);
        });
    }
};
