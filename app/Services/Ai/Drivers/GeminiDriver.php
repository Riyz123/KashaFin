<?php

namespace App\Services\Ai\Drivers;

use App\Models\AiProvider;
use App\Services\Ai\AiReply;
use App\Services\Ai\Exceptions\QuotaExceededException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Google's Generative Language API ("Gemini"). Different request/response
 * shape than OpenAI-style APIs: the key goes as a query param, there is no
 * "system" role (it's a separate field), the assistant role is "model", and
 * tool-call arguments come back as a native object instead of a JSON string.
 */
class GeminiDriver implements AiDriverInterface
{
    public function send(AiProvider $provider, array $messages, array $tools = []): AiReply
    {
        // Google's own error messages suggest the replacement slug WITH the
        // "models/" prefix (e.g. "models/gemini-3.8-flash"), which an admin
        // naturally pastes as-is into the `model` field — so this tolerates
        // either form instead of producing a broken "models/models/..." URL.
        $model = preg_replace('#^models/#', '', trim($provider->model));

        $url = rtrim($provider->base_url, '/')."/models/{$model}:generateContent";

        $systemMessages = array_filter($messages, fn ($m) => $m['role'] === 'system');
        $conversation = array_values(array_filter($messages, fn ($m) => $m['role'] !== 'system'));

        $payload = [
            'contents' => array_map(fn ($m) => [
                'role' => $m['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $m['content']]],
            ], $conversation),
        ];

        if (! empty($systemMessages)) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => implode("\n\n", array_column($systemMessages, 'content'))]],
            ];
        }

        if (! empty($tools)) {
            $payload['tools'] = [[
                'functionDeclarations' => array_map(fn ($tool) => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => $tool['parameters'],
                ], $tools),
            ]];
        }

        try {
            $response = Http::timeout(20)->post("{$url}?key={$provider->api_key}", $payload);
        } catch (\Illuminate\Http\Client\ConnectionException) {
            // The API key travels in the query string here (Gemini's own
            // auth scheme), so a connection-level failure — no HTTP response
            // at all — makes Laravel's exception message echo back the
            // full request URL, key included, straight into the admin
            // panel's error log. Never let that happen.
            throw new \RuntimeException('No se pudo conectar con el proveedor de IA (tiempo de espera agotado).');
        }

        if ($response->status() === 429) {
            throw (new QuotaExceededException('Límite de solicitudes alcanzado.'))
                ->withRetryAfter($this->retryAfterSeconds($response));
        }

        if ($response->failed()) {
            $errorBody = $response->json('error.message') ?? $response->body();
            $status = $response->json('error.status');

            if ($status === 'RESOURCE_EXHAUSTED') {
                throw (new QuotaExceededException($errorBody))
                    ->withRetryAfter($this->retryAfterSeconds($response));
            }

            if ($status === 'UNAVAILABLE' || $response->status() === 503 || $this->looksLikeOverloadError($errorBody)) {
                throw new \RuntimeException("El modelo \"{$model}\" está saturado ahora mismo en los servidores de Google (alta demanda) — no es un problema de tu configuración, suele resolverse solo en segundos o minutos. Detalle del proveedor: {$errorBody}");
            }

            throw new \RuntimeException("Error del proveedor de IA: {$errorBody}");
        }

        $parts = $response->json('candidates.0.content.parts') ?? [];
        $functionCall = $parts[0]['functionCall'] ?? null;

        if ($functionCall) {
            return new AiReply(
                toolName: $functionCall['name'] ?? null,
                toolArguments: (array) ($functionCall['args'] ?? []),
            );
        }

        $content = $parts[0]['text'] ?? null;

        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('El proveedor de IA devolvió una respuesta vacía.');
        }

        return new AiReply(content: trim($content));
    }

    /**
     * Gemini returns RESOURCE_EXHAUSTED for both per-minute (RPM) and
     * per-day (RPD) free-tier limits — same status, very different recovery
     * time. It tells us which via a RetryInfo detail (`retryDelay`, e.g.
     * "42s"); without it we fall back to the admin-configured
     * quota_period_days, same as before this existed.
     */
    private function retryAfterSeconds(Response $response): ?int
    {
        $details = $response->json('error.details') ?? [];

        foreach ($details as $detail) {
            if (isset($detail['retryDelay']) && preg_match('/^(\d+)/', (string) $detail['retryDelay'], $m)) {
                return max(1, (int) $m[1]);
            }
        }

        $header = $response->header('Retry-After');

        return is_numeric($header) ? max(1, (int) $header) : null;
    }

    /**
     * Google's free-tier models (especially newly released ones) regularly
     * return a 503 with this wording when the model itself is overloaded —
     * nothing wrong with the key/config, it's capacity on Google's side.
     * Distinguishing it lets the admin panel say so plainly instead of
     * echoing raw English JSON that reads like a broken integration.
     */
    private function looksLikeOverloadError(?string $message): bool
    {
        if (! $message) {
            return false;
        }

        $message = mb_strtolower($message);

        return str_contains($message, 'high demand')
            || str_contains($message, 'overloaded')
            || str_contains($message, 'try again later');
    }
}
