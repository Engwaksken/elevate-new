<?php

namespace App\Console\Commands;

use App\Services\ActivityReminderService;
use Illuminate\Console\Command;

class SendActivityReminders extends Command
{
    protected $signature = 'activities:send-reminders';
    protected $description = 'Remind people about upcoming appointments and mentorship sessions, and career steps that are due or overdue';

    public function handle(ActivityReminderService $service): int
    {
        $this->info('Sent '.$service->sendDue().' activity reminder(s).');

        return self::SUCCESS;
    }
}
