<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ChatToolCallingTest extends TestCase
{
    use RefreshDatabase;

    private function fakeProvider(): AiProvider
    {
        return AiProvider::create([
            'name' => 'Fake provider',
            'driver' => 'openai_compatible',
            'base_url' => 'https://fake-ai.test/v1',
            'model' => 'fake-model',
            'api_key' => 'fake-key',
            'priority' => 0,
            'is_active' => true,
            'is_exhausted' => false,
            'requests_used' => 0,
            'quota_limit' => null,
            'quota_period_days' => 1,
        ]);
    }

    private function openAiToolCallResponse(string $name, array $arguments): array
    {
        return [
            'choices' => [[
                'message' => [
                    'role' => 'assistant',
                    'content' => null,
                    'tool_calls' => [[
                        'id' => 'call_1',
                        'type' => 'function',
                        'function' => [
                            'name' => $name,
                            'arguments' => json_encode($arguments),
                        ],
                    ]],
                ],
            ]],
        ];
    }

    public function test_ai_tool_call_creates_an_expense(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['name' => 'Transporte']);
        $this->fakeProvider();

        Http::fake([
            'fake-ai.test/*' => Http::response(
                $this->openAiToolCallResponse('add_expense', ['amount' => 25.5, 'category' => 'Transporte']),
                200
            ),
        ]);

        $response = $this->actingAs($user)->postJson(route('chat.send'), [
            'message' => 'me gasté 25.50 soles en pasajes',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['reply' => '✅ Gasto registrado: S/ 25.50 en Transporte, con fecha de hoy.']);

        $this->assertDatabaseHas('expenses', [
            'user_id' => $user->id,
            'amount' => 25.5,
        ]);

        $expense = Expense::where('user_id', $user->id)->first();
        $this->assertSame('Transporte', $expense->category->name);
    }

    public function test_ai_tool_call_creates_a_budget(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['name' => 'Alimentación']);
        $this->fakeProvider();

        Http::fake([
            'fake-ai.test/*' => Http::response(
                $this->openAiToolCallResponse('add_budget', ['category' => 'Alimentación', 'amount' => 300]),
                200
            ),
        ]);

        $response = $this->actingAs($user)->postJson(route('chat.send'), [
            'message' => 'ponme un tope de 300 para comida',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['reply' => '✅ Presupuesto guardado: S/ 300.00 para Alimentación este mes.']);

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'amount' => 300,
        ]);
    }

    public function test_plain_text_reply_still_works_without_a_tool_call(): void
    {
        $user = User::factory()->create();
        $this->fakeProvider();

        Http::fake([
            'fake-ai.test/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => '¡Hola! ¿En qué puedo ayudarte hoy?',
                    ],
                ]],
            ], 200),
        ]);

        $response = $this->actingAs($user)->postJson(route('chat.send'), [
            'message' => 'hola',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['reply' => '¡Hola! ¿En qué puedo ayudarte hoy?']);

        $this->assertDatabaseMissing('expenses', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('budgets', ['user_id' => $user->id]);
    }

    public function test_invalid_amount_from_the_model_is_rejected_instead_of_silently_saved(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['name' => 'Transporte']);
        $this->fakeProvider();

        Http::fake([
            'fake-ai.test/*' => Http::response(
                $this->openAiToolCallResponse('add_expense', ['amount' => 'no-es-un-numero']),
                200
            ),
        ]);

        $response = $this->actingAs($user)->postJson(route('chat.send'), [
            'message' => 'agrega algo raro',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['reply' => 'Necesito un monto válido (mayor a 0) para registrar el gasto.']);
        $this->assertDatabaseMissing('expenses', ['user_id' => $user->id]);
    }
}
