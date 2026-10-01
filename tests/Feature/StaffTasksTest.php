<?php

namespace Tests\Feature;

use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\AppraisalKpi;
use App\Models\Employee;
use App\Models\Task;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class StaffTasksTest extends TestCase
{
    use RefreshDatabase;

    private User $supervisor;
    private User $staff;
    private AppraisalKpi $kpi;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-01 10:00:00'); // a Thursday

        $this->supervisor = User::factory()->create(['user_type' => 'staff', 'status' => 'active', 'name' => 'Sam Supervisor']);
        $this->staff = User::factory()->create(['user_type' => 'staff', 'status' => 'active', 'name' => 'Olivia Officer']);

        $employee = Employee::create(['user_id' => $this->staff->id, 'employee_number' => 'EMP-001', 'supervisor_user_id' => $this->supervisor->id]);
        $cycle = AppraisalCycle::create(['name' => 'Annual 2026', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31']);
        $appraisal = Appraisal::create(['appraisal_cycle_id' => $cycle->id, 'employee_id' => $employee->id, 'manager_user_id' => $this->supervisor->id, 'status' => 'in_progress']);
        $kra = $appraisal->kras()->create(['title' => 'Graduate placement', 'weight' => 100]);
        $this->kpi = $kra->kpis()->create(['title' => 'Graduates placed in jobs', 'weight' => 100, 'position' => 1]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function task(array $attributes): Task
    {
        return Task::create($attributes + [
            'assigned_to' => $this->staff->id,
            'created_by' => $this->staff->id,
            'priority' => 'medium',
            'status' => 'not_started',
            'progress_percent' => 0,
        ]);
    }

    public function test_staff_add_a_task_linked_to_their_kpi(): void
    {
        $this->actingAs($this->staff)->get(route('staff.tasks.index'))
            ->assertOk()
            ->assertSee('Graduates placed in jobs')
            ->assertSee('Nothing due today');

        $this->actingAs($this->staff)->post(route('staff.tasks.store'), [
            'title' => 'Call 10 graduates about openings',
            'kpi' => 'a:'.$this->kpi->id,
            'due_date' => '2026-10-01',
            'priority' => 'high',
        ])->assertSessionHas('success', 'Task added.');

        $task = Task::sole();
        $this->assertSame($this->staff->id, $task->assigned_to);
        $this->assertSame($this->kpi->id, $task->appraisal_kpi_id);

        $this->actingAs($this->staff)->get(route('staff.tasks.index'))
            ->assertSee('Call 10 graduates about openings')
            ->assertSee('Due today');
    }

    public function test_today_week_and_past_views_place_tasks_correctly(): void
    {
        $overdue = $this->task(['title' => 'Overdue report', 'due_date' => '2026-09-29']);
        $today = $this->task(['title' => 'Today standup notes', 'due_date' => '2026-10-01']);
        $friday = $this->task(['title' => 'Friday review', 'due_date' => '2026-10-03']);
        $nextWeek = $this->task(['title' => 'Next week planning', 'due_date' => '2026-10-08']);
        $donePast = $this->task(['title' => 'Last week survey', 'due_date' => '2026-09-24', 'status' => 'completed', 'completed_at' => '2026-09-24 15:00:00', 'outcome' => 'Sent to 40 alumni']);

        $this->actingAs($this->staff)->get(route('staff.tasks.index'))
            ->assertOk()
            ->assertSee('Overdue report')
            ->assertSee('Today standup notes')
            ->assertDontSee('Friday review')
            ->assertDontSee('Next week planning');

        $this->actingAs($this->staff)->get(route('staff.tasks.index', ['view' => 'week']))
            ->assertOk()
            ->assertSee('Week of 28 Sep – 04 Oct 2026')
            ->assertSee('Overdue report')
            ->assertSee('Friday review')
            ->assertDontSee('Next week planning')
            ->assertDontSee('Last week survey');

        $this->actingAs($this->staff)->get(route('staff.tasks.index', ['view' => 'week', 'week' => '2026-10-05']))
            ->assertSee('Next week planning')
            ->assertDontSee('Friday review');

        $this->actingAs($this->staff)->get(route('staff.tasks.index', ['view' => 'past']))
            ->assertOk()
            ->assertSee('Last week survey')
            ->assertSee('Sent to 40 alumni')
            ->assertSee('Overdue report')
            ->assertDontSee('Today standup notes');

        $this->actingAs($this->staff)->get(route('staff.tasks.index', ['view' => 'past', 'outcome' => 'completed']))
            ->assertSee('Last week survey')
            ->assertDontSee('Overdue report');
    }

    public function test_completing_a_task_records_outcome_and_counts_towards_the_kpi(): void
    {
        $task = $this->task(['title' => 'Place two graduates', 'due_date' => '2026-10-01', 'appraisal_kpi_id' => $this->kpi->id]);

        $this->actingAs($this->staff)->patch(route('staff.tasks.complete', $task), ['outcome' => 'Both started at Andela.'])
            ->assertSessionHas('success');

        $task->refresh();
        $this->assertSame('completed', $task->status);
        $this->assertSame('100.00', $task->progress_percent);
        $this->assertNotNull($task->completed_at);
        $this->assertSame('Both started at Andela.', $task->outcome);

        $this->actingAs($this->staff)->get(route('staff.tasks.index'))
            ->assertSee('Done today')
            ->assertSee('1/1 this week · 1 done overall');

        // Reopening clears the completion time.
        $this->actingAs($this->staff)->put(route('staff.tasks.update', $task), [
            'title' => $task->title, 'priority' => 'medium', 'status' => 'in_progress', 'progress_percent' => 50,
            'kpi' => 'a:'.$this->kpi->id, 'due_date' => '2026-10-01',
        ])->assertSessionHas('success');
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_supervisor_assigns_to_team_member_and_tracks_team(): void
    {
        $this->actingAs($this->supervisor)->post(route('staff.tasks.store'), [
            'title' => 'Prepare placement report',
            'assigned_to' => $this->staff->id,
            'kpi' => 'a:'.$this->kpi->id,
            'due_date' => '2026-10-02',
            'priority' => 'urgent',
        ])->assertSessionHas('success', 'Task assigned.');

        $task = Task::sole();
        $this->assertSame($this->supervisor->id, $task->created_by);
        $this->assertTrue(UserNotification::where('user_id', $this->staff->id)->where('action_url', '/staff/tasks')->exists());

        $this->actingAs($this->supervisor)->get(route('staff.tasks.index', ['view' => 'team']))
            ->assertOk()
            ->assertSee('Team Tasks')
            ->assertSee('Prepare placement report')
            ->assertSee('Olivia Officer');

        $this->actingAs($this->staff)->get(route('staff.tasks.index', ['view' => 'week']))
            ->assertDontSee('My Team')
            ->assertSee('From Sam Supervisor', false);
    }

    public function test_staff_cannot_assign_outside_their_team_or_use_someone_elses_kpi(): void
    {
        $outsider = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $this->actingAs($this->staff)->post(route('staff.tasks.store'), [
            'title' => 'Not allowed', 'assigned_to' => $outsider->id, 'priority' => 'medium',
        ])->assertSessionHasErrors('assigned_to');

        $this->actingAs($outsider)->post(route('staff.tasks.store'), [
            'title' => 'Borrowed KPI', 'kpi' => 'a:'.$this->kpi->id, 'priority' => 'medium',
        ])->assertSessionHasErrors('kpi');

        $task = $this->task(['title' => 'Private task']);
        $this->actingAs($outsider)->patch(route('staff.tasks.complete', $task))->assertForbidden();
        $this->actingAs($outsider)->delete(route('staff.tasks.destroy', $task))->assertForbidden();

        $this->assertSame(1, Task::count());
    }

    public function test_participants_cannot_open_staff_tasks(): void
    {
        $participant = User::factory()->create(['user_type' => 'participant', 'status' => 'active']);

        $this->actingAs($participant)->get(route('staff.tasks.index'))->assertForbidden();
    }

    public function test_supervisor_sees_linked_tasks_as_evidence_when_reviewing_the_appraisal(): void
    {
        $this->task(['title' => 'Placed three graduates', 'appraisal_kpi_id' => $this->kpi->id, 'due_date' => '2026-09-20',
            'status' => 'completed', 'completed_at' => '2026-09-20 12:00:00', 'outcome' => 'Placed at MTN, Stanbic and Andela.']);
        $this->task(['title' => 'Follow up two more', 'appraisal_kpi_id' => $this->kpi->id, 'due_date' => '2026-10-10']);
        $this->task(['title' => 'Unrelated errand', 'due_date' => '2026-09-20']);

        $appraisal = $this->kpi->kra->appraisal;
        $appraisal->update(['status' => 'supervisor_review']);

        $this->actingAs($this->supervisor)->get(route('staff.performance.show', $appraisal))
            ->assertOk()
            ->assertSee('1 task done')
            ->assertSee('1 open')
            ->assertSee('Placed three graduates')
            ->assertSee('Placed at MTN, Stanbic and Andela.')
            ->assertDontSee('Unrelated errand');
    }
}
