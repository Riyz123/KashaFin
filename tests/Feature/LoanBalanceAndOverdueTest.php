<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use App\Services\LiquidityProjectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanBalanceAndOverdueTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_received_loan_adds_to_the_current_balance(): void
    {
        $student = User::factory()->create();
        $before = (new LiquidityProjectionService)->currentBalance($student);

        Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 20, 'date' => now()->toDateString(), 'status' => 'pendiente',
        ]);

        $after = (new LiquidityProjectionService)->currentBalance($student);

        $this->assertEqualsWithDelta(20.0, $after - $before, 0.01);
    }

    public function test_a_loan_still_waiting_on_the_other_accounts_confirmation_does_not_count_yet(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $before = (new LiquidityProjectionService)->currentBalance($student);

        Loan::create([
            'user_id' => $student->id, 'person_name' => 'Ana', 'person_user_id' => $other->id,
            'direction' => 'recibido', 'amount' => 20, 'date' => now()->subDay()->toDateString(), 'status' => 'pendiente_confirmacion',
        ]);

        $after = (new LiquidityProjectionService)->currentBalance($student);

        $this->assertEqualsWithDelta(0.0, $after - $before, 0.01);
    }

    public function test_paying_off_a_loan_brings_the_balance_back_down_through_the_registered_expense(): void
    {
        $student = User::factory()->create();
        $before = (new LiquidityProjectionService)->currentBalance($student);

        $loan = Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 20, 'date' => now()->toDateString(), 'status' => 'pendiente',
        ]);

        $loan->requestAmortization(20);

        $after = (new LiquidityProjectionService)->currentBalance($student);

        // +20 from the loan, -20 from the expense it generated: net zero.
        $this->assertEqualsWithDelta(0.0, $after - $before, 0.01);
        $this->assertSame('pagado', $loan->fresh()->status);
    }

    public function test_a_received_loan_past_its_due_date_and_still_owed_is_overdue(): void
    {
        $student = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 20, 'date' => now()->subDays(10), 'due_date' => now()->subDay(), 'status' => 'pendiente',
        ]);

        $this->assertTrue($loan->isOverdue());
    }

    public function test_a_loan_already_fully_paid_is_never_overdue_even_past_its_due_date(): void
    {
        $student = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 20, 'paid_amount' => 20, 'date' => now()->subDays(10), 'due_date' => now()->subDay(), 'status' => 'pagado',
        ]);

        $this->assertFalse($loan->isOverdue());
    }

    public function test_an_overdue_debt_forces_the_liquidity_projection_below_threshold(): void
    {
        $student = User::factory()->create();
        Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 20, 'date' => now()->subDays(10), 'due_date' => now()->subDay(), 'status' => 'pendiente',
        ]);

        $this->assertTrue((new LiquidityProjectionService)->isBelowThreshold($student));
    }
}
