<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanAmortization extends Model
{
    protected $fillable = [
        'loan_id', 'amount', 'status', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'resolved_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pendiente';
    }
}
