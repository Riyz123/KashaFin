<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SharedExpenseParticipant extends Model
{
    protected $fillable = ['shared_expense_id', 'name', 'amount_owed', 'is_paid'];

    protected function casts(): array
    {
        return [
            'amount_owed' => 'decimal:2',
            'is_paid' => 'boolean',
        ];
    }

    public function sharedExpense(): BelongsTo
    {
        return $this->belongsTo(SharedExpense::class);
    }
}
