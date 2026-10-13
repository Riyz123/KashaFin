<?php

namespace App\Services\Ai;

use App\Models\ChatState;
use App\Models\User;
use App\Services\SupportRequestService;

/**
 * Deterministic "hablar con un asesor" flow — never handled by the AI,
 * since whether a human gets paged shouldn't depend on a model's mood.
 * Mirrors ExpenseChatIntent's slot-filling pattern via ChatState.
 */
class AdvisorChatIntent
{
    private const NOUNS = ['asesor', 'humano', 'persona'];

    private const ACTION_WORDS = [
        'hablar', 'comunicar', 'comunicarme', 'contactar', 'contacto', 'necesito', 'ayuda',
    ];

    private const CANCEL_WORDS = ['cancelar', 'cancela', 'nada', 'olvidalo'];

    public function __construct(private SupportRequestService $support) {}

    public function handle(User $user, string $message): ?string
    {
        $state = ChatState::firstOrCreate(['user_id' => $user->id]);

        if ($state->pending_intent === 'contact_advisor') {
            return $this->continueRequest($user, $state, $message);
        }

        if ($this->looksLikeTrigger($message)) {
            $state->update(['pending_intent' => 'contact_advisor', 'pending_data' => []]);

            return 'Claro, puedo avisarle a un asesor para que se comunique contigo. '.
                'Cuéntame brevemente cuál es tu problema o duda (o escribe "cancelar" si ya no es necesario).';
        }

        return null;
    }

    private function looksLikeTrigger(string $message): bool
    {
        return FuzzyMatch::hasWord($message, self::NOUNS)
            && FuzzyMatch::hasWord($message, self::ACTION_WORDS);
    }

    private function continueRequest(User $user, ChatState $state, string $message): string
    {
        if (FuzzyMatch::hasWord($message, self::CANCEL_WORDS)) {
            $state->clear();

            return 'Entendido, no avisé a nadie. ¿En qué más te ayudo?';
        }

        $this->support->create($user, 'asesor', trim($message));
        $state->clear();

        return 'Listo, le avisé a un asesor con tu mensaje. Un asesor se comunicará contigo en unos minutos. '.
            'Mientras tanto, ¿hay algo más en lo que te pueda ayudar?';
    }
}
