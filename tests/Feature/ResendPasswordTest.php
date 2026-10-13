<?php

namespace Tests\Feature;

use App\Mail\TemporaryPasswordMail;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ResendPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_resend_a_fresh_password_to_a_student_who_never_got_the_first_one(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['must_change_password' => true]);
        $originalHash = $student->password;

        $response = $this->actingAs($admin)->patch(route('admin.users.resend-password', $student));

        $response->assertRedirect();
        Mail::assertSent(TemporaryPasswordMail::class, fn ($mail) => $mail->hasTo($student->email));
        $this->assertNotSame($originalHash, $student->fresh()->password);
        $this->assertTrue($student->fresh()->must_change_password);
    }

    public function test_it_refuses_to_resend_once_the_student_already_set_their_own_password(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($admin)->patch(route('admin.users.resend-password', $student))
            ->assertStatus(422);
    }

    public function test_a_dean_cannot_resend_a_password_for_a_student_outside_their_faculty(): void
    {
        $facultyA = Faculty::factory()->create();
        $facultyB = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $facultyA->id]);
        $student = User::factory()->create(['faculty_id' => $facultyB->id, 'must_change_password' => true]);

        $this->actingAs($dean)->patch(route('admin.users.resend-password', $student))
            ->assertForbidden();
    }
}
