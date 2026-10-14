<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanConfirmationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnreadNotificationsEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function notifyBorrower(User $lender, User $borrower): void
    {
        $loan = Loan::create([
            'user_id' => $lender->id, 'person_name' => 'Ana', 'person_user_id' => $borrower->id,
            'direction' => 'prestado', 'amount' => 50, 'date' => now(), 'status' => 'pendiente_confirmacion',
        ]);
        $borrower->notify(new LoanConfirmationNotification($loan));
    }

    public function test_the_endpoint_reports_the_unread_count_and_a_link(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $this->notifyBorrower($lender, $borrower);

        $response = $this->actingAs($borrower)->getJson(route('notifications.unread'));

        $response->assertOk();
        $response->assertJson(['count' => 1]);
        $response->assertJsonPath('items.0.url', route('loans.index'));
    }

    public function test_visiting_the_notifications_page_marks_them_read_and_clears_the_count(): void
    {
        $lender = User::factory()->create();
        $borrower = User::factory()->create();
        $this->notifyBorrower($lender, $borrower);

        $this->actingAs($borrower)->get(route('notifications.index'))->assertOk();

        $response = $this->actingAs($borrower)->getJson(route('notifications.unread'));
        $response->assertJson(['count' => 0]);
    }

    public function test_a_user_with_no_notifications_sees_a_zero_count(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('notifications.unread'));

        $response->assertJson(['count' => 0, 'items' => []]);
    }
}
