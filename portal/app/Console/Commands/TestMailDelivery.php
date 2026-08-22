<?php

namespace App\Console\Commands;

use App\Mail\DeadlineReminderMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Audit finding H8 — MAIL_MAILER is "log", so every deadline reminder and
 * weekly digest the GMU relies on has been written to a file and never sent.
 *
 * Run this after configuring SMTP to prove mail actually leaves the machine:
 *
 *     php artisan gmu:mail-test coordinator@nimr.or.tz
 */
class TestMailDelivery extends Command
{
    protected $signature   = 'gmu:mail-test {recipient : Address to send the test to}';
    protected $description = 'Send a sample deadline reminder to verify mail delivery.';

    public function handle(): int
    {
        $recipient = $this->argument('recipient');
        $mailer    = config('mail.default');

        if ($mailer === 'log') {
            $this->warn('MAIL_MAILER is "log": the message will be written to storage/logs/laravel.log, not delivered.');
            $this->warn('Set real SMTP credentials in .env before relying on reminders.');
        }

        $sample = [[
            'type'        => 'report',
            'label'       => 'Technical Report – Quarter 1',
            'due'         => now()->addDays(7),
            'days'        => 7,
            'award_title' => 'Sample award (delivery test)',
        ]];

        try {
            Mail::to($recipient)->send(new DeadlineReminderMail('GMU Coordinator', $sample, 7));
        } catch (\Throwable $e) {
            $this->error('Delivery failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("Test reminder dispatched to {$recipient} via the \"{$mailer}\" mailer.");

        if ($mailer !== 'log') {
            $this->line('Check the inbox — and the spam folder — to confirm it arrived.');
        }

        return self::SUCCESS;
    }
}
