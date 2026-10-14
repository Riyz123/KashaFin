<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * The "find a real KashaFin account by name/email" query, shared by the
 * shared-expense participant picker and the loan/shared-expense chat
 * intents — only real accounts can be linked (never a free-text name typed
 * straight into a chat message), and admins are never a valid match.
 */
class PersonSearchService
{
    public function search(User $excludingUser, string $query, int $limit = 8): Collection
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            return new Collection;
        }

        return User::query()
            ->where('id', '!=', $excludingUser->id)
            ->where('role', '!=', 'admin')
            ->where(fn ($q) => $q->where('name', 'like', "%{$query}%")->orWhere('email', 'like', "%{$query}%"))
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'email']);
    }
}
