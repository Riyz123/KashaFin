<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Sent to the relevant dean(s) and the master admin right when a student
 * self-registration lands in the pending-approval queue — before this,
 * nobody found out until they happened to open the Usuarios page and
 * noticed a new "pendiente" row themselves.
 */
class NewPendingStudentNotification extends Notification
{
    public function __construct(private User $student) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'pending_student_id' => $this->student->id,
            'message' => "{$this->student->name} ({$this->student->email}) se registró y espera tu aprobación.",
        ];
    }
}
