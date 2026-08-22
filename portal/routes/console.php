<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Send deadline reminders every morning at 07:00 EAT
// Covers 30-day, 14-day, and 7-day windows for reports, compliance events, and ethics renewals
Schedule::command('gmu:send-deadline-reminders')
    ->dailyAt('07:00')
    ->timezone('Africa/Dar_es_Salaam')
    ->appendOutputTo(storage_path('logs/deadline-reminders.log'));

// Send weekly management digest every Monday at 08:00 EAT to coordinators and planning officers
Schedule::command('gmu:send-weekly-digest')
    ->weeklyOn(1, '08:00')
    ->timezone('Africa/Dar_es_Salaam')
    ->appendOutputTo(storage_path('logs/weekly-digest.log'));
