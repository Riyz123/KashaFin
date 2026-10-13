<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\InactivityReminderService;
use Illuminate\Console\Command;

class SendInactivityReminders extends Command
{
    protected $signature = 'reminders:inactivity';

    protected $description = 'Notify active students who have not logged an expense in 3+ days';

    public function handle(InactivityReminderService $service): int
    {
        $count = 0;

        User::query()
            ->where('role', 'estudiante')
            ->where('is_active', true)
            ->whereNotNull('approved_at')
            ->chunkById(50, function ($students) use ($service, &$count) {
                foreach ($students as $student) {
                    if ($service->checkAndNotify($student)) {
                        $count++;
                    }
                }
            });

        $this->info("Sent {$count} inactivity reminder(s).");

        return self::SUCCESS;
    }
}
