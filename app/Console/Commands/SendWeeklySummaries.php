<?php

namespace App\Console\Commands;

use App\Mail\WeeklySummaryMail;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklySummaries extends Command
{
    protected $signature = 'reports:send-weekly-summary';

    protected $description = 'Email every opted-in active student their income/expense summary for the past 7 days';

    public function handle(ReportService $reports): int
    {
        $from = now()->subDays(6)->startOfDay();
        $to = now()->endOfDay();
        $count = 0;

        User::query()
            ->where('role', 'estudiante')
            ->where('is_active', true)
            ->whereNotNull('approved_at')
            ->whereHas('settings', fn ($query) => $query->where('notify_weekly_summary_by_email', true))
            ->with('settings')
            ->chunkById(50, function ($students) use ($reports, $from, $to, &$count) {
                foreach ($students as $student) {
                    Mail::to($student)->send(new WeeklySummaryMail(
                        $student,
                        $reports->incomeVsExpense($student, $from->copy(), $to->copy()),
                        $reports->expensesByCategory($student, $from->copy(), $to->copy()),
                        $from,
                        $to,
                    ));
                    $count++;
                }
            });

        $this->info("Sent {$count} weekly summary email(s).");

        return self::SUCCESS;
    }
}
