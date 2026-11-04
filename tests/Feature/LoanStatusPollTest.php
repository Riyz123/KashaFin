<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanStatusPollTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_statuses_endpoint_reflects_paid_amount_and_status_as_soon_as_they_change(): void
    {
        $debtor = User::factory()->create();
        $creditor = User::factory()->create();
        $debtorLoan = Loan::create([
            'user_id' => $debtor->id, 'person_name' => 'Prestamista', 'person_user_id' => $creditor->id,
            'direction' => 'recibido', 'amount' => 20, 'date' => now(), 'status' => 'pendiente',
        ]);
        $creditorLoan = Loan::create([
            'user_id' => $creditor->id, 'person_name' => 'Deudor', 'person_user_id' => $debtor->id,
            'direction' => 'prestado', 'amount' => 20, 'date' => now(), 'status' => 'pendiente',
        ]);
        $debtorLoan->update(['counterpart_loan_id' => $creditorLoan->id]);
        $creditorLoan->update(['counterpart_loan_id' => $debtorLoan->id]);

        $amortization = $debtorLoan->fresh()->requestAmortization(20);
        $creditorLoan->fresh()->acceptAmortization($amortization);

        $response = $this->actingAs($debtor)->getJson(route('loans.statuses', ['ids' => (string) $debtorLoan->id]));

        $response->assertOk();
        $response->assertJson([
            'loans' => [(string) $debtorLoan->id => ['status' => 'pagado', 'paid_amount' => 20.0]],
        ]);
    }

    public function test_the_statuses_endpoint_counts_pending_amortization_requests_waiting_on_the_caller(): void
    {
        $debtor = User::factory()->create();
        $creditor = User::factory()->create();
        $debtorLoan = Loan::create([
            'user_id' => $debtor->id, 'person_name' => 'Prestamista', 'person_user_id' => $creditor->id,
            'direction' => 'recibido', 'amount' => 20, 'date' => now(), 'status' => 'pendiente',
        ]);
        $creditorLoan = Loan::create([
            'user_id' => $creditor->id, 'person_name' => 'Deudor', 'person_user_id' => $debtor->id,
            'direction' => 'prestado', 'amount' => 20, 'date' => now(), 'status' => 'pendiente',
        ]);
        $debtorLoan->update(['counterpart_loan_id' => $creditorLoan->id]);
        $creditorLoan->update(['counterpart_loan_id' => $debtorLoan->id]);

        $debtorLoan->fresh()->requestAmortization(10);

        $response = $this->actingAs($creditor)->getJson(route('loans.statuses', ['ids' => (string) $creditorLoan->id]));

        $response->assertOk();
        $response->assertJson(['amortization_requests_count' => 1]);
    }

    public function test_the_statuses_endpoint_never_returns_another_users_loan(): void
    {
        $student = User::factory()->create();
        $stranger = User::factory()->create();
        $strangerLoan = Loan::create([
            'user_id' => $stranger->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 20, 'date' => now(), 'status' => 'pendiente',
        ]);

        $response = $this->actingAs($student)->getJson(route('loans.statuses', ['ids' => (string) $strangerLoan->id]));

        $response->assertOk();
        $response->assertJsonMissingPath('loans.'.$strangerLoan->id);
    }
}
