<?php

namespace App\Services;

use App\Mail\LowLiquidityAlertMail;
use App\Models\LiquidityAlert;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class LiquidityAlertService
{
    public function __construct(private LiquidityProjectionService $projection) {}

    /**
     * If the 7-day projection dips below the user's threshold, record a
     * dashboard alert and optionally queue an email. Automatic calls (e.g.
     * from the dashboard) are throttled to once/day; $force bypasses that
     * for an explicit "send now" action.
     */
    public function checkAndNotify(User $user, bool $force = false): ?LiquidityAlert
    {
        if (! $this->projection->isBelowThreshold($user)) {
            return null;
        }

        $alreadySentToday = $user->liquidityAlerts()
            ->whereDate('sent_at', now()->toDateString())
            ->exists();

        if ($alreadySentToday && ! $force) {
            return null;
        }

        $lowest = $this->projection->lowestPoint($this->projection->sevenDayProjection($user));
        $threshold = (float) $user->settings->liquidity_threshold;

        $alert = $user->liquidityAlerts()->create([
            'projected_balance' => $lowest['balance'],
            'threshold' => $threshold,
            'channel' => 'dashboard',
            'sent_at' => now(),
        ]);

        if ($user->settings->notify_low_liquidity_by_email) {
            Mail::to($user)->queue(new LowLiquidityAlertMail($user, (float) $lowest['balance'], $threshold));

            $user->liquidityAlerts()->create([
                'projected_balance' => $lowest['balance'],
                'threshold' => $threshold,
                'channel' => 'email',
                'sent_at' => now(),
            ]);
        }

        return $alert;
    }
}
