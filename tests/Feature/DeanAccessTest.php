<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeanAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_dean_only_sees_students_from_their_own_faculty(): void
    {
        $facultyA = Faculty::factory()->create();
        $facultyB = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $facultyA->id]);

        $studentA = User::factory()->create(['faculty_id' => $facultyA->id, 'name' => 'Estudiante A']);
        $studentB = User::factory()->create(['faculty_id' => $facultyB->id, 'name' => 'Estudiante B']);

        $response = $this->actingAs($dean)->get(route('admin.users.index'));

        $response->assertOk();
        $response->assertSee('Estudiante A');
        $response->assertDontSee('Estudiante B');
    }

    public function test_dean_cannot_manage_a_student_outside_their_faculty(): void
    {
        $facultyA = Faculty::factory()->create();
        $facultyB = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $facultyA->id]);
        $studentB = User::factory()->create(['faculty_id' => $facultyB->id]);

        $response = $this->actingAs($dean)->patch(route('admin.users.toggle-active', $studentB));

        $response->assertForbidden();
    }

    public function test_dean_cannot_access_the_ai_panel_or_faculties_panel(): void
    {
        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);

        $this->actingAs($dean)->get(route('admin.ai.index'))->assertForbidden();
        $this->actingAs($dean)->get(route('admin.faculties.index'))->assertForbidden();
        $this->actingAs($dean)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_dean_gets_redirected_away_from_the_student_app(): void
    {
        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);

        $response = $this->actingAs($dean)->get(route('dashboard'));

        $response->assertRedirect(route('admin.users.index'));
    }

    public function test_master_admin_can_manage_students_across_all_faculties(): void
    {
        $admin = User::factory()->admin()->create();
        $facultyA = Faculty::factory()->create();
        $student = User::factory()->create(['faculty_id' => $facultyA->id]);

        $response = $this->actingAs($admin)->patch(route('admin.users.toggle-active', $student));

        $response->assertRedirect();
        $this->assertFalse($student->refresh()->is_active);
    }
}
