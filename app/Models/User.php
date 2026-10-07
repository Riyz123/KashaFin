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
}
