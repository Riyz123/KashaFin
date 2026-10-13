<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * F014: informal tracking of money lent to or borrowed from another
 * person. The other side can optionally be linked to a real KashaFin
 * account (person_user_id, searched the same way as shared-expense
 * participants), but doesn't have to be — an external person or entity
 * (a store, a bank, a family member) is just a free-text person_name with
 * no linked account. Deliberately NOT wired into LiquidityProjectionService:
 * these are debts between people, not the student's own income/expense, so
 * they stay out of the balance projection math.
 */
class Loan extends Model
{
    protected $fillable = [
        'user_id', 'person_name', 'person_user_id', 'direction', 'amount', 'date', 'due_date', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'due_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function personUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'person_user_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pendiente';
    }

    public function markPaid(): void
    {
        $this->update(['status' => 'pagado']);
    }
}
