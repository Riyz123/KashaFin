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
     * rechazado, pago_pendiente_confirmacion). Widening a MySQL enum (or
     * using Schema::change()) needs doctrine/dbal, which isn't installed,
     * so MySQL/MariaDB gets a plain raw MODIFY instead — simple and safe.
     *
     * SQLite (the tests' driver) has no MODIFY/ALTER COLUMN at all, so it
     * goes through add/copy/drop/rename instead. That path can't be reused
     * for MySQL/MariaDB: on MariaDB < 10.5 (no native RENAME COLUMN),
     * Laravel's rename fallback re-reads the column's default from
     * information_schema — where MariaDB already stores it quoted — and
     * wraps it in quotes again, corrupting it to DEFAULT '''pendiente'''.
     * Verified against a local MariaDB 10.4 copy of the production schema.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
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
        } else {
            DB::statement("ALTER TABLE loans MODIFY status VARCHAR(30) NOT NULL DEFAULT 'pendiente'");
        }

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
