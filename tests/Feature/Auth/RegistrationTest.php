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

    public function test_new_users_create_a_pending_request_instead_of_logging_in(): void
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

        // No password field at all — the account isn't usable until a dean
        // approves it and a real password gets generated and emailed.
        $this->assertGuest();
        $response->assertRedirect(route('login'));

        $user = User::where('email', 'test@upn.pe')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isPendingApproval());
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
