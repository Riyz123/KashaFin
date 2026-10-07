<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('priority');
    }

    /**
     * Whether this provider can be used right now. Lazily recovers the
     * provider (resets its usage counter) if its quota period has elapsed —
     * there is no separate scheduled job for this.
     */
    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->is_exhausted || ($this->quota_limit && $this->requests_used >= $this->quota_limit)) {
            if ($this->period_reset_at && Carbon::now()->greaterThanOrEqualTo($this->period_reset_at)) {
                $this->requests_used = 0;
                $this->is_exhausted = false;
                $this->period_reset_at = Carbon::now()->addDays($this->quota_period_days);
                $this->save();

                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * $exhausted should only be true for genuine quota/rate-limit errors —
     * a transient network or parsing error shouldn't permanently sideline a
     * provider that still has quota left.
     */
    public function recordUsage(bool $success, bool $exhausted = false, ?string $errorMessage = null): void
    {
        if ($this->period_reset_at === null) {
            $this->period_reset_at = Carbon::now()->addDays($this->quota_period_days);
        }

        $this->requests_used++;

        if ($exhausted) {
            $this->is_exhausted = true;
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
