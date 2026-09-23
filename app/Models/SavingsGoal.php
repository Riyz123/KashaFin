<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsGoal extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'target_amount',
        'target_date',
        'current_amount',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'current_amount' => 'decimal:2',
            'target_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(GoalContribution::class);
    }

    public function getProgressPercentAttribute(): float
    {
        $target = (float) $this->target_amount;

        if ($target <= 0) {
            return 0.0;
        }

        return min(100.0, round(((float) $this->current_amount / $target) * 100, 1));
    }

    public function addContribution(float $amount, Carbon $date, ?string $note = null): GoalContribution
    {
        $contribution = $this->contributions()->create([
            'user_id' => $this->user_id,
            'amount' => $amount,
            'date' => $date->toDateString(),
            'note' => $note,
        ]);

        $this->current_amount = (float) $this->current_amount + $amount;

        if ($this->current_amount >= (float) $this->target_amount && $this->status === 'active') {
            $this->status = 'completed';
            $this->completed_at = now();
        }

        $this->save();

        return $contribution;
    }
}
