<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Income;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_can_export_their_complete_history_as_csv(): void
    {
        $student = User::factory()->create();
        Income::create(['user_id' => $student->id, 'amount' => 100, 'date' => now()->subDays(40), 'description' => 'Beca', 'type' => 'variable']);
        Expense::create(['user_id' => $student->id, 'amount' => 20, 'date' => now()->subDays(40), 'description' => 'Almuerzo']);

        $response = $this->actingAs($student)->get(route('history.export'));

        $response->assertOk();
        $content = $response->streamedContent();
        $this->assertStringContainsString('Beca', $content);
        $this->assertStringContainsString('Almuerzo', $content);
    }

    public function test_clearing_history_deletes_movements_but_keeps_savings_goals(): void
    {
        $student = User::factory()->create();
        Income::create(['user_id' => $student->id, 'amount' => 100, 'date' => now(), 'description' => 'Beca', 'type' => 'variable']);
        Expense::create(['user_id' => $student->id, 'amount' => 20, 'date' => now(), 'description' => 'Almuerzo']);
        $goal = SavingsGoal::create(['user_id' => $student->id, 'name' => 'Laptop', 'target_amount' => 1000, 'current_amount' => 200, 'status' => 'active']);

        $this->actingAs($student)->delete(route('history.clear'))->assertRedirect(route('history.index'));

        $this->assertSame(0, $student->incomes()->count());
        $this->assertSame(0, $student->expenses()->count());
        $this->assertNotNull($goal->fresh());
        $this->assertSame('200.00', $goal->fresh()->current_amount);
    }
}
