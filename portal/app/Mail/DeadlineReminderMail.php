<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeadlineReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientName,
        public array  $reminders,   // [ ['type'=>'report|compliance|ethics', 'label'=>'...', 'due'=>Carbon, 'days'=>int, 'award_title'=>'...'] ]
        public int    $daysWindow   // 7, 14, or 30
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Upcoming Deadline Reminder ({$this->daysWindow}-day notice) – NIMR GMU",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.deadline-reminder',
        );
    }
}
