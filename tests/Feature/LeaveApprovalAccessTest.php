<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Leave Approvals is for supervisors (their own team), HR and administrators.
 */
class LeaveApprovalAccessTest extends TestCase
{
    use RefreshDatabase;

    private LeaveType $type;
    private User $supervisor;
    private Employee $teamMember;
    private Employee $otherEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->type = LeaveType::firstOrCreate(['name' => 'Annual Leave'], ['code' => 'AL', 'default_days' => 21, 'is_active' => true]);
        $this->supervisor = $this->staff();
        $this->teamMember = Employee::create(['user_id' => $this->staff('Team Member')->id, 'employee_number' => 'EMP-1', 'supervisor_user_id' => $this->supervisor->id]);
        $this->otherEmployee = Employee::create(['user_id' => $this->staff('Someone Else')->id, 'employee_number' => 'EMP-2']);
    }

    private function staff(string $name = 'Staff Member', array $roles = []): User
    {
        $user = User::factory()->create(['name' => $name, 'user_type' => 'staff', 'status' => 'active']);
        foreach ($roles as $slug) {
            $user->roles()->attach(Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug)]));
        }

        return $user;
    }

    private function leave(Employee $employee, string $status = 'submitted', array $extra = []): LeaveRequest
    {
        return LeaveRequest::create($extra + [
            'employee_id' => $employee->id, 'leave_type_id' => $this->type->id,
            'start_date' => '2026-11-02', 'end_date' => '2026-11-04', 'days_requested' => 3,
            'reason' => 'Family visit', 'status' => $status,
        ]);
    }

    public function test_plain_staff_cannot_open_leave_approvals_or_see_the_link(): void
    {
        $plain = $this->staff('Plain Staff');

        $this->actingAs($plain)->get(route('admin.hr.leave.index'))->assertForbidden();
        $this->actingAs($plain)->get(route('staff.tasks.index'))->assertOk()->assertDontSee(route('admin.hr.leave.index'), false);
    }

    public function test_supervisor_sees_only_their_team_and_can_approve_or_reject_at_their_stage(): void
    {
        $mine = $this->leave($this->teamMember);
        $theirs = $this->leave($this->otherEmployee, 'submitted', ['reason' => 'Not my team']);

        $this->actingAs($this->supervisor)->get(route('admin.hr.leave.index'))
            ->assertOk()->assertSee('Team Member')->assertDontSee('Someone Else');
        $this->actingAs($this->supervisor)->get(route('staff.tasks.index'))->assertSee(route('admin.hr.leave.index'), false);

        $this->actingAs($this->supervisor)->post(route('admin.hr.leave.supervisor-approve', $theirs))->assertForbidden();
        $this->actingAs($this->supervisor)->post(route('admin.hr.leave.supervisor-approve', $mine))->assertRedirect();
        $this->assertSame('supervisor_approved', $mine->fresh()->status);

        // Final approval, edits and cancelling are HR's.
        $this->actingAs($this->supervisor)->post(route('admin.hr.leave.hr-approve', $mine))->assertForbidden();
        $this->actingAs($this->supervisor)->put(route('admin.hr.leave.update', $mine), ['leave_type_id' => $this->type->id, 'start_date' => '2026-11-02', 'end_date' => '2026-11-02'])->assertForbidden();

        $another = $this->leave($this->teamMember, 'pending');
        $this->actingAs($this->supervisor)->post(route('admin.hr.leave.reject', $another), ['decision_notes' => 'Busy week'])->assertRedirect();
        $this->assertSame('rejected', $another->fresh()->status);
    }

    public function test_hr_sees_everything_and_can_approve_edit_reject_and_cancel(): void
    {
        $hr = $this->staff('HR Officer', ['hr']);
        LeaveBalance::create(['employee_id' => $this->otherEmployee->id, 'leave_type_id' => $this->type->id, 'year' => 2026, 'opening_balance' => 21, 'accrued' => 0, 'used' => 0, 'adjustments' => 0, 'remaining' => 21]);

        $team = $this->leave($this->teamMember, 'supervisor_approved');
        $noSupervisor = $this->leave($this->otherEmployee);

        $this->actingAs($hr)->get(route('admin.hr.leave.index'))->assertOk()->assertSee('Team Member')->assertSee('Someone Else');

        // Edit: dates change and days are recalculated (Mon 2 – Fri 6 Nov = 5 working days).
        $this->actingAs($hr)->put(route('admin.hr.leave.update', $noSupervisor), [
            'leave_type_id' => $this->type->id, 'start_date' => '2026-11-02', 'end_date' => '2026-11-08', 'decision_notes' => 'Adjusted per handover',
        ])->assertSessionHasNoErrors();
        $this->assertEquals(5, (float) $noSupervisor->fresh()->days_requested);

        // No supervisor on record: HR may approve directly; the balance is used.
        $this->actingAs($hr)->post(route('admin.hr.leave.hr-approve', $noSupervisor))->assertRedirect();
        $this->assertSame('hr_approved', $noSupervisor->fresh()->status);
        $this->assertEquals(16, (float) LeaveBalance::first()->remaining);

        // Cancelling approved leave returns the days.
        $this->actingAs($hr)->post(route('admin.hr.leave.cancel', $noSupervisor), ['decision_notes' => 'Trip postponed'])->assertRedirect();
        $this->assertSame('cancelled', $noSupervisor->fresh()->status);
        $this->assertEquals(21, (float) LeaveBalance::first()->remaining);

        $this->actingAs($hr)->post(route('admin.hr.leave.reject', $team), ['decision_notes' => 'Clashes with audit'])->assertRedirect();
        $this->assertSame('rejected', $team->fresh()->status);
    }

    public function test_hr_cannot_skip_the_supervisor_when_one_is_assigned(): void
    {
        $hr = $this->staff('HR Officer', ['hr']);
        $leave = $this->leave($this->teamMember);

        $this->actingAs($hr)->post(route('admin.hr.leave.hr-approve', $leave))->assertForbidden();
        $this->assertSame('submitted', $leave->fresh()->status);
    }

    public function test_bulk_delete_is_hr_only(): void
    {
        $leave = $this->leave($this->teamMember);

        $this->actingAs($this->supervisor)->delete(route('admin.hr.leave.bulk-destroy'), ['ids' => [$leave->id]])->assertForbidden();
        $this->assertDatabaseHas('leave_requests', ['id' => $leave->id]);
    }
}
