<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatState extends Model
{
    protected $fillable = [
        'user_id',
        'pending_intent',
        'pending_data',
    ];

    protected function casts(): array
    {
        return [
            'pending_data' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function clear(): void
    {
        $this->update([
            'pending_intent' => null,
            'pending_data' => null,
        ]);
    }
}
