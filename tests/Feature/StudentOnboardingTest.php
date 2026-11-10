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

        $csv = "nombre,correo\nAna Torres,ana.torres@upn.pe\nLuis Paz,luis.paz@upn.pe\n";
        $file = UploadedFile::fake()->createWithContent('alumnos.csv', $csv);

        $response = $this->actingAs($dean)->post(route('admin.users.import'), ['file' => $file]);

        $response->assertRedirect();
        Mail::assertSent(TemporaryPasswordMail::class, 2);

        $this->assertDatabaseHas('users', ['email' => 'ana.torres@upn.pe', 'faculty_id' => $faculty->id, 'role' => 'estudiante']);
        $this->assertDatabaseHas('users', ['email' => 'luis.paz@upn.pe', 'faculty_id' => $faculty->id]);

        $imported = User::where('email', 'ana.torres@upn.pe')->first();
        $this->assertTrue($imported->isPendingApproval() === false);
        $this->assertTrue($imported->must_change_password);
    }

    public function test_csv_import_skips_non_upn_and_duplicate_emails(): void
    {
        Mail::fake();

        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);
        $existing = User::factory()->create(['faculty_id' => $faculty->id]);

        $csv = "nombre,correo\nMalo,malo@gmail.com\nDuplicado,{$existing->email}\nBien,bien@upn.pe\n";
        $file = UploadedFile::fake()->createWithContent('alumnos.csv', $csv);

        $this->actingAs($dean)->post(route('admin.users.import'), ['file' => $file]);

        Mail::assertSent(TemporaryPasswordMail::class, 1);
        $this->assertDatabaseMissing('users', ['email' => 'malo@gmail.com']);
        $this->assertDatabaseHas('users', ['email' => 'bien@upn.pe']);
    }

    /**
     * Excel's plain "CSV (delimitado por comas)" save format (as opposed to
     * "CSV UTF-8") writes tildes/ñ as Windows-1252, not UTF-8 — reading that
     * straight in used to silently corrupt the name. Csv::normalizeCell()
     * detects and converts it.
     */
    public function test_csv_import_reads_accented_names_saved_as_windows_1252(): void
    {
        Mail::fake();

        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);

        $csv = mb_convert_encoding("nombre,correo\nJosé Díaz Ramírez,jose.diaz@upn.pe\n", 'Windows-1252', 'UTF-8');
        $file = UploadedFile::fake()->createWithContent('alumnos.csv', $csv);

        $this->actingAs($dean)->post(route('admin.users.import'), ['file' => $file]);

        $this->assertDatabaseHas('users', ['email' => 'jose.diaz@upn.pe', 'name' => 'José Díaz Ramírez']);
    }

    /**
     * Excel on a Spanish-locale Windows defaults a CSV's own column
     * separator to ";" (since "," is the decimal separator there) —
     * Csv::detectDelimiter() picks that up instead of assuming ",".
     */
    public function test_csv_import_accepts_a_semicolon_delimited_file(): void
    {
        Mail::fake();

        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);

        $csv = "nombre;correo\nAna Torres;ana.torres@upn.pe\n";
        $file = UploadedFile::fake()->createWithContent('alumnos.csv', $csv);

        $this->actingAs($dean)->post(route('admin.users.import'), ['file' => $file]);

        $this->assertDatabaseHas('users', ['email' => 'ana.torres@upn.pe', 'name' => 'Ana Torres']);
    }

    /**
     * A per-faculty template (importTemplate()) carries a "facultad" column
     * already filled in — the admin master shouldn't have to also pick it
     * from the dropdown for that row to land in the right faculty.
     */
    public function test_csv_import_uses_the_facultad_column_over_the_form_dropdown(): void
    {
        Mail::fake();

        $admin = User::factory()->admin()->create();
        $ingenieria = Faculty::factory()->create(['name' => 'Ingeniería']);
        $negocios = Faculty::factory()->create(['name' => 'Negocios']);

        $csv = "nombre,correo,facultad\nAna Torres,ana.torres@upn.pe,Ingeniería\n";
        $file = UploadedFile::fake()->createWithContent('alumnos.csv', $csv);

        $this->actingAs($admin)->post(route('admin.users.import'), [
            'file' => $file,
            'faculty_id' => $negocios->id,
        ]);

        $this->assertDatabaseHas('users', ['email' => 'ana.torres@upn.pe', 'faculty_id' => $ingenieria->id]);
    }

    /**
     * A repeated student code within the same uploaded file (not only one
     * already in the database) must not create a second row or overwrite
     * the first.
     */
    public function test_csv_import_skips_a_repeated_student_code_within_the_same_file(): void
    {
        Mail::fake();

        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);

        $csv = "nombre,correo\nAna Torres,n00321428@upn.pe\nAna T. Repetida,N00321428@UPN.PE\n";
        $file = UploadedFile::fake()->createWithContent('alumnos.csv', $csv);

        $this->actingAs($dean)->post(route('admin.users.import'), ['file' => $file]);

        $this->assertSame(1, User::where('email', 'n00321428@upn.pe')->count());
        $this->assertDatabaseHas('users', ['email' => 'n00321428@upn.pe', 'name' => 'Ana Torres']);
    }

    public function test_export_writes_a_utf8_bom_and_semicolon_delimited_columns(): void
    {
        $faculty = Faculty::factory()->create(['name' => 'Ingeniería']);
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'José Díaz', 'faculty_id' => $faculty->id]);

        $response = $this->actingAs($admin)->get(route('admin.users.export'));

        $content = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString("Nombre;Correo;Facultad;Estado;Registrado\n", $content);
        $this->assertStringContainsString('José Díaz', $content);
    }

    public function test_import_template_is_prefilled_with_the_given_faculty(): void
    {
        $faculty = Faculty::factory()->create(['name' => 'Ingeniería']);
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.users.import-template', $faculty));

        $content = $response->streamedContent();

        $this->assertStringContainsString('nombre;correo;facultad', $content);
        $this->assertStringContainsString('Ingeniería', $content);
    }
}
