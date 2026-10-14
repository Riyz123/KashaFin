<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordResetCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_requesting_a_code_for_a_real_account_emails_it(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'ana.torres@upn.pe']);

        $response = $this->post(route('password.email'), ['email' => $user->email]);

        $response->assertRedirect(route('password.verify'));
        Mail::assertSent(PasswordResetCodeMail::class, fn ($mail) => $mail->hasTo($user->email));
        $this->assertNotNull(Cache::get('password_reset_code:ana.torres@upn.pe'));
    }

    public function test_requesting_a_code_for_an_unknown_email_sends_nothing_but_still_redirects(): void
    {
        Mail::fake();

        $response = $this->post(route('password.email'), ['email' => 'nadie@upn.pe']);

        // Deliberately the same redirect as a real account — never reveals
        // whether an email is registered.
        $response->assertRedirect(route('password.verify'));
        Mail::assertNothingSent();
        $this->assertNull(Cache::get('password_reset_code:nadie@upn.pe'));
    }

    public function test_entering_the_correct_code_allows_setting_a_new_password(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'ana.torres@upn.pe', 'password' => Hash::make('old-password')]);

        $this->post(route('password.email'), ['email' => $user->email]);
        $code = Cache::get('password_reset_code:ana.torres@upn.pe')['code'];

        $verify = $this->post(route('password.verify.submit'), ['code' => $code]);
        $verify->assertRedirect(route('password.reset'));
        $this->assertNull(Cache::get('password_reset_code:ana.torres@upn.pe'));

        $reset = $this->post(route('password.store'), [
            'password' => 'nueva-contrasena-segura',
            'password_confirmation' => 'nueva-contrasena-segura',
        ]);

        $reset->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('nueva-contrasena-segura', $user->fresh()->password));
    }

    public function test_entering_the_wrong_code_does_not_allow_resetting(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'ana.torres@upn.pe']);
        $this->post(route('password.email'), ['email' => $user->email]);

        $response = $this->post(route('password.verify.submit'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');

        // Can't skip ahead to the reset form without verifying first.
        $this->get(route('password.reset'))->assertRedirect(route('password.request'));
    }

    public function test_cannot_reach_the_reset_form_without_verifying_a_code_first(): void
    {
        $this->get(route('password.reset'))->assertRedirect(route('password.request'));
        $this->post(route('password.store'), [
            'password' => 'algo-nuevo-123',
            'password_confirmation' => 'algo-nuevo-123',
        ])->assertRedirect(route('password.request'));
    }

    public function test_too_many_wrong_attempts_forces_requesting_a_new_code(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'ana.torres@upn.pe']);
        $this->post(route('password.email'), ['email' => $user->email]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('password.verify.submit'), ['code' => '000000']);
        }

        $response->assertRedirect(route('password.request'));
        $this->assertNull(Cache::get('password_reset_code:ana.torres@upn.pe'));
    }

    public function test_resending_sends_a_new_code(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'ana.torres@upn.pe']);
        $this->post(route('password.email'), ['email' => $user->email]);

        $this->post(route('password.verify.resend'));

        Mail::assertSent(PasswordResetCodeMail::class, 2);
    }

    public function test_resetting_clears_the_must_change_password_flag(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'ana.torres@upn.pe', 'must_change_password' => true]);
        $this->post(route('password.email'), ['email' => $user->email]);
        $code = Cache::get('password_reset_code:ana.torres@upn.pe')['code'];
        $this->post(route('password.verify.submit'), ['code' => $code]);

        $this->post(route('password.store'), [
            'password' => 'otra-contrasena-123',
            'password_confirmation' => 'otra-contrasena-123',
        ]);

        $this->assertFalse($user->fresh()->must_change_password);
    }
}
