<?php

namespace App\Services;

use App\Mail\InactivityReminderMail;
use App\Models\User;
use App\Notifications\InactivityReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class InactivityReminderService
{
    private const THRESHOLD_DAYS = 3;

    /**
     * Notifies $user if they haven't logged an expense in 3+ days, throttled
     * to once every 3 days so it doesn't nag daily once the threshold hits.
     */
    public function checkAndNotify(User $user): bool
    {
        $lastExpenseDate = $user->expenses()->max('date');
        $reference = $lastExpenseDate ? Carbon::parse($lastExpenseDate) : $user->created_at;
        $daysInactive = $reference->diffInDays(now());

        if ($daysInactive < self::THRESHOLD_DAYS) {
            return false;
        }

        $recentlyNotified = $user->notifications()
            ->where('type', InactivityReminderNotification::class)
            ->where('created_at', '>=', now()->subDays(self::THRESHOLD_DAYS))
            ->exists();

        if ($recentlyNotified) {
            return false;
        }

        $user->notify(new InactivityReminderNotification($daysInactive));

        if ($user->settings->notify_inactivity_by_email) {
            Mail::to($user)->send(new InactivityReminderMail($user, $daysInactive));
        }

        return true;
    }
}
