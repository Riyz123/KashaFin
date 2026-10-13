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
 * build) are smoothed into a daily rate from up to the last 4 weeks of
 * history — documented here so the formula is easy to revisit without a
 * schema change.
 *
 * The smoothing window shrinks for a brand-new account: dividing by a flat
 * 28 days when the account only has, say, 5 days of real activity would
 * dilute the daily rate far below what actually happened those 5 days,
 * making the projection look artificially safe (or risky) until the
 * account "ages into" the full window.
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
        $windowStart = $this->smoothingWindowStart($user, $today);
        $smoothingDays = $this->smoothingDays($windowStart, $today);

        $variableDailyRate = (float) Income::query()->forUser($user)
            ->variable()
            ->between($windowStart, $today)
            ->sum('amount') / $smoothingDays;

        $expenseDailyRate = (float) $user->expenses()
            ->between($windowStart, $today)
            ->sum('amount') / $smoothingDays;

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

    /**
     * Exposes the exact rates and window used to smooth the projection, so
     * the student (or the student debugging with support) can see why a
     * number looks the way it does instead of just trusting the final
     * balance.
     *
     * @return array{window_days: int, variable_daily_rate: float, expense_daily_rate: float, upcoming_fixed: Collection}
     */
    public function smoothingBreakdown(User $user, int $days = 15): array
    {
        $today = Carbon::today();
        $windowStart = $this->smoothingWindowStart($user, $today);
        $smoothingDays = $this->smoothingDays($windowStart, $today);

        $variableDailyRate = (float) Income::query()->forUser($user)
            ->variable()
            ->between($windowStart, $today)
            ->sum('amount') / $smoothingDays;

        $expenseDailyRate = (float) $user->expenses()
            ->between($windowStart, $today)
            ->sum('amount') / $smoothingDays;

        return [
            'window_days' => $smoothingDays,
            'variable_daily_rate' => round($variableDailyRate, 2),
            'expense_daily_rate' => round($expenseDailyRate, 2),
            'upcoming_fixed' => $this->projectFixedOccurrences($user, $today, $days),
        ];
    }

    /**
     * Caps the smoothing window to the account's real history: a new
     * account only has data since its earliest income/expense, so the
     * window shrinks to that instead of always spanning the full 28 days.
     */
    private function smoothingWindowStart(User $user, Carbon $today): Carbon
    {
        $capped = $today->copy()->subDays(self::SMOOTHING_WINDOW_DAYS);

        $earliest = collect([
            Income::query()->forUser($user)->min('date'),
            $user->expenses()->min('date'),
        ])->filter()->map(fn ($date) => Carbon::parse($date)->startOfDay())->min();

        if ($earliest === null || $earliest->lessThanOrEqualTo($capped)) {
            return $capped;
        }

        return $earliest;
    }

    /**
     * Carbon 3's diffInDays() returns a precise (fractional) float by
     * default when the two instants aren't both exactly midnight — floor
     * and cast to int so the divisor (and the "window_days" shown to the
     * student) is always a whole number of days.
     */
    private function smoothingDays(Carbon $windowStart, Carbon $today): int
    {
        return max(1, (int) floor($windowStart->diffInDays($today)));
    }

    public function lowestPoint(Collection $series): array
    {
        if ($series->isEmpty()) {
            return ['date' => null, 'balance' => 0.0];
        }

        return $series->sortBy('balance')->first();
    }

    /**
     * A brand new account with no incomes/expenses yet projects a flat S/ 0
     * balance, which trivially falls below any positive threshold — that's
     * not a real liquidity risk, just an empty account, so there's nothing
     * meaningful to warn about until the student has actually logged
     * something.
     */
    public function isBelowThreshold(User $user): bool
    {
        if (! $user->incomes()->exists() && ! $user->expenses()->exists()) {
            return false;
        }

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
