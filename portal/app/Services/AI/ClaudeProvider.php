<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeProvider implements LlmInterface
{
    private string $apiKey;
    private string $model;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key', '');
        $this->model  = config('services.anthropic.model', 'claude-sonnet-4-6');
    }

    public function complete(string $systemPrompt, string $userPrompt, int $maxTokens = 1024): string
    {
        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post($this->apiUrl, [
                'model'      => $this->model,
                'max_tokens' => $maxTokens,
                'system'     => $systemPrompt,
                'messages'   => [
                    ['role' => 'user', 'content' => $userPrompt],
                ],
            ]);

            if ($response->successful()) {
                return $response->json('content.0.text', '');
            }

            Log::warning('Claude API error', ['status' => $response->status(), 'body' => $response->body()]);
            return '';
        } catch (\Throwable $e) {
            Log::error('Claude API exception', ['error' => $e->getMessage()]);
            return '';
        }
    }

    public function isAvailable(): bool
    {
        return filled($this->apiKey);
    }

    public function providerName(): string
    {
        return 'Claude (Anthropic)';
    }
}
