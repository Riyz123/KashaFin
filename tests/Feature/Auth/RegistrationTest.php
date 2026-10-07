<?php

namespace Tests\Feature\Auth;

use App\Models\Faculty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $faculty = Faculty::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@upn.edu.pe',
            'password' => 'password',
            'password_confirmation' => 'password',
            'faculty_id' => $faculty->id,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_registration_is_rejected_for_non_upn_emails(): void
    {
        $faculty = Faculty::factory()->create();

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'faculty_id' => $faculty->id,
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }
}
