<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_only_matches_the_person_name(): void
    {
        $student = User::factory()->create();
        Loan::create(['user_id' => $student->id, 'person_name' => 'Ana Torres', 'direction' => 'prestado', 'amount' => 50, 'date' => now(), 'status' => 'pendiente']);
        Loan::create(['user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido', 'amount' => 15, 'date' => now(), 'status' => 'pendiente']);

        $response = $this->actingAs($student)->get(route('loans.index', ['search' => 'Ana']));

        $response->assertOk();
        $response->assertSee('Ana Torres');
        $response->assertDontSee('Tienda');
    }

    public function test_direction_and_status_filters_narrow_the_list(): void
    {
        $student = User::factory()->create();
        Loan::create(['user_id' => $student->id, 'person_name' => 'Ana', 'direction' => 'prestado', 'amount' => 50, 'date' => now(), 'status' => 'pendiente']);
        Loan::create(['user_id' => $student->id, 'person_name' => 'Luis', 'direction' => 'recibido', 'amount' => 30, 'date' => now(), 'status' => 'pagado']);

        $response = $this->actingAs($student)->get(route('loans.index', ['direction' => 'prestado']));
        $response->assertSee('Ana');
        $response->assertDontSee('Luis');

        $response = $this->actingAs($student)->get(route('loans.index', ['status' => 'pagado']));
        $response->assertSee('Luis');
        $response->assertDontSee('Ana');
    }

    public function test_pending_confirmations_from_other_accounts_appear_in_their_own_section(): void
    {
        $lender = User::factory()->create(['name' => 'Prestamista']);
        $borrower = User::factory()->create();

        Loan::create([
            'user_id' => $lender->id, 'person_name' => 'Borrower', 'person_user_id' => $borrower->id,
            'direction' => 'prestado', 'amount' => 50, 'date' => now(), 'status' => 'pendiente_confirmacion',
        ]);

        $response = $this->actingAs($borrower)->get(route('loans.index'));

        $response->assertOk();
        $response->assertSee('Confirmaciones pendientes');
        $response->assertSee('Prestamista');
    }
}
