<?php

namespace App\Services;

use App\Models\Appraisal;
use App\Models\AppraisalKpi;
use App\Models\Employee;
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
     * KPIs from each person's current (not yet completed) appraisals, ready for a grouped select.
     *
     * @return Collection<int, array{id: int, owner_id: int, kra: string, title: string, cycle: ?string}>
     */
    public function linkableKpis(iterable $userIds): Collection
    {
        $userIds = collect($userIds)->map(fn ($id) => (int) $id)->unique();

        return AppraisalKpi::query()
            ->with('kra.appraisal.cycle', 'kra.appraisal.employee:id,user_id')
            ->whereHas('kra.appraisal', fn (Builder $appraisal) => $appraisal
                ->where('status', '!=', 'completed')
                ->whereHas('employee', fn (Builder $employee) => $employee->whereIn('user_id', $userIds)))
            ->orderBy('appraisal_kra_id')
            ->orderBy('position')
            ->get()
            ->map(fn (AppraisalKpi $kpi) => [
                'id' => $kpi->id,
                'owner_id' => (int) $kpi->kra->appraisal->employee->user_id,
                'kra' => $kpi->kra->title,
                'title' => $kpi->title,
                'cycle' => $kpi->kra->appraisal->cycle?->name,
            ]);
    }

    /**
     * Today: overdue work, work due today or undated, and what was finished today.
     */
    public function today(Collection $userIds): Collection
    {
        $tasks = $this->base($userIds)
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $open) => $open->open()->where(fn (Builder $due) => $due
                    ->whereNull('due_date')->orWhereDate('due_date', '<=', today())))
                ->orWhereDate('completed_at', today()))
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->get();

        return collect([
            'overdue' => $tasks->filter->isOverdue(),
            'due_today' => $tasks->filter(fn (Task $task) => $task->isOpen() && $task->due_date?->isToday()),
            'undated' => $tasks->filter(fn (Task $task) => $task->isOpen() && ! $task->due_date),
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
                ->orWhereBetween('completed_at', [$weekStart, $weekEnd]))
            ->orderBy('due_date')
            ->get();

        // Place each task on its due day when that falls in this week, otherwise on the day it was finished.
        $anchor = fn (Task $task) => $task->due_date?->between($weekStart, $weekEnd) ? $task->due_date : $task->completed_at;

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
            ->when($filters['kpi'] ?? null, fn (Builder $q, $kpi) => $q->where('appraisal_kpi_id', $kpi))
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
        $tasks = Task::whereIn('appraisal_kpi_id', $kpis->pluck('id'))
            ->get(['id', 'appraisal_kpi_id', 'status', 'due_date', 'completed_at']);

        return $kpis->map(function (array $kpi) use ($tasks, $weekStart, $weekEnd) {
            $linked = $tasks->where('appraisal_kpi_id', $kpi['id']);
            $thisWeek = $linked->filter(fn (Task $task) => ($task->completed_at ?? $task->due_date)?->between($weekStart, $weekEnd));

            return $kpi + [
                'total' => $linked->count(),
                'completed' => $linked->where('status', 'completed')->count(),
                'week_total' => $thisWeek->count(),
                'week_completed' => $thisWeek->where('status', 'completed')->count(),
            ];
        });
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
        return Task::with(['kpi.kra', 'assignee:id,name', 'creator:id,name', 'activity:id,title'])
            ->whereIn('assigned_to', $userIds);
    }
}
