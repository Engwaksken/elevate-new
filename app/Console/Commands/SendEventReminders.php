<?php
namespace App\Console\Commands;

use App\Services\EventReminderService;
use Illuminate\Console\Command;

class SendEventReminders extends Command
{
    protected $signature='events:send-reminders';
    protected $description='Send due ElevateHer360 event reminders to registered and invited people';

    public function handle(EventReminderService $reminders): int
    {
        $sent=$reminders->sendDue();
        $this->info("Processed {$sent} reminder delivery record(s).");

        return self::SUCCESS;
    }
}
