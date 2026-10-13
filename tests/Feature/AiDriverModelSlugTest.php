<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Services\Ai\Drivers\GeminiDriver;
use App\Services\Ai\Drivers\OpenAiCompatibleDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiDriverModelSlugTest extends TestCase
{
    use RefreshDatabase;

    private function makeProvider(array $overrides = []): AiProvider
    {
        return AiProvider::create(array_merge([
            'name' => 'Provider',
            'driver' => 'openai_compatible',
            'base_url' => 'https://fake-ai.test',
            'model' => 'fake-model',
            'api_key' => 'fake-key',
            'priority' => 0,
            'is_active' => true,
            'quota_period_days' => 1,
        ], $overrides));
    }

    public function test_gemini_driver_strips_a_models_prefix_pasted_into_the_model_field(): void
    {
        // Google's own deprecation errors suggest the replacement slug WITH
        // the "models/" prefix included (e.g. "models/gemini-3.8-flash"),
        // which an admin naturally pastes as-is — this must not produce a
        // broken "models/models/..." URL.
        $provider = $this->makeProvider(['driver' => 'gemini', 'model' => 'models/gemini-3.8-flash']);

        Http::fake(function ($request) {
            $this->assertStringContainsString('/models/gemini-3.8-flash:generateContent', $request->url());
            $this->assertStringNotContainsString('models/models/', $request->url());

            return Http::response(['candidates' => [['content' => ['parts' => [['text' => 'ok']]]]]]);
        });

        $reply = (new GeminiDriver)->send($provider, [['role' => 'user', 'content' => 'hola']]);

        $this->assertSame('ok', $reply->content);
    }

    public function test_gemini_driver_also_works_when_the_model_field_has_no_prefix(): void
    {
        $provider = $this->makeProvider(['driver' => 'gemini', 'model' => 'gemini-3.8-flash']);

        Http::fake(function ($request) {
            $this->assertStringContainsString('/models/gemini-3.8-flash:generateContent', $request->url());

            return Http::response(['candidates' => [['content' => ['parts' => [['text' => 'ok']]]]]]);
        });

        (new GeminiDriver)->send($provider, [['role' => 'user', 'content' => 'hola']]);
    }

    public function test_openai_compatible_driver_trims_whitespace_pasted_into_the_model_field(): void
    {
        $provider = $this->makeProvider(['model' => " meta-llama/llama-3.1-8b-instruct\n"]);

        Http::fake(function ($request) {
            $this->assertSame('meta-llama/llama-3.1-8b-instruct', $request->data()['model']);

            return Http::response(['choices' => [['message' => ['content' => 'ok']]]]);
        });

        (new OpenAiCompatibleDriver)->send($provider, [['role' => 'user', 'content' => 'hola']]);
    }
}
