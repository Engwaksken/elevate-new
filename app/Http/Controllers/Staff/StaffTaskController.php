<?php

namespace App\Http\Controllers\Staff;

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
    public const VIEWS = ['today', 'week', 'past', 'team'];

    public function __construct(private StaffTaskService $tasks)
    {
    }

    public function index(Request $request): View
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
            'appraisal_kpi_id' => ['nullable', 'integer'],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
        ]);

        $data['assigned_to'] = (int) ($data['assigned_to'] ?? $task?->assigned_to ?? $user->id);

        // Reassigning is limited to yourself and your team; an existing assignee may stay as is.
        if (! $assignable->contains($data['assigned_to']) && (int) $task?->assigned_to !== $data['assigned_to']) {
            throw ValidationException::withMessages(['assigned_to' => 'You can only assign tasks to yourself or your team.']);
        }

        if ($data['appraisal_kpi_id'] ?? null) {
            $kpi = $this->tasks->linkableKpis([$data['assigned_to']])->firstWhere('id', (int) $data['appraisal_kpi_id']);

            if (! $kpi) {
                throw ValidationException::withMessages(['appraisal_kpi_id' => 'Choose a KPI from the assignee’s current appraisal.']);
            }
        }

        return $data;
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
