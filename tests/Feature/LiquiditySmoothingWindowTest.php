<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use App\Services\LiquidityProjectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiquiditySmoothingWindowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_account_smooths_over_its_real_history_instead_of_a_flat_28_days(): void
    {
        // Account only has 3 days of real history (earliest record 3 days
        // ago). Dividing a S/ 300 total by a flat 28 days would understate
        // the daily rate far below what's actually happening (S/ 100/day)
        // — the fix should divide by the real 3-day window instead.
        $student = User::factory()->create();

        for ($i = 1; $i <= 3; $i++) {
            Expense::create([
                'user_id' => $student->id,
                'category_id' => null,
                'amount' => 100,
                'date' => now()->subDays($i),
                'description' => 'Gasto diario',
            ]);
        }

        $breakdown = (new LiquidityProjectionService)->smoothingBreakdown($student);

        $this->assertSame(3, $breakdown['window_days']);
        $this->assertEqualsWithDelta(100.0, $breakdown['expense_daily_rate'], 0.01);
    }

    public function test_an_old_account_still_caps_the_window_at_28_days(): void
    {
        $student = User::factory()->create();

        Income::create([
            'user_id' => $student->id,
            'amount' => 50,
            'date' => now()->subDays(90),
            'description' => 'Ingreso antiguo',
            'type' => 'variable',
        ]);

        $breakdown = (new LiquidityProjectionService)->smoothingBreakdown($student);

        $this->assertSame(28, $breakdown['window_days']);
    }

    public function test_a_fixed_expense_lands_on_its_scheduled_date_instead_of_being_smoothed_into_the_daily_rate(): void
    {
        $student = User::factory()->create();

        // A variable expense DOES get smoothed...
        Expense::create([
            'user_id' => $student->id,
            'amount' => 7,
            'date' => now()->subDay(),
            'description' => 'Comida',
            'type' => 'variable',
        ]);

        // ...but a fixed one lands as a lump sum on its exact due date, not
        // diluted across the smoothing window. Dated the same day as the
        // variable expense above so it doesn't widen the smoothing window.
        Expense::create([
            'user_id' => $student->id,
            'amount' => 80,
            'date' => now()->subDay(),
            'description' => 'Alquiler',
            'type' => 'fijo',
            'frequency' => 'mensual',
            'next_occurrence_date' => now()->addDays(5)->toDateString(),
        ]);

        $breakdown = (new LiquidityProjectionService)->smoothingBreakdown($student);
        $this->assertEqualsWithDelta(7.0, $breakdown['expense_daily_rate'], 0.01);
        $this->assertEqualsWithDelta(80.0, $breakdown['upcoming_fixed_expenses']->get(now()->addDays(5)->toDateString()), 0.01);

        $series = (new LiquidityProjectionService)->dailySeries($student, 15);
        $dayBefore = $series->firstWhere('date', now()->addDays(4)->toDateString());
        $dayOf = $series->firstWhere('date', now()->addDays(5)->toDateString());

        // Every day already drops by the smoothed variable expense rate
        // (S/ 7); day 5 additionally takes the S/ 80 fixed lump sum, so the
        // day-over-day drop is 7 + 80, not just the fixed amount alone.
        $this->assertEqualsWithDelta(87.0, $dayBefore['balance'] - $dayOf['balance'], 0.01);
    }
}
