<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetAndGoalFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_search_only_matches_the_category_name(): void
    {
        $student = User::factory()->create();
        $food = Category::create(['user_id' => $student->id, 'name' => 'Comida', 'is_active' => true]);
        $transport = Category::create(['user_id' => $student->id, 'name' => 'Transporte', 'is_active' => true]);

        Budget::create(['user_id' => $student->id, 'category_id' => $food->id, 'period_month' => now()->startOfMonth(), 'amount' => 100]);
        Budget::create(['user_id' => $student->id, 'category_id' => $transport->id, 'period_month' => now()->startOfMonth(), 'amount' => 50]);

        $response = $this->actingAs($student)->get(route('budgets.index', ['search' => 'Comida']));

        $response->assertOk();
        $response->assertSee('Comida');
        $response->assertDontSee('Transporte');
    }

    public function test_budget_month_selector_shows_a_different_period(): void
    {
        $student = User::factory()->create();
        $food = Category::create(['user_id' => $student->id, 'name' => 'Comida', 'is_active' => true]);

        $lastMonth = now()->subMonthNoOverflow()->startOfMonth();
        Budget::create(['user_id' => $student->id, 'category_id' => $food->id, 'period_month' => $lastMonth, 'amount' => 75]);

        $response = $this->actingAs($student)->get(route('budgets.index', ['month' => $lastMonth->format('Y-m')]));

        $response->assertOk();
        $response->assertSee('Comida');
    }

    public function test_goal_search_only_matches_the_name(): void
    {
        $student = User::factory()->create();

        SavingsGoal::create(['user_id' => $student->id, 'name' => 'Laptop nueva', 'target_amount' => 1000, 'current_amount' => 0, 'status' => 'active']);
        SavingsGoal::create(['user_id' => $student->id, 'name' => 'Viaje a Cusco', 'target_amount' => 500, 'current_amount' => 0, 'status' => 'active']);

        $response = $this->actingAs($student)->get(route('goals.index', ['search' => 'Laptop']));

        $response->assertOk();
        $response->assertSee('Laptop nueva');
        $response->assertDontSee('Viaje a Cusco');
    }
}
