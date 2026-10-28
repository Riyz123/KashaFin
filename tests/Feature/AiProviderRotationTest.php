<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderRotationTest extends TestCase
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

    public function test_tied_priority_providers_are_ordered_deterministically_by_id(): void
    {
        // All three left at the form's default priority (0) — this is
        // exactly the scenario the user hit: without a secondary sort key,
        // MySQL doesn't guarantee a stable order between ties, so the "use
        // one until it's exhausted" rotation could pick a different
        // provider first on every single message.
        $a = $this->makeProvider(['name' => 'A']);
        $b = $this->makeProvider(['name' => 'B']);
        $c = $this->makeProvider(['name' => 'C']);

        $order = AiProvider::query()->ordered()->pluck('id')->all();

        $this->assertSame([$a->id, $b->id, $c->id], $order);
    }

    public function test_admin_panel_refreshes_a_stale_exhausted_provider_on_load(): void
    {
        $admin = User::factory()->admin()->create();

        $provider = $this->makeProvider([
            'is_exhausted' => true,
            'requests_used' => 100,
            'quota_limit' => 100,
            // Reset date already in the past — the admin should see this
            // reflected as recovered, not stuck showing the old date.
            'period_reset_at' => Carbon::now()->subDay(),
        ]);

        $this->actingAs($admin)->get(route('admin.ai.index'))->assertOk();

        $provider->refresh();
        $this->assertFalse($provider->is_exhausted);
        $this->assertSame(0, $provider->requests_used);
        $this->assertTrue($provider->period_reset_at->isFuture());
    }

    public function test_the_daily_counter_rolls_over_even_if_the_provider_was_never_marked_exhausted(): void
    {
        // The actual bug reported: a provider well under quota_limit (or
        // with no quota_limit at all) never triggered the old reset logic,
        // since it only ran for providers already flagged exhausted/over
        // quota. requests_used would just accumulate forever and
        // period_reset_at would stay frozen at its very first value.
        $provider = $this->makeProvider([
            'is_exhausted' => false,
            'requests_used' => 37,
            'quota_limit' => 1000,
            'period_reset_at' => Carbon::now()->subHour(),
        ]);

        $this->assertTrue($provider->isAvailable());

        $provider->refresh();
        $this->assertSame(0, $provider->requests_used);
        $this->assertTrue($provider->period_reset_at->isFuture());
    }

    public function test_the_counter_does_not_reset_before_the_period_elapses(): void
    {
        $provider = $this->makeProvider([
            'requests_used' => 5,
            'quota_limit' => 1000,
            'period_reset_at' => Carbon::now()->addHours(12),
        ]);

        $this->assertTrue($provider->isAvailable());

        $provider->refresh();
        $this->assertSame(5, $provider->requests_used);
    }

    public function test_admin_can_manually_reactivate_an_exhausted_provider(): void
    {
        // Covers the case where is_exhausted got set by a misclassified
        // error rather than a genuine quota hit — the admin shouldn't have
        // to wait for period_reset_at to try the provider again.
        $admin = User::factory()->admin()->create();

        $provider = $this->makeProvider([
            'is_exhausted' => true,
            'requests_used' => 42,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.ai.reactivate', $provider))
            ->assertRedirect();

        $provider->refresh();
        $this->assertFalse($provider->is_exhausted);
        $this->assertSame(0, $provider->requests_used);
    }

    public function test_admin_test_connection_reports_the_exact_provider_error(): void
    {
        $admin = User::factory()->admin()->create();
        $provider = $this->makeProvider();

        Http::fake([
            'fake-ai.test/*' => Http::response(['error' => ['message' => 'Invalid API key provided.']], 401),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.ai.test', $provider));

        $response->assertRedirect();
        $response->assertSessionHas('status', fn ($status) => str_contains($status, 'Invalid API key provided.'));
    }

    public function test_admin_test_connection_does_not_affect_rotation_state(): void
    {
        $admin = User::factory()->admin()->create();
        $provider = $this->makeProvider();

        Http::fake([
            'fake-ai.test/*' => Http::response(['error' => ['message' => 'rate limit exceeded']], 429),
        ]);

        $this->actingAs($admin)->post(route('admin.ai.test', $provider));

        $provider->refresh();
        $this->assertFalse($provider->is_exhausted);
        $this->assertSame(0, $provider->requests_used);
    }

    public function test_a_successful_test_connection_clears_a_stale_error_from_the_panel(): void
    {
        // The real scenario this guards against: a provider's config gets
        // fixed, "Probar conexión" now succeeds, but "Último uso" kept
        // showing the old chat failure forever because the test endpoint
        // never logged anything — only real chat traffic through
        // AiProviderRouter did, and a deprioritized backup model might not
        // see real traffic again for a long time.
        $admin = User::factory()->admin()->create();
        $provider = $this->makeProvider();
        $provider->usageLogs()->create(['status' => 'error', 'error_message' => 'cURL error 28: Operation timed out']);

        Http::fake(['fake-ai.test/*' => Http::response(['choices' => [['message' => ['content' => 'ok']]]])]);

        $this->actingAs($admin)->post(route('admin.ai.test', $provider));

        $latest = $provider->latestUsageLog()->first();
        $this->assertSame('success', $latest->status);
    }

    public function test_the_live_status_endpoint_flags_the_provider_that_would_actually_be_used_right_now(): void
    {
        $admin = User::factory()->admin()->create();
        $preferred = $this->makeProvider(['name' => 'Groq', 'priority' => 1]);
        $backup = $this->makeProvider(['name' => 'Gemini backup', 'priority' => 2]);
        $exhausted = $this->makeProvider(['name' => 'Agotado', 'priority' => 0, 'is_exhausted' => true]);
        $exhausted->usageLogs()->create(['status' => 'quota_exceeded', 'error_message' => 'Límite alcanzado.']);
        $preferred->usageLogs()->create(['status' => 'success']);

        $response = $this->actingAs($admin)->getJson(route('admin.ai.status'));

        $response->assertOk();
        $response->assertJsonPath("{$preferred->id}.is_preferred", true);
        $response->assertJsonPath("{$preferred->id}.recent_logs.0.status", 'success');
        $response->assertJsonPath("{$backup->id}.is_preferred", false);
        $response->assertJsonPath("{$backup->id}.available", true);
        $response->assertJsonPath("{$exhausted->id}.is_preferred", false);
        $response->assertJsonPath("{$exhausted->id}.available", false);
        $response->assertJsonPath("{$exhausted->id}.recent_logs.0.status", 'quota_exceeded');
    }
}
