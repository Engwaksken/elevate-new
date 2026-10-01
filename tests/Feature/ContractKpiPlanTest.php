<?php

namespace Tests\Feature;

use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\Employee;
use App\Models\EmploymentContract;
use App\Models\StaffKpi;
use App\Models\Task;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\HR\AppraisalScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContractKpiPlanTest extends TestCase
{
    use RefreshDatabase;

    private User $supervisor;
    private User $staff;
    private Employee $employee;
    private EmploymentContract $contract;
    private AppraisalCycle $q3;
    private AppraisalCycle $q4;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-01 09:00:00');

        $this->supervisor = User::factory()->create(['user_type' => 'staff', 'status' => 'active', 'name' => 'Sam Supervisor']);
        $this->staff = User::factory()->create(['user_type' => 'staff', 'status' => 'active', 'name' => 'Olivia Officer']);
        $this->employee = Employee::create(['user_id' => $this->staff->id, 'employee_number' => 'EMP-9', 'supervisor_user_id' => $this->supervisor->id]);
        $this->contract = EmploymentContract::create(['employee_id' => $this->employee->id, 'contract_type' => 'fixed_term', 'start_date' => '2026-07-01', 'end_date' => '2027-06-30', 'status' => 'active']);

        $this->q3 = AppraisalCycle::create(['name' => 'Q3 2026', 'cycle_type' => 'quarterly', 'start_date' => '2026-07-01', 'end_date' => '2026-09-30']);
        $this->q4 = AppraisalCycle::create(['name' => 'Q4 2026', 'cycle_type' => 'quarterly', 'start_date' => '2026-10-01', 'end_date' => '2026-12-31']);
        AppraisalCycle::create(['name' => 'Q1 2026 (before contract)', 'cycle_type' => 'quarterly', 'start_date' => '2026-01-01', 'end_date' => '2026-03-31']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function addKpi(string $kra, string $title, float $weight): void
    {
        $this->actingAs($this->staff)->post(route('staff.kpis.store'), [
            'contract_id' => $this->contract->id, 'kra' => $kra, 'title' => $title,
            'target' => '40', 'unit' => 'graduates', 'weight' => $weight,
        ])->assertSessionHas('success');
    }

    private function approvedPlan(): void
    {
        $this->addKpi('Graduate placement', 'Graduates placed in jobs', 40);
        $this->addKpi('Graduate placement', 'Employer partners signed', 20);
        $this->addKpi('Reporting', 'Monthly reports on time', 40);
        StaffKpi::query()->update(['status' => 'approved', 'reviewed_by' => $this->supervisor->id]);
    }

    public function test_staff_create_kpis_for_their_contract_and_supervisor_approves(): void
    {
        $this->actingAs($this->staff)->get(route('staff.kpis.index'))
            ->assertOk()
            ->assertSee('Fixed term contract')
            ->assertSee('01 Jul 2026 – 30 Jun 2027')
            ->assertSee('Q3 2026')
            ->assertDontSee('Q1 2026 (before contract)');

        $this->addKpi('Graduate placement', 'Graduates placed in jobs', 60);
        $this->addKpi('Reporting', 'Monthly reports on time', 40);

        $kpis = StaffKpi::all();
        $this->assertSame([$this->contract->id], $kpis->pluck('employment_contract_id')->unique()->values()->all());
        $this->assertSame(['draft'], $kpis->pluck('status')->unique()->values()->all());

        $this->actingAs($this->staff)->get(route('staff.kpis.index'))->assertSee('Total weight 100%');

        $this->actingAs($this->staff)->post(route('staff.kpis.submit'), ['contract_id' => $this->contract->id])->assertSessionHas('success');
        $this->assertSame(['submitted'], StaffKpi::pluck('status')->unique()->values()->all());
        $this->assertTrue(UserNotification::where('user_id', $this->supervisor->id)->where('title', 'KPIs awaiting your approval')->exists());

        $this->actingAs($this->supervisor)->get(route('staff.kpis.index'))
            ->assertOk()
            ->assertSee('Team KPIs awaiting your approval')
            ->assertSee('Olivia Officer')
            ->assertSee('Graduates placed in jobs');

        // Returning needs a comment.
        $this->actingAs($this->supervisor)->post(route('staff.kpis.review', $this->employee), [
            'kpi_ids' => $kpis->pluck('id')->all(), 'decision' => 'return',
        ])->assertSessionHasErrors('review_comment');

        $this->actingAs($this->supervisor)->post(route('staff.kpis.review', $this->employee), [
            'kpi_ids' => $kpis->pluck('id')->all(), 'decision' => 'approve',
        ])->assertSessionHas('success');

        $this->assertSame(['approved'], StaffKpi::pluck('status')->unique()->values()->all());
        $this->assertTrue(UserNotification::where('user_id', $this->staff->id)->where('title', 'Your KPIs were approved')->exists());

        // Editing an approved KPI sends it back for re-approval.
        $kpi = StaffKpi::first();
        $this->actingAs($this->staff)->put(route('staff.kpis.update', $kpi), [
            'kra' => $kpi->kra, 'title' => 'Graduates placed in decent jobs', 'weight' => 60,
        ]);
        $this->assertSame('draft', $kpi->fresh()->status);
    }

    public function test_only_the_supervisor_can_review(): void
    {
        $this->addKpi('Reporting', 'Monthly reports', 100);
        $this->actingAs($this->staff)->post(route('staff.kpis.submit'));
        $stranger = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $this->actingAs($stranger)->post(route('staff.kpis.review', $this->employee), [
            'kpi_ids' => StaffKpi::pluck('id')->all(), 'decision' => 'approve',
        ])->assertForbidden();

        $this->assertSame('submitted', StaffKpi::first()->status);
    }

    public function test_tasks_link_to_contract_kpis_and_count_per_quarter(): void
    {
        $this->approvedPlan();
        $placed = StaffKpi::where('title', 'Graduates placed in jobs')->first();

        $this->actingAs($this->staff)->get(route('staff.tasks.index'))
            ->assertSee('Graduates placed in jobs')
            ->assertSee('value="s:'.$placed->id.'"', false);

        $this->actingAs($this->staff)->post(route('staff.tasks.store'), [
            'title' => 'Placement drive', 'kpi' => 's:'.$placed->id, 'due_date' => '2026-10-01', 'priority' => 'medium',
        ])->assertSessionHas('success');
        $this->assertSame($placed->id, Task::sole()->staff_kpi_id);

        // One task done last quarter, one this quarter.
        Task::create(['title' => 'September follow-ups', 'assigned_to' => $this->staff->id, 'created_by' => $this->staff->id, 'staff_kpi_id' => $placed->id,
            'priority' => 'medium', 'status' => 'completed', 'progress_percent' => 100, 'due_date' => '2026-09-15', 'completed_at' => '2026-09-15 12:00:00']);

        $this->actingAs($this->staff)->get(route('staff.kpis.index'))
            ->assertOk()
            ->assertSeeInOrder(['Q3 2026', '1/1 linked tasks done', 'Q4 2026', '0/1 linked tasks done']);
    }

    public function test_starting_a_quarter_fills_the_appraisal_with_balanced_weights_and_quarter_evidence(): void
    {
        $this->approvedPlan();
        $placed = StaffKpi::where('title', 'Graduates placed in jobs')->first();

        Task::create(['title' => 'Q3 placement work', 'assigned_to' => $this->staff->id, 'created_by' => $this->staff->id, 'staff_kpi_id' => $placed->id,
            'priority' => 'medium', 'status' => 'completed', 'progress_percent' => 100, 'due_date' => '2026-09-10', 'completed_at' => '2026-09-10 10:00:00', 'outcome' => 'Six graduates placed.']);
        Task::create(['title' => 'Q4 placement work', 'assigned_to' => $this->staff->id, 'created_by' => $this->staff->id, 'staff_kpi_id' => $placed->id,
            'priority' => 'medium', 'status' => 'completed', 'progress_percent' => 100, 'due_date' => '2026-10-01', 'completed_at' => '2026-10-01 08:00:00']);

        $this->actingAs($this->staff)->post(route('staff.kpis.quarters.start', $this->q3), ['contract_id' => $this->contract->id])
            ->assertRedirect();

        $appraisal = Appraisal::where('appraisal_cycle_id', $this->q3->id)->where('employee_id', $this->employee->id)->firstOrFail();
        $appraisal->load('kras.kpis');

        $this->assertSame('draft', $appraisal->status);
        $this->assertSame($this->supervisor->id, $appraisal->manager_user_id);
        $this->assertEqualsCanonicalizing(['Graduate placement', 'Reporting'], $appraisal->kras->pluck('title')->all());
        $this->assertSame([], app(AppraisalScoreService::class)->weightErrors($appraisal), 'Imported weights must satisfy the appraisal rules');

        $placement = $appraisal->kras->firstWhere('title', 'Graduate placement');
        $this->assertEquals(60, (float) $placement->weight);
        $this->assertEquals([66.67, 33.33], $placement->kpis->pluck('weight')->map(fn ($w) => (float) $w)->all());
        $this->assertSame($placed->id, $placement->kpis->first()->staff_kpi_id);

        // Starting again does not duplicate.
        $this->actingAs($this->staff)->post(route('staff.kpis.quarters.start', $this->q3), ['contract_id' => $this->contract->id]);
        $this->assertSame(3, $appraisal->fresh()->kras->flatMap->kpis->count());

        // The supervisor sees only Q3's tasks as evidence on this appraisal.
        $appraisal->update(['status' => 'supervisor_review']);
        $this->actingAs($this->supervisor)->get(route('staff.performance.show', $appraisal))
            ->assertOk()
            ->assertSee('Q3 placement work')
            ->assertSee('Six graduates placed.')
            ->assertDontSee('Q4 placement work');
    }

    public function test_employee_can_add_newly_approved_kpis_to_an_existing_appraisal(): void
    {
        $this->addKpi('Reporting', 'Monthly reports on time', 100);
        StaffKpi::query()->update(['status' => 'approved']);

        $appraisal = Appraisal::create(['appraisal_cycle_id' => $this->q4->id, 'employee_id' => $this->employee->id, 'manager_user_id' => $this->supervisor->id, 'status' => 'self_assessment']);

        $this->actingAs($this->staff)->get(route('staff.performance.show', $appraisal))
            ->assertOk()
            ->assertSee('Add my contract KPIs');

        $this->actingAs($this->staff)->post(route('staff.kpis.appraisals.import', $appraisal))
            ->assertSessionHas('success', '1 contract KPI added to this appraisal.');

        $this->assertSame('Monthly reports on time', $appraisal->fresh()->kras->first()->kpis->first()->title);

        $this->actingAs($this->staff)->get(route('staff.performance.show', $appraisal))->assertDontSee('Add my contract KPIs');

        // Someone else cannot import into this appraisal.
        $other = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);
        Employee::create(['user_id' => $other->id, 'employee_number' => 'EMP-10']);
        $this->actingAs($other)->post(route('staff.kpis.appraisals.import', $appraisal))->assertForbidden();
    }

    public function test_staff_without_an_employee_record_see_guidance(): void
    {
        $newcomer = User::factory()->create(['user_type' => 'staff', 'status' => 'active']);

        $this->actingAs($newcomer)->get(route('staff.kpis.index'))
            ->assertOk()
            ->assertSee('No employee record yet');

        $this->actingAs($newcomer)->post(route('staff.kpis.store'), ['kra' => 'X', 'title' => 'Y', 'weight' => 10])->assertForbidden();
    }
}
