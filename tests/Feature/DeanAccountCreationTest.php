<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeanAccountCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_deans_email_must_also_be_institutional(): void
    {
        $admin = User::factory()->admin()->create();
        $faculty = Faculty::factory()->create();

        $response = $this->actingAs($admin)->post(route('admin.faculties.deans.store', $faculty), [
            'name' => 'Decano Nuevo',
            'email' => 'decano@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame(0, User::where('role', 'decano')->count());
    }

    public function test_a_dean_with_an_uppercase_institutional_email_is_normalized_and_accepted(): void
    {
        $admin = User::factory()->admin()->create();
        $faculty = Faculty::factory()->create();

        $this->actingAs($admin)->post(route('admin.faculties.deans.store', $faculty), [
            'name' => 'Decano Nuevo',
            'email' => 'D00012345@upn.pe',
            'password' => 'password123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['email' => 'd00012345@upn.pe', 'role' => 'decano']);
    }
}
