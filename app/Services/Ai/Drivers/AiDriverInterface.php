<?php

namespace App\Services\Ai\Drivers;

use App\Models\AiProvider;
use App\Services\Ai\AiReply;
use App\Services\Ai\Exceptions\QuotaExceededException;

interface AiDriverInterface
{
    /**
     * Send a chat completion request and return either a text reply or a
     * tool-call request, depending on what the model decided to do.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  array<int, array{name: string, description: string, parameters: array}>  $tools  provider-agnostic function schemas
     *
     * @throws QuotaExceededException when the provider reports a rate-limit/quota error.
     * @throws \RuntimeException on any other failure.
     */
    public function send(AiProvider $provider, array $messages, array $tools = []): AiReply;
}
