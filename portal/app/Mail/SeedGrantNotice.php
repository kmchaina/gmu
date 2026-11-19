<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * The email copy of a seed grant notification: the same title and message
 * the person sees in the portal, and a button to the page where they act.
 */
class SeedGrantNotice extends Mailable
{
    public function __construct(
        public string $recipientName,
        public string $headline,
        public string $body,
        public string $actionUrl,
        public string $reference,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->headline . ' · NIMR Seed Grant');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.seed-grant-notice');
    }
}
