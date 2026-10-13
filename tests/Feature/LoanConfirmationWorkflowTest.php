<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoanConfirmationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_loan_linked_to_a_real_account_starts_as_pending_confirmation_and_notifies_the_other_user(): void
    {
        Notification::fake();

        $lender = User::factory()->create();
        $borrower = User::factory()->create(['name' => 'Ana Torres']);

        $this->actingAs($lender)->post(route('loans.store'), [
            'person_name' => 'Ana Torres',
            'person_user_id' => $borrower->id,
            'direction' => 'prestado',
            'amount' => 50,
            'date' => now()->toDateString(),
        ])->assertRedirect();

        $loan = Loan::first();
        $this->assertSame('pendiente_confirmacion', $loan->status);

        Notification::assertSentTo($borrower, \App\Notifications\LoanConfirmationNotification::class);
    }

    public function test_approving_creates_a_mirrored_loan_on_the_other_account(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create(['name' => 'Ana Torres']);

        $loan = Loan::create([
            'user_id' => $lender->id, 'person_name' => 'Ana Torres', 'person_user_id' => $borrower->id,
            'direction' => 'prestado', 'amount' => 50, 'date' => now(), 'status' => 'pendiente_confirmacion',
        ]);

        $this->actingAs($borrower)->patch(route('loans.approve', $loan))->assertRedirect();

        $loan->refresh();
        $this->assertSame('pendiente', $loan->status);
        $this->assertNotNull($loan->counterpart_loan_id);

        $mirror = Loan::find($loan->counterpart_loan_id);
        $this->assertSame($borrower->id, $mirror->user_id);
        $this->assertSame($lender->id, $mirror->person_user_id);
        $this->assertSame('recibido', $mirror->direction);
        $this->assertSame('pendiente', $mirror->status);
        $this->assertEqualsWithDelta(50.0, (float) $mirror->amount, 0.01);
        $this->assertSame($loan->id, $mirror->counterpart_loan_id);
    }

    public function test_only_the_invited_account_can_approve_or_reject(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $stranger = User::factory()->create();

        $loan = Loan::create([
            'user_id' => $lender->id, 'person_name' => 'Ana', 'person_user_id' => $borrower->id,
            'direction' => 'prestado', 'amount' => 50, 'date' => now(), 'status' => 'pendiente_confirmacion',
        ]);

        $this->actingAs($stranger)->patch(route('loans.approve', $loan))->assertForbidden();
        $this->actingAs($lender)->patch(route('loans.approve', $loan))->assertForbidden();
    }

    public function test_rejecting_keeps_the_row_for_traceability_without_creating_a_mirror(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();

        $loan = Loan::create([
            'user_id' => $lender->id, 'person_name' => 'Ana', 'person_user_id' => $borrower->id,
            'direction' => 'prestado', 'amount' => 50, 'date' => now(), 'status' => 'pendiente_confirmacion',
        ]);

        $this->actingAs($borrower)->patch(route('loans.reject', $loan))->assertRedirect();

        $loan->refresh();
        $this->assertSame('rechazado', $loan->status);
        $this->assertNull($loan->counterpart_loan_id);
        $this->assertSame(1, Loan::count());
    }

    public function test_reporting_payment_on_a_linked_loan_requires_the_creditors_confirmation(): void
    {
        Notification::fake();

        $lender = User::factory()->create();
        $borrower = User::factory()->create();

        $debtorLoan = Loan::create([
            'user_id' => $borrower->id, 'person_name' => 'Prestamista', 'person_user_id' => $lender->id,
            'direction' => 'recibido', 'amount' => 50, 'date' => now(), 'status' => 'pendiente',
        ]);
        $creditorLoan = Loan::create([
            'user_id' => $lender->id, 'person_name' => 'Deudor', 'person_user_id' => $borrower->id,
            'direction' => 'prestado', 'amount' => 50, 'date' => now(), 'status' => 'pendiente',
        ]);
        $debtorLoan->update(['counterpart_loan_id' => $creditorLoan->id]);
        $creditorLoan->update(['counterpart_loan_id' => $debtorLoan->id]);

        $this->actingAs($borrower)->patch(route('loans.mark-paid', $debtorLoan))->assertRedirect();

        $this->assertSame('pago_pendiente_confirmacion', $debtorLoan->fresh()->status);
        $this->assertSame('pago_pendiente_confirmacion', $creditorLoan->fresh()->status);

        $this->actingAs($lender)->patch(route('loans.confirm-payment', $creditorLoan))->assertRedirect();

        $this->assertSame('pagado', $debtorLoan->fresh()->status);
        $this->assertSame('pagado', $creditorLoan->fresh()->status);
    }

    public function test_the_creditor_can_reject_a_payment_confirmation_and_it_goes_back_to_pending(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();

        $debtorLoan = Loan::create([
            'user_id' => $borrower->id, 'person_name' => 'Prestamista', 'person_user_id' => $lender->id,
            'direction' => 'recibido', 'amount' => 50, 'date' => now(), 'status' => 'pago_pendiente_confirmacion',
        ]);
        $creditorLoan = Loan::create([
            'user_id' => $lender->id, 'person_name' => 'Deudor', 'person_user_id' => $borrower->id,
            'direction' => 'prestado', 'amount' => 50, 'date' => now(), 'status' => 'pago_pendiente_confirmacion',
        ]);
        $debtorLoan->update(['counterpart_loan_id' => $creditorLoan->id]);
        $creditorLoan->update(['counterpart_loan_id' => $debtorLoan->id]);

        $this->actingAs($lender)->patch(route('loans.reject-payment', $creditorLoan))->assertRedirect();

        $this->assertSame('pendiente', $debtorLoan->fresh()->status);
        $this->assertSame('pendiente', $creditorLoan->fresh()->status);
    }

    public function test_an_unlinked_external_loan_is_marked_paid_immediately_without_any_confirmation(): void
    {
        $student = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 15, 'date' => now(), 'status' => 'pendiente',
        ]);

        $this->actingAs($student)->patch(route('loans.mark-paid', $loan))->assertRedirect();

        $this->assertSame('pagado', $loan->fresh()->status);
    }
}
