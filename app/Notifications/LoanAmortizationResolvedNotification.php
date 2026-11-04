<?php

namespace App\Notifications;

use App\Models\LoanAmortization;
use Illuminate\Notifications\Notification;

class LoanAmortizationResolvedNotification extends Notification
{
    public function __construct(private LoanAmortization $amortization, private bool $accepted) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $loan = $this->amortization->loan;
        $amount = number_format((float) $this->amortization->amount, 2);

        $message = $this->accepted
            ? "Se confirmó tu pago de S/ {$amount} a {$loan->person_name}. Te quedan S/ ".number_format($loan->remaining_amount, 2).' por pagar.'
            : "{$loan->person_name} rechazó tu pago reportado de S/ {$amount}. Revisa el monto y vuelve a intentarlo.";

        return [
            'kind' => 'loan_amortization_resolved',
            'loan_id' => $loan->id,
            'amortization_id' => $this->amortization->id,
            'accepted' => $this->accepted,
            'message' => $message,
        ];
    }
}
