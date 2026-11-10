<?php

namespace Tests\Feature\Auth;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_verify_their_email_then_set_a_password_and_are_logged_in(): void
    {
        Mail::fake();
        $faculty = Faculty::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@upn.pe',
            'faculty_id' => $faculty->id,
        ]);

        // Registering only starts the email-code verification step (see
        // RegistrationEmailVerificationTest) — the account doesn't exist yet.
        $this->assertGuest();
        $response->assertRedirect(route('register.verify'));
        $this->assertDatabaseMissing('users', ['email' => 'test@upn.pe']);

        $code = Cache::get('registration_code:test@upn.pe')['code'];
        $response = $this->post(route('register.verify.submit'), ['code' => $code]);

        // The code proves the inbox — the account exists from here on, with
        // a temporary password already emailed, no dean-approval wait.
        // Next comes picking a real password in its place.
        $this->assertGuest();
        $response->assertRedirect(route('register.password'));
        $this->assertDatabaseHas('users', ['email' => 'test@upn.pe']);

        $response = $this->post(route('register.password.store'), [
            'password' => 'Contrasena-segura!',
            'password_confirmation' => 'Contrasena-segura!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'test@upn.pe')->first();
        $this->assertNotNull($user);
        $this->assertFalse($user->isPendingApproval());
    }

    public function test_registration_is_rejected_for_non_upn_emails(): void
    {
        $faculty = Faculty::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'faculty_id' => $faculty->id,
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }
}
