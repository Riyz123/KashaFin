<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'faculty_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDean(): bool
    {
        return $this->role === 'decano';
    }

    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isDean();
    }

    /**
     * Self-registered students start with approved_at = null and an
     * unusable random password — they can't log in until staff approves
     * them (see FacultyController/UserController@approve). Accounts
     * created directly by staff (dean/admin, CSV import) are born
     * approved, so this is never true for them.
     */
    public function isPendingApproval(): bool
    {
        return $this->role === 'estudiante' && $this->approved_at === null;
    }

    public function isOnline(): bool
    {
        return $this->last_seen_at !== null && $this->last_seen_at->greaterThan(now()->subMinutes(5));
    }

    /**
     * Shared by <x-presence-indicator> (first paint) and the live-refresh
     * endpoint it polls afterward, so the wording can't drift between the
     * two.
     */
    public function presenceLabel(): string
    {
        if ($this->isOnline()) {
            return 'En línea';
        }

        if ($this->last_seen_at) {
            return 'Última vez '.$this->last_seen_at->diffForHumans();
        }

        return 'Nunca ha entrado';
    }

    /**
     * Called on every request by TrackLastSeen, but only actually writes
     * when it's been a while — a plain "touch every request" would mean a
     * write query per page load for every active user, which is wasteful on
     * shared hosting and pointless precision nobody needs for an "online"
     * indicator with a 5-minute window.
     */
    public function markSeen(): void
    {
        if ($this->last_seen_at !== null && $this->last_seen_at->greaterThan(now()->subMinutes(2))) {
            return;
        }

        $this->forceFill(['last_seen_at' => now()])->saveQuietly();
    }

    /**
     * Whether $this (acting as staff) is allowed to manage $target's account
     * (toggle active / delete). A master admin can manage any non-admin
     * account; a dean is limited to students within their own faculty.
     */
    public function canManage(User $target): bool
    {
        if ($target->isAdmin()) {
            return false;
        }

        if ($this->isAdmin()) {
            return true;
        }

        return $this->isDean() && $target->role === 'estudiante' && $target->faculty_id === $this->faculty_id;
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(UserSetting::class);
    }

    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Checked by ChatController before anything else — while this is
     * non-null, a human is replying live inside this student's own chat
     * widget, so the AI (and the "hablar con un asesor" detection itself)
     * must stay out of the way entirely.
     */
    public function activeLiveSupportRequest(): ?SupportRequest
    {
        return SupportRequest::where('user_id', $this->id)
            ->where('status', 'en_atencion')
            ->first();
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function savingsGoals(): HasMany
    {
        return $this->hasMany(SavingsGoal::class);
    }

    public function liquidityAlerts(): HasMany
    {
        return $this->hasMany(LiquidityAlert::class);
    }

    public function chatMessages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function chatState(): HasOne
    {
        return $this->hasOne(ChatState::class);
    }

    public function supportRequests(): HasMany
    {
        return $this->hasMany(SupportRequest::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function sharedExpenses(): HasMany
    {
        return $this->hasMany(SharedExpense::class);
    }

    public function sharedExpenseParticipations(): HasMany
    {
        return $this->hasMany(SharedExpenseParticipant::class);
    }
}
