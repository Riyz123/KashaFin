<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Verifies the system prompt actually instructs the model to analyze a
 * "can I afford this?" question with real numbers (balance, 7-day
 * projection, liquidity threshold, budgets) instead of just acknowledging
 * the question — this can't be verified against a real model in CI, so this
 * only confirms the instruction and the real figures are present in what
 * gets sent, which is as far as an automated test can go.
 */
class ChatAffordabilityPromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_system_prompt_instructs_the_model_to_give_a_real_affordability_recommendation(): void
    {
        $user = User::factory()->create();
        $user->settings->update(['liquidity_threshold' => 150]);

        AiProvider::create([
            'name' => 'Fake provider', 'driver' => 'openai_compatible',
            'base_url' => 'https://fake-ai.test/v1', 'model' => 'fake-model', 'api_key' => 'fake-key',
            'priority' => 0, 'is_active' => true, 'is_exhausted' => false,
            'requests_used' => 0, 'quota_limit' => null, 'quota_period_days' => 1,
        ]);

        $capturedBody = null;

        Http::fake(function ($request) use (&$capturedBody) {
            $capturedBody = $request->data();

            return Http::response([
                'choices' => [['message' => ['role' => 'assistant', 'content' => 'No te recomiendo gastar en eso ahora mismo.']]],
            ], 200);
        });

        $this->actingAs($user)->postJson(route('chat.send'), [
            'message' => 'puedo gastar en una cerveza, pero solo tengo 10 soles',
        ])->assertOk();

        $this->assertNotNull($capturedBody);
        $systemMessage = collect($capturedBody['messages'])->firstWhere('role', 'system')['content'];

        $this->assertStringContainsString('recomendación clara y directa', $systemMessage);
        $this->assertStringContainsString('Umbral mínimo de liquidez configurado: S/ 150', $systemMessage);
    }
}
