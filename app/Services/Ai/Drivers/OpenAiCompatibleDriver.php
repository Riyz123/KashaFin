<?php

namespace App\Services\Ai\Drivers;

use App\Models\AiProvider;
use App\Services\Ai\AiReply;
use App\Services\Ai\Exceptions\QuotaExceededException;
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
            'model' => $provider->model,
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
            throw new QuotaExceededException('Límite de solicitudes alcanzado.');
        }

        if ($response->failed()) {
            $errorBody = $response->json('error.message') ?? $response->body();

            if ($this->looksLikeQuotaError($errorBody)) {
                throw new QuotaExceededException($errorBody);
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

    private function looksLikeQuotaError(?string $message): bool
    {
        if (! $message) {
            return false;
        }

        $message = mb_strtolower($message);

        return str_contains($message, 'quota')
            || str_contains($message, 'rate limit')
            || str_contains($message, 'rate_limit')
            || str_contains($message, 'insufficient');
    }
}
