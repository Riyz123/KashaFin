<?php

namespace App\Services;

use App\Models\Income;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Projects the student's available balance forward.
 *
 * Fixed ("fijo") incomes are projected on their exact scheduled dates by
 * walking each template's frequency forward from next_occurrence_date.
 * Variable incomes and all expenses (which have no recurring flag in this
 * build) are smoothed into a daily rate from the last 4 weeks of history —
 * documented here so the formula is easy to revisit without a schema change.
 */
class LiquidityProjectionService
{
    private const SMOOTHING_WINDOW_DAYS = 28;

    public function currentBalance(User $user): float
    {
        $startingBalance = (float) $user->settings->starting_balance;
        $today = Carbon::today();

        $incomeTotal = (float) Income::query()->forUser($user)
            ->where('date', '<=', $today->toDateString())
            ->sum('amount');

        $expenseTotal = (float) $user->expenses()
            ->where('date', '<=', $today->toDateString())
            ->sum('amount');

        return $startingBalance + $incomeTotal - $expenseTotal;
    }

    public function dailySeries(User $user, int $days = 15): Collection
    {
        $today = Carbon::today();
        $windowStart = $today->copy()->subDays(self::SMOOTHING_WINDOW_DAYS);

        $variableDailyRate = (float) Income::query()->forUser($user)
            ->variable()
            ->between($windowStart, $today)
            ->sum('amount') / self::SMOOTHING_WINDOW_DAYS;

        $expenseDailyRate = (float) $user->expenses()
            ->between($windowStart, $today)
            ->sum('amount') / self::SMOOTHING_WINDOW_DAYS;

        $fixedOccurrences = $this->projectFixedOccurrences($user, $today, $days);

        $balance = $this->currentBalance($user);
        $series = collect();

        for ($offset = 1; $offset <= $days; $offset++) {
            $date = $today->copy()->addDays($offset);

            $balance += $variableDailyRate - $expenseDailyRate;
            $balance += $fixedOccurrences->get($date->toDateString(), 0.0);

            $series->push([
                'date' => $date->toDateString(),
                'balance' => round($balance, 2),
            ]);
        }

        return $series;
    }

    public function sevenDayProjection(User $user): Collection
    {
        return $this->dailySeries($user, 15)->take(7);
    }

    public function fifteenDayProjection(User $user): Collection
    {
        return $this->dailySeries($user, 15);
    }

    public function lowestPoint(Collection $series): array
    {
        if ($series->isEmpty()) {
            return ['date' => null, 'balance' => 0.0];
        }

        return $series->sortBy('balance')->first();
    }

    public function isBelowThreshold(User $user): bool
    {
        $threshold = (float) $user->settings->liquidity_threshold;

        return $this->sevenDayProjection($user)->min('balance') < $threshold;
    }

    /**
     * Walk each active fixed-income template forward from its next
     * occurrence date, stepping by its frequency, until the horizon is
     * exceeded. Returns a [date-string => total amount landing that day].
     */
    private function projectFixedOccurrences(User $user, Carbon $today, int $days): Collection
    {
        $horizon = $today->copy()->addDays($days);
        $occurrences = collect();

        Income::query()->forUser($user)
            ->fixed()
            ->whereNull('parent_income_id')
            ->whereNotNull('next_occurrence_date')
            ->get()
            ->each(function (Income $template) use ($occurrences, $horizon) {
                $date = Carbon::parse($template->next_occurrence_date);

                while ($date->lte($horizon)) {
                    $key = $date->toDateString();
                    $occurrences->put($key, $occurrences->get($key, 0.0) + (float) $template->amount);
                    $date = $template->nextOccurrenceAfter($date);
                }
            });

        return $occurrences;
    }
}
