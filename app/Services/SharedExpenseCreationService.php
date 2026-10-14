<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\SharedExpense;
use App\Models\User;
use App\Notifications\SharedExpenseInviteNotification;

/**
 * Shared by SharedExpenseController::store() and (soon) SharedExpenseChatIntent,
 * so the web form and the chatbot create a shared expense through the exact
 * same rule: only my_share becomes a real Expense, every participant starts
 * as "pendiente" and gets notified, never trusted/counted until they accept.
 *
 * $data shape: description, total_amount, my_share, date, category_id
 * (nullable), participants (array of ['user_id', 'amount_owed', 'daily_commitment' (nullable)]).
 */
class SharedExpenseCreationService
{
    public function create(User $user, array $data): SharedExpense
    {
        $myShare = (float) ($data['my_share'] ?? 0);

        $expense = null;

        if ($myShare > 0) {
            $expense = Expense::create([
                'user_id' => $user->id,
                'category_id' => $data['category_id'] ?? null,
                'amount' => $myShare,
                'date' => $data['date'],
                'description' => $data['description'].' (mi parte)',
            ]);
        }

        $sharedExpense = SharedExpense::create([
            'user_id' => $user->id,
            'category_id' => $data['category_id'] ?? null,
            'expense_id' => $expense?->id,
            'description' => $data['description'],
            'total_amount' => $data['total_amount'],
            'my_share' => $myShare,
            'date' => $data['date'],
        ]);

        foreach ($data['participants'] as $participantData) {
            $invitedUser = User::find($participantData['user_id']);

            $participant = $sharedExpense->participants()->create([
                'user_id' => $invitedUser->id,
                'name' => $invitedUser->name,
                'amount_owed' => $participantData['amount_owed'],
                'daily_commitment' => $participantData['daily_commitment'] ?? null,
                'status' => 'pendiente',
            ]);

            $invitedUser->notify(new SharedExpenseInviteNotification($participant));
        }

        return $sharedExpense;
    }
}
