<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidityAlert extends Model
{
    protected $fillable = [
        'user_id',
        'projected_balance',
        'threshold',
        'channel',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'projected_balance' => 'decimal:2',
            'threshold' => 'decimal:2',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
