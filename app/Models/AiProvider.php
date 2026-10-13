<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class AiProvider extends Model
{
    protected $fillable = [
        'name',
        'driver',
        'base_url',
        'model',
        'api_key',
        'priority',
        'is_active',
        'is_exhausted',
        'requests_used',
        'quota_limit',
        'quota_period_days',
        'period_reset_at',
    ];

    protected function casts(): array
    {
        return [
            'api_key' => 'encrypted',
            'is_active' => 'boolean',
            'is_exhausted' => 'boolean',
            'requests_used' => 'integer',
            'quota_limit' => 'integer',
            'quota_period_days' => 'integer',
            'period_reset_at' => 'datetime',
        ];
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class);
    }

    /**
     * Surfaced in the admin panel so there's a visible reason *why* a
     * provider isn't being used, instead of the admin only finding out via
     * the generic "no hay ninguna IA disponible" chat fallback.
     */
    public function latestUsageLog(): HasOne
    {
        return $this->hasOne(AiUsageLog::class)->latestOfMany();
    }

    /**
     * Secondary sort by `id` as a tiebreaker: with the same priority (e.g.
     * everyone left at the form's default of 0), ORDER BY priority alone
     * has no guaranteed order between ties — MySQL can return them in a
     * different order from one query to the next, which looks like the
     * rotation is "using all 3 at once" instead of sticking to one until
     * it's actually exhausted.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority')->orderBy('id');
    }

    /**
     * Whether this provider can be used right now. Lazily rolls the usage
     * counter over to a fresh period if it has elapsed — there is no
     * separate scheduled job for this, so it must happen unconditionally
     * here, not just when the provider happens to already be flagged
     * exhausted/over-quota. A provider well under its daily limit would
     * otherwise never roll over at all: `requests_used` would just keep
     * accumulating forever and `period_reset_at` would stay frozen at
     * whatever it was computed as on the very first call, since nothing
     * would ever re-evaluate it.
     */
    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $this->rolloverPeriodIfElapsed();

        if ($this->is_exhausted) {
            return false;
        }

        if ($this->quota_limit && $this->requests_used >= $this->quota_limit) {
            return false;
        }

        return true;
    }

    private function rolloverPeriodIfElapsed(): void
    {
        if ($this->period_reset_at === null) {
            $this->period_reset_at = Carbon::now()->addDays($this->quota_period_days);
            $this->save();

            return;
        }

        if (Carbon::now()->greaterThanOrEqualTo($this->period_reset_at)) {
            $this->requests_used = 0;
            $this->is_exhausted = false;
            $this->period_reset_at = Carbon::now()->addDays($this->quota_period_days);
            $this->save();
        }
    }

    /**
     * Manual recovery for the admin panel: if a provider got stuck
     * `is_exhausted` from a misclassified error (not a real quota/rate-limit
     * response), there's otherwise no way to use it again before
     * `period_reset_at` — waiting on that isn't acceptable when the key
     * actually works.
     */
    public function reactivate(): void
    {
        $this->update([
            'is_exhausted' => false,
            'requests_used' => 0,
            'period_reset_at' => Carbon::now()->addDays($this->quota_period_days),
        ]);
    }

    /**
     * $exhausted should only be true for genuine quota/rate-limit errors —
     * a transient network or parsing error shouldn't permanently sideline a
     * provider that still has quota left.
     *
     * $retryAfterSeconds, when the provider itself told us how long to
     * wait (a 429's Retry-After header, or Gemini's RetryInfo detail),
     * overrides the admin-configured quota_period_days for THIS reset.
     * Without it, a 429 that was actually just a per-minute throttle would
     * otherwise sideline the provider for the full period (often a whole
     * day) instead of the handful of seconds it really needed.
     */
    public function recordUsage(bool $success, bool $exhausted = false, ?string $errorMessage = null, ?int $retryAfterSeconds = null): void
    {
        if ($this->period_reset_at === null) {
            $this->period_reset_at = Carbon::now()->addDays($this->quota_period_days);
        }

        $this->requests_used++;

        if ($exhausted) {
            $this->is_exhausted = true;

            if ($retryAfterSeconds !== null) {
                $this->period_reset_at = Carbon::now()->addSeconds($retryAfterSeconds);
            }
        }

        $this->save();

        $this->usageLogs()->create([
            'user_id' => auth()->id(),
            'status' => $success ? 'success' : ($exhausted ? 'quota_exceeded' : 'error'),
            'error_message' => $errorMessage,
        ]);
    }

    public function getUsagePercentAttribute(): ?float
    {
        if (! $this->quota_limit) {
            return null;
        }

        return round(min(100, ($this->requests_used / $this->quota_limit) * 100), 1);
    }

    public function getMaskedApiKeyAttribute(): string
    {
        $key = (string) $this->api_key;

        if (strlen($key) <= 8) {
            return str_repeat('•', max(strlen($key), 4));
        }

        return substr($key, 0, 4).str_repeat('•', 8).substr($key, -4);
    }
}
