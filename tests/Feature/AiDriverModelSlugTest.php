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

    public function test_gemini_driver_never_leaks_the_api_key_when_the_connection_times_out(): void
    {
        // Gemini's auth key travels in the query string, so a raw
        // connection-level failure (no HTTP response at all) would
        // otherwise echo the full request URL — key included — straight
        // into the exception message that gets stored in ai_usage_logs
        // and shown in the admin panel.
        $provider = $this->makeProvider(['driver' => 'gemini', 'model' => 'gemini-3.8-flash', 'api_key' => 'SECRET-KEY-12345']);

        Http::fake(fn () => Http::failedConnection('cURL error 28: Operation timed out'));

        try {
            (new GeminiDriver)->send($provider, [['role' => 'user', 'content' => 'hola']]);
            $this->fail('Expected an exception.');
        } catch (\RuntimeException $e) {
            $this->assertStringNotContainsString('SECRET-KEY-12345', $e->getMessage());
        }
    }

    public function test_quota_exceeded_with_a_short_retry_after_does_not_sideline_the_provider_for_the_full_period(): void
    {
        // The real incident this guards against: a provider well under its
        // genuine daily quota got a 429 for a per-minute throttle, and the
        // old code benched it until quota_period_days elapsed (often a full
        // day) instead of the handful of seconds the provider actually
        // asked for.
        $provider = $this->makeProvider(['quota_period_days' => 1]);

        Http::fake(['fake-ai.test/*' => Http::response(
            ['error' => ['message' => 'rate limit exceeded']],
            429,
            ['Retry-After' => '20'],
        )]);

        try {
            (new OpenAiCompatibleDriver)->send($provider, [['role' => 'user', 'content' => 'hola']]);
            $this->fail('Expected a QuotaExceededException.');
        } catch (\App\Services\Ai\Exceptions\QuotaExceededException $e) {
            $provider->recordUsage(success: false, exhausted: true, errorMessage: $e->getMessage(), retryAfterSeconds: $e->retryAfterSeconds);
        }

        $provider->refresh();
        $this->assertTrue($provider->is_exhausted);
        $this->assertTrue($provider->period_reset_at->lessThan(now()->addMinute()));
    }
}
