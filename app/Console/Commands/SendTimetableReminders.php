<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\TimetableReminderService;
class SendTimetableReminders extends Command
{
    protected $signature = 'timetable:send-reminders';
    protected $description = 'Notify enrolled participants and assigned instructors ten minutes before course sessions';
    public function handle(TimetableReminderService $service): int
    {
        $this->info('Sent '.$service->sendDue().' timetable reminder(s).');
        return self::SUCCESS;
    }
}
