<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\SavingsGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoalChatIntentTest extends TestCase
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
                        'function' => ['name' => $name, 'arguments' => json_encode($arguments)],
                    ]],
                ],
            ]],
        ];
    }

    public function test_ai_tool_call_creates_a_goal(): void
    {
        $user = User::factory()->create();
        $this->fakeProvider();

        Http::fake([
            'fake-ai.test/*' => Http::response(
                $this->openAiToolCallResponse('add_goal', ['name' => 'Viaje a Cusco', 'target_amount' => 500]),
                200
            ),
        ]);

        $response = $this->actingAs($user)->postJson(route('chat.send'), [
            'message' => 'quiero ahorrar 500 para un viaje a Cusco',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['reply' => '✅ Meta creada: "Viaje a Cusco" por S/ 500.00.']);

        $this->assertDatabaseHas('savings_goals', [
            'user_id' => $user->id,
            'name' => 'Viaje a Cusco',
            'target_amount' => 500,
            'status' => 'active',
        ]);
    }

    public function test_ai_tool_call_with_a_future_target_date_saves_it(): void
    {
        $user = User::factory()->create();
        $this->fakeProvider();

        Http::fake([
            'fake-ai.test/*' => Http::response(
                $this->openAiToolCallResponse('add_goal', [
                    'name' => 'Laptop nueva', 'target_amount' => 2000,
                    'target_date' => now()->addMonths(3)->toDateString(),
                ]),
                200
            ),
        ]);

        $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'quiero una laptop nueva']);

        $goal = SavingsGoal::where('user_id', $user->id)->first();
        $this->assertNotNull($goal->target_date);
    }

    public function test_a_past_target_date_from_the_model_is_dropped_not_rejected(): void
    {
        $user = User::factory()->create();
        $this->fakeProvider();

        Http::fake([
            'fake-ai.test/*' => Http::response(
                $this->openAiToolCallResponse('add_goal', [
                    'name' => 'Meta vieja', 'target_amount' => 100, 'target_date' => '2020-01-01',
                ]),
                200
            ),
        ]);

        $response = $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'no entiendo bien lo de la meta de ahorro']);

        $response->assertOk();
        $goal = SavingsGoal::where('user_id', $user->id)->first();
        $this->assertNotNull($goal);
        $this->assertNull($goal->target_date);
    }

    public function test_invalid_amount_from_the_model_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->fakeProvider();

        Http::fake([
            'fake-ai.test/*' => Http::response(
                $this->openAiToolCallResponse('add_goal', ['name' => 'Algo', 'target_amount' => 'no-es-numero']),
                200
            ),
        ]);

        $response = $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'no entiendo bien lo de la meta de ahorro']);

        $response->assertJsonFragment(['reply' => 'Necesito un monto válido (mayor a 0) para la meta.']);
        $this->assertDatabaseMissing('savings_goals', ['user_id' => $user->id]);
    }

    public function test_the_regex_voice_flow_asks_for_name_then_amount_and_creates_the_goal(): void
    {
        $user = User::factory()->create();

        $first = $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'quiero registrar una meta nueva']);
        $first->assertJsonFragment(['reply' => '¿Cómo quieres llamar a la meta?']);

        $second = $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'Viaje a Cusco']);
        $second->assertJsonFragment(['reply' => 'Meta: "Viaje a Cusco". ¿Cuánto quieres ahorrar en total?']);

        $third = $this->actingAs($user)->postJson(route('chat.send'), ['message' => '500']);
        $third->assertJsonFragment(['reply' => '✅ Meta creada: "Viaje a Cusco" por S/ 500.00.']);

        $this->assertDatabaseHas('savings_goals', [
            'user_id' => $user->id,
            'name' => 'Viaje a Cusco',
            'target_amount' => 500,
        ]);
    }
}
