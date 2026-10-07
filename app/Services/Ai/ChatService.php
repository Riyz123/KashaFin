<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\Ai\Drivers\AiDriverInterface;
use App\Services\Ai\Drivers\GeminiDriver;
use App\Services\Ai\Drivers\OpenAiCompatibleDriver;
use App\Services\Ai\Exceptions\QuotaExceededException;
use App\Services\LiquidityProjectionService;
use App\Services\ReportService;
use Carbon\Carbon;

class ChatService
{
    private const HISTORY_LIMIT = 10;

    public function __construct(
        private AiProviderRouter $router,
        private LiquidityProjectionService $projection,
        private ReportService $reports,
        private ExpenseChatIntent $expenseIntent,
        private BudgetChatIntent $budgetIntent,
    ) {}

    /**
     * Generate a reply for a free-form message, persisting both sides of
     * the conversation. If the model decides to invoke a tool (add_expense /
     * add_budget), it's executed here and a deterministic confirmation is
     * returned — never a second round-trip asking the AI to "phrase it",
     * which would cost extra quota and risk drifting from the real amount
     * saved. Falls back to a data-only summary (no AI) if every configured
     * provider is unavailable or fails.
     */
    public function reply(User $user, string $userMessage): string
    {
        ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'content' => $userMessage]);

        $messages = $this->buildMessages($user, $userMessage);
        $tools = $this->toolSchemas($user);

        $reply = $this->tryProviders($user, $messages, $tools) ?? $this->fallbackSummary($user);

        ChatMessage::create(['user_id' => $user->id, 'role' => 'assistant', 'content' => $reply]);

        return $reply;
    }

    private function tryProviders(User $user, array $messages, array $tools): ?string
    {
        foreach ($this->router->candidates() as $provider) {
            try {
                $aiReply = $this->driverFor($provider)->send($provider, $messages, $tools);
                $provider->recordUsage(success: true);

                return $aiReply->isToolCall()
                    ? $this->executeTool($user, $aiReply)
                    : $aiReply->content;
            } catch (QuotaExceededException $e) {
                $provider->recordUsage(success: false, exhausted: true, errorMessage: $e->getMessage());
            } catch (\Throwable $e) {
                $provider->recordUsage(success: false, exhausted: false, errorMessage: $e->getMessage());
            }
        }

        return null;
    }

    private function executeTool(User $user, AiReply $aiReply): string
    {
        return match ($aiReply->toolName) {
            'add_expense' => $this->expenseIntent->createFromToolCall($user, $aiReply->toolArguments),
            'add_budget' => $this->budgetIntent->createFromToolCall($user, $aiReply->toolArguments),
            default => 'No reconocí esa acción, ¿puedes reformularla?',
        };
    }

    private function driverFor(AiProvider $provider): AiDriverInterface
    {
        return match ($provider->driver) {
            'gemini' => new GeminiDriver,
            default => new OpenAiCompatibleDriver,
        };
    }

    /**
     * Provider-agnostic tool/function schemas (JSON Schema parameters).
     * Constraining "category" to an enum of this student's real category
     * names means a compliant model can only ever return an exact match —
     * no fuzzy/substring matching needed on the receiving end.
     */
    private function toolSchemas(User $user): array
    {
        $categoryNames = Category::query()->forUser($user)->orderBy('name')->pluck('name')->values()->all();

        return [
            [
                'name' => 'add_expense',
                'description' => 'Registra un gasto nuevo cuando el estudiante diga que gastó dinero o pida anotar/agregar un gasto, en cualquier forma en que lo exprese.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'amount' => [
                            'type' => 'number',
                            'description' => 'Monto exacto que el estudiante mencionó. Nunca inventes un número si no lo dio.',
                        ],
                        'category' => [
                            'type' => 'string',
                            'enum' => $categoryNames,
                            'description' => 'La categoría del gasto, si se puede inferir. Omitir si no es clara.',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Descripción breve opcional del gasto.',
                        ],
                    ],
                    'required' => ['amount'],
                ],
            ],
            [
                'name' => 'add_budget',
                'description' => 'Crea o actualiza el presupuesto mensual de una categoría cuando el estudiante lo pida, en cualquier forma en que lo exprese.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'category' => [
                            'type' => 'string',
                            'enum' => $categoryNames,
                            'description' => 'La categoría para la que se define el presupuesto.',
                        ],
                        'amount' => [
                            'type' => 'number',
                            'description' => 'Monto exacto del presupuesto que el estudiante mencionó.',
                        ],
                    ],
                    'required' => ['category', 'amount'],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function buildMessages(User $user, string $userMessage): array
    {
        $messages = [
            ['role' => 'system', 'content' => $this->systemPrompt($user)],
        ];

        $history = ChatMessage::query()->forUser($user)
            ->latest('id')
            ->take(self::HISTORY_LIMIT)
            ->get()
            ->reverse();

        foreach ($history as $message) {
            $messages[] = ['role' => $message->role, 'content' => $message->content];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        return $messages;
    }

    /**
     * Per-conversation context injection: a fresh snapshot of this
     * student's own data is sent with every request so replies are
     * personalized. This is NOT model fine-tuning — free third-party APIs
     * don't expose that — it's a compact summary rebuilt on each message.
     */
    private function systemPrompt(User $user): string
    {
        return "Eres el asistente financiero de KashaFin, una app para estudiantes universitarios. ".
            "Responde en español, en tono cercano y natural — si te saludan, saluda de vuelta y pregunta en ".
            "qué puedes ayudar; si te piden un reporte, análisis o recomendación, usa los datos reales de abajo ".
            "para dar una respuesta concreta (no hace falta que sea breve si piden detalle); para preguntas ".
            "simples, responde corto.\n\n".
            "Tienes dos herramientas disponibles: add_expense y add_budget. Úsalas cuando el estudiante quiera ".
            "registrar un gasto o definir un presupuesto, sin importar cómo lo exprese (\"me gasté 20 en \", ".
            "\"anota que pagué...\", \"quiero poner un tope de...\", etc.). Usa siempre el monto exacto que haya ".
            "dado — nunca inventes ni redondees un número. Si falta un dato obligatorio (el monto), no invoques ".
            "la herramienta todavía: pregúntale primero en texto normal y espera su respuesta.\n\n".
            "Datos actuales del estudiante:\n".$this->studentSummary($user);
    }

    private function studentSummary(User $user): string
    {
        $now = Carbon::now();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $balance = $this->projection->currentBalance($user);
        $summary = $this->reports->incomeVsExpense($user, $monthStart, $monthEnd);
        $lowest = $this->projection->lowestPoint($this->projection->sevenDayProjection($user));

        $budgetsLine = $user->budgets()
            ->with('category')
            ->whereDate('period_month', $monthStart->toDateString())
            ->get()
            ->map(fn ($budget) => "{$budget->category->name}: {$budget->percent_consumed}% usado")
            ->implode(' · ') ?: 'sin presupuestos configurados este mes';

        $goalsLine = $user->savingsGoals()
            ->where('status', 'active')
            ->get()
            ->map(fn ($goal) => "{$goal->name} ({$goal->progress_percent}%)")
            ->implode(' · ') ?: 'sin metas activas';

        $categoryLine = $this->reports->expensesByCategory($user, $monthStart, $monthEnd)
            ->map(fn ($row) => "{$row['category']}: S/ {$row['total']}")
            ->implode(' · ') ?: 'sin gastos registrados este mes';

        return implode("\n", [
            "- Saldo actual: S/ {$balance}",
            "- Ingresos del mes: S/ {$summary['income']} | Gastos del mes: S/ {$summary['expense']}",
            "- Gastos del mes por categoría: {$categoryLine}",
            "- Proyección más baja (7 días): S/ {$lowest['balance']} el {$lowest['date']}",
            "- Presupuestos: {$budgetsLine}",
            "- Metas activas: {$goalsLine}",
        ]);
    }

    private function fallbackSummary(User $user): string
    {
        return "Ahora mismo no hay ninguna IA disponible (todas las configuradas están agotadas o inactivas), ".
            "pero aquí tienes tu resumen:\n\n".$this->studentSummary($user).
            "\n\nIntenta de nuevo más tarde, o si eres el administrador, revisa el panel de IA.";
    }
}
