<?php

namespace App\Services\Ai;

use App\Models\User;
use App\Services\PersonSearchService;
use App\Services\SharedExpenseCreationService;
use Illuminate\Support\Carbon;

/**
 * Shared expenses are only handled via the AI tool-call path (add_shared_expense)
 * — there's no regex trigger and no handle(). Unlike LoanChatIntent, this
 * doesn't use ChatState for disambiguation either: the AI is expected to
 * gather the whole picture (description, total, my_share, and every
 * participant with their name and amount) across the conversation BEFORE
 * calling the tool once, same as add_expense/add_budget/add_income already
 * do. If a participant's name doesn't resolve to exactly one real account,
 * this returns a plain error describing the problem; the AI sees that as
 * its own reply, and the student's next message lets it retry the tool
 * call with a corrected name — no separate state machine needed for that.
 */
class SharedExpenseChatIntent
{
    public function __construct(
        private PersonSearchService $personSearch,
        private SharedExpenseCreationService $sharedExpenseCreation,
    ) {}

    public function createFromToolCall(User $user, array $arguments): string
    {
        $description = is_string($arguments['description'] ?? null) ? trim($arguments['description']) : '';

        if ($description === '') {
            return '¿Qué fue el gasto que quieres compartir?';
        }

        $totalAmount = $this->coerceAmount($arguments['total_amount'] ?? null, allowZero: false);

        if ($totalAmount === null) {
            return 'Necesito el monto total del gasto (mayor a 0).';
        }

        $myShare = $this->coerceAmount($arguments['my_share'] ?? 0, allowZero: true) ?? 0.0;

        $participantsInput = $arguments['participants'] ?? null;

        if (! is_array($participantsInput) || count($participantsInput) === 0) {
            return '¿Con quién vas a compartir este gasto, y cuánto le corresponde a cada uno?';
        }

        $parsed = [];

        foreach ($participantsInput as $participant) {
            $name = is_string($participant['name'] ?? null) ? trim($participant['name']) : '';
            $amount = $this->coerceAmount($participant['amount_owed'] ?? null, allowZero: false);

            if ($name === '' || $amount === null) {
                return 'Necesito el nombre y el monto exacto que le corresponde a cada participante.';
            }

            $parsed[] = ['name' => $name, 'amount_owed' => $amount];
        }

        $participantsTotal = array_sum(array_column($parsed, 'amount_owed'));

        if (round($myShare + $participantsTotal, 2) !== round($totalAmount, 2)) {
            return 'Tu parte más lo que deben los demás (S/ '.number_format($myShare + $participantsTotal, 2).
                ') debe sumar el total (S/ '.number_format($totalAmount, 2).'). ¿Puedes confirmar los montos?';
        }

        $resolved = [];
        $usedIds = [];

        foreach ($parsed as $participant) {
            $matches = $this->personSearch->search($user, $participant['name']);

            if ($matches->isEmpty()) {
                return "No encontré ninguna cuenta de KashaFin para \"{$participant['name']}\". ".
                    'Esa persona necesita tener una cuenta registrada para agregarla a un gasto compartido.';
            }

            if ($matches->count() > 1) {
                $options = $matches->map(fn ($u) => "{$u->name} ({$u->email})")->implode(', ');

                return "Encontré varias personas llamadas \"{$participant['name']}\": {$options}. ".
                    'Dime el nombre completo o el correo de la persona correcta.';
            }

            $match = $matches->first();

            // PersonSearchService already excludes $user from its results,
            // so a match is never the student themself — no separate check
            // needed for "added myself as a participant".
            if (in_array($match->id, $usedIds, true)) {
                return "Ya agregaste a {$match->name} — no puedes agregar a la misma persona dos veces.";
            }

            $usedIds[] = $match->id;
            $resolved[] = ['user_id' => $match->id, 'name' => $match->name, 'amount_owed' => $participant['amount_owed']];
        }

        $sharedExpense = $this->sharedExpenseCreation->create($user, [
            'description' => $description,
            'total_amount' => $totalAmount,
            'my_share' => $myShare,
            'date' => Carbon::now()->toDateString(),
            'participants' => $resolved,
        ]);

        $names = implode(', ', array_column($resolved, 'name'));

        return "✅ Gasto compartido registrado: \"{$sharedExpense->description}\" por S/ ".number_format($totalAmount, 2).
            ". Se notificó a {$names} para que confirmen su parte.";
    }

    private function coerceAmount(mixed $value, bool $allowZero): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $amount = (float) $value;

        return ($allowZero ? $amount >= 0 : $amount > 0) ? $amount : null;
    }
}
