<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Real UPN institutional emails use the student's code as the local part
 * (e.g. N00321428@upn.pe) — not lowercase. These must be accepted and
 * normalized, not rejected, across every place a student's email is taken.
 */
class UppercaseInstitutionalEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_registration_accepts_and_normalizes_an_uppercase_institutional_email(): void
    {
        Mail::fake();
        $faculty = Faculty::factory()->create();

        $response = $this->post(route('register'), [
            'name' => 'Estudiante Real',
            'email' => 'N00321428@upn.pe',
            'faculty_id' => $faculty->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionDoesntHaveErrors('email');
        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'n00321428@upn.pe']);
    }

    public function test_admin_manual_add_accepts_and_normalizes_an_uppercase_institutional_email(): void
    {
        Mail::fake();
        $admin = User::factory()->admin()->create();
        $faculty = Faculty::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Estudiante Real',
            'email' => 'N00321428@upn.pe',
            'faculty_id' => $faculty->id,
        ]);

        $response->assertSessionDoesntHaveErrors('email');
        $this->assertDatabaseHas('users', ['email' => 'n00321428@upn.pe']);
    }
}
