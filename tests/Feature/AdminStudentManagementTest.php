<?php

namespace Tests\Feature;

use App\Mail\TemporaryPasswordMail;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminStudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manually_add_an_already_approved_student(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $faculty = Faculty::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Carlos Nuevo',
            'email' => 'carlos.nuevo@upn.pe',
            'faculty_id' => $faculty->id,
        ]);

        $response->assertRedirect();
        Mail::assertSent(TemporaryPasswordMail::class, 1);

        $student = User::where('email', 'carlos.nuevo@upn.pe')->first();
        $this->assertNotNull($student);
        $this->assertFalse($student->isPendingApproval());
        $this->assertTrue($student->must_change_password);
        $this->assertSame($faculty->id, $student->faculty_id);
    }

    public function test_dean_adding_a_student_is_locked_to_their_own_faculty(): void
    {
        Mail::fake();

        $facultyA = Faculty::factory()->create();
        $facultyB = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $facultyA->id]);

        $this->actingAs($dean)->post(route('admin.users.store'), [
            'name' => 'Ana Dean Add',
            'email' => 'ana.dean@upn.pe',
            // Even if a dean somehow submitted a different faculty_id, it's
            // ignored — the controller always uses the dean's own.
            'faculty_id' => $facultyB->id,
        ])->assertRedirect();

        $student = User::where('email', 'ana.dean@upn.pe')->first();
        $this->assertSame($facultyA->id, $student->faculty_id);
    }

    public function test_admin_can_reassign_a_students_faculty(): void
    {
        $admin = User::factory()->admin()->create();
        $facultyA = Faculty::factory()->create();
        $facultyB = Faculty::factory()->create();
        $student = User::factory()->create(['faculty_id' => $facultyA->id]);

        $this->actingAs($admin)->patch(route('admin.users.update-faculty', $student), [
            'faculty_id' => $facultyB->id,
        ])->assertRedirect();

        $this->assertSame($facultyB->id, $student->refresh()->faculty_id);
    }

    public function test_dean_cannot_reassign_a_students_faculty(): void
    {
        $facultyA = Faculty::factory()->create();
        $facultyB = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $facultyA->id]);
        $student = User::factory()->create(['faculty_id' => $facultyA->id]);

        $this->actingAs($dean)->patch(route('admin.users.update-faculty', $student), [
            'faculty_id' => $facultyB->id,
        ])->assertForbidden();
    }
}
