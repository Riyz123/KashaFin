<?php

namespace App\Services\Ai;

use App\Models\AiProvider;
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
    ) {}

    /**
     * Generate a reply for a free-form message, persisting both sides of
     * the conversation. Falls back to a data-only summary (no AI) if every
     * configured provider is unavailable or fails.
     */
    public function reply(User $user, string $userMessage): string
    {
        ChatMessage::create(['user_id' => $user->id, 'role' => 'user', 'content' => $userMessage]);

        $messages = $this->buildMessages($user, $userMessage);

        $reply = $this->tryProviders($messages) ?? $this->fallbackSummary($user);

        ChatMessage::create(['user_id' => $user->id, 'role' => 'assistant', 'content' => $reply]);

        return $reply;
    }

    private function tryProviders(array $messages): ?string
    {
        foreach ($this->router->candidates() as $provider) {
            try {
                $reply = $this->driverFor($provider)->send($provider, $messages);
                $provider->recordUsage(success: true);

                return $reply;
            } catch (QuotaExceededException $e) {
                $provider->recordUsage(success: false, exhausted: true, errorMessage: $e->getMessage());
            } catch (\Throwable $e) {
                $provider->recordUsage(success: false, exhausted: false, errorMessage: $e->getMessage());
            }
        }

        return null;
    }

    private function driverFor(AiProvider $provider): AiDriverInterface
    {
        return match ($provider->driver) {
            'gemini' => new GeminiDriver,
            default => new OpenAiCompatibleDriver,
        };
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
            "Responde en español, en tono cercano. Si te piden un reporte, análisis o recomendación, usa los ".
            "datos reales de abajo para dar una respuesta concreta y bien explicada (no hace falta que sea ".
            "breve si te piden detalle o un análisis); para preguntas simples, responde corto. ".
            "No inventes montos ni muevas dinero: si el estudiante quiere registrar un gasto o crear un ".
            "presupuesto, dile que escriba algo como 'agrega un gasto' o 'crea un presupuesto' y el sistema ".
            "lo guiará paso a paso — tú nunca ejecutas esa acción directamente.\n\n".
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
