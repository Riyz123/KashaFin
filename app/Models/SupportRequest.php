<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A student asking to talk to a human advisor (via the chatbot's "hablar con
 * un asesor" flow) or reporting a simple incidence (from the help page).
 * Both are the same shape — a message that needs a staff member's attention
 * — so they share one table, distinguished only by `type`.
 *
 * `asesor` requests go through three states: pendiente (waiting for staff
 * to pick it up) -> en_atencion (a staff member is live-replying inside
 * the student's own chat widget right now, which pauses the AI for that
 * student — see User::activeLiveSupportRequest()) -> atendido (closed).
 * `incidencia` requests never enter en_atencion — they go straight from
 * pendiente to atendido via markAttended(), same as before this existed.
 */
class SupportRequest extends Model
{
    protected $fillable = ['user_id', 'type', 'message', 'status', 'attended_by_id', 'attended_at'];

    protected function casts(): array
    {
        return [
            'attended_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attended_by_id');
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->oldest('id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pendiente';
    }

    public function isBeingAttendedLive(): bool
    {
        return $this->status === 'en_atencion';
    }

    public function isClosed(): bool
    {
        return $this->status === 'atendido';
    }

    /**
     * Staff picks up a pending "asesor" request and starts replying live,
     * inside the student's own chat widget. Seeds the thread with the
     * student's original message (if not already there) so everything —
     * the original ask and the back-and-forth that follows — lives in one
     * place instead of the original `message` column being a separate,
     * disconnected thing from the conversation.
     */
    public function startLiveAttention(User $staff): void
    {
        $this->update([
            'status' => 'en_atencion',
            'attended_by_id' => $staff->id,
            'attended_at' => now(),
        ]);

        if ($this->chatMessages()->doesntExist()) {
            ChatMessage::create([
                'user_id' => $this->user_id,
                'role' => 'user',
                'content' => $this->message,
                'support_request_id' => $this->id,
            ]);
        }
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
     * Used when escalating/handing off a case (a decano to administración,
     * or an admin to the student's decano) — releases it back to
     * "pendiente" so whoever receives it picks it up fresh via the normal
     * flow (no new status needed for this). Who gets notified about the
     * handoff is decided by the caller.
     */
    public function releaseBackToPending(): void
    {
        $this->update([
            'status' => 'pendiente',
            'attended_by_id' => null,
            'attended_at' => null,
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

    /**
     * Deliberately NOT User::canManage() — that one refuses to manage any
     * admin-role account at all (it's meant for account-level actions like
     * deactivating/deleting a user), which would wrongly 403 a master
     * admin handling a support request filed by another admin account (or
     * their own, e.g. while testing the feature themselves). A support
     * request isn't account management, so the rule here mirrors
     * scopeVisibleTo() instead: any admin can handle any request, a dean
     * only requests from their own faculty.
     */
    public function canBeManagedBy(User $actor): bool
    {
        if ($actor->isAdmin()) {
            return true;
        }

        return $actor->isDean() && $this->user->faculty_id === $actor->faculty_id;
    }
}
