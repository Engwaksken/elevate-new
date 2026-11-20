<?php

namespace App\Console\Commands;

use App\Services\HR\HrComplianceService;
use Illuminate\Console\Command;

/** Automatic reminders to staff with missing HR documents or unacknowledged policies. */
class SendHrComplianceReminders extends Command
{
    protected $signature = 'hr:send-compliance-reminders';
    protected $description = 'Remind staff about missing HR documents and policies they have not acknowledged';

    public function handle(HrComplianceService $compliance): int
    {
        $sent = $compliance->sendReminders(null, 'all', true);
        $this->info("Reminded {$sent} staff member(s).");

        return self::SUCCESS;
    }
}
