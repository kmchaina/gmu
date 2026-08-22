<?php

namespace App\Services\AI;

/**
 * Fallback provider used when no API key is configured.
 * Returns empty strings so callers degrade gracefully to rule-based output only.
 */
class NullProvider implements LlmInterface
{
    public function complete(string $systemPrompt, string $userPrompt, int $maxTokens = 1024): string
    {
        return '';
    }

    public function isAvailable(): bool
    {
        return false;
    }

    public function providerName(): string
    {
        return 'None (rule-based only)';
    }
}
