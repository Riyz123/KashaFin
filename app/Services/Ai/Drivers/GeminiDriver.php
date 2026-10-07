<?php

namespace App\Services\Ai\Drivers;

use App\Models\AiProvider;
use App\Services\Ai\Exceptions\QuotaExceededException;
use Illuminate\Support\Facades\Http;

/**
 * Google's Generative Language API ("Gemini"). Different request/response
 * shape than OpenAI-style APIs: the key goes as a query param, there is no
 * "system" role (it's a separate field), and the assistant role is "model".
 */
class GeminiDriver implements AiDriverInterface
{
    public function send(AiProvider $provider, array $messages): string
    {
        $url = rtrim($provider->base_url, '/')."/models/{$provider->model}:generateContent";

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

        $response = Http::timeout(20)->post("{$url}?key={$provider->api_key}", $payload);

        if ($response->status() === 429) {
            throw new QuotaExceededException('Límite de solicitudes alcanzado.');
        }

        if ($response->failed()) {
            $errorBody = $response->json('error.message') ?? $response->body();
            $status = $response->json('error.status');

            if ($status === 'RESOURCE_EXHAUSTED') {
                throw new QuotaExceededException($errorBody);
            }

            throw new \RuntimeException("Error del proveedor de IA: {$errorBody}");
        }

        $content = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($content) || trim($content) === '') {
            throw new \RuntimeException('El proveedor de IA devolvió una respuesta vacía.');
        }

        return trim($content);
    }
}
