<?php

namespace App\Notifications;

use App\Models\LoanAmortization;
use Illuminate\Notifications\Notification;

class LoanAmortizationRequestedNotification extends Notification
{
    public function __construct(private LoanAmortization $amortization) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $loan = $this->amortization->loan->loadMissing('user');

        return [
            'kind' => 'loan_amortization_requested',
            'loan_id' => $loan->id,
            'amortization_id' => $this->amortization->id,
            'message' => "{$loan->user->name} dice que te pagó S/ ".number_format((float) $this->amortization->amount, 2).
                ' de la deuda. Confirma si es correcto.',
        ];
    }
}
