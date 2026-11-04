<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Expense extends Model
{
    use LogsActivity;

    // Trazabilidad: solo se registra crear/eliminar, nunca editar — el
    // usuario pidió explícitamente "lo que se hace o se borra", no un
    // historial de cada edición de campo.
    protected static $doNotRecordEvents = ['updated'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('expense')
            ->setDescriptionForEvent(fn (string $event) => ($event === 'created' ? 'Registró' : 'Eliminó').
                ' un gasto de S/ '.number_format((float) $this->amount, 2).
                ($this->category?->name ? " en {$this->category->name}" : ''));
    }

    protected $fillable = [
        'user_id',
        'category_id',
        'parent_expense_id',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'parent_expense_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(Expense::class, 'parent_expense_id');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
    }

    public function scopeFixed(Builder $query): Builder
    {
        return $query->where('type', 'fijo');
    }

    public function scopeVariable(Builder $query): Builder
    {
        return $query->where('type', 'variable');
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
