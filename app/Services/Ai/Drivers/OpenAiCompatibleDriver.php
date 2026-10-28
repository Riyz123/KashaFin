<?php

namespace App\Services\Ai\Drivers;

use App\Models\AiProvider;
use App\Services\Ai\AiReply;
use App\Services\Ai\Exceptions\QuotaExceededException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Works with any provider that mimics OpenAI's /chat/completions endpoint
 * (Groq, OpenRouter, Together, etc.) — the large majority of free-tier APIs.
 */
class OpenAiCompatibleDriver implements AiDriverInterface
{
    public function send(AiProvider $provider, array $messages, array $tools = []): AiReply
    {
        $url = rtrim($provider->base_url, '/').'/chat/completions';

        $payload = [
            'model' => trim($provider->model),
            'messages' => $messages,
            'temperature' => 0.4,
            'max_tokens' => 500,
        ];

        if (! empty($tools)) {
            $payload['tools'] = array_map(fn ($tool) => ['type' => 'function', 'function' => $tool], $tools);
            $payload['tool_choice'] = 'auto';
        }

        $response = Http::withToken($provider->api_key)
            ->timeout(20)
            ->post($url, $payload);

        if ($response->status() === 429) {
            throw (new QuotaExceededException('Límite de solicitudes alcanzado.'))
                ->withRetryAfter($this->retryAfterSeconds($response));
        }

        if ($response->failed()) {
            $errorBody = $response->json('error.message') ?? $response->body();

            if ($this->looksLikeQuotaError($errorBody)) {
                throw (new QuotaExceededException($errorBody))
                    ->withRetryAfter($this->retryAfterSeconds($response));
            }

            if ($response->status() === 503 || $this->looksLikeOverloadError($errorBody)) {
                $model = trim($provider->model);

                throw new \RuntimeException("El modelo \"{$model}\" está saturado ahora mismo en el servidor del proveedor (alta demanda) — no es un problema de tu configuración, suele resolverse solo en segundos o minutos. Detalle del proveedor: {$errorBody}");
            }

            throw new \RuntimeException("Error del proveedor de IA: {$errorBody}");
        }

        $message = $response->json('choices.0.message') ?? [];
        $toolCalls = $message['tool_calls'] ?? null;

        if (! empty($toolCalls)) {
            $function = $toolCalls[0]['function'] ?? [];
            $arguments = json_decode($function['arguments'] ?? '{}', true);

            return new AiReply(
                toolName: $function['name'] ?? null,
                toolArguments: is_array($arguments) ? $arguments : [],
            );
        }

        $content = $message['content'] ?? null;

        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('El proveedor de IA devolvió una respuesta vacía.');
        }

        return new AiReply(content: trim($content));
    }

    /**
     * A 429 can mean "out of quota until the billing period resets" or just
     * "too many requests this minute" — Groq/OpenRouter tell us which via a
     * standard Retry-After header (seconds). Without it we fall back to the
     * admin-configured quota_period_days, same as before this existed.
     */
    private function retryAfterSeconds(Response $response): ?int
    {
        $header = $response->header('Retry-After');

        return is_numeric($header) ? max(1, (int) $header) : null;
    }

    private function looksLikeQuotaError(?string $message): bool
    {
        if (! $message) {
            return false;
        }

        $message = mb_strtolower($message);

        // Deliberately specific: a bare "insufficient" also matches errors
        // like "insufficient permissions" on a misconfigured key, which is
        // a real, fixable config problem — not exhausted quota — and
        // shouldn't permanently sideline the provider until period reset.
        return str_contains($message, 'quota')
            || str_contains($message, 'rate limit')
            || str_contains($message, 'rate_limit')
            || str_contains($message, 'insufficient_quota')
            || str_contains($message, 'insufficient quota');
    }

    /**
     * Same reasoning as the Gemini driver's equivalent: a model overloaded
     * on the provider's own servers isn't a quota problem or a config bug,
     * and the admin panel should say so instead of echoing raw JSON.
     */
    private function looksLikeOverloadError(?string $message): bool
    {
        if (! $message) {
            return false;
        }

        $message = mb_strtolower($message);

        return str_contains($message, 'high demand')
            || str_contains($message, 'overloaded')
            || str_contains($message, 'try again later')
            || str_contains($message, 'service unavailable');
    }
}
