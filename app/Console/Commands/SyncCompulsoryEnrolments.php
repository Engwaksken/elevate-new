<?php

namespace App\Console\Commands;

use App\Models\Enrolment;
use App\Services\EnrolmentService;
use Illuminate\Console\Command;

class SyncCompulsoryEnrolments extends Command
{
    protected $signature = 'enrolments:sync-compulsory {--report : Also list participants with more than one chosen course in a cohort}';

    protected $description = 'Enrol already-registered participants in the compulsory courses of the courses they are taking';

    public function handle(EnrolmentService $enrolments): int
    {
        $created = $enrolments->syncAllCompulsory();
        $this->info("{$created} compulsory course enrolment(s) added.");

        if ($this->option('report')) {
            // Existing data is left untouched; this only lists what breaks the rule now.
            $rows = Enrolment::query()
                ->selectRaw('user_id, cohort_id, COUNT(*) as courses')
                ->where(fn ($q) => $q->whereNull('status')->orWhereNotIn('status', EnrolmentService::INACTIVE_STATUSES))
                ->whereHas('course', fn ($q) => $q->whereDoesntHave('compulsoryFor'))
                ->groupBy('user_id', 'cohort_id')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            $this->line($rows->isEmpty()
                ? 'No participant has more than one chosen course in a cohort.'
                : "{$rows->count()} participant/cohort pair(s) have more than one chosen course:");

            if ($rows->isNotEmpty()) {
                $this->table(['User ID', 'Cohort ID', 'Chosen courses'], $rows->map(fn ($r) => [$r->user_id, $r->cohort_id ?? '(none)', $r->courses]));
            }
        }

        return self::SUCCESS;
    }
}
