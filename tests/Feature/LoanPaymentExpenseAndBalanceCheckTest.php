<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Paying off a debt — whether through the one-click "marcar como pagado"
 * button or a stepped amortization — must always register a labeled expense
 * (so the balance actually comes back down, see LoanBalanceAndOverdueTest),
 * and must never let a student pay back more than their current balance
 * actually has.
 */
class LoanPaymentExpenseAndBalanceCheckTest extends TestCase
{
    use RefreshDatabase;

    private function makeLinkedPair(User $debtor, User $creditor, float $amount = 20): array
    {
        $debtorLoan = Loan::create([
            'user_id' => $debtor->id, 'person_name' => 'Prestamista', 'person_user_id' => $creditor->id,
            'direction' => 'recibido', 'amount' => $amount, 'date' => now(), 'status' => 'pendiente',
        ]);
        $creditorLoan = Loan::create([
            'user_id' => $creditor->id, 'person_name' => 'Deudor', 'person_user_id' => $debtor->id,
            'direction' => 'prestado', 'amount' => $amount, 'date' => now(), 'status' => 'pendiente',
        ]);
        $debtorLoan->update(['counterpart_loan_id' => $creditorLoan->id]);
        $creditorLoan->update(['counterpart_loan_id' => $debtorLoan->id]);

        return [$debtorLoan->fresh(), $creditorLoan->fresh()];
    }

    public function test_marking_an_unlinked_loan_as_paid_registers_a_labeled_expense(): void
    {
        $student = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 15, 'date' => now(), 'status' => 'pendiente',
        ]);

        $this->actingAs($student)->patch(route('loans.mark-paid', $loan))->assertRedirect();

        $this->assertSame('pagado', $loan->fresh()->status);

        $expense = Expense::where('user_id', $student->id)->first();
        $this->assertNotNull($expense);
        $this->assertEqualsWithDelta(15.0, (float) $expense->amount, 0.01);
        $this->assertSame('Amortización de deuda a Tienda', $expense->description);
    }

    public function test_marking_as_paid_only_expenses_whatever_was_not_already_amortized(): void
    {
        $student = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 20, 'date' => now(), 'status' => 'pendiente',
        ]);

        $this->actingAs($student)->post(route('loans.amortizations.store', $loan), ['amount' => 12]);
        $this->actingAs($student)->patch(route('loans.mark-paid', $loan))->assertRedirect();

        $this->assertSame('pagado', $loan->fresh()->status);
        $this->assertEqualsWithDelta(20.0, (float) $loan->fresh()->paid_amount, 0.01);
        $this->assertEqualsWithDelta(8.0, Expense::where('user_id', $student->id)->latest('id')->first()->amount, 0.01);
    }

    public function test_confirming_a_linked_loans_full_payment_expenses_the_debtor_not_the_creditor(): void
    {
        $debtor = User::factory()->create();
        $creditor = User::factory()->create();
        [$debtorLoan, $creditorLoan] = $this->makeLinkedPair($debtor, $creditor, 25);

        $this->actingAs($debtor)->patch(route('loans.mark-paid', $debtorLoan));
        $this->actingAs($creditor)->patch(route('loans.confirm-payment', $creditorLoan))->assertRedirect();

        $this->assertSame('pagado', $debtorLoan->fresh()->status);
        $this->assertSame('pagado', $creditorLoan->fresh()->status);

        $this->assertSame(1, Expense::where('user_id', $debtor->id)->count());
        $this->assertSame(0, Expense::where('user_id', $creditor->id)->count());
    }

    public function test_marking_as_paid_is_blocked_when_the_current_balance_is_not_enough(): void
    {
        $student = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 50, 'date' => now(), 'status' => 'pendiente',
        ]);
        // Already spent the borrowed money — only 10 of the 50 is actually left.
        Expense::create(['user_id' => $student->id, 'amount' => 40, 'date' => now(), 'description' => 'Gastado ya']);

        $response = $this->actingAs($student)->patch(route('loans.mark-paid', $loan));

        $response->assertStatus(422);
        $this->assertSame('pendiente', $loan->fresh()->status);
        $this->assertEqualsWithDelta(0.0, (float) $loan->fresh()->paid_amount, 0.01);
    }

    public function test_an_unlinked_amortization_is_blocked_when_the_current_balance_is_not_enough(): void
    {
        $student = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 50, 'date' => now(), 'status' => 'pendiente',
        ]);
        Expense::create(['user_id' => $student->id, 'amount' => 45, 'date' => now(), 'description' => 'Gastado ya']);

        $response = $this->actingAs($student)->post(route('loans.amortizations.store', $loan), ['amount' => 10]);

        $response->assertStatus(422);
        $this->assertEqualsWithDelta(0.0, (float) $loan->fresh()->paid_amount, 0.01);
    }

    public function test_accepting_a_linked_amortization_is_blocked_when_the_debtors_balance_is_not_enough(): void
    {
        $debtor = User::factory()->create();
        $creditor = User::factory()->create();
        [$debtorLoan, $creditorLoan] = $this->makeLinkedPair($debtor, $creditor, 50);

        Expense::create(['user_id' => $debtor->id, 'amount' => 45, 'date' => now(), 'description' => 'Gastado ya']);

        $this->actingAs($debtor)->post(route('loans.amortizations.store', $debtorLoan), ['amount' => 10]);
        $amortization = $debtorLoan->amortizations()->first();

        $response = $this->actingAs($creditor)->patch(route('loans.amortizations.accept', $amortization));

        $response->assertStatus(422);
        $this->assertEqualsWithDelta(0.0, (float) $debtorLoan->fresh()->paid_amount, 0.01);
    }
}
