<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Income;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The dashboard's "Saldo actual" was reported as miscounted with no way for
 * a student to check it themselves — this page shows the exact same formula
 * LiquidityProjectionService::currentBalance() computes, broken into its
 * four terms, so it must always add up to that same total.
 */
class BalanceBreakdownTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_breakdown_matches_the_dashboards_current_balance(): void
    {
        $student = User::factory()->create();
        $student->settings()->update(['starting_balance' => 50]);

        Income::create(['user_id' => $student->id, 'amount' => 100, 'date' => now()->subDays(2), 'description' => 'Beca', 'type' => 'variable']);
        Expense::create(['user_id' => $student->id, 'amount' => 30, 'date' => now()->subDay(), 'description' => 'Comida']);
        Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 20, 'date' => now()->toDateString(), 'status' => 'pendiente',
        ]);

        $response = $this->actingAs($student)->get(route('dashboard.balance'));

        $response->assertOk();
        $response->assertViewHas('startingBalance', 50.0);
        $response->assertViewHas('incomeTotal', 100.0);
        $response->assertViewHas('expenseTotal', 30.0);
        $response->assertViewHas('loanTotal', 20.0);
        $response->assertViewHas('currentBalance', 140.0);
        $response->assertSee('S/ 140.00');

        $dashboard = $this->get(route('dashboard'));
        $dashboard->assertViewHas('currentBalance', 140.0);
    }

    public function test_a_future_dated_income_does_not_count_yet(): void
    {
        $student = User::factory()->create();

        Income::create(['user_id' => $student->id, 'amount' => 100, 'date' => now()->addWeek(), 'description' => 'Beca', 'type' => 'variable']);

        $response = $this->actingAs($student)->get(route('dashboard.balance'));

        $response->assertViewHas('incomeTotal', 0.0);
        $response->assertViewHas('incomeCount', 0);
    }

    public function test_a_pending_confirmation_loan_is_excluded(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();

        Loan::create([
            'user_id' => $student->id, 'person_name' => 'Ana', 'person_user_id' => $other->id,
            'direction' => 'recibido', 'amount' => 20, 'date' => now()->toDateString(), 'status' => 'pendiente_confirmacion',
        ]);

        $response = $this->actingAs($student)->get(route('dashboard.balance'));

        $response->assertViewHas('loanTotal', 0.0);
        $response->assertViewHas('loanCount', 0);
    }
}
