<?php

namespace App\Notifications;

use App\Models\Expense;
use Illuminate\Notifications\Notification;

class ExpenseConfirmationNotification extends Notification
{
    public function __construct(private Expense $expense) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'expense_confirmation',
            'expense_id' => $this->expense->id,
            'amount' => (float) $this->expense->amount,
            'description' => $this->expense->description,
            'message' => 'Registramos tu gasto fijo de S/ '.number_format((float) $this->expense->amount, 2).
                ' ('.($this->expense->description ?: 'sin descripción').'). Si no lo pagaste, bórralo desde tu historial.',
        ];
    }
}
