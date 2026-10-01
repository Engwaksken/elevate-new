<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('events:send-reminders')->everyFiveMinutes()->withoutOverlapping();

// Cached Word-to-PDF previews are rebuilt on demand, so old ones can go.
Schedule::call(function () {
    $directory = storage_path('app/previews');

    foreach (glob($directory.'/*.pdf') ?: [] as $file) {
        if (filemtime($file) < now()->subDays(7)->getTimestamp()) {
            @unlink($file);
        }
    }
})->daily()->name('prune-file-previews');
