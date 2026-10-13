<?php

namespace App\Notifications;

use App\Models\Income;
use Illuminate\Notifications\Notification;

class IncomeConfirmationNotification extends Notification
{
    public function __construct(private Income $income) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'income_confirmation',
            'income_id' => $this->income->id,
            'amount' => (float) $this->income->amount,
            'description' => $this->income->description,
            'message' => 'Registramos tu ingreso fijo de S/ '.number_format((float) $this->income->amount, 2).
                ' ('.($this->income->description ?: 'sin descripción').'). Si no lo recibiste, bórralo desde tu historial.',
        ];
    }
}
