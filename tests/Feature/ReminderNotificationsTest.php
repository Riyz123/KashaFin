<?php

namespace Tests\Feature;

use App\Mail\InactivityReminderMail;
use App\Mail\IncomeConfirmationMail;
use App\Models\Expense;
use App\Models\Income;
use App\Models\User;
use App\Notifications\InactivityReminderNotification;
use App\Services\InactivityReminderService;
use App\Services\RecurringIncomeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReminderNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private function createExpense(User $user, string $date): Expense
    {
        return Expense::create([
            'user_id' => $user->id,
            'category_id' => null,
            'amount' => 20,
            'date' => $date,
            'description' => 'Gasto de prueba',
        ]);
    }

    private function createFixedIncomeTemplate(User $user, array $overrides = []): Income
    {
        return Income::create(array_merge([
            'user_id' => $user->id,
            'amount' => 450,
            'date' => now()->subMonth()->toDateString(),
            'description' => 'Beca universitaria',
            'type' => 'fijo',
            'frequency' => 'mensual',
            'next_occurrence_date' => now()->subDay()->toDateString(),
        ], $overrides));
    }

    public function test_a_student_with_no_expenses_in_3_plus_days_gets_notified_and_emailed(): void
    {
        Mail::fake();

        $student = User::factory()->create();
        $this->createExpense($student, now()->subDays(5)->toDateString());

        (new InactivityReminderService)->checkAndNotify($student);

        $this->assertSame(1, $student->notifications()->where('type', InactivityReminderNotification::class)->count());
        Mail::assertSent(InactivityReminderMail::class, 1);
    }

    public function test_a_student_with_a_recent_expense_is_not_notified(): void
    {
        $student = User::factory()->create();
        $this->createExpense($student, now()->toDateString());

        $notified = (new InactivityReminderService)->checkAndNotify($student);

        $this->assertFalse($notified);
        $this->assertSame(0, $student->notifications()->count());
    }

    public function test_the_inactivity_reminder_is_throttled_and_not_resent_immediately(): void
    {
        Mail::fake();

        $student = User::factory()->create();
        $this->createExpense($student, now()->subDays(4)->toDateString());

        $service = new InactivityReminderService;
        $this->assertTrue($service->checkAndNotify($student));
        $this->assertFalse($service->checkAndNotify($student));

        $this->assertSame(1, $student->notifications()->count());
    }

    public function test_disabling_the_email_toggle_still_notifies_in_app_but_skips_the_email(): void
    {
        Mail::fake();

        $student = User::factory()->create();
        $student->settings->update(['notify_inactivity_by_email' => false]);
        $this->createExpense($student, now()->subDays(5)->toDateString());

        (new InactivityReminderService)->checkAndNotify($student);

        $this->assertSame(1, $student->notifications()->count());
        Mail::assertNotSent(InactivityReminderMail::class);
    }

    public function test_a_due_fixed_income_occurrence_notifies_the_student_to_confirm_it(): void
    {
        Mail::fake();

        $student = User::factory()->create();
        $this->createFixedIncomeTemplate($student);

        (new RecurringIncomeService)->generateDueOccurrences();

        $this->assertSame(2, Income::where('user_id', $student->id)->count());
        $this->assertSame(1, $student->notifications()->count());
        Mail::assertSent(IncomeConfirmationMail::class, 1);
    }
}
