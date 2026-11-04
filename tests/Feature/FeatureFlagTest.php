<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Faculty;
use App\Models\FeatureFlag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_flag_not_seeded_at_all_fails_open_as_enabled(): void
    {
        $this->assertTrue(FeatureFlag::isEnabled('something_that_does_not_exist'));
    }

    public function test_every_section_starts_enabled_after_migrating(): void
    {
        foreach (['incomes', 'expenses', 'budgets', 'savings_goals', 'loans', 'shared_expenses', 'ai_chat'] as $key) {
            $this->assertTrue(FeatureFlag::isEnabled($key), "{$key} should start enabled");
        }
    }

    public function test_disabling_a_section_blocks_its_index_page_with_a_friendly_message_instead_of_an_error(): void
    {
        $student = User::factory()->create();
        FeatureFlag::where('key', 'expenses')->first()->update(['is_enabled' => false]);

        $response = $this->actingAs($student)->get(route('expenses.index'));

        $response->assertOk();
        $response->assertSee('deshabilitada por el momento');
    }

    public function test_disabling_a_section_blocks_creating_new_data_but_does_not_touch_what_already_exists(): void
    {
        $student = User::factory()->create();
        $existing = Expense::create([
            'user_id' => $student->id, 'amount' => 20, 'date' => now()->toDateString(), 'description' => 'Antes de apagar',
        ]);

        FeatureFlag::where('key', 'expenses')->first()->update(['is_enabled' => false]);

        $this->actingAs($student)->post(route('expenses.store'), [
            'amount' => 10, 'date' => now()->toDateString(), 'type' => 'variable',
        ])->assertRedirect(route('dashboard'));

        $this->assertSame(1, Expense::where('user_id', $student->id)->count());
        $this->assertNotNull($existing->fresh());
    }

    public function test_a_json_request_to_a_disabled_section_gets_a_403_instead_of_html(): void
    {
        $student = User::factory()->create();
        FeatureFlag::where('key', 'shared_expenses')->first()->update(['is_enabled' => false]);

        $response = $this->actingAs($student)->getJson(route('shared-expenses.search-users', ['q' => 'ana']));

        $response->assertStatus(403);
    }

    public function test_the_feature_still_works_normally_once_reenabled(): void
    {
        $student = User::factory()->create();
        FeatureFlag::where('key', 'expenses')->first()->update(['is_enabled' => false]);
        FeatureFlag::where('key', 'expenses')->first()->update(['is_enabled' => true]);

        $this->actingAs($student)->get(route('expenses.index'))->assertOk()->assertDontSee('deshabilitada por el momento');
    }

    public function test_only_the_master_admin_can_toggle_a_section(): void
    {
        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);
        $flag = FeatureFlag::where('key', 'expenses')->first();

        $this->actingAs($dean)->patch(route('admin.feature-flags.toggle', $flag))->assertForbidden();
    }

    public function test_the_master_admin_can_toggle_a_section_and_it_records_who_and_when(): void
    {
        $admin = User::factory()->admin()->create();
        $flag = FeatureFlag::where('key', 'loans')->first();

        $this->actingAs($admin)->patch(route('admin.feature-flags.toggle', $flag))->assertRedirect();

        $flag->refresh();
        $this->assertFalse($flag->is_enabled);
        $this->assertSame($admin->id, $flag->disabled_by);
        $this->assertNotNull($flag->disabled_at);

        $this->actingAs($admin)->patch(route('admin.feature-flags.toggle', $flag))->assertRedirect();

        $flag->refresh();
        $this->assertTrue($flag->is_enabled);
        $this->assertNull($flag->disabled_by);
        $this->assertNull($flag->disabled_at);
    }

    public function test_the_sidebar_shows_a_disabled_badge_for_a_turned_off_section(): void
    {
        $student = User::factory()->create();
        FeatureFlag::where('key', 'loans')->first()->update(['is_enabled' => false]);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Deshabilitado');
    }
}
