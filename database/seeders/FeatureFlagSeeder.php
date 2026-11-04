<?php

namespace Database\Seeders;

use App\Models\FeatureFlag;
use Illuminate\Database\Seeder;

class FeatureFlagSeeder extends Seeder
{
    /**
     * Every section starts enabled so this migration/seed never changes
     * behavior for existing accounts — the admin has to actively disable
     * something for anything to change.
     */
    public function run(): void
    {
        $flags = [
            ['key' => 'incomes', 'label' => 'Ingresos', 'description' => 'Registrar y ver ingresos (fijos y variables).'],
            ['key' => 'expenses', 'label' => 'Gastos', 'description' => 'Registrar y ver gastos (fijos y variables).'],
            ['key' => 'budgets', 'label' => 'Presupuesto', 'description' => 'Definir y ver presupuestos por categoría.'],
            ['key' => 'savings_goals', 'label' => 'Metas', 'description' => 'Crear metas de ahorro y registrar aportes.'],
            ['key' => 'loans', 'label' => 'Préstamos', 'description' => 'Registrar préstamos, confirmaciones y amortizaciones de deuda.'],
            ['key' => 'shared_expenses', 'label' => 'Gastos compartidos', 'description' => 'Dividir gastos con otros estudiantes.'],
            ['key' => 'ai_chat', 'label' => 'Asistente IA', 'description' => 'El chat/voz con IA (incluye todas sus acciones: agregar gasto, ingreso, préstamo, etc. por chat).'],
        ];

        foreach ($flags as $flag) {
            FeatureFlag::query()->firstOrCreate(['key' => $flag['key']], $flag);
        }
    }
}
