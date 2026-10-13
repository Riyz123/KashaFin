<?php

namespace Tests\Feature;

use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_student_can_report_an_incident_from_the_help_page(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();

        $this->actingAs($student)
            ->post(route('help.store'), ['message' => 'No puedo subir mi comprobante de ingreso.'])
            ->assertRedirect(route('help.index'));

        $supportRequest = SupportRequest::first();
        $this->assertNotNull($supportRequest);
        $this->assertSame('incidencia', $supportRequest->type);
        $this->assertSame($student->id, $supportRequest->user_id);
        $this->assertSame(1, $admin->notifications()->count());
    }

    public function test_the_help_page_lists_the_students_own_recent_requests(): void
    {
        $student = User::factory()->create();
        SupportRequest::create(['user_id' => $student->id, 'type' => 'incidencia', 'message' => 'Algo falló']);

        $this->actingAs($student)
            ->get(route('help.index'))
            ->assertOk()
            ->assertSee('Algo falló');
    }
}
