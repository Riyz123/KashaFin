<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Loan;
use App\Models\SharedExpense;
use App\Models\SharedExpenseParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepting_all_shared_expense_invitations_accepts_only_this_users_pending_ones(): void
    {
        $student = User::factory()->create();
        $other = User::factory()->create();
        $creator = User::factory()->create();

        $shared1 = SharedExpense::create(['user_id' => $creator->id, 'description' => 'A', 'total_amount' => 20, 'my_share' => 10, 'date' => now()]);
        $shared2 = SharedExpense::create(['user_id' => $creator->id, 'description' => 'B', 'total_amount' => 20, 'my_share' => 10, 'date' => now()]);
        $mine1 = $shared1->participants()->create(['user_id' => $student->id, 'name' => $student->name, 'amount_owed' => 10, 'status' => 'pendiente']);
        $mine2 = $shared2->participants()->create(['user_id' => $student->id, 'name' => $student->name, 'amount_owed' => 10, 'status' => 'pendiente']);
        $notMine = $shared1->participants()->create(['user_id' => $other->id, 'name' => $other->name, 'amount_owed' => 10, 'status' => 'pendiente']);

        $this->actingAs($student)->patch(route('shared-expenses.accept-all'))->assertRedirect();

        $this->assertSame('aceptado', $mine1->fresh()->status);
        $this->assertSame('aceptado', $mine2->fresh()->status);
        $this->assertSame('pendiente', $notMine->fresh()->status);
    }

    public function test_approving_all_loan_confirmations_only_affects_the_invited_account(): void
    {
        $lender1 = User::factory()->create();
        $lender2 = User::factory()->create();
        $borrower = User::factory()->create();
        $stranger = User::factory()->create();

        $loan1 = Loan::create(['user_id' => $lender1->id, 'person_name' => 'B', 'person_user_id' => $borrower->id, 'direction' => 'prestado', 'amount' => 50, 'date' => now(), 'status' => 'pendiente_confirmacion']);
        $loan2 = Loan::create(['user_id' => $lender2->id, 'person_name' => 'B', 'person_user_id' => $borrower->id, 'direction' => 'prestado', 'amount' => 30, 'date' => now(), 'status' => 'pendiente_confirmacion']);
        $notMine = Loan::create(['user_id' => $lender1->id, 'person_name' => 'S', 'person_user_id' => $stranger->id, 'direction' => 'prestado', 'amount' => 10, 'date' => now(), 'status' => 'pendiente_confirmacion']);

        $this->actingAs($borrower)->patch(route('loans.approve-all'))->assertRedirect();

        $this->assertSame('pendiente', $loan1->fresh()->status);
        $this->assertSame('pendiente', $loan2->fresh()->status);
        $this->assertSame('pendiente_confirmacion', $notMine->fresh()->status);
    }

    public function test_confirming_all_payments_only_affects_prestado_direction_for_this_account(): void
    {
        $lender = User::factory()->create();
        $borrower1 = User::factory()->create();
        $borrower2 = User::factory()->create();

        $creditorLoan1 = Loan::create(['user_id' => $lender->id, 'person_name' => 'D1', 'person_user_id' => $borrower1->id, 'direction' => 'prestado', 'amount' => 50, 'date' => now(), 'status' => 'pago_pendiente_confirmacion']);
        $debtorLoan1 = Loan::create(['user_id' => $borrower1->id, 'person_name' => 'L', 'person_user_id' => $lender->id, 'direction' => 'recibido', 'amount' => 50, 'date' => now(), 'status' => 'pago_pendiente_confirmacion']);
        $creditorLoan1->update(['counterpart_loan_id' => $debtorLoan1->id]);
        $debtorLoan1->update(['counterpart_loan_id' => $creditorLoan1->id]);

        $creditorLoan2 = Loan::create(['user_id' => $lender->id, 'person_name' => 'D2', 'person_user_id' => $borrower2->id, 'direction' => 'prestado', 'amount' => 20, 'date' => now(), 'status' => 'pago_pendiente_confirmacion']);

        $this->actingAs($lender)->patch(route('loans.confirm-all-payments'))->assertRedirect();

        $this->assertSame('pagado', $creditorLoan1->fresh()->status);
        $this->assertSame('pagado', $debtorLoan1->fresh()->status);
        $this->assertSame('pagado', $creditorLoan2->fresh()->status);
    }

    public function test_dean_approving_all_pending_only_affects_their_own_faculty(): void
    {
        $facultyA = Faculty::factory()->create();
        $facultyB = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $facultyA->id]);

        $pendingInA1 = User::factory()->create(['role' => 'estudiante', 'faculty_id' => $facultyA->id, 'approved_at' => null]);
        $pendingInA2 = User::factory()->create(['role' => 'estudiante', 'faculty_id' => $facultyA->id, 'approved_at' => null]);
        $pendingInB = User::factory()->create(['role' => 'estudiante', 'faculty_id' => $facultyB->id, 'approved_at' => null]);

        $this->actingAs($dean)->patch(route('admin.users.approve-all'))->assertRedirect();

        $this->assertNotNull($pendingInA1->fresh()->approved_at);
        $this->assertNotNull($pendingInA2->fresh()->approved_at);
        $this->assertNull($pendingInB->fresh()->approved_at);
    }

    public function test_approving_all_with_nothing_pending_does_not_error(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->patch(route('admin.users.approve-all'))->assertRedirect();
    }
}
