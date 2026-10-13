<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `user_id` already means "who owns this loan record" — this is a
     * different thing: an optional link to the OTHER person in the loan,
     * when they're a real KashaFin account. `person_name` stays required
     * either way (free text for an external person/entity, or a snapshot
     * of the linked account's name).
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('person_user_id')->nullable()->after('person_name')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_user_id');
        });
    }
};
