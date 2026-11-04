<?php

namespace Tests\Feature;

use App\Models\Loan;
use App\Models\User;
use App\Notifications\OverdueLoanReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotifyOverdueLoansTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_a_reminder_for_an_overdue_loan_that_was_never_notified(): void
    {
        $student = User::factory()->create();
        $loan = Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 20, 'date' => now()->subDays(10), 'due_date' => now()->subDay(), 'status' => 'pendiente',
        ]);

        $this->artisan('loans:notify-overdue')->assertSuccessful();

        $this->assertSame(1, $student->notifications()->where('type', OverdueLoanReminderNotification::class)->count());
        $this->assertNotNull($loan->fresh()->last_overdue_notified_at);
    }

    public function test_does_not_resend_before_3_days_have_passed(): void
    {
        $student = User::factory()->create();
        Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 20, 'date' => now()->subDays(10), 'due_date' => now()->subDay(), 'status' => 'pendiente',
            'last_overdue_notified_at' => now()->subDay(),
        ]);

        $this->artisan('loans:notify-overdue')->assertSuccessful();

        $this->assertSame(0, $student->notifications()->where('type', OverdueLoanReminderNotification::class)->count());
    }

    public function test_resends_once_3_days_have_passed_since_the_last_reminder(): void
    {
        $student = User::factory()->create();
        Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 20, 'date' => now()->subDays(10), 'due_date' => now()->subDays(5), 'status' => 'pendiente',
            'last_overdue_notified_at' => now()->subDays(3),
        ]);

        $this->artisan('loans:notify-overdue')->assertSuccessful();

        $this->assertSame(1, $student->notifications()->where('type', OverdueLoanReminderNotification::class)->count());
    }

    public function test_a_loan_that_is_not_overdue_gets_no_reminder(): void
    {
        $student = User::factory()->create();
        Loan::create([
            'user_id' => $student->id, 'person_name' => 'Tienda', 'direction' => 'recibido',
            'amount' => 20, 'date' => now(), 'due_date' => now()->addWeek(), 'status' => 'pendiente',
        ]);

        $this->artisan('loans:notify-overdue')->assertSuccessful();

        $this->assertSame(0, $student->notifications()->count());
    }
}
