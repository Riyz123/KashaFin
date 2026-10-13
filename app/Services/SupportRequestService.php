<?php

namespace App\Services;

use App\Models\SupportRequest;
use App\Models\User;
use App\Notifications\NewSupportRequestNotification;

class SupportRequestService
{
    /**
     * Creates the request and notifies every master admin plus the
     * student's own dean (if their faculty has one assigned).
     */
    public function create(User $student, string $type, string $message): SupportRequest
    {
        $request = SupportRequest::create([
            'user_id' => $student->id,
            'type' => $type,
            'message' => $message,
        ]);

        $this->notifyStaff($request, $student);

        return $request;
    }

    private function notifyStaff(SupportRequest $request, User $student): void
    {
        $recipients = User::query()->where('role', 'admin')->get();

        $dean = $student->faculty?->dean();

        if ($dean) {
            $recipients->push($dean);
        }

        foreach ($recipients as $recipient) {
            $recipient->notify(new NewSupportRequestNotification($request));
        }
    }
}
