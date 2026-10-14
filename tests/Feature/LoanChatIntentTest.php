<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoanChatIntentTest extends TestCase
{
    use RefreshDatabase;

    private function fakeProvider(): AiProvider
    {
        return AiProvider::create([
            'name' => 'Fake provider',
            'driver' => 'openai_compatible',
            'base_url' => 'https://fake-ai.test/v1',
            'model' => 'fake-model',
            'api_key' => 'fake-key',
            'priority' => 0,
            'is_active' => true,
            'quota_period_days' => 1,
        ]);
    }

    private function toolCall(string $name, array $arguments): void
    {
        Http::fake([
            'fake-ai.test/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'call_1',
                            'type' => 'function',
                            'function' => ['name' => $name, 'arguments' => json_encode($arguments)],
                        ]],
                    ],
                ]],
            ], 200),
        ]);
    }

    public function test_loan_to_an_external_person_is_created_immediately_without_an_account(): void
    {
        $user = User::factory()->create();
        $this->fakeProvider();
        $this->toolCall('add_loan', ['direction' => 'prestado', 'amount' => 50, 'person_name' => 'Tienda Don José']);

        $response = $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'le presté 50 a la tienda']);

        $response->assertOk();
        $this->assertDatabaseHas('loans', [
            'user_id' => $user->id,
            'person_name' => 'Tienda Don José',
            'person_user_id' => null,
            'status' => 'pendiente',
        ]);
    }

    public function test_loan_to_a_single_matching_real_account_links_it_and_notifies(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $borrower = User::factory()->create(['name' => 'Ana Torres']);
        $this->fakeProvider();
        $this->toolCall('add_loan', ['direction' => 'prestado', 'amount' => 50, 'person_name' => 'Ana Torres']);

        $response = $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'le presté 50 a Ana Torres']);

        $response->assertOk();
        $loan = Loan::where('user_id', $user->id)->first();
        $this->assertSame($borrower->id, $loan->person_user_id);
        $this->assertSame('pendiente_confirmacion', $loan->status);

        Notification::assertSentTo($borrower, \App\Notifications\LoanConfirmationNotification::class);
    }

    public function test_an_ambiguous_name_asks_to_pick_one_and_the_next_message_resolves_it(): void
    {
        $user = User::factory()->create();
        $ana1 = User::factory()->create(['name' => 'Ana Torres', 'email' => 'ana1@upn.pe']);
        $ana2 = User::factory()->create(['name' => 'Ana Gómez', 'email' => 'ana2@upn.pe']);
        $this->fakeProvider();
        $this->toolCall('add_loan', ['direction' => 'prestado', 'amount' => 50, 'person_name' => 'Ana']);

        $first = $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'le presté 50 a Ana']);
        $first->assertOk();
        $this->assertDatabaseMissing('loans', ['user_id' => $user->id]);
        $firstReply = $first->json('reply');
        $this->assertStringContainsString('Ana Torres', $firstReply);
        $this->assertStringContainsString('Ana Gómez', $firstReply);

        // Candidates are listed alphabetically by name — "Ana Gómez" sorts
        // before "Ana Torres", so option 1 is $ana2.
        $second = $this->actingAs($user)->postJson(route('chat.send'), ['message' => '1']);
        $second->assertOk();

        $loan = Loan::where('user_id', $user->id)->first();
        $this->assertNotNull($loan);
        $this->assertSame($ana2->id, $loan->person_user_id);
    }

    public function test_invalid_amount_from_the_model_is_rejected(): void
    {
        $user = User::factory()->create();
        $this->fakeProvider();
        $this->toolCall('add_loan', ['direction' => 'prestado', 'amount' => 'no-es-numero', 'person_name' => 'Ana']);

        $response = $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'le presté a Ana']);

        $response->assertJsonFragment(['reply' => 'Necesito un monto válido (mayor a 0) para el préstamo.']);
        $this->assertDatabaseMissing('loans', ['user_id' => $user->id]);
    }
}
