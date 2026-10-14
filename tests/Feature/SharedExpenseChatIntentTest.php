<?php

namespace Tests\Feature;

use App\Models\AiProvider;
use App\Models\SharedExpense;
use App\Models\SharedExpenseParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SharedExpenseChatIntentTest extends TestCase
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

    public function test_creates_a_shared_expense_with_one_participant_and_notifies_them(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $luis = User::factory()->create(['name' => 'Luis Ramos']);
        $this->fakeProvider();
        $this->toolCall('add_shared_expense', [
            'description' => 'Uber al aeropuerto',
            'total_amount' => 100,
            'my_share' => 40,
            'participants' => [['name' => 'Luis Ramos', 'amount_owed' => 60]],
        ]);

        $response = $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'dividamos el Uber con Luis']);

        $response->assertOk();
        $this->assertDatabaseHas('shared_expenses', [
            'user_id' => $user->id,
            'description' => 'Uber al aeropuerto',
            'total_amount' => 100,
            'my_share' => 40,
        ]);
        $this->assertDatabaseHas('shared_expense_participants', [
            'user_id' => $luis->id,
            'amount_owed' => 60,
            'status' => 'pendiente',
        ]);
        $this->assertDatabaseHas('expenses', ['user_id' => $user->id, 'amount' => 40]);

        Notification::assertSentTo($luis, \App\Notifications\SharedExpenseInviteNotification::class);
    }

    public function test_creates_with_multiple_participants(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['name' => 'Luis Ramos']);
        User::factory()->create(['name' => 'Ana Torres']);
        $this->fakeProvider();
        $this->toolCall('add_shared_expense', [
            'description' => 'Pizza del grupo',
            'total_amount' => 90,
            'my_share' => 30,
            'participants' => [
                ['name' => 'Luis Ramos', 'amount_owed' => 30],
                ['name' => 'Ana Torres', 'amount_owed' => 30],
            ],
        ]);

        $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'dividamos la pizza entre tres']);

        $shared = SharedExpense::where('user_id', $user->id)->first();
        $this->assertSame(2, SharedExpenseParticipant::where('shared_expense_id', $shared->id)->count());
    }

    public function test_an_unknown_person_name_is_rejected_with_a_clear_message(): void
    {
        $user = User::factory()->create();
        $this->fakeProvider();
        $this->toolCall('add_shared_expense', [
            'description' => 'Almuerzo',
            'total_amount' => 50,
            'my_share' => 25,
            'participants' => [['name' => 'Alguien Que No Existe', 'amount_owed' => 25]],
        ]);

        $response = $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'dividamos el almuerzo']);

        $response->assertOk();
        $this->assertStringContainsString('No encontré ninguna cuenta', $response->json('reply'));
        $this->assertDatabaseMissing('shared_expenses', ['user_id' => $user->id]);
    }

    public function test_an_ambiguous_person_name_is_rejected_listing_the_matches(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['name' => 'Ana Torres', 'email' => 'ana1@upn.pe']);
        User::factory()->create(['name' => 'Ana Gómez', 'email' => 'ana2@upn.pe']);
        $this->fakeProvider();
        $this->toolCall('add_shared_expense', [
            'description' => 'Almuerzo',
            'total_amount' => 50,
            'my_share' => 25,
            'participants' => [['name' => 'Ana', 'amount_owed' => 25]],
        ]);

        $response = $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'dividamos el almuerzo con Ana']);

        $reply = $response->json('reply');
        $this->assertStringContainsString('Ana Torres', $reply);
        $this->assertStringContainsString('Ana Gómez', $reply);
        $this->assertDatabaseMissing('shared_expenses', ['user_id' => $user->id]);
    }

    public function test_mismatched_amounts_are_rejected_before_creating_anything(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['name' => 'Luis Ramos']);
        $this->fakeProvider();
        $this->toolCall('add_shared_expense', [
            'description' => 'Uber',
            'total_amount' => 100,
            'my_share' => 40,
            'participants' => [['name' => 'Luis Ramos', 'amount_owed' => 50]],
        ]);

        $response = $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'dividamos el Uber con Luis']);

        $this->assertStringContainsString('debe sumar el total', $response->json('reply'));
        $this->assertDatabaseMissing('shared_expenses', ['user_id' => $user->id]);
    }

    public function test_naming_yourself_as_a_participant_is_treated_as_not_found(): void
    {
        // PersonSearchService excludes the requesting user from its own
        // results, so there's nothing special to detect here — it's
        // indistinguishable from any other unmatched name.
        $user = User::factory()->create(['name' => 'Yo Mismo']);
        $this->fakeProvider();
        $this->toolCall('add_shared_expense', [
            'description' => 'Uber',
            'total_amount' => 100,
            'my_share' => 50,
            'participants' => [['name' => 'Yo Mismo', 'amount_owed' => 50]],
        ]);

        $response = $this->actingAs($user)->postJson(route('chat.send'), ['message' => 'dividamos el Uber']);

        $this->assertStringContainsString('No encontré ninguna cuenta', $response->json('reply'));
        $this->assertDatabaseMissing('shared_expenses', ['user_id' => $user->id]);
    }
}
