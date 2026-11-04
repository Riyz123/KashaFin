<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets the master admin turn a whole section of the student app off
     * (Gastos, Préstamos, el Asistente IA, etc.) without touching a single
     * row of the data already in it — this table only gates ACCESS
     * (routes + the chatbot's intents), nothing here ever deletes or
     * hides existing expenses/loans/etc. `disabled_at`/`disabled_by`
     * exist purely for admin-facing traceability of who turned a section
     * off and when, on top of the activity log the model already keeps.
     */
    public function up(): void
    {
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('disabled_at')->nullable();
            $table->foreignId('disabled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Inserted directly here (not only in FeatureFlagSeeder) so this
        // single migration is enough on its own in production — matches
        // how every other schema change in this project ships as one
        // incremental SQL file, without a separate db:seed step.
        $now = now();
        DB::table('feature_flags')->insert(array_map(fn (array $flag) => $flag + [
            'is_enabled' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], [
            ['key' => 'incomes', 'label' => 'Ingresos', 'description' => 'Registrar y ver ingresos (fijos y variables).'],
            ['key' => 'expenses', 'label' => 'Gastos', 'description' => 'Registrar y ver gastos (fijos y variables).'],
            ['key' => 'budgets', 'label' => 'Presupuesto', 'description' => 'Definir y ver presupuestos por categoría.'],
            ['key' => 'savings_goals', 'label' => 'Metas', 'description' => 'Crear metas de ahorro y registrar aportes.'],
            ['key' => 'loans', 'label' => 'Préstamos', 'description' => 'Registrar préstamos, confirmaciones y amortizaciones de deuda.'],
            ['key' => 'shared_expenses', 'label' => 'Gastos compartidos', 'description' => 'Dividir gastos con otros estudiantes.'],
            ['key' => 'ai_chat', 'label' => 'Asistente IA', 'description' => 'El chat/voz con IA (incluye todas sus acciones: agregar gasto, ingreso, préstamo, etc. por chat).'],
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }
};
