<?php

namespace App\Services\AI;

interface LlmInterface
{
    /**
     * Send a prompt and get a text response.
     *
     * @param  string  $systemPrompt  Context/role instructions
     * @param  string  $userPrompt    The actual request
     * @param  int     $maxTokens
     * @return string
     */
    public function complete(string $systemPrompt, string $userPrompt, int $maxTokens = 1024): string;

    /**
     * Returns true if the provider is configured and reachable.
     */
    public function isAvailable(): bool;

    /**
     * Human-readable provider name for UI display.
     */
    public function providerName(): string;
}
