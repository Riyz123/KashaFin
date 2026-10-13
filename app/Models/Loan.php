<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * F014: informal tracking of money lent to or borrowed from another
 * person (not necessarily a KashaFin account — just a name). Deliberately
 * NOT wired into LiquidityProjectionService: these are debts between
 * people, not the student's own income/expense, so they stay out of the
 * balance projection math.
 */
class Loan extends Model
{
    protected $fillable = [
        'user_id', 'person_name', 'direction', 'amount', 'date', 'due_date', 'status', 'notes',
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

    public function isPending(): bool
    {
        return $this->status === 'pendiente';
    }

    public function markPaid(): void
    {
        $this->update(['status' => 'pagado']);
    }
}
