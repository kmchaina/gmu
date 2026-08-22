<?php

namespace App\Console\Commands;

use App\Models\Award;
use App\Models\Report;
use App\Models\User;
use App\Services\Sms\SmsGateway;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Configuration and data health, in one place.
 *
 * Audit finding H8: mail was set to "log", so every deadline reminder the GMU
 * depends on was written to a file and never delivered — and nothing said so.
 * Silent misconfiguration is the failure mode this command exists to end.
 *
 *     php artisan gmu:health
 *
 * Exits non-zero when something is wrong, so it can be wired into a scheduled
 * check or a deployment step.
 */
class HealthCheck extends Command
{
    protected $signature   = 'gmu:health {--strict : Treat warnings as failures}';
    protected $description = 'Check the configuration and data the GMU depends on.';

    private array $problems = [];
    private array $warnings = [];

    public function handle(SmsGateway $sms): int
    {
        $this->line('');
        $this->configuration($sms);
        $this->storage();
        $this->data();
        $this->line('');

        foreach ($this->problems as $problem) {
            $this->error('  FAIL  ' . $problem);
        }

        foreach ($this->warnings as $warning) {
            $this->warn('  WARN  ' . $warning);
        }

        if ($this->problems === [] && $this->warnings === []) {
            $this->info('  All checks passed.');
        }

        $this->line('');

        $failed = $this->problems !== [] || ($this->option('strict') && $this->warnings !== []);

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function configuration(SmsGateway $sms): void
    {
        $this->components->info('Configuration');

        // Development and the test suite are held to the same checks, but a
        // missing mailer there is a warning rather than a failure.
        $production = ! app()->environment(['local', 'testing']);

        $this->check('Environment', (string) app()->environment(), true);

        // Debug mode outside development prints stack traces containing
        // credentials to whoever triggers an error.
        if (config('app.debug') && $production) {
            $this->problems[] = 'APP_DEBUG is on outside local development — stack traces will expose credentials.';
        }
        $this->check('Debug mode', config('app.debug') ? 'on' : 'off', ! (config('app.debug') && $production));

        // Mail: the reminders and the weekly digest are the unit's main output.
        $mailer = config('mail.default');
        if ($mailer === 'log') {
            $message = 'MAIL_MAILER is "log": reminders and digests are written to storage/logs, not delivered.';
            $production ? $this->problems[] = $message : $this->warnings[] = $message;
        }
        $this->check('Mailer', (string) $mailer, $mailer !== 'log');

        $this->check('From address', (string) (config('mail.from.address') ?: 'not set'), filled(config('mail.from.address')));

        // Sessions over plain HTTP are interceptable on a shared network.
        if ($production && ! config('session.secure')) {
            $this->warnings[] = 'SESSION_SECURE_COOKIE is off — session cookies will travel over plain HTTP.';
        }

        $this->check('SMS gateway', $sms->name(), true);

        // SQLite has no user, so this is null under the test suite.
        $dbUser = (string) (config('database.connections.' . config('database.default') . '.username') ?: 'n/a');

        if ($dbUser === 'root') {
            $this->problems[] = 'The application connects to MySQL as root — use a scoped account.';
        }
        $this->check('Database user', $dbUser, $dbUser !== 'root');

        $this->check('Base currency', (string) config('currency.base'), true);
    }

    private function storage(): void
    {
        $this->components->info('Storage');

        try {
            Storage::disk('local')->put('health-check.tmp', 'ok');
            $writable = Storage::disk('local')->get('health-check.tmp') === 'ok';
            Storage::disk('local')->delete('health-check.tmp');
        } catch (\Throwable $e) {
            $writable = false;
        }

        if (! $writable) {
            $this->problems[] = 'The private disk is not writable — uploads will fail.';
        }
        $this->check('Private disk writable', $writable ? 'yes' : 'no', $writable);

        // Anything uploaded before Phase 1 may still be sitting in the public
        // disk, which is served directly by the web server.
        try {
            $publicFiles = collect(Storage::disk('public')->allFiles())
                ->reject(fn($path) => str_ends_with($path, '.gitignore') || str_ends_with($path, '.htaccess'))
                ->count();
        } catch (\Throwable $e) {
            $publicFiles = 0;
        }

        if ($publicFiles > 0) {
            $this->warnings[] = "{$publicFiles} file(s) remain on the public disk — move them to the private disk.";
        }
        $this->check('Files on the public disk', (string) $publicFiles, $publicFiles === 0);
    }

    private function data(): void
    {
        $this->components->info('Data');

        try {
            DB::connection()->getPdo();
            $this->check('Database reachable', 'yes', true);
        } catch (\Throwable $e) {
            $this->problems[] = 'Database unreachable: ' . $e->getMessage();
            $this->check('Database reachable', 'no', false);

            return;
        }

        $coordinators = User::whereHas('role', fn($q) => $q->where('name', 'coordinator'))
            ->where('is_active', true)->count();

        if ($coordinators === 0) {
            $this->problems[] = 'No active Coordinator account — nobody can administer the system.';
        }
        $this->check('Active coordinators', (string) $coordinators, $coordinators > 0);

        $mustChange = User::where('must_change_password', true)->count();
        if ($mustChange > 0) {
            $this->warnings[] = "{$mustChange} account(s) still on a password someone else set.";
        }

        $overdue = Report::where('status', 'pending')->whereDate('due_date', '<', now())->count();
        $this->check('Overdue reports', (string) $overdue, $overdue === 0);

        $awaitingCloseout = Award::whereDate('end_date', '<', now())->where('status', '!=', 'completed')->count();
        if ($awaitingCloseout > 0) {
            $this->warnings[] = "{$awaitingCloseout} award(s) ended without closeout being completed.";
        }
        $this->check('Awards awaiting closeout', (string) $awaitingCloseout, $awaitingCloseout === 0);

        $unscheduled = Award::where('status', 'active')->where('schedule_generated', false)->count();
        if ($unscheduled > 0) {
            $this->warnings[] = "{$unscheduled} active award(s) have no generated reporting schedule.";
        }
    }

    private function check(string $label, string $value, bool $ok): void
    {
        $this->line(sprintf(
            '  %s %s %s',
            $ok ? '<fg=green>✓</>' : '<fg=red>✗</>',
            str_pad($label, 28, '.'),
            $value
        ));
    }
}
