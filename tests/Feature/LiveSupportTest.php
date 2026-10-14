<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Faculty;
use App\Models\SupportRequest;
use App\Models\User;
use App\Notifications\SupportRequestEscalatedNotification;
use App\Services\SupportRequestService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LiveSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_starting_live_attention_seeds_the_thread_with_the_original_message(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create();
        $request = (new SupportRequestService)->create($student, 'asesor', 'Necesito ayuda con mi presupuesto');

        $this->actingAs($admin)->patch(route('admin.support.start-live', $request))->assertRedirect();

        $request->refresh();
        $this->assertSame('en_atencion', $request->status);
        $this->assertSame($admin->id, $request->attended_by_id);

        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $student->id,
            'role' => 'user',
            'content' => 'Necesito ayuda con mi presupuesto',
            'support_request_id' => $request->id,
        ]);
    }

    public function test_an_incidencia_request_can_also_be_started_live(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create();
        $request = (new SupportRequestService)->create($student, 'incidencia', 'Hay un error en mi reporte');

        $this->actingAs($admin)->patch(route('admin.support.start-live', $request))->assertRedirect();

        $this->assertSame('en_atencion', $request->fresh()->status);
        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $student->id,
            'content' => 'Hay un error en mi reporte',
            'support_request_id' => $request->id,
        ]);
    }

    public function test_while_live_the_student_chat_never_calls_the_ai_and_just_stores_the_message(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create();
        $request = (new SupportRequestService)->create($student, 'asesor', 'Ayuda');
        $request->startLiveAttention($admin);

        Http::fake();

        $response = $this->actingAs($student)->postJson(route('chat.send'), ['message' => 'Hola, sigo esperando']);

        $response->assertOk();
        $response->assertExactJson(['reply' => null, 'live' => true]);

        Http::assertNothingSent();

        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $student->id,
            'role' => 'user',
            'content' => 'Hola, sigo esperando',
            'support_request_id' => $request->id,
        ]);
    }

    public function test_admin_reply_creates_an_advisor_message_and_student_poll_picks_it_up(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create();
        $request = (new SupportRequestService)->create($student, 'asesor', 'Ayuda');
        $request->startLiveAttention($admin);

        $this->actingAs($admin)->post(route('admin.support.reply', $request), [
            'content' => 'Claro, cuéntame qué pasó',
        ])->assertRedirect();

        $this->assertDatabaseHas('chat_messages', [
            'user_id' => $student->id,
            'role' => 'advisor',
            'content' => 'Claro, cuéntame qué pasó',
        ]);

        $poll = $this->actingAs($student)->getJson(route('live-support.poll', ['after_id' => 0]));

        $poll->assertOk();
        $poll->assertJson(['live' => true]);
        $poll->assertJsonFragment(['content' => 'Claro, cuéntame qué pasó']);
    }

    public function test_the_student_poll_never_returns_their_own_messages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create();
        $request = (new SupportRequestService)->create($student, 'asesor', 'Ayuda');
        $request->startLiveAttention($admin);

        ChatMessage::create(['user_id' => $student->id, 'role' => 'user', 'content' => 'mi propio mensaje', 'support_request_id' => $request->id]);

        $poll = $this->actingAs($student)->getJson(route('live-support.poll', ['after_id' => 0]));

        $poll->assertOk();
        $poll->assertDontSee('mi propio mensaje');
    }

    public function test_admin_poll_only_returns_the_students_messages_after_a_given_id(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create();
        $request = (new SupportRequestService)->create($student, 'asesor', 'Ayuda');
        $request->startLiveAttention($admin);

        $old = ChatMessage::create(['user_id' => $student->id, 'role' => 'user', 'content' => 'viejo', 'support_request_id' => $request->id]);
        $new = ChatMessage::create(['user_id' => $student->id, 'role' => 'user', 'content' => 'nuevo', 'support_request_id' => $request->id]);
        ChatMessage::create(['user_id' => $student->id, 'role' => 'advisor', 'content' => 'respuesta propia', 'support_request_id' => $request->id]);

        $response = $this->actingAs($admin)->getJson(route('admin.support.messages', ['supportRequest' => $request, 'after_id' => $old->id]));

        $response->assertOk();
        $response->assertJsonFragment(['content' => 'nuevo']);
        $response->assertDontSee('viejo');
        $response->assertDontSee('respuesta propia');
    }

    public function test_finalizing_closes_the_request_and_the_ai_resumes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create();
        $request = (new SupportRequestService)->create($student, 'asesor', 'Ayuda');
        $request->startLiveAttention($admin);

        $this->actingAs($admin)->patch(route('admin.support.finalize', $request))->assertRedirect();

        $this->assertSame('atendido', $request->fresh()->status);
        $this->assertNull($student->fresh()->activeLiveSupportRequest());

        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'Hola de nuevo']]]], 200)]);
        \App\Models\AiProvider::create([
            'name' => 'Fake', 'driver' => 'openai_compatible', 'base_url' => 'https://fake-ai.test/v1',
            'model' => 'fake', 'api_key' => 'x', 'priority' => 0, 'is_active' => true, 'quota_period_days' => 1,
        ]);

        $response = $this->actingAs($student)->postJson(route('chat.send'), ['message' => 'gracias']);

        $response->assertOk();
        $this->assertNotTrue($response->json('live'));
    }

    public function test_a_dean_cannot_start_live_attention_outside_their_faculty(): void
    {
        $facultyA = Faculty::factory()->create();
        $facultyB = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $facultyA->id]);
        $student = User::factory()->create(['faculty_id' => $facultyB->id]);
        $request = (new SupportRequestService)->create($student, 'asesor', 'Ayuda');

        $this->actingAs($dean)->patch(route('admin.support.start-live', $request))->assertForbidden();
    }

    /**
     * The real bug this guards against: an admin testing the feature by
     * filing a support request from their own admin account got a 403
     * trying to act on it, because the old authorization check
     * (User::canManage()) refuses to manage ANY admin-role account —
     * correct for account actions like deactivating a user, wrong here,
     * since handling a support ticket isn't account management.
     */
    public function test_an_admin_can_manage_a_support_request_filed_by_an_admin_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $request = (new SupportRequestService)->create($admin, 'asesor', 'Probando el flujo yo mismo');

        $this->actingAs($admin)->patch(route('admin.support.start-live', $request))->assertRedirect();
        $request->refresh();
        $this->assertSame('en_atencion', $request->status);

        $this->actingAs($admin)->patch(route('admin.support.finalize', $request))->assertRedirect();
        $this->assertSame('atendido', $request->fresh()->status);
    }

    public function test_the_statuses_endpoint_only_returns_requests_visible_to_the_caller(): void
    {
        $facultyA = Faculty::factory()->create();
        $facultyB = Faculty::factory()->create();
        $deanA = User::factory()->create(['role' => 'decano', 'faculty_id' => $facultyA->id]);
        $studentA = User::factory()->create(['faculty_id' => $facultyA->id]);
        $studentB = User::factory()->create(['faculty_id' => $facultyB->id]);
        $requestA = (new SupportRequestService)->create($studentA, 'asesor', 'Ayuda A');
        $requestB = (new SupportRequestService)->create($studentB, 'asesor', 'Ayuda B');

        $response = $this->actingAs($deanA)->getJson(route('admin.support.statuses', ['ids' => "{$requestA->id},{$requestB->id}"]));

        $response->assertOk();
        $response->assertJsonStructure([(string) $requestA->id => ['status', 'attended_by']]);
        $response->assertJsonMissingPath((string) $requestB->id);
    }

    public function test_a_dean_escalating_notifies_every_admin_and_releases_the_request(): void
    {
        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['faculty_id' => $faculty->id]);
        $request = (new SupportRequestService)->create($student, 'asesor', 'Esto se me escapa');
        $request->startLiveAttention($dean);

        $this->actingAs($dean)->patch(route('admin.support.escalate', $request))->assertRedirect();

        $request->refresh();
        $this->assertSame('pendiente', $request->status);
        $this->assertNull($request->attended_by_id);
        $this->assertSame(1, $otherAdmin->notifications()->where('type', SupportRequestEscalatedNotification::class)->count());
    }

    public function test_an_admin_escalating_notifies_the_students_dean(): void
    {
        $faculty = Faculty::factory()->create();
        $dean = User::factory()->create(['role' => 'decano', 'faculty_id' => $faculty->id]);
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['faculty_id' => $faculty->id]);
        $request = (new SupportRequestService)->create($student, 'incidencia', 'Problema de la facultad');

        $this->actingAs($admin)->patch(route('admin.support.escalate', $request))->assertRedirect();

        $this->assertSame('pendiente', $request->fresh()->status);
        $this->assertSame(1, $dean->notifications()->where('type', SupportRequestEscalatedNotification::class)->count());
    }

    public function test_an_admin_cannot_escalate_to_a_faculty_with_no_dean(): void
    {
        $faculty = Faculty::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $student = User::factory()->create(['faculty_id' => $faculty->id]);
        $request = (new SupportRequestService)->create($student, 'incidencia', 'Sin decano asignado');

        $this->actingAs($admin)->patch(route('admin.support.escalate', $request))->assertStatus(422);
    }
}
