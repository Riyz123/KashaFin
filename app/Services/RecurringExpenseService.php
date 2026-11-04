<?php

namespace App\Services;

use App\Models\Expense;
use App\Notifications\ExpenseConfirmationNotification;
use Carbon\Carbon;

/**
 * Mirrors RecurringIncomeService for the "fijo" side of expenses (rent,
 * subscriptions): clones a due template into a real expense row and
 * advances the template's next_occurrence_date.
 */
class RecurringExpenseService
{
    public function computeNextOccurrence(Expense $template): Carbon
    {
        return $template->nextOccurrenceAfter(Carbon::parse($template->next_occurrence_date ?? $template->date));
    }

    public function generateDueOccurrences(): int
    {
        $today = Carbon::today();
        $generated = 0;

        Expense::query()
            ->whereNull('parent_expense_id')
            ->fixed()
            ->whereNotNull('next_occurrence_date')
            ->where('next_occurrence_date', '<=', $today->toDateString())
            ->each(function (Expense $template) use (&$generated) {
                $dueDate = Carbon::parse($template->next_occurrence_date);

                $occurrence = Expense::create([
                    'user_id' => $template->user_id,
                    'category_id' => $template->category_id,
                    'parent_expense_id' => $template->id,
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

                $template->user->notify(new ExpenseConfirmationNotification($occurrence));

                $generated++;
            });

        return $generated;
    }
}
