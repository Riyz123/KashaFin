<?php

namespace App\Notifications;

use App\Models\SharedExpenseParticipant;
use Illuminate\Notifications\Notification;

class SharedExpenseInviteNotification extends Notification
{
    public function __construct(private SharedExpenseParticipant $participant) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->participant->loadMissing('sharedExpense.user');
        $shared = $this->participant->sharedExpense;

        return [
            'participant_id' => $this->participant->id,
            'shared_expense_id' => $shared->id,
            'message' => "{$shared->user->name} te agregó al gasto compartido \"{$shared->description}\" — te corresponde S/ ".
                number_format((float) $this->participant->amount_owed, 2).'.',
        ];
    }
}
