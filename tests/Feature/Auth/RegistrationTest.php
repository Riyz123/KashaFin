<?php

namespace Tests\Feature\Auth;

use App\Models\Faculty;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_and_are_logged_in_immediately(): void
    {
        $faculty = Faculty::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@upn.pe',
            'faculty_id' => $faculty->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'test@upn.pe')->first();
        $this->assertNotNull($user);
        $this->assertSame('estudiante', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->approved_at);
        $this->assertFalse($user->isPendingApproval());
    }

    public function test_registration_is_rejected_for_non_upn_emails(): void
    {
        $faculty = Faculty::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'faculty_id' => $faculty->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_registration_requires_a_confirmed_password(): void
    {
        $faculty = Faculty::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@upn.pe',
            'faculty_id' => $faculty->id,
            'password' => 'password123',
            'password_confirmation' => 'not-the-same',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'test@upn.pe']);
    }
}
