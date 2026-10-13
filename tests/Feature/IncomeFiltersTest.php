<?php

namespace Tests\Feature;

use App\Models\Income;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncomeFiltersTest extends TestCase
{
    use RefreshDatabase;

    private function makeIncome(User $user, array $overrides = []): Income
    {
        return Income::create(array_merge([
            'user_id' => $user->id,
            'amount' => 10,
            'date' => now(),
            'description' => 'Ingreso',
            'type' => 'variable',
            'frequency' => null,
            'next_occurrence_date' => null,
        ], $overrides));
    }

    public function test_search_only_matches_the_description(): void
    {
        $student = User::factory()->create();
        $this->makeIncome($student, ['description' => 'Beca universitaria']);
        $this->makeIncome($student, ['description' => 'Propina']);

        $response = $this->actingAs($student)->get(route('incomes.index', ['search' => 'Beca']));

        $response->assertOk();
        $response->assertSee('Beca universitaria');
        $response->assertDontSee('Propina');
    }

    public function test_type_filter_only_shows_matching_incomes(): void
    {
        $student = User::factory()->create();
        $this->makeIncome($student, ['description' => 'Sueldo fijo', 'type' => 'fijo', 'frequency' => 'mensual']);
        $this->makeIncome($student, ['description' => 'Venta ocasional', 'type' => 'variable']);

        $response = $this->actingAs($student)->get(route('incomes.index', ['type' => 'variable']));

        $response->assertOk();
        $response->assertSee('Venta ocasional');
        $response->assertDontSee('Sueldo fijo');
    }

    public function test_amount_column_sorts_and_toggles_direction(): void
    {
        $student = User::factory()->create();
        $this->makeIncome($student, ['description' => 'Chico', 'amount' => 5]);
        $this->makeIncome($student, ['description' => 'Grande', 'amount' => 50]);

        $asc = $this->actingAs($student)->get(route('incomes.index', ['sort' => 'amount', 'dir' => 'asc']));
        $asc->assertSeeInOrder(['Chico', 'Grande']);

        $desc = $this->actingAs($student)->get(route('incomes.index', ['sort' => 'amount', 'dir' => 'desc']));
        $desc->assertSeeInOrder(['Grande', 'Chico']);
    }
}
