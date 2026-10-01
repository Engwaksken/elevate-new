{{-- One task row. Expects $task and optional $showAssignee. --}}
@php
    $overdue = $task->isOverdue();
    $done = $task->status === 'completed';
@endphp
<article class="st-task {{ $done ? 'is-done' : '' }} {{ $overdue ? 'is-overdue' : '' }}">
    @if($task->isOpen())
        <button type="button" class="st-check" data-modal-open="task-complete-{{ $task->id }}" aria-label="Mark “{{ $task->title }}” done"><i class="fas fa-check"></i></button>
    @else
        <span class="st-check is-checked" aria-hidden="true"><i class="fas fa-check"></i></span>
    @endif
    <div class="st-task-body">
        <div class="st-task-title">
            <strong>{{ $task->title }}</strong>
            <span class="st-priority st-priority-{{ $task->priority }}">{{ ucfirst($task->priority ?: 'medium') }}</span>
        </div>
        <div class="st-task-meta">
            @if(! empty($showAssignee))<span><i class="fas fa-user"></i> {{ $task->assignee?->name }}</span>@endif
            @if($task->due_date)
                <span class="{{ $overdue ? 'st-overdue' : '' }}"><i class="fas fa-calendar-day"></i> {{ $overdue ? 'Overdue · ' : '' }}Due {{ $task->due_date->isToday() ? 'today' : $task->due_date->format('D d M') }}</span>
            @endif
            @if($done && $task->completed_at)<span><i class="fas fa-circle-check"></i> Done {{ $task->completed_at->format('D d M H:i') }}</span>@endif
            @if($task->kpiTitle())
                <span class="st-kpi" title="{{ $task->staffKpi?->kra ?? $task->kpi?->kra?->title }}"><i class="fas fa-bullseye"></i> {{ $task->kpiTitle() }}</span>
            @elseif($task->activity)
                <span><i class="fas fa-diagram-project"></i> {{ $task->activity->title }}</span>
            @endif
            @if($task->creator && (int) $task->created_by !== (int) $task->assigned_to)<span><i class="fas fa-user-tie"></i> From {{ $task->creator->name }}</span>@endif
            @if($task->isOpen() && (float) $task->progress_percent > 0)<span><i class="fas fa-bars-progress"></i> {{ round((float) $task->progress_percent) }}%</span>@endif
        </div>
        @if($done && $task->outcome)<p class="st-outcome">{{ $task->outcome }}</p>@endif
    </div>
    <button type="button" class="btn btn-outline btn-sm" data-modal-open="task-edit-{{ $task->id }}"><i class="fas fa-pen"></i><span class="sr-only"> Edit</span></button>
</article>
