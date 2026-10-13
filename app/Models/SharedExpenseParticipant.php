<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A participant is always a real KashaFin account (searched/invited by the
 * creator), never a free-text name — this is for UPN students (and, once
 * they exist as a role, staff) splitting a real expense, not an informal
 * IOU with someone outside the app. They must accept the invitation before
 * their share counts toward anything.
 *
 * Paying off a share works two ways, both via the same contributions
 * ledger: a lump sum whenever (no daily_commitment set), or a daily
 * installment plan (daily_commitment set) — the latter gets day-by-day
 * shortfall tracking via debtTimeline(), separate from SavingsGoal's
 * progress-toward-a-future-target semantics: this is paying down a debt
 * for something that already happened, not saving for something ahead.
 */
class SharedExpenseParticipant extends Model
{
    protected $fillable = [
        'shared_expense_id', 'user_id', 'name', 'amount_owed',
        'status', 'daily_commitment', 'committed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_owed' => 'decimal:2',
            'daily_commitment' => 'decimal:2',
            'committed_at' => 'datetime',
        ];
    }

    public function sharedExpense(): BelongsTo
    {
        return $this->belongsTo(SharedExpense::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(SharedExpenseContribution::class, 'participant_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pendiente';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'aceptado';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rechazado';
    }

    public function accept(?float $dailyCommitment = null): void
    {
        $this->update([
            'status' => 'aceptado',
            'daily_commitment' => $dailyCommitment,
            'committed_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update(['status' => 'rechazado']);
    }

    public function totalPaid(): float
    {
        return (float) $this->contributions()->sum('amount');
    }

    public function remaining(): float
    {
        return max(0.0, (float) $this->amount_owed - $this->totalPaid());
    }

    public function isSettled(): bool
    {
        return $this->remaining() <= 0.0;
    }

    /**
     * Only meaningful with a daily_commitment: walks every day since
     * committed_at, tracking the cumulative amount that *should* have been
     * paid by now (capped at amount_owed) against what was *actually* paid
     * (contributions grouped by date), to find the current shortfall and
     * the earliest day it's been outstanding since — "debes S/ 2 desde
     * hace 3 días" — plus a day-by-day breakdown for traceability of
     * exactly which days fell short. Resets the "since" date the moment
     * they fully catch up, even if they fall behind again later.
     *
     * @return array{shortfall: float, since: ?Carbon, days: array<int, array{date: Carbon, expected: float, paid: float, shortfall: float}>}
     */
    public function debtTimeline(): array
    {
        if (! $this->daily_commitment || ! $this->committed_at || $this->isSettled()) {
            return ['shortfall' => 0.0, 'since' => null, 'days' => []];
        }

        $dailyRate = (float) $this->daily_commitment;
        $totalOwed = (float) $this->amount_owed;

        $paidByDate = $this->contributions()->get()
            ->groupBy(fn (SharedExpenseContribution $c) => $c->date->toDateString())
            ->map(fn ($rows) => (float) $rows->sum('amount'));

        $cursor = $this->committed_at->copy()->startOfDay();
        $today = Carbon::today();

        // Safety cap so a very old committed_at can never turn this into a
        // long-running loop — a year of daily tracking is already far more
        // than this feature is meant to cover.
        $horizon = $cursor->copy()->addDays(366);
        if ($today->gt($horizon)) {
            $today = $horizon;
        }

        $cumulativeExpected = 0.0;
        $cumulativePaid = 0.0;
        $since = null;
        $days = [];

        while ($cursor->lte($today)) {
            $cumulativeExpected = min($cumulativeExpected + $dailyRate, $totalOwed);
            $cumulativePaid += $paidByDate->get($cursor->toDateString(), 0.0);

            $dayShortfall = round($cumulativeExpected - $cumulativePaid, 2);

            if ($dayShortfall > 0) {
                $since ??= $cursor->copy();
                $days[] = [
                    'date' => $cursor->copy(),
                    'expected' => round($cumulativeExpected, 2),
                    'paid' => round($cumulativePaid, 2),
                    'shortfall' => $dayShortfall,
                ];
            } else {
                $since = null;
            }

            $cursor->addDay();
        }

        return [
            'shortfall' => max(0.0, round($cumulativeExpected - $cumulativePaid, 2)),
            'since' => $since,
            'days' => $days,
        ];
    }
}
