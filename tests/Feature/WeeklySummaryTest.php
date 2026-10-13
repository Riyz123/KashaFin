<?php

namespace Tests\Feature;

use App\Console\Commands\SendWeeklySummaries;
use App\Mail\WeeklySummaryMail;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class WeeklySummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_opted_in_students_receive_the_weekly_summary(): void
    {
        Mail::fake();

        $student = User::factory()->create();
        Expense::create(['user_id' => $student->id, 'amount' => 20, 'date' => now(), 'description' => 'Almuerzo']);

        $this->artisan(SendWeeklySummaries::class)->assertSuccessful();

        Mail::assertSent(WeeklySummaryMail::class, fn ($mail) => $mail->hasTo($student->email));
    }

    public function test_students_who_opted_out_do_not_receive_it(): void
    {
        Mail::fake();

        $student = User::factory()->create();
        $student->settings->update(['notify_weekly_summary_by_email' => false]);

        $this->artisan(SendWeeklySummaries::class)->assertSuccessful();

        Mail::assertNotSent(WeeklySummaryMail::class);
    }
}
