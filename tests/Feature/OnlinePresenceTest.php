<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OnlinePresenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_request_sets_last_seen_at_for_a_fresh_user(): void
    {
        $user = User::factory()->create(['last_seen_at' => null]);

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertNotNull($user->fresh()->last_seen_at);
    }

    public function test_last_seen_is_not_rewritten_within_the_two_minute_throttle(): void
    {
        $user = User::factory()->create(['last_seen_at' => Carbon::now()->subSeconds(30)]);
        $original = $user->last_seen_at;

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertEquals($original, $user->fresh()->last_seen_at);
    }

    public function test_last_seen_is_rewritten_after_the_throttle_elapses(): void
    {
        $user = User::factory()->create(['last_seen_at' => Carbon::now()->subMinutes(5)]);

        $this->actingAs($user)->get(route('dashboard'));

        $this->assertTrue($user->fresh()->last_seen_at->greaterThan(Carbon::now()->subMinute()));
    }

    public function test_is_online_within_five_minutes_otherwise_not(): void
    {
        $recent = User::factory()->create(['last_seen_at' => Carbon::now()->subMinutes(2)]);
        $stale = User::factory()->create(['last_seen_at' => Carbon::now()->subMinutes(10)]);
        $never = User::factory()->create(['last_seen_at' => null]);

        $this->assertTrue($recent->isOnline());
        $this->assertFalse($stale->isOnline());
        $this->assertFalse($never->isOnline());
    }

    public function test_the_admin_users_page_shows_the_presence_indicator(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        User::factory()->create(['role' => 'estudiante', 'last_seen_at' => Carbon::now()->subMinute()]);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('En línea');
    }
}
