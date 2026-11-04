<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Notifications\OverdueLoanReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NotifyOverdueLoans extends Command
{
    protected $signature = 'loans:notify-overdue';

    protected $description = 'Notify students every 3 days while a received debt stays overdue';

    public function handle(): int
    {
        $count = 0;

        Loan::query()
            ->where('direction', 'recibido')
            ->whereNotIn('status', ['pendiente_confirmacion', 'rechazado', 'pagado'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::today()->toDateString())
            ->get()
            ->filter(fn (Loan $loan) => $loan->isOverdue())
            ->filter(fn (Loan $loan) => $loan->last_overdue_notified_at === null
                || $loan->last_overdue_notified_at->lte(Carbon::now()->subDays(3)))
            ->each(function (Loan $loan) use (&$count) {
                $loan->user->notify(new OverdueLoanReminderNotification($loan));
                $loan->update(['last_overdue_notified_at' => Carbon::now()]);
                $count++;
            });

        $this->info("Sent {$count} overdue loan reminder(s).");

        return self::SUCCESS;
    }
}
