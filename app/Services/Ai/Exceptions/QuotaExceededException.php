<?php

namespace App\Services\Ai\Exceptions;

use RuntimeException;

class QuotaExceededException extends RuntimeException
{
    /**
     * Seconds the provider itself said to wait before retrying (from a
     * `Retry-After` header or Gemini's RetryInfo detail), when it told us.
     * Null means we have no better information than the admin-configured
     * quota_period_days.
     */
    public ?int $retryAfterSeconds = null;

    public function withRetryAfter(?int $seconds): self
    {
        $this->retryAfterSeconds = $seconds;

        return $this;
    }
}
