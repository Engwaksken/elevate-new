<?php

namespace Tests\Feature;

use App\Jobs\GenerateDailyItQueueReview;
use App\Models\Role;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schedule;
use Tests\TestCase;

class DailyItQueueReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_creates_one_task_for_each_active_it_lead_or_assistant(): void
    {
        $leadRole = Role::firstOrCreate(['slug' => 'it-lead'], ['name' => 'IT Lead']);
        $assistantRole = Role::firstOrCreate(['slug' => 'it-assistant'], ['name' => 'IT Assistant']);
        $lead = User::factory()->create(['status' => 'active']);
        $assistant = User::factory()->create(['status' => 'active']);
        $inactive = User::factory()->create(['status' => 'inactive']);
        $ordinary = User::factory()->create(['status' => 'active']);
        $lead->roles()->attach($leadRole);
        $assistant->roles()->attach($assistantRole);
        $inactive->roles()->attach($leadRole);

        (new GenerateDailyItQueueReview('2026-10-05'))->handle();

        $this->assertDatabaseCount('tasks', 2);
        $this->assertEqualsCanonicalizing([$lead->id, $assistant->id], Task::query()->pluck('assigned_to')->all());
        $this->assertDatabaseMissing('tasks', ['assigned_to' => $inactive->id]);
        $this->assertDatabaseMissing('tasks', ['assigned_to' => $ordinary->id]);
    }

    public function test_duplicate_run_date_does_not_duplicate_tasks(): void
    {
        $role = Role::firstOrCreate(['slug' => 'it-lead'], ['name' => 'IT Lead']);
        $reviewer = User::factory()->create(['status' => 'active']);
        $reviewer->roles()->attach($role);

        (new GenerateDailyItQueueReview('2026-10-05'))->handle();
        (new GenerateDailyItQueueReview('2026-10-05'))->handle();

        $this->assertDatabaseCount('tasks', 1);
    }

    public function test_daily_it_queue_review_is_scheduled_daily(): void
    {
        $event = collect(Schedule::events())->first(fn ($event) => $event->description === 'generate-daily-it-queue-review');

        $this->assertNotNull($event);
        $this->assertSame('0 0 * * *', $event->expression);
        $this->assertSame('Africa/Kampala', $event->timezone);
    }
}
