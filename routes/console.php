<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule auto-logout command to run every minute
Schedule::command('users:auto-logout')->everyMinute();

// Schedule overtime auto-logout to run every minute
Schedule::command('users:auto-logout-overtime')->everyMinute();

// Schedule reminder emails to run every minute
Schedule::command('emails:send-reminders')->everyMinute();
