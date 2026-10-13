<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Notifications\Notification;

class LoanPaymentConfirmationNotification extends Notification
{
    public function __construct(private Loan $loan) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->loan->loadMissing('user');

        return [
            'kind' => 'loan_payment_confirmation',
            'loan_id' => $this->loan->id,
            'message' => "{$this->loan->user->name} dice que ya te pagó S/ ".number_format((float) $this->loan->amount, 2).
                '. Confirma si lo recibiste.',
        ];
    }
}
