<?php

namespace App\Support;

/**
 * Maps a stored notification's `data` payload to its section tag (shown
 * next to every notification so it's visually obvious which part of the
 * app it's about — préstamos and soporte notifications used to look
 * identical in the list) and, when there's somewhere relevant to go, a
 * "go to that section" button. Not every kind has a natural destination
 * (e.g. an inactivity nudge isn't "about" any one record), so `url`/`label`
 * can be null — `section` is always set.
 */
class NotificationLink
{
    public static function for(array $data): array
    {
        $kind = $data['kind'] ?? null;

        return match (true) {
            $kind === 'loan_confirmation' || $kind === 'loan_payment_confirmation' => [
                'section' => 'Préstamos', 'url' => route('loans.index'), 'label' => 'Ir a préstamos',
            ],
            $kind === 'income_confirmation' => [
                'section' => 'Ingresos', 'url' => route('incomes.index'), 'label' => 'Ir a ingresos',
            ],
            isset($data['shared_expense_id']) => [
                'section' => 'Gastos compartidos', 'url' => route('shared-expenses.index'), 'label' => 'Ir a gastos compartidos',
            ],
            isset($data['support_request_id']) => [
                'section' => 'Soporte', 'url' => route('admin.support.index'), 'label' => 'Ir a soporte',
            ],
            isset($data['pending_student_id']) => [
                'section' => 'Usuarios', 'url' => route('admin.users.index', ['status' => 'pending']), 'label' => 'Ver estudiantes pendientes',
            ],
            $kind === 'inactivity' => [
                'section' => 'Recordatorio', 'url' => null, 'label' => null,
            ],
            default => [
                'section' => 'General', 'url' => null, 'label' => null,
            ],
        };
    }
}
