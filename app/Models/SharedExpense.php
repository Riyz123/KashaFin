<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * F015: an expense paid by the student but split with other KashaFin
 * accounts (institutional users, searched and invited — never a free-text
 * name). Only `my_share` becomes a real Expense row — the rest is money
 * owed back to the student by each participant, tracked separately so it's
 * never double counted as the student's own spending.
 */
class SharedExpense extends Model
{
    use LogsActivity;

    protected static $doNotRecordEvents = ['updated'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('shared_expense')
            ->setDescriptionForEvent(fn (string $event) => ($event === 'created' ? 'Creó' : 'Eliminó').
                " el gasto compartido \"{$this->description}\" por S/ ".number_format((float) $this->total_amount, 2));
    }

    protected $fillable = ['user_id', 'category_id', 'expense_id', 'description', 'total_amount', 'my_share', 'date'];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'my_share' => 'decimal:2',
            'date' => 'date',
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

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SharedExpenseParticipant::class);
    }

    /**
     * How much is still owed by accepted participants — pending/rejected
     * invitations don't count toward anything yet.
     */
    public function totalOwedPending(): float
    {
        return $this->participants
            ->where('status', 'aceptado')
            ->sum(fn (SharedExpenseParticipant $p) => $p->remaining());
    }
}
