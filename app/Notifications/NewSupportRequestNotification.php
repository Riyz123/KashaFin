<?php

namespace App\Notifications;

use App\Models\SupportRequest;
use Illuminate\Notifications\Notification;

/**
 * Database-only (no email — this needs to show up instantly in the admin/
 * dean panel, not wait on a transactional email): a student asked to talk
 * to an advisor, or reported an incidence, through the chatbot or the help
 * page.
 */
class NewSupportRequestNotification extends Notification
{
    public function __construct(private SupportRequest $supportRequest) {}

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
            'type' => $this->supportRequest->type,
            'message' => $this->supportRequest->message,
        ];
    }
}
