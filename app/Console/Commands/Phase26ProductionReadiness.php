<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class Phase26ProductionReadiness extends Command
{
    protected $signature = 'phase26:production-check';
    protected $description = 'Check key production-readiness settings for ElevateHer360';

    public function handle(): int
    {
        $failures = [];
        $warnings = [];

        $env = app()->environment();

        if ($env !== 'production') {
            $failures[] = "APP_ENV is '{$env}', expected 'production'.";
        }

        if (config('app.debug')) {
            $failures[] = 'APP_DEBUG is enabled.';
        }

        if ((string) config('app.name') === 'Laravel') {
            $warnings[] = 'APP_NAME is still Laravel.';
        }

        $url = (string) config('app.url');

        if (
            $url === ''
            || str_contains($url, 'localhost')
            || str_contains($url, '127.0.0.1')
        ) {
            $failures[] = "APP_URL is not a production URL: {$url}";
        }

        if (config('mail.default') === 'log') {
            $failures[] = 'MAIL_MAILER is still log; production notifications will not be delivered.';
        }

        if (config('filesystems.default') === 'local') {
            $warnings[] = 'FILESYSTEM_DISK is local. This is acceptable only if server storage/backup policy is intentional.';
        }

        if (! Schema::hasTable('queue_jobs')) {
            $warnings[] = 'queue_jobs table not found.';
        }

        if (! is_link(public_path('storage')) && ! file_exists(public_path('storage'))) {
            $failures[] = 'public/storage link is missing.';
        }

        $this->info('Phase 26 Production Readiness');
        $this->line('-----------------------------');

        foreach ($failures as $message) {
            $this->error($message);
        }

        foreach ($warnings as $message) {
            $this->warn($message);
        }

        if (! $failures && ! $warnings) {
            $this->info('No production-readiness issues detected by this check.');
        }

        if ($failures) {
            $this->newLine();
            $this->error(count($failures).' production blocker(s) detected.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('No blocking configuration issues detected.');

        return self::SUCCESS;
    }
}
