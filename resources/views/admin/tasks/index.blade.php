@extends('layouts.admin')
@section('title','Tasks | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Planning & Delivery</span><h1>Tasks</h1><p>Manage activity tasks, assignees, priorities, due dates and progress.</p></div>
<div class="admin-page-actions"><button type="button" class="btn btn-primary" data-modal-open="createTaskModal"><i class="fas fa-plus"></i> New Task</button></div>
</div>

<div class="admin-stats-grid compact">
@foreach([['total','Total Tasks','fa-list-check'],['in_progress','In Progress','fa-bars-progress'],['completed','Completed','fa-circle-check'],['overdue','Overdue','fa-triangle-exclamation']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search task, activity, assignee or description..."></div>
<select name="status"><option value="">All statuses</option>@foreach(['not_started','in_progress','returned_for_revision','completed','overdue'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
<select name="priority"><option value="">All priorities</option>@foreach(['Low','Medium','High','Critical'] as $p)<option value="{{ $p }}" @selected(request('priority')===$p)>{{ $p }}</option>@endforeach</select>
<input type="date" name="from" value="{{ request('from') }}" title="Due date from">
<input type="date" name="to" value="{{ request('to') }}" title="Due date to">
<select name="per_page">@foreach([10,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',25)===$n)>{{ $n }}/page</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.tasks.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Task</th><th>Activity</th><th>Assignee</th><th>Priority</th><th>Due</th><th>Progress</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($tasks as $task)
<tr>
<td><strong>{{ $task->title }}</strong><small class="admin-cell-hint">{{ Str::limit($task->description,90) }}</small></td>
<td>{{ data_get($task,'activity.title','—') }}</td>
<td>{{ data_get($task,'assignee.name','—') }}</td>
<td>{{ $task->priority ?: '—' }}</td>
<td>{{ optional($task->due_date)->format('d M Y') ?: '—' }}</td>
<td><strong>{{ number_format((float)$task->progress_percent,0) }}%</strong><div style="height:5px;background:#eee;border-radius:5px;margin-top:5px;overflow:hidden"><div style="height:100%;width:{{ min(100,(float)$task->progress_percent) }}%;background:#800000"></div></div></td>
<td><span class="status-chip {{ $task->status }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span></td>
<td class="table-actions"><button type="button" class="btn btn-outline btn-sm" data-modal-open="updateTask{{ $task->id }}"><i class="fas fa-pen"></i> Update</button></td>
</tr>
@empty<tr><td colspan="8"><div class="admin-empty">No tasks found.</div></td></tr>@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $tasks->links() }}</div>
</div>

<div class="eh-modal" id="createTaskModal" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>New Task</h2><p>Create a task under an existing activity.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" id="createTaskForm" action="">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Activity *</label><select id="taskActivity" required><option value="">Select activity</option>@foreach($activities as $a)<option value="{{ $a->id }}">{{ $a->title }}</option>@endforeach</select></div>
<div class="form-group full"><label>Title *</label><input name="title" required></div>
<div class="form-group"><label>Assigned To</label><select name="assigned_to"><option value="">Unassigned</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Priority</label><select name="priority"><option value="">None</option>@foreach(['Low','Medium','High','Critical'] as $p)<option value="{{ $p }}">{{ $p }}</option>@endforeach</select></div>
<div class="form-group"><label>Start Date</label><input type="date" name="start_date"></div>
<div class="form-group"><label>Due Date</label><input type="date" name="due_date"></div>
<div class="form-group full"><label>Description</label><textarea name="description"></textarea></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Task</button></div>
</form>
</div></div>

@foreach($tasks as $task)
<div class="eh-modal" id="updateTask{{ $task->id }}" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Update Task</h2><p>{{ $task->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.tasks.update',$task) }}">@csrf @method('PUT')
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Status *</label><select name="status">@foreach(['not_started','in_progress','returned_for_revision','completed','overdue'] as $s)<option value="{{ $s }}" @selected($task->status===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
<div class="form-group"><label>Progress % *</label><input type="number" name="progress_percent" value="{{ $task->progress_percent }}" min="0" max="100" required></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Save Progress</button></div>
</form></div></div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function () {
    const select=document.getElementById('taskActivity');
    const form=document.getElementById('createTaskForm');
    if(!select || !form) return;
    select.addEventListener('change', function(){
        form.action = this.value ? `/admin/activities/${this.value}/tasks` : '';
    });
});
</script>
@endsection
