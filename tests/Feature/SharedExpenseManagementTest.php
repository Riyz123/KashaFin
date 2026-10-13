<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\SharedExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_shared_expense_only_registers_my_share_as_a_real_expense(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->post(route('shared-expenses.store'), [
            'description' => 'Pizza para el grupo',
            'total_amount' => 60,
            'my_share' => 20,
            'date' => now()->toDateString(),
            'participants' => [
                ['name' => 'Ana', 'amount_owed' => 20],
                ['name' => 'Luis', 'amount_owed' => 20],
            ],
        ])->assertRedirect(route('shared-expenses.index'));

        $sharedExpense = SharedExpense::first();
        $this->assertNotNull($sharedExpense);
        $this->assertSame(2, $sharedExpense->participants()->count());
        $this->assertSame(1, Expense::where('user_id', $student->id)->count());
        $this->assertSame('20.00', Expense::first()->amount);
    }

    public function test_the_totals_must_add_up_to_the_full_amount(): void
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)->post(route('shared-expenses.store'), [
            'description' => 'Pizza',
            'total_amount' => 60,
            'my_share' => 20,
            'date' => now()->toDateString(),
            'participants' => [
                ['name' => 'Ana', 'amount_owed' => 10],
            ],
        ]);

        $response->assertSessionHasErrors('total_amount');
        $this->assertSame(0, SharedExpense::count());
    }

    public function test_toggling_a_participant_marks_them_as_paid(): void
    {
        $student = User::factory()->create();
        $shared = SharedExpense::create([
            'user_id' => $student->id, 'description' => 'Uber', 'total_amount' => 20, 'my_share' => 10, 'date' => now(),
        ]);
        $participant = $shared->participants()->create(['name' => 'Ana', 'amount_owed' => 10]);

        $this->actingAs($student)->patch(route('shared-expenses.toggle-paid', $participant))->assertRedirect();

        $this->assertTrue($participant->fresh()->is_paid);
    }

    public function test_deleting_a_shared_expense_also_deletes_the_linked_personal_expense(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->post(route('shared-expenses.store'), [
            'description' => 'Cena',
            'total_amount' => 40,
            'my_share' => 20,
            'date' => now()->toDateString(),
            'participants' => [['name' => 'Ana', 'amount_owed' => 20]],
        ]);

        $shared = SharedExpense::first();
        $this->actingAs($student)->delete(route('shared-expenses.destroy', $shared))->assertRedirect();

        $this->assertSame(0, SharedExpense::count());
        $this->assertSame(0, Expense::count());
    }
}
