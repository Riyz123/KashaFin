<?php

namespace App\Services;

use App\Mail\IncomeConfirmationMail;
use App\Models\Income;
use App\Models\User;
use App\Notifications\IncomeConfirmationNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class RecurringIncomeService
{
    public function computeNextOccurrence(Income $template): Carbon
    {
        return $template->nextOccurrenceAfter(Carbon::parse($template->next_occurrence_date ?? $template->date));
    }

    /**
     * Clone every "fijo" income template whose next occurrence is due into a
     * real income row, and advance the template's next_occurrence_date.
     */
    public function generateDueOccurrences(): int
    {
        $today = Carbon::today();
        $generated = 0;

        Income::query()
            ->whereNull('parent_income_id')
            ->fixed()
            ->whereNotNull('next_occurrence_date')
            ->where('next_occurrence_date', '<=', $today->toDateString())
            ->each(function (Income $template) use (&$generated) {
                $dueDate = Carbon::parse($template->next_occurrence_date);

                $occurrence = Income::create([
                    'user_id' => $template->user_id,
                    'parent_income_id' => $template->id,
                    'amount' => $template->amount,
                    'date' => $dueDate->toDateString(),
                    'description' => $template->description,
                    'type' => 'fijo',
                    'frequency' => null,
                    'next_occurrence_date' => null,
                ]);

                $template->update([
                    'next_occurrence_date' => $template->nextOccurrenceAfter($dueDate)->toDateString(),
                ]);

                $this->notifyConfirmation($template->user, $occurrence);

                $generated++;
            });

        return $generated;
    }

    /**
     * RF39: ask the student to confirm an auto-generated fixed income
     * actually arrived — it's a guess based on the recurring schedule, not
     * a fact, so it's surfaced for review rather than trusted silently.
     */
    private function notifyConfirmation(User $user, Income $occurrence): void
    {
        $user->notify(new IncomeConfirmationNotification($occurrence));

        if ($user->settings?->notify_income_confirmation_by_email) {
            Mail::to($user)->send(new IncomeConfirmationMail($user, $occurrence));
        }
    }
}
