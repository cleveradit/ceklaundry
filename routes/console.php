<?php

use App\Services\NotificationRecoveryService;
use App\Services\ReminderScheduler;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:expire-business-access')->everyMinute();
Schedule::call(fn () => app(NotificationRecoveryService::class)->recover())->everyMinute();
Schedule::command('app:purge-expired-demos')->everyMinute();
Schedule::call(fn () => app(ReminderScheduler::class)->run())->dailyAt('08:00')->timezone('Asia/Jakarta');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
