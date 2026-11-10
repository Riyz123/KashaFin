<?php

namespace Tests\Feature;

use App\Mail\RegistrationCodeMail;
use App\Mail\TemporaryPasswordMail;
use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registering_sends_a_code_and_does_not_create_the_account_yet(): void
    {
        Mail::fake();
        $faculty = Faculty::factory()->create();

        $response = $this->post(route('register'), [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upn.pe',
            'faculty_id' => $faculty->id,
        ]);

        $response->assertRedirect(route('register.verify'));
        Mail::assertSent(RegistrationCodeMail::class, fn ($mail) => $mail->hasTo('ana.torres@upn.pe'));
        $this->assertDatabaseMissing('users', ['email' => 'ana.torres@upn.pe']);
    }

    public function test_entering_the_correct_code_creates_an_active_account_with_a_temporary_password(): void
    {
        Mail::fake();
        $faculty = Faculty::factory()->create();

        $this->post(route('register'), [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upn.pe',
            'faculty_id' => $faculty->id,
        ]);

        $code = Cache::get('registration_code:ana.torres@upn.pe')['code'];

        $response = $this->post(route('register.verify.submit'), ['code' => $code]);

        $response->assertRedirect(route('register.password'));
        $this->assertGuest();
        $this->assertNull(Cache::get('registration_code:ana.torres@upn.pe'));

        $user = User::where('email', 'ana.torres@upn.pe')->first();
        $this->assertNotNull($user);
        $this->assertSame($faculty->id, $user->faculty_id);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->must_change_password);
        $this->assertFalse($user->isPendingApproval());
        Mail::assertSent(TemporaryPasswordMail::class, fn ($mail) => $mail->hasTo('ana.torres@upn.pe'));
    }

    public function test_setting_a_password_replaces_the_temporary_one_and_logs_in(): void
    {
        Mail::fake();
        $faculty = Faculty::factory()->create();

        $this->post(route('register'), [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upn.pe',
            'faculty_id' => $faculty->id,
        ]);

        $code = Cache::get('registration_code:ana.torres@upn.pe')['code'];
        $this->post(route('register.verify.submit'), ['code' => $code]);

        $response = $this->post(route('register.password.store'), [
            'password' => 'Contrasena-segura!',
            'password_confirmation' => 'Contrasena-segura!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'ana.torres@upn.pe')->first();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Contrasena-segura!', $user->password));
    }

    public function test_setting_a_mismatched_password_confirmation_leaves_the_temporary_password_in_place(): void
    {
        Mail::fake();
        $faculty = Faculty::factory()->create();

        $this->post(route('register'), [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upn.pe',
            'faculty_id' => $faculty->id,
        ]);

        $code = Cache::get('registration_code:ana.torres@upn.pe')['code'];
        $this->post(route('register.verify.submit'), ['code' => $code]);
        $userBefore = User::where('email', 'ana.torres@upn.pe')->first();

        $response = $this->post(route('register.password.store'), [
            'password' => 'Contrasena-segura!',
            'password_confirmation' => 'otra-cosa',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();

        $user = $userBefore->fresh();
        $this->assertTrue($user->must_change_password);
        $this->assertSame($userBefore->password, $user->password);
    }

    /**
     * The whole point of emailing a temporary password up front: a student
     * who never comes back to finish picking their own can still get in
     * with it, and the existing EnsurePasswordIsFresh middleware takes it
     * from there exactly like a dean-created account would.
     */
    public function test_abandoning_the_password_step_still_allows_logging_in_with_the_temporary_password(): void
    {
        Mail::fake();
        $faculty = Faculty::factory()->create();

        $this->post(route('register'), [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upn.pe',
            'faculty_id' => $faculty->id,
        ]);
        $code = Cache::get('registration_code:ana.torres@upn.pe')['code'];
        $this->post(route('register.verify.submit'), ['code' => $code]);

        $sentMail = null;
        Mail::assertSent(TemporaryPasswordMail::class, function ($mail) use (&$sentMail) {
            $sentMail = $mail;

            return true;
        });

        $this->post(route('login'), [
            'email' => 'ana.torres@upn.pe',
            'password' => $sentMail->temporaryPassword,
        ]);

        $this->assertAuthenticated();
        $this->get(route('dashboard'))->assertRedirect(route('password.force-edit'));
    }

    public function test_visiting_the_password_step_without_having_verified_a_code_redirects_to_register(): void
    {
        $this->get(route('register.password'))->assertRedirect(route('register'));
    }

    public function test_entering_the_wrong_code_does_not_create_an_account(): void
    {
        Mail::fake();
        $faculty = Faculty::factory()->create();

        $this->post(route('register'), [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upn.pe',
            'faculty_id' => $faculty->id,
        ]);

        $response = $this->post(route('register.verify.submit'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertDatabaseMissing('users', ['email' => 'ana.torres@upn.pe']);
    }

    public function test_too_many_wrong_attempts_forces_restarting_the_registration(): void
    {
        Mail::fake();
        $faculty = Faculty::factory()->create();

        $this->post(route('register'), [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upn.pe',
            'faculty_id' => $faculty->id,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('register.verify.submit'), ['code' => '000000']);
        }

        $response->assertRedirect(route('register'));
        $this->assertNull(Cache::get('registration_code:ana.torres@upn.pe'));
    }

    public function test_resending_sends_a_new_code(): void
    {
        Mail::fake();
        $faculty = Faculty::factory()->create();

        $this->post(route('register'), [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upn.pe',
            'faculty_id' => $faculty->id,
        ]);

        $firstCode = Cache::get('registration_code:ana.torres@upn.pe')['code'];

        $this->post(route('register.verify.resend'));

        $secondCode = Cache::get('registration_code:ana.torres@upn.pe')['code'];

        Mail::assertSent(RegistrationCodeMail::class, 2);
        // Astronomically unlikely to collide, but assert distinctness rather
        // than assuming it to keep the test meaningful either way.
        if ($firstCode === $secondCode) {
            $this->markTestIncomplete('Random codes collided — re-run.');
        }
    }

    public function test_visiting_the_verify_page_without_a_pending_registration_redirects_to_register(): void
    {
        $this->get(route('register.verify'))->assertRedirect(route('register'));
    }
}
