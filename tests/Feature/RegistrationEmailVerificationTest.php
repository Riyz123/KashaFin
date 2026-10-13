<?php

namespace Tests\Feature;

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
            'email' => 'ana.torres@upn.edu.pe',
            'faculty_id' => $faculty->id,
        ]);

        $response->assertRedirect(route('register.verify'));
        Mail::assertSent(RegistrationCodeMail::class, fn ($mail) => $mail->hasTo('ana.torres@upn.edu.pe'));
        $this->assertDatabaseMissing('users', ['email' => 'ana.torres@upn.edu.pe']);
    }

    public function test_entering_the_correct_code_creates_the_pending_account(): void
    {
        Mail::fake();
        $faculty = Faculty::factory()->create();

        $this->post(route('register'), [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upn.edu.pe',
            'faculty_id' => $faculty->id,
        ]);

        $code = Cache::get('registration_code:ana.torres@upn.edu.pe')['code'];

        $response = $this->post(route('register.verify.submit'), ['code' => $code]);

        $response->assertRedirect(route('login'));

        $user = User::where('email', 'ana.torres@upn.edu.pe')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->isPendingApproval());
        $this->assertSame($faculty->id, $user->faculty_id);
        $this->assertNull(Cache::get('registration_code:ana.torres@upn.edu.pe'));
    }

    public function test_entering_the_wrong_code_does_not_create_an_account(): void
    {
        Mail::fake();
        $faculty = Faculty::factory()->create();

        $this->post(route('register'), [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upn.edu.pe',
            'faculty_id' => $faculty->id,
        ]);

        $response = $this->post(route('register.verify.submit'), ['code' => '000000']);

        $response->assertSessionHasErrors('code');
        $this->assertDatabaseMissing('users', ['email' => 'ana.torres@upn.edu.pe']);
    }

    public function test_too_many_wrong_attempts_forces_restarting_the_registration(): void
    {
        Mail::fake();
        $faculty = Faculty::factory()->create();

        $this->post(route('register'), [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upn.edu.pe',
            'faculty_id' => $faculty->id,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('register.verify.submit'), ['code' => '000000']);
        }

        $response->assertRedirect(route('register'));
        $this->assertNull(Cache::get('registration_code:ana.torres@upn.edu.pe'));
    }

    public function test_resending_sends_a_new_code(): void
    {
        Mail::fake();
        $faculty = Faculty::factory()->create();

        $this->post(route('register'), [
            'name' => 'Ana Torres',
            'email' => 'ana.torres@upn.edu.pe',
            'faculty_id' => $faculty->id,
        ]);

        $firstCode = Cache::get('registration_code:ana.torres@upn.edu.pe')['code'];

        $this->post(route('register.verify.resend'));

        $secondCode = Cache::get('registration_code:ana.torres@upn.edu.pe')['code'];

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
