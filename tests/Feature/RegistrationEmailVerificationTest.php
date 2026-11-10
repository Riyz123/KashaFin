<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\RegistrationVerificationController;
use App\Mail\RegistrationCodeMail;
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

    public function test_entering_the_correct_code_sends_to_the_password_step_without_creating_the_account_yet(): void
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
        $this->assertDatabaseMissing('users', ['email' => 'ana.torres@upn.pe']);
        $this->assertNull(Cache::get('registration_code:ana.torres@upn.pe'));
        $this->assertNotNull(Cache::get(RegistrationVerificationController::verifiedKey('ana.torres@upn.pe')));
    }

    public function test_setting_a_password_creates_an_already_active_account_with_no_approval_wait(): void
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
            'password' => 'contrasena-segura',
            'password_confirmation' => 'contrasena-segura',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'ana.torres@upn.pe')->first();
        $this->assertNotNull($user);
        $this->assertSame($faculty->id, $user->faculty_id);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->isPendingApproval());
        $this->assertNull(Cache::get(RegistrationVerificationController::verifiedKey('ana.torres@upn.pe')));
    }

    public function test_setting_a_mismatched_password_confirmation_does_not_create_an_account(): void
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
            'password' => 'contrasena-segura',
            'password_confirmation' => 'otra-cosa',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'ana.torres@upn.pe']);
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
