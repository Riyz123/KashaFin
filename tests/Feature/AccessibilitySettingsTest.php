<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessibilitySettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_can_set_a_larger_text_size_and_it_applies_to_the_page(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->patch(route('settings.update'), [
            'currency' => 'PEN',
            'theme' => 'light',
            'font_size' => 'large',
            'language' => 'es',
            'week_start_day' => 1,
            'liquidity_threshold' => 100,
            'starting_balance' => 0,
        ])->assertRedirect();

        $this->assertSame('large', $student->settings->fresh()->font_size);

        $response = $this->actingAs($student)->get(route('dashboard'));
        $response->assertOk();
        $this->assertStringContainsString('text-scale-large', $response->getContent());
    }

    public function test_the_dashboard_includes_a_skip_to_content_link(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Saltar al contenido');
    }
}
