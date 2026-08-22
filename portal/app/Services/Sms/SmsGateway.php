<?php

namespace App\Services\Sms;

interface SmsGateway
{
    /**
     * Send one short message. Returns true when the gateway accepted it.
     */
    public function send(string $phone, string $message): bool;

    public function isConfigured(): bool;

    public function name(): string;
}
