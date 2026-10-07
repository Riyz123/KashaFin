<?php

namespace App\Services\Ai\Drivers;

use App\Models\AiProvider;
use App\Services\Ai\Exceptions\QuotaExceededException;

interface AiDriverInterface
{
    /**
     * Send a chat completion request and return the assistant's reply text.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     *
     * @throws QuotaExceededException when the provider reports a rate-limit/quota error.
     * @throws \RuntimeException on any other failure.
     */
    public function send(AiProvider $provider, array $messages): string;
}
