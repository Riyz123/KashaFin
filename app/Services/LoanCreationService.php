<?php

namespace App\Services;

use App\Models\Loan;
use App\Notifications\LoanConfirmationNotification;

/**
 * Shared by LoanController::store() and (soon) LoanChatIntent, so the web
 * form and the chatbot create a loan through the exact same rule: linking a
 * real account starts it as pendiente_confirmacion and notifies that person;
 * an unlinked/external person is created and counted immediately.
 */
class LoanCreationService
{
    public function create(array $data): Loan
    {
        if (! empty($data['person_user_id'])) {
            $data['status'] = 'pendiente_confirmacion';
        }

        $loan = Loan::create($data);

        if ($loan->person_user_id) {
            $loan->personUser->notify(new LoanConfirmationNotification($loan));
        }

        return $loan;
    }

    public function statusMessage(Loan $loan): string
    {
        return $loan->person_user_id
            ? 'Préstamo registrado. Se notificó a la otra persona para que lo confirme.'
            : 'Préstamo registrado correctamente.';
    }
}
