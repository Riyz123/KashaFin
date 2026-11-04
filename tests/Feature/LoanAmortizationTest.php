<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Loan;
use App\Models\LoanAmortization;
use App\Models\User;
use App\Notifications\LoanAmortizationRequestedNotification;
use App\Notifications\LoanAmortizationResolvedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoanAmortizationTest extends TestCase
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

    public function test_an_unlinked_loan_amortization_applies_immediately_and_registers_an_expense(): void
    {
        $student = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 20, 'date' => now(), 'status' => 'pendiente',
        ]);

        $this->actingAs($student)->post(route('loans.amortizations.store', $loan), ['amount' => 10])
            ->assertRedirect();

        $loan->refresh();
        $this->assertEqualsWithDelta(10.0, (float) $loan->paid_amount, 0.01);
        $this->assertEqualsWithDelta(10.0, $loan->remaining_amount, 0.01);
        $this->assertSame('pendiente', $loan->status);

        $expense = Expense::where('user_id', $student->id)->first();
        $this->assertNotNull($expense);
        $this->assertEqualsWithDelta(10.0, (float) $expense->amount, 0.01);
    }

    public function test_a_linked_loan_amortization_requires_the_creditors_confirmation(): void
    {
        Notification::fake();

        $debtor = User::factory()->create();
        $creditor = User::factory()->create();
        [$debtorLoan] = $this->makeLinkedPair($debtor, $creditor);

        $this->actingAs($debtor)->post(route('loans.amortizations.store', $debtorLoan), ['amount' => 10])
            ->assertRedirect();

        $this->assertSame(0, (int) $debtorLoan->fresh()->paid_amount);
        $this->assertSame(1, LoanAmortization::where('loan_id', $debtorLoan->id)->where('status', 'pendiente')->count());
        Notification::assertSentTo($creditor, LoanAmortizationRequestedNotification::class);
    }

    public function test_accepting_an_amortization_reduces_the_remaining_debt_and_registers_an_expense_for_the_debtor(): void
    {
        Notification::fake();

        $debtor = User::factory()->create();
        $creditor = User::factory()->create();
        [$debtorLoan, $creditorLoan] = $this->makeLinkedPair($debtor, $creditor);

        $amortization = $debtorLoan->requestAmortization(10);

        $this->actingAs($creditor)->patch(route('loans.amortizations.accept', $amortization))->assertRedirect();

        $debtorLoan->refresh();
        $creditorLoan->refresh();
        $this->assertEqualsWithDelta(10.0, (float) $debtorLoan->paid_amount, 0.01);
        $this->assertEqualsWithDelta(10.0, (float) $creditorLoan->paid_amount, 0.01);
        $this->assertSame('pendiente', $debtorLoan->status);

        $expense = Expense::where('user_id', $debtor->id)->first();
        $this->assertNotNull($expense);
        $this->assertEqualsWithDelta(10.0, (float) $expense->amount, 0.01);
        Notification::assertSentTo($debtor, LoanAmortizationResolvedNotification::class);
    }

    public function test_accepting_the_final_amortization_marks_the_loan_as_fully_paid(): void
    {
        $debtor = User::factory()->create();
        $creditor = User::factory()->create();
        [$debtorLoan, $creditorLoan] = $this->makeLinkedPair($debtor, $creditor, 20);

        $first = $debtorLoan->requestAmortization(10);
        $creditorLoan->acceptAmortization($first);

        $second = $debtorLoan->fresh()->requestAmortization(10);
        $creditorLoan->fresh()->acceptAmortization($second);

        $this->assertSame('pagado', $debtorLoan->fresh()->status);
        $this->assertSame('pagado', $creditorLoan->fresh()->status);
        $this->assertEqualsWithDelta(0.0, $debtorLoan->fresh()->remaining_amount, 0.01);
        $this->assertSame(2, Expense::where('user_id', $debtor->id)->count());
    }

    public function test_rejecting_an_amortization_leaves_the_debt_unchanged(): void
    {
        $debtor = User::factory()->create();
        $creditor = User::factory()->create();
        [$debtorLoan, $creditorLoan] = $this->makeLinkedPair($debtor, $creditor);

        $amortization = $debtorLoan->requestAmortization(10);

        $this->actingAs($creditor)->patch(route('loans.amortizations.reject', $amortization))->assertRedirect();

        $this->assertSame('rechazado', $amortization->fresh()->status);
        $this->assertSame(0, (int) $debtorLoan->fresh()->paid_amount);
        $this->assertSame(0, Expense::where('user_id', $debtor->id)->count());
    }

    public function test_cannot_amortize_more_than_the_remaining_debt(): void
    {
        $student = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 20, 'date' => now(), 'status' => 'pendiente',
        ]);

        $response = $this->actingAs($student)->post(route('loans.amortizations.store', $loan), ['amount' => 50]);

        $response->assertSessionHasErrors('amount');
        $this->assertSame(0, (int) $loan->fresh()->paid_amount);
    }

    public function test_an_already_resolved_amortization_cannot_be_accepted_again(): void
    {
        $debtor = User::factory()->create();
        $creditor = User::factory()->create();
        [$debtorLoan, $creditorLoan] = $this->makeLinkedPair($debtor, $creditor);

        $amortization = $debtorLoan->requestAmortization(10);
        $this->actingAs($creditor)->patch(route('loans.amortizations.accept', $amortization))->assertRedirect();

        // Simulates a second accept racing in right behind the first one —
        // by the time it runs, the amortization is no longer pending, so it
        // must be rejected instead of applying the payment twice.
        $this->actingAs($creditor)->patch(route('loans.amortizations.accept', $amortization))
            ->assertStatus(422);

        $this->assertEqualsWithDelta(10.0, (float) $debtorLoan->fresh()->paid_amount, 0.01);
        $this->assertEqualsWithDelta(10.0, (float) $creditorLoan->fresh()->paid_amount, 0.01);
        $this->assertSame(1, Expense::where('user_id', $debtor->id)->count());
    }

    public function test_an_accepted_amortizations_traceability_survives_even_if_the_loan_is_later_deleted(): void
    {
        $debtor = User::factory()->create();
        $creditor = User::factory()->create();
        [$debtorLoan, $creditorLoan] = $this->makeLinkedPair($debtor, $creditor);

        $amortization = $debtorLoan->requestAmortization(10);
        $creditorLoan->acceptAmortization($amortization);

        // The debtor deletes their own loan row afterward (cascades and
        // removes the loan_amortizations row with it) — the creditor's own
        // paid_amount was already committed independently, and the fact
        // that this payment happened must still be readable in the
        // activity log, which has no foreign key back to the loan.
        $debtorLoan->fresh()->delete();

        $this->assertNull(\App\Models\LoanAmortization::find($amortization->id));
        $this->assertEqualsWithDelta(10.0, (float) $creditorLoan->fresh()->paid_amount, 0.01);

        $log = \Spatie\Activitylog\Models\Activity::where('log_name', 'loan_amortization')
            ->where('subject_id', $amortization->id)
            ->where('description', 'like', '%aceptó el pago%')
            ->first();

        $this->assertNotNull($log, 'the accepted amortization must leave a trace even after the loan is deleted');
    }

    public function test_only_the_creditor_can_accept_or_reject_an_amortization(): void
    {
        $debtor = User::factory()->create();
        $creditor = User::factory()->create();
        $stranger = User::factory()->create();
        [$debtorLoan] = $this->makeLinkedPair($debtor, $creditor);

        $amortization = $debtorLoan->requestAmortization(10);

        $this->actingAs($stranger)->patch(route('loans.amortizations.accept', $amortization))->assertForbidden();
        $this->actingAs($debtor)->patch(route('loans.amortizations.accept', $amortization))->assertForbidden();
    }
}
