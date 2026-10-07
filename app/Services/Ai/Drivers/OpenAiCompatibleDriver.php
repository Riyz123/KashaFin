<?php

namespace App\Services\Ai\Drivers;

use App\Models\AiProvider;
use App\Services\Ai\Exceptions\QuotaExceededException;
use Illuminate\Support\Facades\Http;

/**
 * Works with any provider that mimics OpenAI's /chat/completions endpoint
 * (Groq, OpenRouter, Together, etc.) — the large majority of free-tier APIs.
 */
class OpenAiCompatibleDriver implements AiDriverInterface
{
    public function send(AiProvider $provider, array $messages): string
    {
        $url = rtrim($provider->base_url, '/').'/chat/completions';

        $response = Http::withToken($provider->api_key)
            ->timeout(20)
            ->post($url, [
                'model' => $provider->model,
                'messages' => $messages,
                'temperature' => 0.4,
                'max_tokens' => 500,
            ]);

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

        $content = $response->json('choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('El proveedor de IA devolvió una respuesta vacía.');
        }

        return trim($content);
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
