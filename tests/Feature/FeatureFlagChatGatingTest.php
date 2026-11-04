<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\Category;
use App\Models\Expense;
use App\Models\FeatureFlag;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FeatureFlagChatGatingTest extends TestCase
{
    use RefreshDatabase;

    private function fakeProvider(): AiProvider
    {
        return AiProvider::create([
            'name' => 'Fake provider', 'driver' => 'openai_compatible', 'base_url' => 'https://fake-ai.test/v1',
            'model' => 'fake-model', 'api_key' => 'fake-key', 'priority' => 0, 'is_active' => true,
            'is_exhausted' => false, 'requests_used' => 0, 'quota_limit' => null, 'quota_period_days' => 1,
        ]);
    }

    private function openAiToolCallResponse(string $name, array $arguments): array
    {
        return [
            'choices' => [[
                'message' => [
                    'role' => 'assistant', 'content' => null,
                    'tool_calls' => [[
                        'id' => 'call_1', 'type' => 'function',
                        'function' => ['name' => $name, 'arguments' => json_encode($arguments)],
                    ]],
                ],
            ]],
        ];
    }

    public function test_the_regex_trigger_for_a_disabled_section_refuses_instead_of_creating_data(): void
    {
        $student = User::factory()->create();
        FeatureFlag::where('key', 'expenses')->first()->update(['is_enabled' => false]);

        $response = $this->actingAs($student)->postJson(route('chat.send'), [
            'message' => 'agrega un gasto',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['reply' => 'La sección "Gastos" está deshabilitada por el momento.']);
        $this->assertSame(0, Expense::where('user_id', $student->id)->count());
    }

    public function test_the_ai_tool_call_for_a_disabled_section_refuses_instead_of_creating_data(): void
    {
        $student = User::factory()->create();
        Category::factory()->create(['name' => 'Transporte']);
        $this->fakeProvider();
        FeatureFlag::where('key', 'expenses')->first()->update(['is_enabled' => false]);

        Http::fake([
            'fake-ai.test/*' => Http::response(
                $this->openAiToolCallResponse('add_expense', ['amount' => 25.5, 'category' => 'Transporte']),
                200
            ),
        ]);

        $response = $this->actingAs($student)->postJson(route('chat.send'), [
            'message' => 'me gasté 25.50 soles en pasajes',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['reply' => 'La sección "Gastos" está deshabilitada por el momento.']);
        $this->assertSame(0, Expense::where('user_id', $student->id)->count());
    }

    public function test_disabling_ai_chat_entirely_blocks_the_assistant_without_touching_any_intent(): void
    {
        $student = User::factory()->create();
        FeatureFlag::where('key', 'ai_chat')->first()->update(['is_enabled' => false]);

        $response = $this->actingAs($student)->postJson(route('chat.send'), [
            'message' => 'agrega un gasto',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['reply' => 'La sección "Asistente IA" está deshabilitada por el momento.']);
        $this->assertSame(0, Expense::where('user_id', $student->id)->count());
    }

    public function test_disabling_loans_does_not_affect_the_expense_tool(): void
    {
        $student = User::factory()->create();
        Category::factory()->create(['name' => 'Transporte']);
        $this->fakeProvider();
        FeatureFlag::where('key', 'loans')->first()->update(['is_enabled' => false]);

        Http::fake([
            'fake-ai.test/*' => Http::response(
                $this->openAiToolCallResponse('add_expense', ['amount' => 10, 'category' => 'Transporte']),
                200
            ),
        ]);

        $response = $this->actingAs($student)->postJson(route('chat.send'), [
            'message' => 'me gasté 10 soles',
        ]);

        $response->assertOk();
        $this->assertSame(1, Expense::where('user_id', $student->id)->count());
        $this->assertSame(0, Loan::where('user_id', $student->id)->count());
    }
}
