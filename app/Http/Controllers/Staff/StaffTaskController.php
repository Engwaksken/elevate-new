<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Concerns\ExportsTables;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Services\StaffTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffTaskController extends Controller
{
    use ExportsTables;
    public const VIEWS = ['today', 'week', 'past', 'team'];

    public function __construct(private StaffTaskService $tasks)
    {
    }

    public function index(Request $request): View|\Symfony\Component\HttpFoundation\Response
    {
        $user = $request->user();
        $view = in_array($request->get('view'), self::VIEWS, true) ? $request->get('view') : 'today';
        $teamIds = $this->tasks->teamMemberIds($user);

        if ($view === 'team' && $teamIds->isEmpty()) {
            $view = 'today';
        }

        // Team view covers everyone the user supervises, optionally narrowed to one member.
        $scope = $view === 'team'
            ? ($teamIds->contains($member = $request->integer('member')) ? collect([$member]) : $teamIds)
            : collect([$user->id]);

        $weekStart = $this->weekStart($request->get('week'));

        if ($format = $this->exportFormat($request)) {
            $source = match ($view) {
                'past' => $this->tasks->past($scope, $request->only(['outcome', 'from', 'to', 'kpi'])),
                'week' => $this->tasks->week($scope, $weekStart)->flatten(1)->unique('id')->values(),
                'team' => $this->tasks->today($scope)->flatten(1)
                    ->merge($this->tasks->week($scope, $weekStart)->flatten(1))->unique('id')->values(),
                default => $this->tasks->today($scope)->flatten(1)->unique('id')->values(),
            };
            $titles = ['today' => 'My Tasks - Today', 'week' => 'My Tasks - Week of '.$weekStart->format('d M Y'), 'past' => 'My Tasks - Past', 'team' => 'Team Tasks'];

            return $this->exportTable($format, $titles[$view] ?? 'My Tasks', $source, [
                'Task' => 'title',
                'Assignee' => 'assignee.name',
                'KPI' => fn (Task $t) => $t->kpiTitle(),
                'Activity' => 'activity.title',
                'Priority' => fn ($r) => str_replace('_', ' ', (string) $r->priority),
                'Start' => 'start_date',
                'Due' => 'due_date',
                'Progress %' => 'progress_percent',
                'Status' => fn ($r) => str_replace('_', ' ', (string) $r->status),
                'Completed At' => 'completed_at',
                'Outcome' => 'outcome',
                'Assigned By' => 'creator.name',
            ]);
        }

        $assignable = $this->tasks->assignableUsers($user);
        $kpis = $this->tasks->linkableKpis($assignable->pluck('id'));

        return view('staff.tasks.index', [
            'view' => $view,
            'weekStart' => $weekStart,
            'today' => in_array($view, ['today', 'team'], true) ? $this->tasks->today($scope) : null,
            'week' => in_array($view, ['week', 'team'], true) ? $this->tasks->week($scope, $weekStart) : null,
            'past' => $view === 'past'
                ? $this->tasks->past($scope, $request->only(['outcome', 'from', 'to', 'kpi']))->paginate(20)->withQueryString()
                : null,
            'stats' => $this->tasks->stats($scope, $weekStart),
            'kpiSummary' => $this->tasks->kpiSummary($kpis->whereIn('owner_id', $scope)->values(), $weekStart),
            'kpis' => $kpis,
            'assignable' => $assignable,
            'team' => $assignable->whereIn('id', $teamIds),
            'hasTeam' => $teamIds->isNotEmpty(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $this->validated($request);

        Task::create($data + [
            'created_by' => $user->id,
            'status' => 'not_started',
            'progress_percent' => 0,
        ]);

        return back()->with('success', (int) $data['assigned_to'] === $user->id ? 'Task added.' : 'Task assigned.');
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        abort_unless($this->tasks->canManage($request->user(), $task), 403);

        $data = $this->validated($request, $task) + $request->validate([
            'status' => ['required', Rule::in(['not_started', 'in_progress', 'returned_for_revision', 'completed'])],
            'progress_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'outcome' => ['nullable', 'string', 'max:5000'],
        ]);

        $task->update($data + $this->completion($task, $data['status'], $data['progress_percent'] ?? null));

        return back()->with('success', 'Task updated.');
    }

    public function complete(Request $request, Task $task): RedirectResponse
    {
        abort_unless($this->tasks->canManage($request->user(), $task), 403);

        $data = $request->validate(['outcome' => ['nullable', 'string', 'max:5000']]);

        $task->update(['outcome' => $data['outcome'] ?? $task->outcome] + $this->completion($task, 'completed', 100));

        return back()->with('success', 'Task marked as done.');
    }

    public function moveToNextDay(Request $request, Task $task): RedirectResponse
    {
        abort_unless($this->tasks->canManage($request->user(), $task), 403);

        $this->tasks->moveToNextDay($task);

        return back()->with('success', 'Task moved to the next working day ('.$this->tasks->nextWorkingDay()->format('D d M').').');
    }

    public function moveAllPending(Request $request): RedirectResponse
    {
        $user = $request->user();
        // Scope: my tasks, or my whole team when the user manages people.
        $scope = $request->boolean('team') && $this->tasks->teamMemberIds($user)->isNotEmpty()
            ? $this->tasks->teamMemberIds($user)
            : collect([$user->id]);

        $moved = $this->tasks->movePendingToNextDay($scope);

        return back()->with(
            'success',
            $moved === 0
                ? 'Nothing pending to move.'
                : $moved.' pending task'.($moved === 1 ? '' : 's').' moved to '.$this->tasks->nextWorkingDay()->format('D d M').'.'
        );
    }

    public function destroy(Request $request, Task $task): RedirectResponse
    {
        // Workplan tasks are managed from Planning & Delivery; only personal tasks are deleted here.
        abort_unless($task->activity_id === null && (int) $task->created_by === $request->user()->id, 403);

        $task->delete();

        return back()->with('success', 'Task deleted.');
    }

    private function validated(Request $request, ?Task $task = null): array
    {
        $user = $request->user();
        $assignable = $this->tasks->assignableUsers($user)->pluck('id');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'integer'],
            'kpi' => ['nullable', 'string', 'max:30'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
        ]);

        $data['assigned_to'] = (int) ($data['assigned_to'] ?? $task?->assigned_to ?? $user->id);

        // Reassigning is limited to yourself and your team; an existing assignee may stay as is.
        if (! $assignable->contains($data['assigned_to']) && (int) $task?->assigned_to !== $data['assigned_to']) {
            throw ValidationException::withMessages(['assigned_to' => 'You can only assign tasks to yourself or your team.']);
        }

        $columns = $this->tasks->kpiColumns($data['kpi'] ?? null, $data['assigned_to']);

        if ($columns === null) {
            throw ValidationException::withMessages(['kpi' => 'Choose one of the assignee’s own KPIs.']);
        }

        unset($data['kpi']);

        return $data + $columns;
    }

    private function completion(Task $task, string $status, mixed $progress): array
    {
        if ($status === 'completed') {
            return ['status' => 'completed', 'progress_percent' => 100, 'completed_at' => $task->completed_at ?? now()];
        }

        return ['status' => $status, 'progress_percent' => $progress ?? $task->progress_percent, 'completed_at' => null];
    }

    private function weekStart(?string $week): Carbon
    {
        try {
            $date = $week ? Carbon::parse($week) : today();
        } catch (\Throwable) {
            $date = today();
        }

        return $date->copy()->startOfWeek(Carbon::MONDAY);
    }
}
