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
        Schema::table('users', function (Blueprint $table) {
            // Null = solicitud pendiente de aprobación por su decano (o
            // admin). Las cuentas creadas directamente por staff (decanos,
            // admins, importación CSV) nacen ya aprobadas.
            $table->timestamp('approved_at')->nullable()->after('faculty_id');
            $table->boolean('must_change_password')->default(false)->after('approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['approved_at', 'must_change_password']);
        });
    }
};
