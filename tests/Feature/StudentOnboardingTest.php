<?php

namespace Tests\Feature;

use App\Mail\TemporaryPasswordMail;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StudentOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dean_approving_a_pending_student_emails_a_working_password(): void
    {
        Mail::fake();

        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);
        $student = User::factory()->pendingApproval()->create(['faculty_id' => $faculty->id]);

        $this->actingAs($dean)->patch(route('admin.users.approve', $student))->assertRedirect();

        Mail::assertSent(TemporaryPasswordMail::class, function ($mail) use ($student) {
            return $mail->hasTo($student->email) && strlen($mail->temporaryPassword) >= 8;
        });

        $student->refresh();
        $this->assertNotNull($student->approved_at);
        $this->assertTrue($student->is_active);
        $this->assertTrue($student->must_change_password);
    }

    public function test_dean_cannot_approve_a_student_outside_their_faculty(): void
    {
        $facultyA = Faculty::factory()->create();
        $facultyB = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $facultyA->id]);
        $student = User::factory()->pendingApproval()->create(['faculty_id' => $facultyB->id]);

        $this->actingAs($dean)->patch(route('admin.users.approve', $student))->assertForbidden();
    }

    public function test_user_with_must_change_password_is_forced_to_the_password_screen(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('password.force-edit'));

        $this->actingAs($user)->put(route('password.force-update'), [
            'password' => 'nueva-contrasena',
            'password_confirmation' => 'nueva-contrasena',
        ])->assertRedirect(route('dashboard'));

        $this->assertFalse($user->refresh()->must_change_password);
    }

    public function test_dean_can_import_a_csv_roster_and_each_row_gets_emailed(): void
    {
        Mail::fake();

        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);

        $csv = "nombre,correo\nAna Torres,ana.torres@upn.edu.pe\nLuis Paz,luis.paz@upn.edu.pe\n";
        $file = UploadedFile::fake()->createWithContent('alumnos.csv', $csv);

        $response = $this->actingAs($dean)->post(route('admin.users.import'), ['file' => $file]);

        $response->assertRedirect();
        Mail::assertSent(TemporaryPasswordMail::class, 2);

        $this->assertDatabaseHas('users', ['email' => 'ana.torres@upn.edu.pe', 'faculty_id' => $faculty->id, 'role' => 'estudiante']);
        $this->assertDatabaseHas('users', ['email' => 'luis.paz@upn.edu.pe', 'faculty_id' => $faculty->id]);

        $imported = User::where('email', 'ana.torres@upn.edu.pe')->first();
        $this->assertTrue($imported->isPendingApproval() === false);
        $this->assertTrue($imported->must_change_password);
    }

    public function test_csv_import_skips_non_upn_and_duplicate_emails(): void
    {
        Mail::fake();

        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);
        $existing = User::factory()->create(['faculty_id' => $faculty->id]);

        $csv = "nombre,correo\nMalo,malo@gmail.com\nDuplicado,{$existing->email}\nBien,bien@upn.edu.pe\n";
        $file = UploadedFile::fake()->createWithContent('alumnos.csv', $csv);

        $this->actingAs($dean)->post(route('admin.users.import'), ['file' => $file]);

        Mail::assertSent(TemporaryPasswordMail::class, 1);
        $this->assertDatabaseMissing('users', ['email' => 'malo@gmail.com']);
        $this->assertDatabaseHas('users', ['email' => 'bien@upn.edu.pe']);
    }
}
