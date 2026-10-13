<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_only_matches_the_description_not_the_category_name(): void
    {
        $student = User::factory()->create();
        $food = Category::create(['user_id' => $student->id, 'name' => 'Comida', 'is_active' => true]);

        Expense::create(['user_id' => $student->id, 'category_id' => $food->id, 'amount' => 10, 'date' => now(), 'description' => 'Almuerzo']);
        Expense::create(['user_id' => $student->id, 'category_id' => null, 'amount' => 20, 'date' => now(), 'description' => 'Cuaderno']);

        $response = $this->actingAs($student)->get(route('expenses.index', ['search' => 'Comida']));

        $response->assertOk();
        $response->assertDontSee('Almuerzo');
        $response->assertDontSee('Cuaderno');
    }

    public function test_category_column_sorts_alphabetically_and_toggles_direction(): void
    {
        $student = User::factory()->create();
        $zoo = Category::create(['user_id' => $student->id, 'name' => 'Zoológico', 'is_active' => true]);
        $bus = Category::create(['user_id' => $student->id, 'name' => 'Bus', 'is_active' => true]);

        Expense::create(['user_id' => $student->id, 'category_id' => $zoo->id, 'amount' => 10, 'date' => now(), 'description' => 'Entrada zoo']);
        Expense::create(['user_id' => $student->id, 'category_id' => $bus->id, 'amount' => 5, 'date' => now(), 'description' => 'Pasaje']);

        $asc = $this->actingAs($student)->get(route('expenses.index', ['sort' => 'category', 'dir' => 'asc']));
        $asc->assertOk();
        $asc->assertSeeInOrder(['Bus', 'Zoológico']);

        $desc = $this->actingAs($student)->get(route('expenses.index', ['sort' => 'category', 'dir' => 'desc']));
        $desc->assertOk();
        $desc->assertSeeInOrder(['Zoológico', 'Bus']);
    }
}
