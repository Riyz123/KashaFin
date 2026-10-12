<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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
}
