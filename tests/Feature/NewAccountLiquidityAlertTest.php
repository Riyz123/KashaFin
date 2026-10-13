<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\User;
use App\Services\LiquidityProjectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewAccountLiquidityAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_brand_new_account_with_no_activity_is_never_flagged_as_below_threshold(): void
    {
        $student = User::factory()->create();

        $this->assertFalse((new LiquidityProjectionService)->isBelowThreshold($student));
    }

    public function test_the_dashboard_does_not_show_a_liquidity_warning_for_a_brand_new_account(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('por debajo de tu umbral');
    }

    public function test_an_account_with_real_activity_can_still_be_flagged_below_threshold(): void
    {
        $student = User::factory()->create();
        $student->settings->update(['liquidity_threshold' => 1000, 'starting_balance' => 0]);
        Expense::create([
            'user_id' => $student->id, 'category_id' => null, 'amount' => 50,
            'date' => now(), 'description' => 'Gasto real',
        ]);

        $this->assertTrue((new LiquidityProjectionService)->isBelowThreshold($student));
    }
}
