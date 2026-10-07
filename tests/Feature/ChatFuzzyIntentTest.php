<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatFuzzyIntentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_typo_in_the_trigger_word_still_starts_the_expense_flow(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('chat.send'), [
            'message' => 'agrga un gsto', // typo'd "agrega un gasto"
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['reply' => '¿De cuánto fue el gasto?']);
    }

    public function test_a_pronoun_attached_to_the_verb_still_starts_the_income_flow(): void
    {
        $user = User::factory()->create();

        // "Agrégame" = "agrega" + the attached pronoun "me" ("add [it] for
        // me") — a normal Spanish imperative, not a typo. Length alone would
        // push it past a small edit-distance threshold without the
        // pronoun-suffix handling in FuzzyMatch.
        $response = $this->actingAs($user)->postJson(route('chat.send'), [
            'message' => 'Agrégame un ingreso',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['reply' => '¿De cuánto fue el ingreso?']);
    }

    public function test_a_typo_in_the_category_name_still_resolves_it(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['name' => 'Alimentación']);

        $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'agrega un gasto']);

        $response = $this->actingAs($user)->postJson(route('chat.send'), [
            'message' => '15 en alimentaccion', // typo: double "c"
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['reply' => '✅ Gasto registrado: S/ 15.00 en Alimentación, con fecha de hoy.']);
    }

    public function test_a_repeated_word_does_not_break_the_trigger(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('chat.send'), [
            'message' => 'quiero quiero agregar un gasto por favor',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['reply' => '¿De cuánto fue el gasto?']);
    }

    public function test_a_question_mentioning_gasto_does_not_falsely_trigger_the_expense_flow(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('chat.send'), [
            'message' => 'quiero saber cuánto gasto tengo este mes',
        ]);

        $response->assertOk();
        // No AI provider configured in this test, so a real question falls
        // to the data-summary fallback — NOT the expense slot-filling flow.
        $response->assertJsonMissing(['reply' => '¿De cuánto fue el gasto?']);
        $this->assertDatabaseMissing('expenses', ['user_id' => $user->id]);
    }

    public function test_fuzzy_income_type_and_frequency_with_typos(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'agrega un ingreso']);
        $this->actingAs($user)->postJson(route('chat.send'), ['message' => '500 fijoo']); // typo
        $response = $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'mensul']); // typo

        $response->assertOk();
        $response->assertJsonFragment(['reply' => '✅ Ingreso registrado: S/ 500.00 (fijo (mensual)), con fecha de hoy.']);

        $this->assertDatabaseHas('incomes', [
            'user_id' => $user->id,
            'amount' => 500,
            'type' => 'fijo',
            'frequency' => 'mensual',
        ]);
    }
}
