<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Income;
use App\Models\LiquidityAlert;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        User::factory()->admin()->create([
            'name' => 'Administrador KashaFin',
            'email' => 'admin@kashafin.test',
        ]);

        $user = User::factory()->create([
            'name' => 'Estudiante Demo',
            'email' => 'demo@kashafin.test',
        ]);

        $user->settings->update([
            'starting_balance' => 500,
            'liquidity_threshold' => 150,
        ]);

        // Fixed monthly income ("beca universitaria").
        $nextBeca = Carbon::now()->startOfMonth()->addDays(4);
        if ($nextBeca->isPast()) {
            $nextBeca->addMonthNoOverflow();
        }
        Income::create([
            'user_id' => $user->id,
            'amount' => 850,
            'date' => Carbon::now()->startOfMonth()->addDays(4),
            'description' => 'Beca universitaria',
            'type' => 'fijo',
            'frequency' => 'mensual',
            'next_occurrence_date' => $nextBeca->toDateString(),
        ]);

        // Variable incomes across the last 4 weeks.
        $variableIncomes = [
            ['amount' => 150, 'days_ago' => 25, 'description' => 'Trabajo freelance'],
            ['amount' => 90, 'days_ago' => 18, 'description' => 'Venta de apuntes'],
            ['amount' => 120, 'days_ago' => 12, 'description' => 'Trabajo freelance'],
            ['amount' => 60, 'days_ago' => 5, 'description' => 'Venta ocasional'],
            ['amount' => 100, 'days_ago' => 2, 'description' => 'Trabajo freelance'],
        ];
        foreach ($variableIncomes as $income) {
            Income::create([
                'user_id' => $user->id,
                'amount' => $income['amount'],
                'date' => Carbon::now()->subDays($income['days_ago']),
                'description' => $income['description'],
                'type' => 'variable',
            ]);
        }

        $categories = Category::query()->forUser($user)->get()->keyBy('name');
        $customCategory = Category::create([
            'user_id' => $user->id,
            'name' => 'Suscripciones',
            'is_default' => false,
        ]);

        $expensePlan = [
            ['category' => 'Transporte', 'amount' => [3, 12], 'description' => 'Pasaje', 'count' => 10],
            ['category' => 'Alimentación', 'amount' => [8, 25], 'description' => 'Almuerzo', 'count' => 12],
            ['category' => 'Materiales de estudio', 'amount' => [15, 80], 'description' => 'Fotocopias / útiles', 'count' => 5],
            ['category' => 'Entretenimiento', 'amount' => [20, 60], 'description' => 'Salida con amigos', 'count' => 4],
            ['category' => 'Otros', 'amount' => [10, 40], 'description' => 'Gasto varios', 'count' => 4],
        ];

        foreach ($expensePlan as $plan) {
            for ($i = 0; $i < $plan['count']; $i++) {
                Expense::create([
                    'user_id' => $user->id,
                    'category_id' => $categories[$plan['category']]->id,
                    'amount' => fake()->randomFloat(2, $plan['amount'][0], $plan['amount'][1]),
                    'date' => Carbon::now()->subDays(fake()->numberBetween(0, 58)),
                    'description' => $plan['description'],
                ]);
            }
        }

        // A subscription expense pushing its budget over the 90% warning line.
        Expense::create([
            'user_id' => $user->id,
            'category_id' => $customCategory->id,
            'amount' => 45,
            'date' => Carbon::now()->startOfMonth()->addDays(2),
            'description' => 'Streaming de música',
        ]);

        $periodMonth = Carbon::now()->startOfMonth();
        Budget::create(['user_id' => $user->id, 'category_id' => $categories['Transporte']->id, 'period_month' => $periodMonth, 'amount' => 100]);
        Budget::create(['user_id' => $user->id, 'category_id' => $categories['Alimentación']->id, 'period_month' => $periodMonth, 'amount' => 200]);
        Budget::create(['user_id' => $user->id, 'category_id' => $categories['Entretenimiento']->id, 'period_month' => $periodMonth, 'amount' => 100]);
        Budget::create(['user_id' => $user->id, 'category_id' => $customCategory->id, 'period_month' => $periodMonth, 'amount' => 50]);

        // Active goal, partially funded.
        $laptopGoal = SavingsGoal::create([
            'user_id' => $user->id,
            'name' => 'Nueva laptop',
            'target_amount' => 2400,
            'target_date' => Carbon::now()->addMonths(5),
            'current_amount' => 0,
            'status' => 'active',
        ]);
        $laptopGoal->addContribution(1000, Carbon::now()->subDays(20), 'Ahorro inicial');
        $laptopGoal->addContribution(600, Carbon::now()->subDays(6), 'Aporte de freelance');

        SavingsGoal::create([
            'user_id' => $user->id,
            'name' => 'Viaje de graduación',
            'target_amount' => 2000,
            'target_date' => Carbon::now()->addMonths(9),
            'current_amount' => 600,
            'status' => 'active',
        ]);

        // A completed goal for the history view.
        $emergencyFund = SavingsGoal::create([
            'user_id' => $user->id,
            'name' => 'Fondo de emergencia',
            'target_amount' => 500,
            'current_amount' => 0,
            'status' => 'active',
        ]);
        $emergencyFund->addContribution(500, Carbon::now()->subDays(40), 'Meta alcanzada');

        LiquidityAlert::create([
            'user_id' => $user->id,
            'projected_balance' => 80,
            'threshold' => 150,
            'channel' => 'dashboard',
            'sent_at' => Carbon::now()->subDays(3),
        ]);
    }
}
