<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class InactivityReminderNotification extends Notification
{
    public function __construct(private int $daysInactive) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'inactivity',
            'days_inactive' => $this->daysInactive,
            'message' => "Llevas {$this->daysInactive} días sin registrar un gasto. ¿Olvidaste anotar algo?",
        ];
    }
}
