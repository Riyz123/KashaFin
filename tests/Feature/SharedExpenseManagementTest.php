<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\SharedExpense;
use App\Models\User;
use App\Notifications\SharedExpenseInviteNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SharedExpenseManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_shared_expense_only_registers_my_share_as_a_real_expense_and_invites_participants(): void
    {
        $student = User::factory()->create();
        $ana = User::factory()->create();
        $luis = User::factory()->create();

        $this->actingAs($student)->post(route('shared-expenses.store'), [
            'description' => 'Pizza para el grupo',
            'total_amount' => 60,
            'my_share' => 20,
            'date' => now()->toDateString(),
            'participants' => [
                ['user_id' => $ana->id, 'amount_owed' => 20],
                ['user_id' => $luis->id, 'amount_owed' => 20],
            ],
        ])->assertRedirect(route('shared-expenses.index'));

        $sharedExpense = SharedExpense::first();
        $this->assertNotNull($sharedExpense);
        $this->assertSame(2, $sharedExpense->participants()->count());
        $this->assertSame('pendiente', $sharedExpense->participants()->first()->status);
        $this->assertSame(1, Expense::where('user_id', $student->id)->count());
        $this->assertSame('20.00', Expense::first()->amount);
        $this->assertSame(1, $ana->notifications()->where('type', SharedExpenseInviteNotification::class)->count());
        $this->assertSame(1, $luis->notifications()->count());
    }

    public function test_the_totals_must_add_up_to_the_full_amount(): void
    {
        $student = User::factory()->create();
        $ana = User::factory()->create();

        $response = $this->actingAs($student)->post(route('shared-expenses.store'), [
            'description' => 'Pizza',
            'total_amount' => 60,
            'my_share' => 20,
            'date' => now()->toDateString(),
            'participants' => [
                ['user_id' => $ana->id, 'amount_owed' => 10],
            ],
        ]);

        $response->assertSessionHasErrors('total_amount');
        $this->assertSame(0, SharedExpense::count());
    }

    public function test_cannot_invite_yourself_as_a_participant(): void
    {
        $student = User::factory()->create();

        $response = $this->actingAs($student)->post(route('shared-expenses.store'), [
            'description' => 'Pizza',
            'total_amount' => 40,
            'my_share' => 20,
            'date' => now()->toDateString(),
            'participants' => [
                ['user_id' => $student->id, 'amount_owed' => 20],
            ],
        ]);

        $response->assertSessionHasErrors('participants');
        $this->assertSame(0, SharedExpense::count());
    }

    public function test_search_users_finds_matches_by_name_or_email_excluding_self_and_admins(): void
    {
        $student = User::factory()->create();
        $ana = User::factory()->create(['name' => 'Ana Torres', 'email' => 'ana.torres@upn.pe']);
        User::factory()->admin()->create(['name' => 'Ana Admin']);

        $response = $this->actingAs($student)->getJson(route('shared-expenses.search-users', ['q' => 'ana']));

        $response->assertOk();
        $emails = collect($response->json())->pluck('email');
        $this->assertTrue($emails->contains('ana.torres@upn.pe'));
        $this->assertSame(1, $emails->count());
    }

    public function test_an_invited_participant_must_accept_before_their_share_counts(): void
    {
        $student = User::factory()->create();
        $ana = User::factory()->create();

        $this->actingAs($student)->post(route('shared-expenses.store'), [
            'description' => 'Cena', 'total_amount' => 40, 'my_share' => 20, 'date' => now()->toDateString(),
            'participants' => [['user_id' => $ana->id, 'amount_owed' => 20]],
        ]);

        $shared = SharedExpense::first();
        $this->assertSame(0.0, $shared->fresh()->totalOwedPending());

        $participant = $shared->participants()->first();
        $this->actingAs($ana)->patch(route('shared-expenses.accept', $participant))->assertRedirect();

        $this->assertSame(20.0, $shared->fresh()->totalOwedPending());
    }

    public function test_only_the_invited_user_can_accept_their_own_invitation(): void
    {
        $student = User::factory()->create();
        $ana = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($student)->post(route('shared-expenses.store'), [
            'description' => 'Cena', 'total_amount' => 40, 'my_share' => 20, 'date' => now()->toDateString(),
            'participants' => [['user_id' => $ana->id, 'amount_owed' => 20]],
        ]);

        $participant = SharedExpense::first()->participants()->first();

        $this->actingAs($other)->patch(route('shared-expenses.accept', $participant))->assertForbidden();
    }

    public function test_an_accepted_participant_can_log_a_contribution_toward_their_share(): void
    {
        $student = User::factory()->create();
        $ana = User::factory()->create();

        $this->actingAs($student)->post(route('shared-expenses.store'), [
            'description' => 'Cena', 'total_amount' => 40, 'my_share' => 20, 'date' => now()->toDateString(),
            'participants' => [['user_id' => $ana->id, 'amount_owed' => 20]],
        ]);

        $participant = SharedExpense::first()->participants()->first();
        $participant->accept();

        $this->actingAs($ana)->post(route('shared-expenses.contributions.store', $participant), [
            'amount' => 12, 'date' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertSame(12.0, $participant->fresh()->totalPaid());
        $this->assertSame(8.0, $participant->fresh()->remaining());
        $this->assertFalse($participant->fresh()->isSettled());
    }

    public function test_debt_timeline_tracks_a_daily_shortfall_since_the_day_it_first_appeared(): void
    {
        $student = User::factory()->create();
        $ana = User::factory()->create();

        $this->actingAs($student)->post(route('shared-expenses.store'), [
            'description' => 'Plan compartido', 'total_amount' => 50, 'my_share' => 0, 'date' => now()->toDateString(),
            'participants' => [['user_id' => $ana->id, 'amount_owed' => 50, 'daily_commitment' => 5]],
        ]);

        $participant = SharedExpense::first()->participants()->first();
        $participant->accept(5);
        $participant->update(['committed_at' => now()->subDays(2)->startOfDay()]);

        // Day -2 (today - 2): paid only 3 of the 5 expected -> shortfall 2.
        $participant->contributions()->create(['amount' => 3, 'date' => now()->subDays(2)->toDateString()]);
        // Day -1: paid the full 5 for that day, but still 2 behind overall.
        $participant->contributions()->create(['amount' => 5, 'date' => now()->subDays(1)->toDateString()]);

        $timeline = $participant->fresh()->debtTimeline();

        // Today itself also accrues its own day's expected quota (unpaid
        // so far), so the running shortfall includes that on top of the
        // S/ 2 left over from day -2 — but it's still dated back to day
        // -2, the first day the shortfall ever appeared.
        $this->assertSame(7.0, $timeline['shortfall']);
        $this->assertNotNull($timeline['since']);
        $this->assertSame(now()->subDays(2)->toDateString(), $timeline['since']->toDateString());
        $this->assertNotEmpty($timeline['days']);
    }

    public function test_deleting_a_shared_expense_also_deletes_the_linked_personal_expense(): void
    {
        $student = User::factory()->create();
        $ana = User::factory()->create();

        $this->actingAs($student)->post(route('shared-expenses.store'), [
            'description' => 'Cena',
            'total_amount' => 40,
            'my_share' => 20,
            'date' => now()->toDateString(),
            'participants' => [['user_id' => $ana->id, 'amount_owed' => 20]],
        ]);

        $shared = SharedExpense::first();
        $this->actingAs($student)->delete(route('shared-expenses.destroy', $shared))->assertRedirect();

        $this->assertSame(0, SharedExpense::count());
        $this->assertSame(0, Expense::count());
    }
}
