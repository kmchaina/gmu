<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Africa's Talking — the SMS route most Tanzanian institutions already have a
 * account with, and the one that reaches a PI in the field where email does
 * not (slide 10 of the GMU concept asks for alerts by email *and* WhatsApp;
 * SMS is the same channel shape and is available today).
 *
 * Set GMU_SMS_GATEWAY=africastalking with a username and API key to switch it
 * on. Until then the null gateway logs instead of sending, so nothing silently
 * claims a message was delivered.
 */
class AfricasTalkingGateway implements SmsGateway
{
    private string $username;
    private string $apiKey;
    private string $senderId;
    private string $endpoint;

    public function __construct()
    {
        $this->username = (string) config('services.sms.username', '');
        $this->apiKey   = (string) config('services.sms.key', '');
        $this->senderId = (string) config('services.sms.sender_id', 'NIMR');
        $this->endpoint = (string) config('services.sms.endpoint', 'https://api.africastalking.com/version1/messaging');
    }

    public function send(string $phone, string $message): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        try {
            $response = Http::asForm()
                ->withHeaders(['apiKey' => $this->apiKey, 'Accept' => 'application/json'])
                ->timeout(15)
                ->post($this->endpoint, [
                    'username' => $this->username,
                    'to'       => $this->normalise($phone),
                    'message'  => $message,
                    'from'     => $this->senderId,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('SMS gateway rejected a message', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('SMS gateway error', ['error' => $e->getMessage()]);
        }

        return false;
    }

    public function isConfigured(): bool
    {
        return filled($this->username) && filled($this->apiKey);
    }

    public function name(): string
    {
        return "Africa's Talking";
    }

    /**
     * Tanzanian numbers are stored in several shapes; the gateway wants E.164.
     */
    private function normalise(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '255')) {
            return '+' . $digits;
        }

        if (str_starts_with($digits, '0')) {
            return '+255' . substr($digits, 1);
        }

        return str_starts_with($phone, '+') ? $phone : '+' . $digits;
    }
}
