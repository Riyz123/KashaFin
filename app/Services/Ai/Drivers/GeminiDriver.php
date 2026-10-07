<?php

namespace App\Services\Ai\Drivers;

use App\Models\AiProvider;
use App\Services\Ai\AiReply;
use App\Services\Ai\Exceptions\QuotaExceededException;
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

        if (! empty($tools)) {
            $payload['tools'] = [[
                'functionDeclarations' => array_map(fn ($tool) => [
                    'name' => $tool['name'],
                    'description' => $tool['description'],
                    'parameters' => $tool['parameters'],
                ], $tools),
            ]];
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
}
