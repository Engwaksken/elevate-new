<?php

namespace App\Jobs;

use App\Models\DailyItQueueReview;
use App\Models\Task;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GenerateDailyItQueueReview implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public readonly string $runDate)
    {
    }

    public function handle(): void
    {
        try {
            DB::transaction(function (): void {
                // Unique run_date claims the date atomically; any task failure rolls it back.
                DailyItQueueReview::create(['run_date' => $this->runDate]);

                User::query()
                    ->where('status', 'active')
                    ->whereHas('roles', fn ($query) => $query
                        ->whereIn('slug', ['it-lead', 'it-assistant'])
                        ->orWhereIn('name', ['IT Lead', 'IT Assistant']))
                    ->get()
                    ->each(fn (User $reviewer) => Task::create([
                        'title' => "Daily IT queue review ({$this->runDate})",
                        'description' => 'Review the IT support queue, assess open tickets, and ensure each ticket has an appropriate status and owner.',
                        'assigned_to' => $reviewer->id,
                        'created_by' => null,
                        'start_date' => $this->runDate,
                        'due_date' => $this->runDate,
                        'priority' => 'medium',
                        'status' => 'not_started',
                    ]));
            });
        } catch (QueryException $exception) {
            // Treat only a successfully committed competing claim as an idempotent duplicate.
            if (DailyItQueueReview::whereDate('run_date', $this->runDate)->exists()) {
                return;
            }

            throw $exception;
        }
    }
}
