<?php

namespace App\Services\Ai;

final class AiReply
{
    public function __construct(
        public readonly ?string $content = null,
        public readonly ?string $toolName = null,
        public readonly array $toolArguments = [],
    ) {}

    public function isToolCall(): bool
    {
        return $this->toolName !== null;
    }
}
