<?php

namespace App\Support;

/**
 * Maps a stored notification's `data` payload to "go to the relevant
 * section" button — so a notification that says "te deben algo" links
 * straight to Préstamos instead of leaving the student to find it
 * themselves. Not every notification kind has a natural destination
 * (e.g. an inactivity nudge isn't "about" any one record), so this can
 * return null.
 */
class NotificationLink
{
    public static function for(array $data): ?array
    {
        $kind = $data['kind'] ?? null;

        return match (true) {
            $kind === 'loan_confirmation' || $kind === 'loan_payment_confirmation' => [
                'url' => route('loans.index'), 'label' => 'Ir a préstamos',
            ],
            $kind === 'income_confirmation' => [
                'url' => route('incomes.index'), 'label' => 'Ir a ingresos',
            ],
            isset($data['shared_expense_id']) => [
                'url' => route('shared-expenses.index'), 'label' => 'Ir a gastos compartidos',
            ],
            isset($data['support_request_id']) => [
                'url' => route('admin.support.index'), 'label' => 'Ir a soporte',
            ],
            default => null,
        };
    }
}
