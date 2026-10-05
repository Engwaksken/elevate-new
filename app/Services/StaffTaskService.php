<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\AppraisalKpi;
use App\Models\Employee;
use App\Models\StaffKpi;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Personal staff tasks: daily, weekly and past views, linked to the KPIs in each
 * person's appraisal so day-to-day work can be traced to performance targets.
 */
class StaffTaskService
{
    /**
     * Staff the user supervises: direct reports on the HR record or in an appraisal.
     */
    public function teamMemberIds(User $user): Collection
    {
        return Employee::where('supervisor_user_id', $user->id)->pluck('user_id')
            ->merge(Appraisal::where('manager_user_id', $user->id)
                ->join('employees', 'employees.id', '=', 'appraisals.employee_id')
                ->pluck('employees.user_id'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->reject(fn (int $id) => $id === $user->id)
            ->unique()
            ->values();
    }

    /**
     * People the user may assign tasks to: themselves and their team.
     */
    public function assignableUsers(User $user): Collection
    {
        return User::whereIn('id', $this->teamMemberIds($user)->push($user->id))
            ->orderByRaw('id = ? desc', [$user->id])
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function canManage(User $user, Task $task): bool
    {
        return (int) $task->assigned_to === $user->id
            || (int) $task->created_by === $user->id
            || $this->teamMemberIds($user)->contains((int) $task->assigned_to);
    }

    /**
     * KPIs a task can be linked to, for a select: each person's contract KPIs (key "s:<id>")
     * plus KPIs typed directly into a current appraisal that did not come from the contract ("a:<id>").
     *
     * @return Collection<int, array{key: string, type: string, id: int, owner_id: int, kra: string, title: string, source: ?string}>
     */
    public function linkableKpis(iterable $userIds): Collection
    {
        $userIds = collect($userIds)->map(fn ($id) => (int) $id)->unique();
        $employees = Employee::whereIn('user_id', $userIds)->pluck('user_id', 'id');

        $contractKpis = StaffKpi::with('contract')
            ->whereIn('employee_id', $employees->keys())
            ->where(fn (Builder $query) => $query
                ->whereNull('employment_contract_id')
                ->orWhereHas('contract', fn (Builder $contract) => $contract->whereIn('status', ['active', 'draft'])))
            ->orderBy('position')
            ->get()
            ->map(fn (StaffKpi $kpi) => [
                'key' => 's:'.$kpi->id,
                'type' => 'staff',
                'id' => $kpi->id,
                'owner_id' => (int) $employees[$kpi->employee_id],
                'kra' => $kpi->kra,
                'title' => $kpi->title,
                'source' => 'Contract KPIs'.($kpi->status === 'approved' ? '' : ' ('.$kpi->status.')'),
            ]);

        $appraisalKpis = AppraisalKpi::query()
            ->with('kra.appraisal.cycle', 'kra.appraisal.employee:id,user_id')
            ->whereNull('staff_kpi_id')
            ->whereHas('kra.appraisal', fn (Builder $appraisal) => $appraisal
                ->where('status', '!=', 'completed')
                ->whereHas('employee', fn (Builder $employee) => $employee->whereIn('user_id', $userIds)))
            ->orderBy('appraisal_kra_id')
            ->orderBy('position')
            ->get()
            ->map(fn (AppraisalKpi $kpi) => [
                'key' => 'a:'.$kpi->id,
                'type' => 'appraisal',
                'id' => $kpi->id,
                'owner_id' => (int) $kpi->kra->appraisal->employee->user_id,
                'kra' => $kpi->kra->title,
                'title' => $kpi->title,
                'source' => $kpi->kra->appraisal->cycle?->name,
            ]);

        return $contractKpis->concat($appraisalKpis)->values();
    }

    /**
     * Turn a "s:<id>" / "a:<id>" key into task columns, if it belongs to the given person.
     */
    public function kpiColumns(?string $key, int $userId): ?array
    {
        if (! $key) {
            return ['staff_kpi_id' => null, 'appraisal_kpi_id' => null];
        }

        $kpi = $this->linkableKpis([$userId])->firstWhere('key', $key);

        if (! $kpi) {
            return null;
        }

        return $kpi['type'] === 'staff'
            ? ['staff_kpi_id' => $kpi['id'], 'appraisal_kpi_id' => null]
            : ['staff_kpi_id' => null, 'appraisal_kpi_id' => $kpi['id']];
    }

    /**
     * Today: overdue work, work due today or undated, and what was finished today.
     */
    public function today(Collection $userIds): Collection
    {
        $tasks = $this->base($userIds)
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $open) => $open->open()->where(fn (Builder $due) => $due
                    ->whereNull('due_date')
                    ->orWhereDate('due_date', '<=', today())
                    ->orWhereDate('start_date', today())))
                ->orWhereDate('completed_at', today()))
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->get();

        return collect([
            'overdue' => $tasks->filter->isOverdue(),
            'due_today' => $tasks->filter(fn (Task $task) => $task->isOpen() && $task->due_date?->isToday()),
            'assigned_today' => $tasks->filter(fn (Task $task) => $task->isOpen()
                && $task->start_date?->isToday()
                && ! $task->due_date?->isToday()
                && ! $task->isOverdue()),
            'undated' => $tasks->filter(fn (Task $task) => $task->isOpen() && ! $task->due_date && ! $task->start_date?->isToday()),
            'done_today' => $tasks->reject->isOpen(),
        ]);
    }

    /**
     * One week (Monday–Sunday) grouped by day, by due date or completion date.
     *
     * @return Collection<string, Collection<int, Task>> keyed Y-m-d for each day
     */
    public function week(Collection $userIds, Carbon $weekStart): Collection
    {
        $weekEnd = $weekStart->copy()->endOfWeek();

        $tasks = $this->base($userIds)
            ->where(fn (Builder $query) => $query
                ->whereBetween('due_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->orWhereBetween('start_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->orWhereBetween('completed_at', [$weekStart, $weekEnd]))
            ->orderBy('due_date')
            ->get();

        // Place each task on its assigned (start) day when that falls in this
        // week, otherwise on its due day, otherwise on the day it was finished.
        $anchor = fn (Task $task) => $task->start_date?->between($weekStart, $weekEnd)
            ? $task->start_date
            : ($task->due_date?->between($weekStart, $weekEnd) ? $task->due_date : $task->completed_at);

        return collect(range(0, 6))->mapWithKeys(function (int $offset) use ($weekStart, $tasks, $anchor) {
            $day = $weekStart->copy()->addDays($offset);

            return [$day->toDateString() => $tasks->filter(fn (Task $task) => $anchor($task)?->isSameDay($day))->values()];
        });
    }

    /**
     * Past: anything due before today or completed before today.
     */
    public function past(Collection $userIds, array $filters): Builder
    {
        return $this->base($userIds)
            ->where(fn (Builder $query) => $query
                ->whereDate('due_date', '<', today())
                ->orWhereDate('completed_at', '<', today()))
            ->when(($filters['outcome'] ?? null) === 'completed', fn (Builder $q) => $q->where('status', 'completed'))
            ->when(($filters['outcome'] ?? null) === 'missed', fn (Builder $q) => $q->open())
            ->when($filters['from'] ?? null, fn (Builder $q, $from) => $q->whereDate('due_date', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $q, $to) => $q->whereDate('due_date', '<=', $to))
            ->when($filters['kpi'] ?? null, function (Builder $q, string $key) {
                [$type, $id] = array_pad(explode(':', $key, 2), 2, null);
                $q->where($type === 's' ? 'staff_kpi_id' : 'appraisal_kpi_id', (int) $id);
            })
            ->orderByDesc('due_date')
            ->orderByDesc('completed_at');
    }

    /**
     * Per-KPI task counts for the given people: this week and overall.
     */
    public function kpiSummary(Collection $kpis, Carbon $weekStart): Collection
    {
        if ($kpis->isEmpty()) {
            return collect();
        }

        $weekEnd = $weekStart->copy()->endOfWeek();
        $tasks = Task::query()
            ->where(fn (Builder $query) => $query
                ->whereIn('staff_kpi_id', $kpis->where('type', 'staff')->pluck('id'))
                ->orWhereIn('appraisal_kpi_id', $kpis->where('type', 'appraisal')->pluck('id')))
            ->get(['id', 'staff_kpi_id', 'appraisal_kpi_id', 'status', 'due_date', 'completed_at']);

        return $kpis->map(function (array $kpi) use ($tasks, $weekStart, $weekEnd) {
            $column = $kpi['type'] === 'staff' ? 'staff_kpi_id' : 'appraisal_kpi_id';
            $linked = $tasks->where($column, $kpi['id']);
            $thisWeek = $linked->filter(fn (Task $task) => ($task->completed_at ?? $task->due_date)?->between($weekStart, $weekEnd));

            return $kpi + [
                'total' => $linked->count(),
                'completed' => $linked->where('status', 'completed')->count(),
                'week_total' => $thisWeek->count(),
                'week_completed' => $thisWeek->where('status', 'completed')->count(),
            ];
        });
    }

    /**
     * The next working day after today (or after the given date), skipping weekends.
     */
    public function nextWorkingDay(?Carbon $from = null): Carbon
    {
        $date = ($from ?? today())->copy()->startOfDay()->addDay();

        while ($date->isWeekend()) {
            $date->addDay();
        }

        return $date;
    }

    /**
     * Move one task to the next working day.
     */
    public function moveToNextDay(Task $task): Task
    {
        $task->update(['due_date' => $this->nextWorkingDay()]);

        return $task->fresh();
    }

    /**
     * Roll every open task that is due today or overdue to the next working day.
     * Only tasks assigned to the given people are touched. Returns the number moved.
     */
    public function movePendingToNextDay(Collection $userIds): int
    {
        $target = $this->nextWorkingDay();

        return Task::query()
            ->whereIn('assigned_to', $userIds)
            ->open()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', today())
            ->update(['due_date' => $target->toDateString()]);
    }

    public function stats(Collection $userIds, Carbon $weekStart): array
    {
        $weekEnd = $weekStart->copy()->endOfWeek();

        return [
            'due_today' => $this->base($userIds)->open()->whereDate('due_date', today())->count(),
            'overdue' => $this->base($userIds)->open()->whereDate('due_date', '<', today())->count(),
            'week_done' => $this->base($userIds)->where('status', 'completed')->whereBetween('completed_at', [$weekStart, $weekEnd])->count(),
            'week_total' => $this->base($userIds)->whereBetween('due_date', [$weekStart->toDateString(), $weekEnd->toDateString()])->count(),
        ];
    }

    private function base(Collection $userIds): Builder
    {
        return Task::with(['kpi.kra', 'staffKpi', 'assignee:id,name', 'creator:id,name', 'activity:id,title'])
            ->whereIn('assigned_to', $userIds);
    }
}
