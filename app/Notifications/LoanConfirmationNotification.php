<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Notifications\Notification;

class LoanConfirmationNotification extends Notification
{
    public function __construct(private Loan $loan) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->loan->loadMissing('user');

        $verb = $this->loan->direction === 'prestado' ? 'dice que te prestó' : 'dice que le prestaste';

        return [
            'kind' => 'loan_confirmation',
            'loan_id' => $this->loan->id,
            'message' => "{$this->loan->user->name} {$verb} S/ ".number_format((float) $this->loan->amount, 2).
                '. Confirma si es correcto.',
        ];
    }
}
