<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_an_expense_logs_an_activity_with_a_readable_description(): void
    {
        $student = User::factory()->create();
        $category = Category::factory()->create(['name' => 'Transporte']);

        $this->actingAs($student)->post(route('expenses.store'), [
            'amount' => 25.5,
            'date' => now()->toDateString(),
            'category_id' => $category->id,
            'description' => 'Pasaje',
        ])->assertRedirect();

        $activity = Activity::where('log_name', 'expense')->first();

        $this->assertNotNull($activity);
        $this->assertSame($student->id, $activity->causer_id);
        $this->assertStringContainsString('Registró un gasto de S/ 25.50', $activity->description);
        $this->assertStringContainsString('Transporte', $activity->description);
    }

    public function test_deleting_an_expense_logs_an_activity(): void
    {
        $student = User::factory()->create();
        $expense = Expense::create(['user_id' => $student->id, 'amount' => 10, 'date' => now(), 'description' => 'x']);

        $this->actingAs($student)->delete(route('expenses.destroy', $expense))->assertRedirect();

        $activity = Activity::where('log_name', 'expense')->where('event', 'deleted')->first();

        $this->assertNotNull($activity);
        $this->assertStringContainsString('Eliminó un gasto', $activity->description);
    }

    public function test_updating_an_expense_does_not_log_anything(): void
    {
        $student = User::factory()->create();
        $category = Category::factory()->create();
        $expense = Expense::create(['user_id' => $student->id, 'category_id' => $category->id, 'amount' => 10, 'date' => now(), 'description' => 'x']);

        Activity::query()->delete();

        $this->actingAs($student)->put(route('expenses.update', $expense), [
            'amount' => 20,
            'date' => now()->toDateString(),
            'category_id' => $category->id,
            'description' => 'y',
        ])->assertRedirect();

        $this->assertSame(0, Activity::count());
    }

    public function test_deleting_a_loan_logs_an_activity_with_the_person_and_amount(): void
    {
        $student = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $student->id, 'person_name' => 'Ana', 'direction' => 'prestado',
            'amount' => 50, 'date' => now(), 'status' => 'pendiente',
        ]);

        $this->actingAs($student)->delete(route('loans.destroy', $loan))->assertRedirect();

        $activity = Activity::where('log_name', 'loan')->first();

        $this->assertNotNull($activity);
        $this->assertStringContainsString('Ana', $activity->description);
        $this->assertStringContainsString('50.00', $activity->description);
    }

    public function test_the_activity_page_only_shows_the_current_users_own_activity(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();

        // Direct model creation, not an HTTP request — actingAs() still
        // sets the real auth state for the test process, which is what
        // the activitylog package reads as the causer at creation time.
        $this->actingAs($student);
        Expense::create(['user_id' => $student->id, 'amount' => 10, 'date' => now(), 'description' => 'mine']);

        $this->actingAs($other);
        Expense::create(['user_id' => $other->id, 'amount' => 99, 'date' => now(), 'description' => 'not mine']);

        $response = $this->actingAs($student)->get(route('activity.index'));

        $response->assertOk();
        $response->assertSee('10.00');
        $response->assertDontSee('99.00');
    }

    public function test_the_activity_page_filters_by_section(): void
    {
        $student = User::factory()->create();
        $this->actingAs($student);
        Expense::create(['user_id' => $student->id, 'amount' => 10, 'date' => now(), 'description' => 'gasto']);
        \App\Models\SavingsGoal::create(['user_id' => $student->id, 'name' => 'Laptop', 'target_amount' => 500, 'current_amount' => 0, 'status' => 'active']);

        $response = $this->actingAs($student)->get(route('activity.index', ['section' => 'goal']));

        $response->assertOk();
        $response->assertSee('Laptop');
        $response->assertDontSee('S/ 10.00');
    }
}
