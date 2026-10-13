<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single payment a participant logged toward their share of a shared
 * expense — the ledger that SharedExpenseParticipant::debtTimeline() walks
 * day by day to figure out how far behind (if at all) they are.
 */
class SharedExpenseContribution extends Model
{
    protected $fillable = ['participant_id', 'amount', 'date', 'note'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(SharedExpenseParticipant::class, 'participant_id');
    }
}
