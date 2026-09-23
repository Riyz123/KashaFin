<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Income extends Model
{
    protected $fillable = [
        'user_id',
        'parent_income_id',
        'amount',
        'date',
        'description',
        'type',
        'frequency',
        'next_occurrence_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'next_occurrence_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Income::class, 'parent_income_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(Income::class, 'parent_income_id');
    }

    public function scopeFixed(Builder $query): Builder
    {
        return $query->where('type', 'fijo');
    }

    public function scopeVariable(Builder $query): Builder
    {
        return $query->where('type', 'variable');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
    }

    public function nextOccurrenceAfter(Carbon $date): Carbon
    {
        return match ($this->frequency) {
            'semanal' => $date->copy()->addWeek(),
            'quincenal' => $date->copy()->addDays(15),
            'mensual' => $date->copy()->addMonthNoOverflow(),
            default => $date->copy()->addMonthNoOverflow(),
        };
    }
}
