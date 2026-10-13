<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_first_time_student_sees_the_onboarding_tour_on_the_dashboard(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('¡Bienvenido a KashaFin!');
    }

    public function test_the_tour_does_not_show_again_after_being_dismissed(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->patch(route('settings.onboarding'))->assertNoContent();

        $this->assertNotNull($student->settings->fresh()->onboarding_completed_at);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('¡Bienvenido a KashaFin!');
    }
}
