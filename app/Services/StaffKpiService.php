<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\AppraisalCycle;
use App\Models\Employee;
use App\Models\EmploymentContract;
use App\Models\StaffKpi;
use App\Models\Task;
use App\Models\User;
use App\Services\HR\AppraisalWorkflowService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Contract KPIs: staff set KPIs for their employment contract, supervisors approve them,
 * tasks are linked to them, and each quarterly appraisal is filled from the approved set.
 */
class StaffKpiService
{
    /** Appraisal statuses in which the employee may still change KRAs/KPIs (as in saveEmployee). */
    public const EMPLOYEE_EDITABLE = ['draft', 'in_progress', 'returned_for_revision', 'goal_setting', 'self_assessment'];

    public function __construct(
        private StaffTaskService $tasks,
        private UserNotificationService $notifications,
        private AppraisalWorkflowService $workflow,
    ) {
    }

    /**
     * The contract to plan against: the requested one, else the active one, else the latest.
     */
    public function contractFor(Employee $employee, ?int $contractId = null): ?EmploymentContract
    {
        $contracts = $employee->contracts()->orderByDesc('start_date')->get();

        return ($contractId ? $contracts->firstWhere('id', $contractId) : null)
            ?? $contracts->firstWhere('status', 'active')
            ?? $contracts->first();
    }

    public function kpis(Employee $employee, ?EmploymentContract $contract): Collection
    {
        return $employee->kpis()
            ->where('employment_contract_id', $contract?->id)
            ->get();
    }

    /**
     * Quarterly appraisal cycles that fall within the contract (all quarterly cycles without one).
     */
    public function quarters(?EmploymentContract $contract): Collection
    {
        return AppraisalCycle::query()
            ->where('cycle_type', 'quarterly')
            ->when($contract, fn ($query) => $query
                ->whereDate('end_date', '>=', $contract->start_date)
                ->when($contract->end_date, fn ($q) => $q->whereDate('start_date', '<=', $contract->end_date)))
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Per quarter: the appraisal (if started) and, per KPI, tasks done and open in that quarter.
     */
    public function quarterProgress(Employee $employee, Collection $kpis, Collection $quarters): Collection
    {
        $appraisals = Appraisal::where('employee_id', $employee->id)
            ->whereIn('appraisal_cycle_id', $quarters->pluck('id'))
            ->withCount('kras')
            ->get()
            ->keyBy('appraisal_cycle_id');

        $tasks = Task::whereIn('staff_kpi_id', $kpis->pluck('id'))
            ->where('assigned_to', $employee->user_id)
            ->get(['id', 'staff_kpi_id', 'status', 'due_date', 'completed_at']);

        return $quarters->map(function (AppraisalCycle $cycle) use ($appraisals, $tasks, $kpis) {
            $inQuarter = $tasks->filter(fn (Task $task) => ($task->completed_at ?? $task->due_date)?->between($cycle->start_date, $cycle->end_date->copy()->endOfDay()));

            return [
                'cycle' => $cycle,
                'appraisal' => $appraisals->get($cycle->id),
                'current' => today()->between($cycle->start_date, $cycle->end_date),
                'done' => $inQuarter->where('status', 'completed')->count(),
                'total' => $inQuarter->count(),
                'per_kpi' => $kpis->mapWithKeys(fn (StaffKpi $kpi) => [$kpi->id => [
                    'done' => $inQuarter->where('staff_kpi_id', $kpi->id)->where('status', 'completed')->count(),
                    'total' => $inQuarter->where('staff_kpi_id', $kpi->id)->count(),
                ]]),
            ];
        });
    }

    /**
     * Send draft or returned KPIs to the supervisor for approval.
     */
    public function submit(Employee $employee, ?EmploymentContract $contract): int
    {
        $kpis = $this->kpis($employee, $contract)->filter->isEditableByOwner();

        if ($kpis->isEmpty()) {
            throw ValidationException::withMessages(['kpis' => 'There are no draft or returned KPIs to submit.']);
        }

        StaffKpi::whereKey($kpis->pluck('id'))->update(['status' => 'submitted', 'review_comment' => null]);

        if ($employee->supervisor_user_id && $supervisor = User::find($employee->supervisor_user_id)) {
            $this->notifySafely($supervisor, 'KPIs awaiting your approval',
                ($employee->user?->name ?? 'A team member').' submitted '.$kpis->count().' contract '.str('KPI')->plural($kpis->count()).' for approval.');
        }

        return $kpis->count();
    }

    /**
     * Supervisor decision on submitted KPIs of one employee.
     */
    public function review(User $reviewer, Employee $employee, array $kpiIds, string $decision, ?string $comment): int
    {
        abort_unless($this->tasks->teamMemberIds($reviewer)->contains((int) $employee->user_id) || $reviewer->isSuperAdmin(), 403);

        if ($decision === 'return' && blank($comment)) {
            throw ValidationException::withMessages(['review_comment' => 'Say what should change when returning KPIs.']);
        }

        $count = StaffKpi::where('employee_id', $employee->id)
            ->whereKey($kpiIds)
            ->where('status', 'submitted')
            ->update([
                'status' => $decision === 'approve' ? 'approved' : 'returned',
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_comment' => $comment,
            ]);

        if ($count && $employee->user) {
            $this->notifySafely($employee->user,
                $decision === 'approve' ? 'Your KPIs were approved' : 'Your KPIs were returned for changes',
                $reviewer->name.($decision === 'approve' ? ' approved ' : ' returned ').$count.' '.str('KPI')->plural($count).'.'.($comment ? ' '.$comment : ''));
        }

        return $count;
    }

    /**
     * Start (or open) the quarter's appraisal and fill it with the approved contract KPIs.
     */
    public function startQuarter(Employee $employee, AppraisalCycle $cycle, ?EmploymentContract $contract): Appraisal
    {
        abort_unless($cycle->cycle_type === 'quarterly', 422, 'Only quarterly cycles can be started from My KPIs.');

        $appraisal = DB::transaction(function () use ($employee, $cycle) {
            $appraisal = Appraisal::firstOrCreate(
                ['appraisal_cycle_id' => $cycle->id, 'employee_id' => $employee->id],
                ['manager_user_id' => $employee->supervisor_user_id, 'status' => 'draft']
            );

            if ($appraisal->wasRecentlyCreated) {
                $this->workflow->note($appraisal, 'Quarterly appraisal started from contract KPIs.');
            }

            return $appraisal;
        });

        $this->importInto($appraisal, $contract);

        return $appraisal;
    }

    /**
     * Add approved contract KPIs that are not yet in the appraisal, grouped into KRAs.
     * Existing rows (and any scores on them) are never changed.
     */
    public function importInto(Appraisal $appraisal, ?EmploymentContract $contract): int
    {
        if ($appraisal->locked_at || ! in_array($appraisal->status, self::EMPLOYEE_EDITABLE, true)) {
            throw ValidationException::withMessages(['appraisal' => 'This appraisal can no longer take new KPIs.']);
        }

        $employee = $appraisal->employee;
        $approved = $this->kpis($employee, $contract)->where('status', 'approved');

        $appraisal->load('kras.kpis');
        $alreadyIn = $appraisal->kras->flatMap->kpis->pluck('staff_kpi_id')->filter();
        $toAdd = $approved->reject(fn (StaffKpi $kpi) => $alreadyIn->contains($kpi->id));

        DB::transaction(function () use ($appraisal, $toAdd) {
            foreach ($toAdd->groupBy('kra') as $kraTitle => $kpis) {
                $kra = $appraisal->kras->firstWhere('title', $kraTitle)
                    ?? $appraisal->kras()->create([
                        'title' => $kraTitle,
                        'weight' => 0,
                        'position' => $appraisal->kras->count(),
                    ]);

                foreach ($kpis as $kpi) {
                    $kra->kpis()->create([
                        'staff_kpi_id' => $kpi->id,
                        'title' => $kpi->title,
                        'description' => $kpi->description,
                        'measurement_method' => $kpi->measurement_method,
                        'target' => $kpi->target,
                        'unit' => $kpi->unit,
                        'weight' => 0,
                        'position' => $kra->kpis()->count(),
                    ]);
                }

                $this->rebalance($kra);
                $appraisal->setRelation('kras', $appraisal->kras()->with('kpis')->get());
            }
        });

        return $toAdd->count();
    }

    /**
     * Contract weights are shares of the whole plan; appraisals need KRA weights that total 100
     * and KPI weights that total 100 inside each KRA. For a KRA made only of contract KPIs,
     * the KRA takes the sum of its KPIs' plan weights and each KPI its share of that sum.
     * KRAs mixing typed-in KPIs are left for the employee to balance.
     */
    private function rebalance(\App\Models\AppraisalKra $kra): void
    {
        $rows = $kra->kpis()->with('staffKpi')->orderBy('position')->get();

        if ($rows->isEmpty() || $rows->contains(fn ($row) => ! $row->staffKpi)) {
            return;
        }

        $total = (float) $rows->sum(fn ($row) => (float) $row->staffKpi->weight);
        $assigned = 0.0;

        foreach ($rows->values() as $index => $row) {
            $share = $index === $rows->count() - 1
                ? round(100 - $assigned, 2)
                : ($total > 0 ? round((float) $row->staffKpi->weight / $total * 100, 2) : round(100 / $rows->count(), 2));
            $assigned += $share;
            $row->update(['weight' => $share]);
        }

        $kra->update(['weight' => round($total, 2)]);
    }

    public function pendingTeamReviews(User $supervisor): Collection
    {
        $teamIds = $this->tasks->teamMemberIds($supervisor);

        return StaffKpi::with(['employee.user', 'contract'])
            ->where('status', 'submitted')
            ->whereHas('employee', fn ($query) => $query->whereIn('user_id', $teamIds))
            ->orderBy('employee_id')
            ->orderBy('position')
            ->get()
            ->groupBy('employee_id');
    }

    private function notifySafely(User $user, string $title, string $message): void
    {
        try {
            $this->notifications->send($user, 'performance', $title, $message, route('staff.kpis.index'));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
