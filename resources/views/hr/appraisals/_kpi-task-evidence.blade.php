{{-- Tasks linked to a KPI from the employee's My Tasks, shown as scoring evidence. Expects $tasks (Collection<Task>). --}}
@php
    $doneTasks = $tasks->where('status', 'completed')->sortByDesc('completed_at');
    $openTasks = $tasks->where('status', '!=', 'completed');
@endphp
@if($tasks->isNotEmpty())
<details class="kpi-evidence">
    <summary><i class="fas fa-list-check"></i> {{ $doneTasks->count() }} {{ \Illuminate\Support\Str::plural('task', $doneTasks->count()) }} done @if($openTasks->isNotEmpty())· {{ $openTasks->count() }} open @endif</summary>
    <ul>
        @foreach($doneTasks as $task)
            <li>
                <strong>{{ $task->title }}</strong>
                <span>{{ $task->completed_at?->format('d M Y') }}</span>
                @if($task->outcome)<p>{{ $task->outcome }}</p>@endif
            </li>
        @endforeach
        @foreach($openTasks as $task)
            <li class="is-open">
                <strong>{{ $task->title }}</strong>
                <span>{{ $task->due_date && $task->isOverdue() ? 'Overdue since '.$task->due_date->format('d M') : 'Open'.($task->due_date ? ' · due '.$task->due_date->format('d M') : '') }}</span>
            </li>
        @endforeach
    </ul>
</details>
@else
<div class="kpi-evidence-empty">No tasks linked in My Tasks</div>
@endif
