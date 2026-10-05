<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\GenerateDailyItQueueReview;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('events:send-reminders')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('timetable:send-reminders')->everyMinute()->withoutOverlapping();

Schedule::call(function () {
    GenerateDailyItQueueReview::dispatch(now('Africa/Kampala')->toDateString());
})->dailyAt('00:00')->timezone('Africa/Kampala')->name('generate-daily-it-queue-review')->withoutOverlapping();

// Cached Word-to-PDF previews are rebuilt on demand, so old ones can go.
Schedule::call(function () {
    $directory = storage_path('app/previews');

    foreach (glob($directory.'/*.pdf') ?: [] as $file) {
        if (filemtime($file) < now()->subDays(7)->getTimestamp()) {
            @unlink($file);
        }
    }
})->daily()->name('prune-file-previews');
