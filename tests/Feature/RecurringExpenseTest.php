<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use App\Notifications\ExpenseConfirmationNotification;
use App\Services\RecurringExpenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecurringExpenseTest extends TestCase
{
    use RefreshDatabase;

    private function createFixedExpenseTemplate(User $user, array $overrides = []): Expense
    {
        return Expense::create(array_merge([
            'user_id' => $user->id,
            'amount' => 80,
            'date' => now()->subMonth()->toDateString(),
            'description' => 'Alquiler',
            'type' => 'fijo',
            'frequency' => 'mensual',
            'next_occurrence_date' => now()->subDay()->toDateString(),
        ], $overrides));
    }

    public function test_creating_a_fixed_expense_computes_its_next_occurrence_date(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->post(route('expenses.store'), [
            'amount' => 50,
            'date' => now()->toDateString(),
            'description' => 'Internet',
            'type' => 'fijo',
            'frequency' => 'mensual',
        ])->assertRedirect();

        $expense = Expense::first();
        $this->assertSame('fijo', $expense->type);
        $this->assertSame(now()->addMonthNoOverflow()->toDateString(), $expense->next_occurrence_date->toDateString());
    }

    public function test_a_variable_expense_never_gets_a_frequency_or_next_occurrence_date(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->post(route('expenses.store'), [
            'amount' => 15,
            'date' => now()->toDateString(),
            'description' => 'Antojo',
            'type' => 'variable',
        ])->assertRedirect();

        $expense = Expense::first();
        $this->assertNull($expense->frequency);
        $this->assertNull($expense->next_occurrence_date);
    }

    public function test_a_fixed_expense_without_a_frequency_is_rejected(): void
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)->post(route('expenses.store'), [
            'amount' => 50,
            'date' => now()->toDateString(),
            'description' => 'Internet',
            'type' => 'fijo',
        ]);

        $response->assertSessionHasErrors('frequency');
        $this->assertSame(0, Expense::count());
    }

    public function test_a_due_fixed_expense_occurrence_generates_a_real_expense_and_notifies_the_student(): void
    {
        $student = User::factory()->create();
        $this->createFixedExpenseTemplate($student);

        $generated = (new RecurringExpenseService)->generateDueOccurrences();

        $this->assertSame(1, $generated);
        $this->assertSame(2, Expense::where('user_id', $student->id)->count());
        $this->assertSame(1, $student->notifications()->where('type', ExpenseConfirmationNotification::class)->count());
    }

    public function test_generating_due_occurrences_advances_the_templates_next_occurrence_date(): void
    {
        $student = User::factory()->create();
        $template = $this->createFixedExpenseTemplate($student, [
            'next_occurrence_date' => now()->subDay()->toDateString(),
        ]);

        (new RecurringExpenseService)->generateDueOccurrences();

        $template->refresh();
        $this->assertTrue($template->next_occurrence_date->isFuture());
    }
}
