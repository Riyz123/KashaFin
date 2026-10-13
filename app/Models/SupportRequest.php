<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student asking to talk to a human advisor (via the chatbot's "hablar con
 * un asesor" flow) or reporting a simple incidence (from the help page).
 * Both are the same shape — a message that needs a staff member's attention
 * — so they share one table, distinguished only by `type`.
 */
class SupportRequest extends Model
{
    protected $fillable = ['user_id', 'type', 'message', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attended_by_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pendiente';
    }

    public function markAttended(User $staff): void
    {
        $this->update([
            'status' => 'atendido',
            'attended_by_id' => $staff->id,
            'attended_at' => now(),
        ]);
    }

    /**
     * Visible to the master admin (everything) or a dean (only requests
     * from students in their own faculty) — same shape as
     * User::canManage().
     */
    public function scopeVisibleTo($query, User $actor)
    {
        if ($actor->isAdmin()) {
            return $query;
        }

        return $query->whereHas('user', fn ($q) => $q->where('faculty_id', $actor->faculty_id));
    }
}
