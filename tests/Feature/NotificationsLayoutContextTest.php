<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsLayoutContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_dean_always_gets_the_admin_chrome(): void
    {
        $dean = User::factory()->create(['role' => 'decano']);

        $response = $this->actingAs($dean)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertViewHas('isStaff', true);
    }

    public function test_an_admin_gets_the_admin_chrome_by_default(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertViewHas('isStaff', true);
    }

    public function test_an_admin_coming_from_the_student_sidebar_keeps_the_student_chrome(): void
    {
        // The real bug this guards against: an admin browsing "Ver app de
        // estudiante" clicked Notificaciones and got yanked into the admin
        // panel instead of staying in the student app, because the old
        // code decided the layout purely from role.
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('notifications.index', ['panel' => 'student']));

        $response->assertOk();
        $response->assertViewHas('isStaff', false);
    }

    public function test_a_student_always_gets_the_student_chrome(): void
    {
        $student = User::factory()->create(['role' => 'estudiante']);

        $response = $this->actingAs($student)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertViewHas('isStaff', false);
    }
}
