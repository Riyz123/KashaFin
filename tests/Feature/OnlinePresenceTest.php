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

    public function test_presence_label_covers_the_three_states(): void
    {
        $online = User::factory()->create(['last_seen_at' => Carbon::now()->subMinute()]);
        $stale = User::factory()->create(['last_seen_at' => Carbon::now()->subHour()]);
        $never = User::factory()->create(['last_seen_at' => null]);

        $this->assertSame('En línea', $online->presenceLabel());
        $this->assertStringContainsString('Última vez', $stale->presenceLabel());
        $this->assertSame('Nunca ha entrado', $never->presenceLabel());
    }

    public function test_the_presence_endpoint_returns_fresh_state_for_the_given_ids(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $online = User::factory()->create(['role' => 'estudiante', 'last_seen_at' => Carbon::now()->subMinute()]);
        $offline = User::factory()->create(['role' => 'estudiante', 'last_seen_at' => Carbon::now()->subHour()]);

        $response = $this->actingAs($admin)->getJson(route('admin.users.presence', ['ids' => "{$online->id},{$offline->id}"]));

        $response->assertOk();
        $response->assertJson([
            (string) $online->id => ['online' => true, 'label' => 'En línea'],
        ]);
        $this->assertFalse($response->json((string) $offline->id)['online']);
    }

    public function test_a_dean_only_gets_presence_for_their_own_faculty(): void
    {
        $facultyA = \App\Models\Faculty::factory()->create();
        $facultyB = \App\Models\Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $facultyA->id]);
        $inA = User::factory()->create(['role' => 'estudiante', 'faculty_id' => $facultyA->id, 'last_seen_at' => now()]);
        $inB = User::factory()->create(['role' => 'estudiante', 'faculty_id' => $facultyB->id, 'last_seen_at' => now()]);

        $response = $this->actingAs($dean)->getJson(route('admin.users.presence', ['ids' => "{$inA->id},{$inB->id}"]));

        $response->assertOk();
        $this->assertArrayHasKey((string) $inA->id, $response->json());
        $this->assertArrayNotHasKey((string) $inB->id, $response->json());
    }
}
