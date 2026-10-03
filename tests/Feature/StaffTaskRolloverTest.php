<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use App\Services\StaffTaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StaffTaskRolloverTest extends TestCase
{
    use RefreshDatabase;

    public function test_next_working_day_skips_the_weekend(): void
    {
        $service = app(StaffTaskService::class);

        Carbon::setTestNow(Carbon::parse('2026-10-02 09:00:00')); // Friday
        $this->assertSame('2026-10-05', $service->nextWorkingDay()->toDateString()); // Monday

        Carbon::setTestNow(Carbon::parse('2026-10-03 09:00:00')); // Saturday
        $this->assertSame('2026-10-05', $service->nextWorkingDay()->toDateString());

        Carbon::setTestNow(Carbon::parse('2026-10-05 09:00:00')); // Monday
        $this->assertSame('2026-10-06', $service->nextWorkingDay()->toDateString());

        Carbon::setTestNow();
    }

    public function test_pending_tasks_are_moved_to_the_next_working_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-02 09:00:00')); // Friday

        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $overdue = Task::create(['title' => 'Overdue', 'assigned_to' => $user->id, 'status' => 'not_started', 'due_date' => '2026-10-01', 'created_by' => $user->id]);
        $dueToday = Task::create(['title' => 'Due today', 'assigned_to' => $user->id, 'status' => 'in_progress', 'due_date' => '2026-10-02', 'created_by' => $user->id]);
        $future = Task::create(['title' => 'Future', 'assigned_to' => $user->id, 'status' => 'not_started', 'due_date' => '2026-10-09', 'created_by' => $user->id]);
        $done = Task::create(['title' => 'Done', 'assigned_to' => $user->id, 'status' => 'completed', 'due_date' => '2026-10-01', 'created_by' => $user->id, 'completed_at' => now()]);

        $moved = app(StaffTaskService::class)->movePendingToNextDay(collect([$user->id]));

        $this->assertSame(2, $moved);
        $this->assertSame('2026-10-05', $overdue->fresh()->due_date->toDateString());
        $this->assertSame('2026-10-05', $dueToday->fresh()->due_date->toDateString());
        $this->assertSame('2026-10-09', $future->fresh()->due_date->toDateString());
        $this->assertSame('2026-10-01', $done->fresh()->due_date->toDateString());

        Carbon::setTestNow();
    }

    public function test_staff_can_move_a_single_task_via_the_route(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-02 09:00:00')); // Friday

        $user = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $task = Task::create(['title' => 'Call applicant', 'assigned_to' => $user->id, 'status' => 'not_started', 'due_date' => '2026-10-02', 'created_by' => $user->id]);

        $this->actingAs($user)
            ->post(route('staff.tasks.move', $task))
            ->assertRedirect();

        $this->assertSame('2026-10-05', $task->fresh()->due_date->toDateString());

        Carbon::setTestNow();
    }
}
