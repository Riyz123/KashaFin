<?php

namespace App\Notifications;

use App\Models\SupportRequest;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Database-only, same as NewSupportRequestNotification — a decano handed
 * a case over to administración, or an admin handed it over to the
 * student's decano, because it was outside what they could resolve
 * themselves.
 */
class SupportRequestEscalatedNotification extends Notification
{
    public function __construct(private SupportRequest $supportRequest, private User $escalatedBy) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->supportRequest->loadMissing('user');

        return [
            'support_request_id' => $this->supportRequest->id,
            'student_name' => $this->supportRequest->user->name,
            'escalated_by_name' => $this->escalatedBy->name,
            'message' => "{$this->escalatedBy->name} te derivó un caso de {$this->supportRequest->user->name}: {$this->supportRequest->message}",
        ];
    }
}
