<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Notifications\Notification;

/**
 * Sent every 3 days while a debt stays overdue (see
 * Console\Commands\NotifyOverdueLoans). There's no delete affordance on
 * any notification in this app — opening the notifications list is what
 * "reading" one means (see NotificationController::index()) — so this one
 * is "undeletable" the same way every other notification already is.
 */
class OverdueLoanReminderNotification extends Notification
{
    public function __construct(private Loan $loan) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'loan_overdue_reminder',
            'loan_id' => $this->loan->id,
            'message' => 'Tu deuda de S/ '.number_format($this->loan->remaining_amount, 2)." con {$this->loan->person_name} está vencida. Pagar ahora.",
        ];
    }
}
