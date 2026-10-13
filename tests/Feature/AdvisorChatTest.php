<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvisorChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_asking_to_talk_to_an_advisor_starts_a_request_then_the_next_message_files_it_and_notifies_staff(): void
    {
        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create(['faculty_id' => $faculty->id]);

        $response = $this->actingAs($student)->postJson(route('chat.send'), ['message' => 'quiero hablar con un asesor']);
        $response->assertOk();
        $this->assertStringContainsString('Cuéntame brevemente', $response->json('reply'));

        $response = $this->postJson(route('chat.send'), ['message' => 'no me deja registrar un gasto de mi beca']);
        $response->assertOk();
        $this->assertStringContainsString('se comunicará contigo', $response->json('reply'));

        $supportRequest = SupportRequest::first();
        $this->assertNotNull($supportRequest);
        $this->assertSame('asesor', $supportRequest->type);
        $this->assertSame($student->id, $supportRequest->user_id);
        $this->assertSame('pendiente', $supportRequest->status);

        $this->assertSame(1, $dean->notifications()->count());
        $this->assertSame(1, $admin->notifications()->count());
    }

    public function test_cancelling_an_in_progress_advisor_request_does_not_create_one(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->postJson(route('chat.send'), ['message' => 'necesito hablar con un asesor'])->assertOk();
        $this->postJson(route('chat.send'), ['message' => 'cancelar'])->assertOk();

        $this->assertSame(0, SupportRequest::count());
    }

    public function test_a_dean_can_only_see_and_attend_support_requests_from_their_own_faculty(): void
    {
        $facultyA = Faculty::factory()->create();
        $facultyB = Faculty::factory()->create();
        $deanA = User::factory()->create(['role' => 'decano', 'faculty_id' => $facultyA->id]);
        $studentA = User::factory()->create(['faculty_id' => $facultyA->id]);
        $studentB = User::factory()->create(['faculty_id' => $facultyB->id]);

        $requestA = SupportRequest::create(['user_id' => $studentA->id, 'type' => 'asesor', 'message' => 'hola']);
        $requestB = SupportRequest::create(['user_id' => $studentB->id, 'type' => 'asesor', 'message' => 'hola']);

        $this->actingAs($deanA);

        $this->get(route('admin.support.index'))->assertSee($studentA->name)->assertDontSee($studentB->name);

        $this->patch(route('admin.support.attend', $requestB))->assertForbidden();
        $this->patch(route('admin.support.attend', $requestA))->assertRedirect();

        $this->assertSame('atendido', $requestA->refresh()->status);
    }
}
