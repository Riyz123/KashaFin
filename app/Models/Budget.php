<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'period_month',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function spentAmount(): float
    {
        return (float) Expense::query()
            ->where('user_id', $this->user_id)
            ->where('category_id', $this->category_id)
            ->whereBetween('date', [
                $this->period_month->copy()->startOfMonth()->toDateString(),
                $this->period_month->copy()->endOfMonth()->toDateString(),
            ])
            ->sum('amount');
    }

    public function getPercentConsumedAttribute(): float
    {
        $amount = (float) $this->amount;

        if ($amount <= 0) {
            return 0.0;
        }

        return round(($this->spentAmount() / $amount) * 100, 1);
    }

    public function getIsOverWarningThresholdAttribute(): bool
    {
        return $this->percent_consumed >= 90;
    }
}
