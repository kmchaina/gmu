<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public array  $stats,       // overall counts: active_awards, open_calls, overdue_reports, proposals_pending_review
        public array  $upcoming,    // next 14-day items: [ ['type','label','award_title','due','days'] ]
        public array  $overdueItems // overdue items: [ ['type','label','award_title','overdue_days'] ]
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Weekly Grants Management Digest – NIMR GMU',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.weekly-digest',
        );
    }
}
