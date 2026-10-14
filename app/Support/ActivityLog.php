<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

/**
 * Query helper for "trazabilidad" — every create/delete event the 6 main
 * sections log via LogsActivity (see Expense/Income/Budget/SavingsGoal/
 * Loan/SharedExpense). This is personal to each student, never an admin
 * audit view over other people's data, so it's always scoped to the
 * currently authenticated user.
 */
class ActivityLog
{
    public static function forCurrentUser(?string $logName = null): Builder
    {
        return Activity::query()
            ->where('causer_type', User::class)
            ->where('causer_id', Auth::id())
            ->when($logName, fn (Builder $query) => $query->where('log_name', $logName))
            ->latest();
    }
}
