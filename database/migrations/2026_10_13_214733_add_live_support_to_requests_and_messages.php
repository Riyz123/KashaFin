<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * support_requests.status (2-value enum: pendiente/atendido) needs a
     * third value (en_atencion, "a human is live-replying right now").
     * chat_messages.role (2-value enum: user/assistant) needs a third
     * value (advisor). Widening a MySQL enum needs doctrine/dbal (not
     * installed), so both become plain strings instead — same approach
     * already used for loans.status. MySQL/MariaDB gets a raw MODIFY (not
     * Laravel's renameColumn() fallback, which corrupts a quoted string
     * default on MariaDB < 10.5 — verified and fixed once already for
     * loans.status in an earlier migration).
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            Schema::table('support_requests', function (Blueprint $table) {
                $table->string('status_new', 30)->default('pendiente')->after('status');
            });
            DB::table('support_requests')->update(['status_new' => DB::raw('status')]);
            Schema::table('support_requests', function (Blueprint $table) {
                $table->dropColumn('status');
            });
            Schema::table('support_requests', function (Blueprint $table) {
                $table->renameColumn('status_new', 'status');
            });

            Schema::table('chat_messages', function (Blueprint $table) {
                // A harmless placeholder default so adding this NOT NULL
                // column to an already-populated table succeeds — every
                // row gets overwritten with its real role right after.
                $table->string('role_new', 20)->default('user')->after('role');
            });
            DB::table('chat_messages')->update(['role_new' => DB::raw('role')]);
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->dropColumn('role');
            });
            Schema::table('chat_messages', function (Blueprint $table) {
                $table->renameColumn('role_new', 'role');
            });
        } else {
            DB::statement("ALTER TABLE support_requests MODIFY status VARCHAR(30) NOT NULL DEFAULT 'pendiente'");
            DB::statement('ALTER TABLE chat_messages MODIFY role VARCHAR(20) NOT NULL');
        }

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->foreignId('support_request_id')->nullable()->after('role')
                ->constrained('support_requests')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('support_request_id');
        });
    }
};
