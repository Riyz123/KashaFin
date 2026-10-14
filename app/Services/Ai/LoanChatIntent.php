<?php

namespace App\Services\Ai;

use App\Models\ChatState;
use App\Models\Loan;
use App\Models\User;
use App\Services\LoanCreationService;
use App\Services\PersonSearchService;
use Illuminate\Support\Carbon;

/**
 * Loans are only handled via the AI tool-call path (add_loan), never a regex
 * trigger: recognizing "le presté a Ana 50 soles" vs "me prestaron 50" and
 * pulling out a person's name reliably is exactly the kind of free-text
 * extraction the AI is for. handle() exists only to CONTINUE a conversation
 * already in progress — specifically, resolving which real account matched
 * when the person's name was ambiguous (reusing ChatState the same way the
 * other intents do for multi-turn slot-filling).
 */
class LoanChatIntent
{
    public function __construct(
        private PersonSearchService $personSearch,
        private LoanCreationService $loanCreation,
    ) {}

    public function handle(User $user, string $message): ?string
    {
        $state = ChatState::firstOrCreate(['user_id' => $user->id]);

        if ($state->pending_intent === 'loan_person_pick') {
            return $this->continuePersonPick($user, $state, $message);
        }

        return null;
    }

    /**
     * Called by ChatService when the AI decides to invoke the "add_loan"
     * tool. Every value is re-validated here — never trusted blindly.
     */
    public function createFromToolCall(User $user, array $arguments): string
    {
        $direction = $arguments['direction'] ?? null;

        if (! in_array($direction, ['prestado', 'recibido'], true)) {
            return '¿Tú le prestaste a esa persona, o ella te prestó a ti?';
        }

        $amount = $this->coerceAmount($arguments['amount'] ?? null);

        if ($amount === null) {
            return 'Necesito un monto válido (mayor a 0) para el préstamo.';
        }

        $personName = is_string($arguments['person_name'] ?? null) ? trim($arguments['person_name']) : '';

        if ($personName === '') {
            return '¿Cómo se llama la persona?';
        }

        $dueDate = $this->coerceDueDate($arguments['due_date'] ?? null);

        return $this->resolvePersonAndCreate($user, $direction, $amount, $personName, $dueDate);
    }

    private function resolvePersonAndCreate(User $user, string $direction, float $amount, string $personName, ?string $dueDate): string
    {
        $matches = $this->personSearch->search($user, $personName);

        if ($matches->isEmpty()) {
            return $this->persistLoan($user, $direction, $amount, $personName, null, $dueDate);
        }

        if ($matches->count() === 1) {
            $match = $matches->first();

            return $this->persistLoan($user, $direction, $amount, $match->name, $match->id, $dueDate);
        }

        $state = ChatState::firstOrCreate(['user_id' => $user->id]);
        $state->update([
            'pending_intent' => 'loan_person_pick',
            'pending_data' => [
                'direction' => $direction,
                'amount' => $amount,
                'due_date' => $dueDate,
                'candidates' => $matches->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->values()->all(),
            ],
        ]);

        return $this->candidatesPrompt($matches);
    }

    private function continuePersonPick(User $user, ChatState $state, string $message): string
    {
        $data = $state->pending_data ?? [];
        $candidates = $data['candidates'] ?? [];

        $chosen = $this->matchCandidate($message, $candidates);

        if ($chosen === null) {
            return "No reconocí esa opción. ".$this->candidatesPrompt(collect($candidates));
        }

        $reply = $this->persistLoan($user, $data['direction'], $data['amount'], $chosen['name'], $chosen['id'], $data['due_date'] ?? null);
        $state->clear();

        return $reply;
    }

    /**
     * Accepts a 1-based number, an ordinal word ("la primera", "el segundo"),
     * or a message that contains one candidate's name clearly enough that
     * no other candidate's name also matches.
     */
    private function matchCandidate(string $message, array $candidates): ?array
    {
        $normalized = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($message));

        if (preg_match('/(\d+)/', $normalized, $m)) {
            $index = ((int) $m[1]) - 1;

            if (isset($candidates[$index])) {
                return $candidates[$index];
            }
        }

        $ordinals = ['primer' => 0, 'primera' => 0, 'segundo' => 1, 'segunda' => 1, 'tercer' => 2, 'tercera' => 2, 'cuarto' => 3, 'cuarta' => 3];

        foreach ($ordinals as $word => $index) {
            if (str_contains($normalized, $word) && isset($candidates[$index])) {
                return $candidates[$index];
            }
        }

        $byName = array_values(array_filter(
            $candidates,
            fn ($c) => str_contains($normalized, \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($c['name'])))
        ));

        return count($byName) === 1 ? $byName[0] : null;
    }

    private function candidatesPrompt($candidates): string
    {
        $lines = collect($candidates)->values()->map(fn ($c, $i) => ($i + 1).". {$c['name']} ({$c['email']})")->implode("\n");

        return "Encontré varias personas con ese nombre:\n{$lines}\n¿Cuál es? Responde con el número.";
    }

    private function persistLoan(User $user, string $direction, float $amount, string $personName, ?int $personUserId, ?string $dueDate): string
    {
        $loan = $this->loanCreation->create([
            'user_id' => $user->id,
            'person_name' => $personName,
            'person_user_id' => $personUserId,
            'direction' => $direction,
            'amount' => $amount,
            'date' => Carbon::now()->toDateString(),
            'due_date' => $dueDate,
        ]);

        $verb = $direction === 'prestado' ? 'le prestaste a' : 'te prestó';

        return "✅ Préstamo registrado: {$verb} {$personName} S/ ".number_format($amount, 2).
            '. '.$this->loanCreation->statusMessage($loan);
    }

    private function coerceAmount(mixed $value): ?float
    {
        if (! is_numeric($value)) {
            return null;
        }

        $amount = (float) $value;

        return $amount > 0 ? $amount : null;
    }

    private function coerceDueDate(mixed $value): ?string
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        return $date->isFuture() || $date->isToday() ? $date->toDateString() : null;
    }
}
