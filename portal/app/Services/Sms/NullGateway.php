<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

/**
 * Used until a gateway is contracted. Messages are written to the log so the
 * behaviour can be seen and tested without an account, and nothing silently
 * pretends to have been delivered.
 */
class NullGateway implements SmsGateway
{
    public function send(string $phone, string $message): bool
    {
        Log::info('SMS (no gateway configured)', ['to' => $phone, 'message' => $message]);

        return false;
    }

    public function isConfigured(): bool
    {
        return false;
    }

    public function name(): string
    {
        return 'None (logged only)';
    }
}
