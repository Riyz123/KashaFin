<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'currency',
        'theme',
        'week_start_day',
        'liquidity_threshold',
        'notify_low_liquidity_by_email',
        'starting_balance',
    ];

    protected function casts(): array
    {
        return [
            'week_start_day' => 'integer',
            'liquidity_threshold' => 'decimal:2',
            'starting_balance' => 'decimal:2',
            'notify_low_liquidity_by_email' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getCurrencySymbolAttribute(): string
    {
        return match ($this->currency) {
            'USD' => '$',
            default => 'S/',
        };
    }

    public function weekStartCarbonConstant(): int
    {
        return $this->week_start_day;
    }
}
