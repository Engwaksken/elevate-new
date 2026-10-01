{{-- Task fields. Expects $assignable, $kpis, $prefix and optional $task. --}}
@php
    $task ??= null;
    $currentAssignee = (int) ($task?->assigned_to ?? auth()->id());
@endphp
<div class="modal-grid" data-task-form>
    <div class="form-group full"><label for="{{ $prefix }}-title">Task *</label><input id="{{ $prefix }}-title" name="title" value="{{ $task?->title }}" required maxlength="190" placeholder="e.g. Follow up with 10 graduates on placement"></div>

    @if($assignable->count() > 1)
    <div class="form-group">
        <label for="{{ $prefix }}-assignee">Assigned to</label>
        <select id="{{ $prefix }}-assignee" name="assigned_to" data-task-assignee>
            @foreach($assignable as $person)
                <option value="{{ $person->id }}" @selected($currentAssignee === $person->id)>{{ $person->id === auth()->id() ? 'Me ('.$person->name.')' : $person->name }}</option>
            @endforeach
        </select>
    </div>
    @else
        <input type="hidden" name="assigned_to" value="{{ $currentAssignee }}" data-task-assignee>
    @endif

    <div class="form-group">
        <label for="{{ $prefix }}-priority">Priority</label>
        <select id="{{ $prefix }}-priority" name="priority">
            @foreach(\App\Models\Task::PRIORITIES as $priority)
                <option value="{{ $priority }}" @selected(($task?->priority ?? 'medium') === $priority)>{{ ucfirst($priority) }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group full">
        <label for="{{ $prefix }}-kpi">Linked KPI</label>
        <select id="{{ $prefix }}-kpi" name="appraisal_kpi_id" data-task-kpi>
            <option value="">Not linked to a KPI</option>
            @foreach($kpis->groupBy(fn ($kpi) => $kpi['owner_id'].'|'.$kpi['kra']) as $group => $groupKpis)
                <optgroup label="{{ $groupKpis->first()['kra'] }}{{ $groupKpis->first()['cycle'] ? ' · '.$groupKpis->first()['cycle'] : '' }}" data-owner="{{ $groupKpis->first()['owner_id'] }}">
                    @foreach($groupKpis as $kpi)
                        <option value="{{ $kpi['id'] }}" data-owner="{{ $kpi['owner_id'] }}" @selected((int) $task?->appraisal_kpi_id === $kpi['id'])>{{ $kpi['title'] }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
        <small class="form-hint">KPIs come from the assignee’s current appraisal (Appraisals &amp; Performance).</small>
    </div>

    <div class="form-group"><label for="{{ $prefix }}-start">Start date</label><input id="{{ $prefix }}-start" type="date" name="start_date" value="{{ $task?->start_date?->toDateString() }}"></div>
    <div class="form-group"><label for="{{ $prefix }}-due">Due date</label><input id="{{ $prefix }}-due" type="date" name="due_date" value="{{ $task?->due_date?->toDateString() ?? ($task ? '' : today()->toDateString()) }}"></div>

    <div class="form-group full"><label for="{{ $prefix }}-description">Details</label><textarea id="{{ $prefix }}-description" name="description" rows="3" maxlength="5000">{{ $task?->description }}</textarea></div>

    @if($task)
        <div class="form-group">
            <label for="{{ $prefix }}-status">Status</label>
            <select id="{{ $prefix }}-status" name="status">
                @foreach(['not_started' => 'Not started', 'in_progress' => 'In progress', 'returned_for_revision' => 'Returned for revision', 'completed' => 'Done'] as $value => $label)
                    <option value="{{ $value }}" @selected(($task->status === 'overdue' ? 'in_progress' : $task->status) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group"><label for="{{ $prefix }}-progress">Progress %</label><input id="{{ $prefix }}-progress" type="number" name="progress_percent" min="0" max="100" step="5" value="{{ round((float) $task->progress_percent) }}"></div>
        <div class="form-group full"><label for="{{ $prefix }}-outcome">Outcome / notes</label><textarea id="{{ $prefix }}-outcome" name="outcome" rows="2" maxlength="5000">{{ $task->outcome }}</textarea></div>
    @endif
</div>
