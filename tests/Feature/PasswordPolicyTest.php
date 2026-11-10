<?php

namespace Tests\Feature;

use App\Models\Faculty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * App-wide password rule (AppServiceProvider::boot()): min 6, at least one
 * uppercase, one lowercase, one special character — checked here through
 * the self-registration password step, since every other password-setting
 * path (reset, forced change, profile update, dean creation) shares the
 * exact same Password::defaults() rule.
 */
class PasswordPolicyTest extends TestCase
{
    use RefreshDatabase;

    private function reachPasswordStep(): void
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
    }

    public function test_rejects_a_password_shorter_than_six_characters(): void
    {
        $this->reachPasswordStep();

        $response = $this->post(route('register.password.store'), [
            'password' => 'Ab1!x',
            'password_confirmation' => 'Ab1!x',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_rejects_a_password_with_no_uppercase_letter(): void
    {
        $this->reachPasswordStep();

        $response = $this->post(route('register.password.store'), [
            'password' => 'minuscula!',
            'password_confirmation' => 'minuscula!',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_rejects_a_password_with_no_lowercase_letter(): void
    {
        $this->reachPasswordStep();

        $response = $this->post(route('register.password.store'), [
            'password' => 'MAYUSCULA!',
            'password_confirmation' => 'MAYUSCULA!',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_rejects_a_password_with_no_special_character(): void
    {
        $this->reachPasswordStep();

        $response = $this->post(route('register.password.store'), [
            'password' => 'SinEspecial1',
            'password_confirmation' => 'SinEspecial1',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_accepts_a_password_meeting_every_rule(): void
    {
        $this->reachPasswordStep();

        $response = $this->post(route('register.password.store'), [
            'password' => 'Valida1!',
            'password_confirmation' => 'Valida1!',
        ]);

        $response->assertRedirect(route('dashboard'));
    }
}
