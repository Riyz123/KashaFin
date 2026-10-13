<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * F015: an expense paid by the student but split with other people
 * (tracked by name only, not necessarily KashaFin accounts). Only
 * `my_share` becomes a real Expense row — the rest is money owed back to
 * the student, tracked separately via participants so it's never double
 * counted as the student's own spending.
 */
class SharedExpense extends Model
{
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

    public function totalOwedPending(): float
    {
        return (float) $this->participants()->where('is_paid', false)->sum('amount_owed');
    }
}
