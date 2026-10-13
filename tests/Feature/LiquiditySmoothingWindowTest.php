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
}
