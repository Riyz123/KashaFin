<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `status` was a 2-value enum (pendiente/pagado). It now needs 3 more
     * values for the confirmation workflow (pendiente_confirmacion,
     * rechazado, pago_pendiente_confirmacion) — widening a MySQL enum needs
     * a raw MODIFY, and doctrine/dbal (required by Schema::change()) isn't
     * installed, so instead this swaps the column for a plain string via
     * add/copy/drop/rename, which works the same way on MySQL and SQLite
     * (the tests' driver) without losing existing rows' values.
     */
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('status_new', 30)->default('pendiente')->after('status');
        });

        DB::table('loans')->update(['status_new' => DB::raw('status')]);

        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->renameColumn('status_new', 'status');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('counterpart_loan_id')->nullable()->after('status')
                ->constrained('loans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('counterpart_loan_id');
        });
    }
};
