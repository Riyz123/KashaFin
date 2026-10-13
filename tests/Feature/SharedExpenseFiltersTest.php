<?php

namespace Tests\Feature;

use App\Models\SharedExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedExpenseFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_only_matches_the_description(): void
    {
        $student = User::factory()->create();

        SharedExpense::create([
            'user_id' => $student->id, 'description' => 'Cena de cumpleaños',
            'total_amount' => 100, 'my_share' => 50, 'date' => now(),
        ]);
        SharedExpense::create([
            'user_id' => $student->id, 'description' => 'Taxi al aeropuerto',
            'total_amount' => 40, 'my_share' => 20, 'date' => now(),
        ]);

        $response = $this->actingAs($student)->get(route('shared-expenses.index', ['search' => 'Cena']));

        $response->assertOk();
        $response->assertSee('Cena de cumpleaños');
        $response->assertDontSee('Taxi al aeropuerto');
    }
}
