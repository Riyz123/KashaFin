<?php

namespace Tests\Feature;

use App\Mail\TemporaryPasswordMail;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Real institutional email delivery isn't reliable enough to be the only
 * way staff can hand a student their temporary password — these confirm
 * the password always shows up in the response even if the mailer fails.
 */
class PasswordShownOnScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_a_student_shows_the_temporary_password_in_the_status_message(): void
    {
        Mail::fake();
        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);
        $student = User::factory()->pendingApproval()->create(['faculty_id' => $faculty->id]);

        $response = $this->actingAs($dean)->patch(route('admin.users.approve', $student));

        $response->assertSessionHas('status', fn ($status) => preg_match('/[A-Za-z0-9]{10}/', $status) === 1);
    }

    public function test_a_broken_mailer_does_not_block_approval_or_hide_the_password(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP connection refused'));

        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);
        $student = User::factory()->pendingApproval()->create(['faculty_id' => $faculty->id]);

        $response = $this->actingAs($dean)->patch(route('admin.users.approve', $student));

        $response->assertRedirect();
        $response->assertSessionHas('status', fn ($status) => preg_match('/[A-Za-z0-9]{10}/', $status) === 1);
        $this->assertTrue($student->fresh()->isPendingApproval() === false);
    }
}
