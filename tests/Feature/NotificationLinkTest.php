<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanConfirmationNotification;
use App\Support\NotificationLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_loan_confirmation_links_to_loans(): void
    {
        $link = NotificationLink::for(['kind' => 'loan_confirmation', 'loan_id' => 1]);

        $this->assertSame(route('loans.index'), $link['url']);
    }

    public function test_loan_payment_confirmation_links_to_loans(): void
    {
        $link = NotificationLink::for(['kind' => 'loan_payment_confirmation', 'loan_id' => 1]);

        $this->assertSame(route('loans.index'), $link['url']);
    }

    public function test_income_confirmation_links_to_incomes(): void
    {
        $link = NotificationLink::for(['kind' => 'income_confirmation', 'income_id' => 1]);

        $this->assertSame(route('incomes.index'), $link['url']);
    }

    public function test_shared_expense_invite_links_to_shared_expenses(): void
    {
        $link = NotificationLink::for(['shared_expense_id' => 1, 'participant_id' => 1]);

        $this->assertSame(route('shared-expenses.index'), $link['url']);
    }

    public function test_unknown_kind_has_no_link(): void
    {
        $this->assertNull(NotificationLink::for(['kind' => 'inactivity', 'days_inactive' => 5]));
        $this->assertNull(NotificationLink::for([]));
    }

    public function test_the_notifications_page_renders_a_go_to_button_for_a_loan_confirmation(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $lender->id, 'person_name' => 'Ana', 'person_user_id' => $borrower->id,
            'direction' => 'prestado', 'amount' => 50, 'date' => now(), 'status' => 'pendiente_confirmacion',
        ]);
        $borrower->notify(new LoanConfirmationNotification($loan));

        $response = $this->actingAs($borrower)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee(route('loans.index'), false);
        $response->assertSee('Ir a préstamos');
    }
}
