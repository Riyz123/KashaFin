<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LoanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_can_register_a_loan_they_gave(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->post(route('loans.store'), [
            'person_name' => 'Ana',
            'direction' => 'prestado',
            'amount' => 50,
            'date' => now()->toDateString(),
        ])->assertRedirect(route('loans.index'));

        $loan = Loan::first();
        $this->assertSame('Ana', $loan->person_name);
        $this->assertSame('prestado', $loan->direction);
        $this->assertSame('pendiente', $loan->status);
    }

    public function test_marking_a_loan_as_paid_updates_its_status(): void
    {
        $student = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $student->id, 'person_name' => 'Luis', 'direction' => 'recibido',
            'amount' => 30, 'date' => now(), 'status' => 'pendiente',
        ]);

        $this->actingAs($student)->patch(route('loans.mark-paid', $loan))->assertRedirect();

        $this->assertSame('pagado', $loan->fresh()->status);
    }

    public function test_a_student_cannot_modify_another_students_loan(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $owner->id, 'person_name' => 'Luis', 'direction' => 'recibido',
            'amount' => 30, 'date' => now(), 'status' => 'pendiente',
        ]);

        $this->actingAs($other)->patch(route('loans.mark-paid', $loan))->assertForbidden();
    }

    public function test_the_index_page_totals_pending_amounts_by_direction(): void
    {
        $student = User::factory()->create();
        Loan::create(['user_id' => $student->id, 'person_name' => 'Ana', 'direction' => 'prestado', 'amount' => 50, 'date' => now(), 'status' => 'pendiente']);
        Loan::create(['user_id' => $student->id, 'person_name' => 'Luis', 'direction' => 'recibido', 'amount' => 30, 'date' => now(), 'status' => 'pendiente']);
        Loan::create(['user_id' => $student->id, 'person_name' => 'Pepe', 'direction' => 'prestado', 'amount' => 999, 'date' => now(), 'status' => 'pagado']);

        $response = $this->actingAs($student)->get(route('loans.index'));

        $response->assertOk();
        $this->assertSame(50.0, $response->viewData('totals')['owed_to_me']);
        $this->assertSame(30.0, $response->viewData('totals')['i_owe']);
    }

    public function test_a_loan_can_be_linked_to_a_real_institutional_account(): void
    {
        $student = User::factory()->create();
        $ana = User::factory()->create(['name' => 'Ana Torres']);

        $this->actingAs($student)->post(route('loans.store'), [
            'person_name' => 'Ana Torres',
            'person_user_id' => $ana->id,
            'direction' => 'prestado',
            'amount' => 50,
            'date' => now()->toDateString(),
        ])->assertRedirect();

        $loan = Loan::first();
        $this->assertSame($ana->id, $loan->person_user_id);
    }

    public function test_a_loan_can_also_be_registered_for_an_external_person_without_an_account(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->post(route('loans.store'), [
            'person_name' => 'Tienda de la esquina',
            'direction' => 'recibido',
            'amount' => 15,
            'date' => now()->toDateString(),
        ])->assertRedirect();

        $loan = Loan::first();
        $this->assertSame('Tienda de la esquina', $loan->person_name);
        $this->assertNull($loan->person_user_id);
    }

    public function test_a_loan_cannot_be_edited_only_created_or_deleted(): void
    {
        $this->assertFalse(Route::has('loans.edit'));
        $this->assertFalse(Route::has('loans.update'));
        $this->assertTrue(Route::has('loans.store'));
        $this->assertTrue(Route::has('loans.destroy'));
    }
}
