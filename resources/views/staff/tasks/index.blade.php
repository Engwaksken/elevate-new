@extends('layouts.admin')
@section('title','My Tasks | ElevateHer360')
@section('content')
@php
    $tabs = ['today' => ['Today', 'fa-sun'], 'week' => ['This Week', 'fa-calendar-week'], 'past' => ['Past', 'fa-clock-rotate-left']];
    if ($hasTeam) { $tabs['team'] = ['My Team', 'fa-people-group']; }
    $shown = collect();
    $isCurrentWeek = $weekStart->isSameDay(today()->startOfWeek());
    $groupLabels = [
        'overdue' => ['Overdue', 'fa-triangle-exclamation', 'Still open past their due date.'],
        'due_today' => ['Due today', 'fa-sun', null],
        'undated' => ['No due date', 'fa-inbox', null],
        'done_today' => ['Done today', 'fa-circle-check', null],
    ];
@endphp

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">People &amp; Performance</span>
    <h1>{{ $view === 'team' ? 'Team Tasks' : 'My Tasks' }}</h1>
    <p>Plan daily and weekly work, link each task to a KPI in your appraisal, and keep a record of what was done.</p>
</div>
<div class="admin-page-actions">
    <a href="{{ route('staff.performance.index') }}" class="btn btn-outline"><i class="fas fa-bullseye"></i> My KPIs</a>
    <button type="button" class="btn btn-primary" data-modal-open="task-new"><i class="fas fa-plus"></i> New task</button>
</div>
</div>

<div class="admin-stats-grid compact">
@foreach([
    ['due_today', 'Due Today', 'fa-sun'],
    ['overdue', 'Overdue', 'fa-triangle-exclamation'],
    ['week_done', 'Done This Week', 'fa-circle-check'],
    ['week_total', 'Due This Week', 'fa-calendar-week'],
] as [$key, $label, $icon])
<div class="admin-stat {{ $key === 'overdue' && $stats[$key] > 0 ? 'st-stat-alert' : '' }}"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key]) }}</strong></div></div>
@endforeach
</div>

<nav class="st-tabs" aria-label="Task views">
    @foreach($tabs as $key => [$label, $icon])
        <a href="{{ route('staff.tasks.index', ['view' => $key]) }}" class="{{ $view === $key ? 'active' : '' }}" @if($view === $key) aria-current="page" @endif><i class="fas {{ $icon }}"></i> {{ $label }}</a>
    @endforeach
</nav>

<div class="st-layout">
<div class="st-main">

@if($view === 'team')
<form method="GET" class="admin-toolbar">
    <input type="hidden" name="view" value="team">
    <select name="member" onchange="this.form.submit()">
        <option value="">Everyone in my team</option>
        @foreach($team as $member)
            <option value="{{ $member->id }}" @selected((int) request('member') === $member->id)>{{ $member->name }}</option>
        @endforeach
    </select>
</form>
@endif

@if($today)
<section class="admin-panel">
    <div class="st-section-head"><h2>{{ $view === 'team' ? 'Today across the team' : 'Today · '.today()->format('l d F') }}</h2></div>
    @php $todayCount = $today->sum(fn ($group) => $group->count()); @endphp
    @if($todayCount === 0)
        <div class="admin-empty"><i class="fas fa-mug-hot"></i><strong>Nothing due today</strong><span>Add a task to plan your day.</span></div>
    @endif
    @foreach($groupLabels as $key => [$label, $icon, $hint])
        @continue($today[$key]->isEmpty())
        <div class="st-group st-group-{{ $key }}">
            <h3><i class="fas {{ $icon }}"></i> {{ $label }} <span>{{ $today[$key]->count() }}</span></h3>
            @foreach($today[$key] as $task)
                @include('staff.tasks._row', ['task' => $task, 'showAssignee' => $view === 'team'])
                @php $shown->push($task); @endphp
            @endforeach
        </div>
    @endforeach
</section>
@endif

@if($week)
<section class="admin-panel">
    <div class="st-section-head">
        <h2>Week of {{ $weekStart->format('d M') }} – {{ $weekStart->copy()->endOfWeek()->format('d M Y') }}</h2>
        <div class="st-week-nav">
            <a class="btn btn-outline btn-sm" href="{{ route('staff.tasks.index', array_filter(['view' => $view === 'team' ? 'team' : 'week', 'member' => request('member'), 'week' => $weekStart->copy()->subWeek()->toDateString()])) }}" aria-label="Previous week"><i class="fas fa-chevron-left"></i></a>
            @unless($isCurrentWeek)<a class="btn btn-outline btn-sm" href="{{ route('staff.tasks.index', array_filter(['view' => $view === 'team' ? 'team' : 'week', 'member' => request('member')])) }}">This week</a>@endunless
            <a class="btn btn-outline btn-sm" href="{{ route('staff.tasks.index', array_filter(['view' => $view === 'team' ? 'team' : 'week', 'member' => request('member'), 'week' => $weekStart->copy()->addWeek()->toDateString()])) }}" aria-label="Next week"><i class="fas fa-chevron-right"></i></a>
        </div>
    </div>
    <div class="st-week">
        @foreach($week as $date => $dayTasks)
            @php $day = \Illuminate\Support\Carbon::parse($date); @endphp
            <div class="st-day {{ $day->isToday() ? 'is-today' : '' }} {{ $day->isWeekend() ? 'is-weekend' : '' }}">
                <h3>{{ $day->format('D') }} <span>{{ $day->format('d M') }}</span>
                    @if($dayTasks->isNotEmpty())<small>{{ $dayTasks->where('status', 'completed')->count() }}/{{ $dayTasks->count() }} done</small>@endif
                </h3>
                @forelse($dayTasks as $task)
                    @include('staff.tasks._row', ['task' => $task, 'showAssignee' => $view === 'team'])
                    @php $shown->push($task); @endphp
                @empty
                    <p class="st-empty-day">No tasks</p>
                @endforelse
            </div>
        @endforeach
    </div>
</section>
@endif

@if($past)
<section class="admin-panel">
    <form method="GET" class="admin-toolbar">
        <input type="hidden" name="view" value="past">
        <select name="outcome">
            <option value="">All past tasks</option>
            <option value="completed" @selected(request('outcome') === 'completed')>Completed</option>
            <option value="missed" @selected(request('outcome') === 'missed')>Not completed</option>
        </select>
        <select name="kpi">
            <option value="">All KPIs</option>
            @foreach($kpiSummary as $kpi)
                <option value="{{ $kpi['id'] }}" @selected((int) request('kpi') === $kpi['id'])>{{ $kpi['title'] }}</option>
            @endforeach
        </select>
        <input type="date" name="from" value="{{ request('from') }}" aria-label="Due from">
        <input type="date" name="to" value="{{ request('to') }}" aria-label="Due to">
        <button class="btn btn-primary btn-sm">Apply</button>
        <a href="{{ route('staff.tasks.index', ['view' => 'past']) }}" class="btn btn-outline btn-sm">Reset</a>
    </form>
    @forelse($past->getCollection()->groupBy(fn ($task) => ($task->due_date ?? $task->completed_at)?->format('Y-m-d')) as $date => $dayTasks)
        <div class="st-group">
            <h3><i class="fas fa-calendar-day"></i> {{ $date ? \Illuminate\Support\Carbon::parse($date)->format('l d F Y') : 'Undated' }} <span>{{ $dayTasks->where('status', 'completed')->count() }}/{{ $dayTasks->count() }} done</span></h3>
            @foreach($dayTasks as $task)
                @include('staff.tasks._row', ['task' => $task])
                @php $shown->push($task); @endphp
            @endforeach
        </div>
    @empty
        <div class="admin-empty"><i class="fas fa-clock-rotate-left"></i><strong>No past tasks</strong><span>Completed and missed tasks will appear here.</span></div>
    @endforelse
    <div class="admin-pagination">{{ $past->links() }}</div>
</section>
@endif
</div>

<aside class="st-side">
<section class="admin-panel">
    <div class="st-section-head"><h2><i class="fas fa-bullseye"></i> KPI progress</h2></div>
    <p class="st-muted">Tasks linked to {{ $view === 'team' ? 'team' : 'your' }} current appraisal KPIs, for the week of {{ $weekStart->format('d M') }}.</p>
    @forelse($kpiSummary->groupBy('kra') as $kra => $kraKpis)
        <div class="st-kra">
            <h4>{{ $kra }}</h4>
            @foreach($kraKpis as $kpi)
                @php $pct = $kpi['week_total'] ? round($kpi['week_completed'] / $kpi['week_total'] * 100) : 0; @endphp
                <a class="st-kpi-row" href="{{ route('staff.tasks.index', ['view' => 'past', 'kpi' => $kpi['id']]) }}">
                    <span class="st-kpi-title">{{ $kpi['title'] }}</span>
                    <span class="st-kpi-bar" role="img" aria-label="{{ $kpi['week_completed'] }} of {{ $kpi['week_total'] }} tasks done this week"><span style="width:{{ $pct }}%"></span></span>
                    <span class="st-kpi-count">{{ $kpi['week_completed'] }}/{{ $kpi['week_total'] }} this week · {{ $kpi['completed'] }} done overall</span>
                </a>
            @endforeach
        </div>
    @empty
        <div class="admin-empty"><i class="fas fa-bullseye"></i><strong>No KPIs yet</strong><span>KPIs appear once {{ $view === 'team' ? 'your team have' : 'you have' }} an appraisal with KRAs and KPIs.</span></div>
    @endforelse
</section>
</aside>
</div>

{{-- New task --}}
<div class="eh-modal" id="task-new" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="task-new-title" @if($errors->any() && ! old('task_id')) data-modal-autoopen @endif>
<div class="eh-modal-dialog">
<form method="POST" action="{{ route('staff.tasks.store') }}">
    @csrf
    <div class="eh-modal-header">
        <div><h2 id="task-new-title">New task</h2><p>Plan a piece of work and link it to the KPI it moves forward.</p></div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="eh-modal-body">
        @if($errors->any() && ! old('task_id'))<div class="icm-notice" role="alert"><span><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</span></div>@endif
        @include('staff.tasks._form', ['prefix' => 'task-new'])
    </div>
    <div class="eh-modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button class="btn btn-primary"><i class="fas fa-plus"></i> Add task</button>
    </div>
</form>
</div>
</div>

@foreach($shown->unique('id') as $task)
{{-- Edit --}}
<div class="eh-modal" id="task-edit-{{ $task->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="task-edit-{{ $task->id }}-title" @if($errors->any() && (int) old('task_id') === $task->id) data-modal-autoopen @endif>
<div class="eh-modal-dialog">
<form method="POST" action="{{ route('staff.tasks.update', $task) }}">
    @csrf @method('PUT')
    <input type="hidden" name="task_id" value="{{ $task->id }}">
    <div class="eh-modal-header">
        <div><h2 id="task-edit-{{ $task->id }}-title">Edit task</h2><p>{{ $task->title }}</p></div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="eh-modal-body">
        @if($errors->any() && (int) old('task_id') === $task->id)<div class="icm-notice" role="alert"><span><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</span></div>@endif
        @include('staff.tasks._form', ['prefix' => 'task-'.$task->id, 'task' => $task])
    </div>
    <div class="eh-modal-footer">
        @if($task->activity_id === null && (int) $task->created_by === auth()->id())
            <button type="submit" form="task-delete-{{ $task->id }}" class="btn btn-outline st-delete"><i class="fas fa-trash"></i> Delete</button>
        @endif
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save</button>
    </div>
</form>
<form method="POST" action="{{ route('staff.tasks.destroy', $task) }}" id="task-delete-{{ $task->id }}" data-delete-form data-confirm="Delete “{{ $task->title }}”?">@csrf @method('DELETE')</form>
</div>
</div>

{{-- Complete --}}
@if($task->isOpen())
<div class="eh-modal" id="task-complete-{{ $task->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="task-complete-{{ $task->id }}-title">
<div class="eh-modal-dialog eh-modal-sm">
<form method="POST" action="{{ route('staff.tasks.complete', $task) }}">
    @csrf @method('PATCH')
    <div class="eh-modal-header">
        <div><h2 id="task-complete-{{ $task->id }}-title">Mark as done</h2><p>{{ $task->title }}</p></div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="eh-modal-body">
        @if($task->kpi)<p class="st-muted"><i class="fas fa-bullseye"></i> Counts towards <strong>{{ $task->kpi->title }}</strong>.</p>@endif
        <div class="modal-grid"><div class="form-group full">
            <label for="task-complete-{{ $task->id }}-outcome">What was achieved? <span class="form-hint" style="display:inline">(optional, useful as appraisal evidence)</span></label>
            <textarea id="task-complete-{{ $task->id }}-outcome" name="outcome" rows="3" maxlength="5000">{{ $task->outcome }}</textarea>
        </div></div>
    </div>
    <div class="eh-modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button class="btn btn-primary"><i class="fas fa-check"></i> Done</button>
    </div>
</form>
</div>
</div>
@endif
@endforeach

<script>
// Only offer KPIs that belong to the selected assignee.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-task-form]').forEach(form => {
        const assignee = form.querySelector('[data-task-assignee]');
        const kpi = form.querySelector('[data-task-kpi]');
        if (!assignee || !kpi) return;
        const sync = () => {
            kpi.querySelectorAll('optgroup').forEach(group => {
                const mine = group.dataset.owner === assignee.value;
                group.hidden = !mine;
                group.disabled = !mine;
            });
            if (kpi.selectedOptions[0]?.dataset.owner && kpi.selectedOptions[0].dataset.owner !== assignee.value) kpi.value = '';
        };
        assignee.addEventListener('change', sync);
        sync();
    });
});
</script>
@endsection
