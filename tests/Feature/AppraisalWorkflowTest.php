<?php

namespace Tests\Feature;

use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppraisalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $employeeUser;
    private User $supervisor;
    private Appraisal $appraisal;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employeeUser = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->supervisor = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $employee = Employee::create([
            'user_id' => $this->employeeUser->id,
            'employee_number' => 'EMP-001',
            'supervisor_user_id' => $this->supervisor->id,
        ]);

        $cycle = AppraisalCycle::create([
            'name' => 'Annual 2026',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        $this->appraisal = Appraisal::create([
            'appraisal_cycle_id' => $cycle->id,
            'employee_id' => $employee->id,
            'manager_user_id' => $this->supervisor->id,
            'status' => 'draft',
        ]);
    }

    private function url(string $suffix = ''): string
    {
        return '/staff/performance/'.$this->appraisal->id.$suffix;
    }

    private function employeePayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'achievements' => 'Delivered the cohort.',
            'kras' => [
                [
                    'title' => 'Programme delivery',
                    'weight' => 60,
                    'description' => 'Run cohorts',
                    'employee_comment' => 'Went well',
                    'kpis' => [
                        ['title' => 'Sessions held', 'target' => '10', 'actual_achievement' => '10', 'weight' => 100, 'employee_score' => 4],
                    ],
                ],
                [
                    'title' => 'Reporting',
                    'weight' => 40,
                    'kpis' => [
                        ['title' => 'Reports filed', 'target' => '4', 'actual_achievement' => '3', 'weight' => 100, 'employee_score' => 3],
                    ],
                ],
            ],
        ], $overrides);
    }

    public function test_full_workflow_from_draft_to_completed(): void
    {
        $this->actingAs($this->employeeUser)->get($this->url())->assertOk();

        $this->actingAs($this->employeeUser)
            ->put($this->url('/employee'), $this->employeePayload())
            ->assertSessionHasNoErrors();

        $appraisal = $this->appraisal->fresh();
        $this->assertSame('in_progress', $appraisal->status);
        $this->assertCount(2, $appraisal->kras);

        $this->actingAs($this->employeeUser)->post($this->url('/submit'))->assertSessionHas('success');
        $this->assertSame('submitted', $this->appraisal->fresh()->status);

        $kras = $this->appraisal->fresh()->kras()->with('kpis')->get();
        $supervisorKras = [];
        foreach ($kras as $kra) {
            $supervisorKras[$kra->id] = [
                'supervisor_rating' => 4,
                'kpis' => $kra->kpis->mapWithKeys(fn ($kpi) => [$kpi->id => ['supervisor_score' => 4]])->all(),
            ];
        }

        $this->actingAs($this->supervisor)->get($this->url())->assertOk();
        $this->actingAs($this->supervisor)
            ->put($this->url('/supervisor'), ['manager_comments' => 'Good', 'kras' => $supervisorKras])
            ->assertSessionHasNoErrors();
        $this->assertSame('supervisor_review', $this->appraisal->fresh()->status);

        $this->actingAs($this->supervisor)->post($this->url('/review-complete'));
        $this->assertSame('meeting_pending', $this->appraisal->fresh()->status);

        $agreed = [];
        foreach ($kras as $kra) {
            $agreed[$kra->id] = [
                'agreed_rating' => 5,
                'kpis' => $kra->kpis->mapWithKeys(fn ($kpi) => [$kpi->id => ['agreed_score' => 5]])->all(),
            ];
        }

        $this->actingAs($this->supervisor)
            ->put($this->url('/meeting'), ['meeting_at' => '2026-10-01 10:00', 'kras' => $agreed])
            ->assertSessionHasNoErrors();

        $appraisal = $this->appraisal->fresh();
        $this->assertSame('meeting_completed', $appraisal->status);
        $this->assertEquals(100.0, (float) $appraisal->performance_percent);

        $this->actingAs($this->employeeUser)->get($this->url())->assertOk();
        $this->actingAs($this->employeeUser)->post($this->url('/employee-confirm'), ['comment' => 'Agreed']);
        $this->assertSame('employee_confirmation', $this->appraisal->fresh()->status);

        $this->actingAs($this->supervisor)->post($this->url('/supervisor-confirm'));

        $appraisal = $this->appraisal->fresh();
        $this->assertSame('completed', $appraisal->status);
        $this->assertNotNull($appraisal->supervisor_confirmed_at);
        $this->assertSame(
            ['in_progress', 'submitted', 'supervisor_review', 'meeting_pending', 'meeting_completed', 'employee_confirmation', 'supervisor_confirmation', 'completed'],
            $appraisal->statusHistory()->orderBy('id')->pluck('to_status')->all()
        );

        $this->actingAs($this->supervisor)->get($this->url())->assertOk();
    }

    public function test_employee_edit_preserves_supervisor_scores_on_existing_rows(): void
    {
        $this->actingAs($this->employeeUser)->put($this->url('/employee'), $this->employeePayload());

        $kra = $this->appraisal->kras()->first();
        $kpi = $kra->kpis()->first();
        $kra->update(['supervisor_rating' => 3]);
        $kpi->update(['supervisor_score' => 2]);

        $payload = $this->employeePayload();
        $payload['kras'][0]['id'] = $kra->id;
        $payload['kras'][0]['title'] = 'Programme delivery (revised)';
        $payload['kras'][0]['kpis'][0]['id'] = $kpi->id;

        $this->actingAs($this->employeeUser)->put($this->url('/employee'), $payload)->assertSessionHasNoErrors();

        $this->assertSame('Programme delivery (revised)', $kra->fresh()->title);
        $this->assertEquals(3, (float) $kra->fresh()->supervisor_rating);
        $this->assertEquals(2, (float) $kpi->fresh()->supervisor_score);
        $this->assertCount(2, $this->appraisal->fresh()->kras);
    }

    public function test_employee_cannot_touch_another_appraisals_kra(): void
    {
        $this->actingAs($this->employeeUser)->put($this->url('/employee'), $this->employeePayload());

        $other = Appraisal::create([
            'appraisal_cycle_id' => AppraisalCycle::create(['name' => 'Other', 'start_date' => '2026-01-01', 'end_date' => '2026-12-31'])->id,
            'employee_id' => $this->appraisal->employee_id,
            'manager_user_id' => $this->supervisor->id,
            'status' => 'draft',
        ]);
        $foreignKra = $other->kras()->create(['title' => 'Foreign', 'weight' => 100]);

        $payload = $this->employeePayload();
        $payload['kras'][0]['id'] = $foreignKra->id;

        $this->actingAs($this->employeeUser)->put($this->url('/employee'), $payload)->assertNotFound();
        $this->assertSame('Foreign', $foreignKra->fresh()->title);
    }

    public function test_submit_is_blocked_when_weights_do_not_total_100(): void
    {
        $this->actingAs($this->employeeUser)->put($this->url('/employee'), $this->employeePayload(['kras' => [1 => ['weight' => 10]]]));

        $this->actingAs($this->employeeUser)->post($this->url('/submit'))->assertSessionHas('error');
        $this->assertSame('in_progress', $this->appraisal->fresh()->status);
    }

    public function test_supervisor_can_return_for_revision_and_employee_can_edit_again(): void
    {
        $this->actingAs($this->employeeUser)->put($this->url('/employee'), $this->employeePayload());
        $this->actingAs($this->employeeUser)->post($this->url('/submit'));

        $this->actingAs($this->supervisor)
            ->post($this->url('/return'), ['return_comment' => 'Add evidence'])
            ->assertSessionHas('success');

        $appraisal = $this->appraisal->fresh();
        $this->assertSame('returned_for_revision', $appraisal->status);
        $this->assertNull($appraisal->employee_submitted_at);

        $this->actingAs($this->employeeUser)->get($this->url())->assertOk()->assertSee('Add evidence');
    }

    private function hrAdmin(): User
    {
        $admin = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $admin->roles()->attach(Role::create(['name' => 'Super Admin', 'slug' => 'super-admin'])->id);

        return $admin;
    }

    public function test_hr_can_lock_and_reopen_an_appraisal(): void
    {
        $hr = $this->hrAdmin();
        $this->actingAs($this->employeeUser)->put($this->url('/employee'), $this->employeePayload());
        $this->appraisal->update(['status' => 'completed', 'employee_confirmed_at' => now(), 'supervisor_confirmed_at' => now()]);

        $this->actingAs($hr)->get('/admin/hr/appraisals')->assertOk()->assertSee('Lock Appraisal');

        $this->actingAs($hr)
            ->post('/admin/hr/appraisals/'.$this->appraisal->id.'/lock', ['reason' => 'Cycle closed'])
            ->assertSessionHas('success');

        $appraisal = $this->appraisal->fresh();
        $this->assertNotNull($appraisal->locked_at);
        $this->assertSame('completed', $appraisal->status);
        $this->assertSame('Locked by HR: Cycle closed', $appraisal->statusHistory()->latest('id')->value('comment'));

        $this->actingAs($hr)->get('/admin/hr/appraisals')->assertOk()->assertSee('Reopen Appraisal');
        $this->actingAs($hr)->get($this->url())->assertOk();

        $this->actingAs($hr)
            ->post('/admin/hr/appraisals/'.$this->appraisal->id.'/reopen', [])
            ->assertSessionHasErrors('reason');

        $this->actingAs($hr)
            ->post('/admin/hr/appraisals/'.$this->appraisal->id.'/reopen', ['reason' => 'Score dispute'])
            ->assertSessionHas('success');

        $appraisal = $this->appraisal->fresh();
        $this->assertNull($appraisal->locked_at);
        $this->assertSame('returned_for_revision', $appraisal->status);
        $this->assertSame('Score dispute', $appraisal->reopened_reason);
        $this->assertNull($appraisal->employee_confirmed_at);
        $this->assertCount(2, $appraisal->kras);

        $this->actingAs($this->employeeUser)
            ->put($this->url('/employee'), $this->employeePayload())
            ->assertSessionHasNoErrors();
    }

    public function test_lock_and_reopen_are_restricted(): void
    {
        $this->actingAs($this->supervisor)
            ->post('/admin/hr/appraisals/'.$this->appraisal->id.'/lock')
            ->assertForbidden();

        $hr = $this->hrAdmin();
        $this->actingAs($hr)
            ->post('/admin/hr/appraisals/'.$this->appraisal->id.'/reopen', ['reason' => 'x'])
            ->assertStatus(422);
    }

    public function test_hr_finalise_is_blocked_for_workspace_appraisals(): void
    {
        $this->appraisal->update([
            'status' => 'meeting_pending',
            'employee_submitted_at' => now(),
            'manager_submitted_at' => now(),
        ]);

        $this->actingAs($this->hrAdmin())
            ->post('/admin/hr/appraisals/'.$this->appraisal->id.'/finalise')
            ->assertStatus(422);

        $this->assertSame('meeting_pending', $this->appraisal->fresh()->status);
    }

    public function test_role_and_stage_guards(): void
    {
        $stranger = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        $this->actingAs($stranger)->get($this->url())->assertForbidden();

        $this->actingAs($this->supervisor)->put($this->url('/employee'), $this->employeePayload())->assertForbidden();
        $this->actingAs($this->employeeUser)->post($this->url('/review-complete'))->assertForbidden();

        $this->actingAs($this->supervisor)->post($this->url('/review-complete'))->assertStatus(422);

        $this->appraisal->update(['locked_at' => now()]);
        $this->actingAs($this->employeeUser)->put($this->url('/employee'), $this->employeePayload())->assertStatus(422);
    }
}
