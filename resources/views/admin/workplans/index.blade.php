@extends('layouts.admin')
@section('title','Workplans | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Planning & Performance</span>
        <h1>Workplans</h1>
        <p>Create, submit, approve and monitor workplans, milestones and activities.</p>
    </div>
    <div class="admin-page-actions">
        <button type="button" class="btn btn-primary" data-modal-open="createWorkplanModal">
            <i class="fas fa-plus"></i> New Workplan
        </button>
    </div>
</div>

<div class="admin-stats-grid compact">
@foreach([
['total','Total Workplans','fa-list-check'],
['draft','Draft','fa-pen-ruler'],
['submitted','Submitted','fa-paper-plane'],
['approved','Approved','fa-circle-check']
] as [$key,$label,$icon])
<div class="admin-stat">
    <span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
    <div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div>
</div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
    <div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search title, financial year or description..."></div>
    <select name="status"><option value="">All statuses</option>@foreach(['draft','submitted','approved'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
    <select name="period_type"><option value="">All periods</option>@foreach(['annual','quarterly','monthly','programme','project','department','staff'] as $p)<option value="{{ $p }}" @selected(request('period_type')===$p)>{{ ucfirst($p) }}</option>@endforeach</select>
    <input type="date" name="from" value="{{ request('from') }}">
    <input type="date" name="to" value="{{ request('to') }}">
    <select name="per_page">@foreach([10,20,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',20)===$n)>{{ $n }}/page</option>@endforeach</select>
    <button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
    <a href="{{ route('admin.workplans.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Workplan</th><th>Period</th><th>Dates</th><th>Progress</th><th>Milestones</th><th>Activities</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($workplans as $workplan)
<tr>
<td><strong>{{ $workplan->title }}</strong><small class="admin-cell-hint">{{ $workplan->financial_year ?: 'No financial year' }}</small></td>
<td>{{ ucfirst($workplan->period_type) }}</td>
<td>{{ optional($workplan->start_date)->format('d M Y') ?: '—' }} — {{ optional($workplan->end_date)->format('d M Y') ?: '—' }}</td>
<td><strong>{{ number_format((float)$workplan->progress_percent,0) }}%</strong><div style="height:5px;background:#eee;border-radius:5px;margin-top:5px;overflow:hidden"><div style="height:100%;width:{{ min(100,(float)$workplan->progress_percent) }}%;background:#800000"></div></div></td>
<td>{{ $workplan->milestones_count }}</td>
<td>{{ $workplan->activities_count }}</td>
<td><span class="status-chip {{ $workplan->status }}">{{ ucfirst($workplan->status) }}</span></td>
<td class="table-actions"><div class="action-group">
<button type="button" class="btn-icon" title="Add milestone" data-modal-open="addMilestone{{ $workplan->id }}"><i class="fas fa-flag"></i></button>
<button type="button" class="btn-icon" title="Add activity" data-modal-open="addActivity{{ $workplan->id }}"><i class="fas fa-calendar-plus"></i></button>
@if($workplan->status==='draft')<button type="button" class="btn-icon" data-modal-open="submitWorkplan{{ $workplan->id }}"><i class="fas fa-paper-plane"></i></button>@endif
@if($workplan->status==='submitted')<button type="button" class="btn-icon" data-modal-open="approveWorkplan{{ $workplan->id }}"><i class="fas fa-check"></i></button>@endif
</div></td>
</tr>

@if($workplan->milestones->count() || $workplan->activities->count())
<tr><td colspan="8">
<details>
<summary><strong>View milestones and activities</strong></summary>
<div class="admin-tabs" data-admin-tabs style="margin-top:10px">
<button type="button" class="admin-tab active" data-admin-tab="m{{ $workplan->id }}">Milestones</button>
<button type="button" class="admin-tab" data-admin-tab="a{{ $workplan->id }}">Activities</button>
</div>
<div class="admin-tab-pane active" data-admin-pane="m{{ $workplan->id }}">
<table class="admin-table"><thead><tr><th>Milestone</th><th>Due</th><th>Progress</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($workplan->milestones as $m)
<tr><td>{{ $m->title }}</td><td>{{ optional($m->due_date)->format('d M Y') ?: '—' }}</td><td>{{ number_format((float)$m->progress_percent,0) }}%</td><td>{{ ucfirst(str_replace('_',' ',$m->status)) }}</td><td><button type="button" class="btn btn-outline btn-sm" data-modal-open="milestoneProgress{{ $m->id }}">Update</button></td></tr>
@empty<tr><td colspan="5">No milestones.</td></tr>@endforelse
</tbody></table>
</div>
<div class="admin-tab-pane" data-admin-pane="a{{ $workplan->id }}">
<table class="admin-table"><thead><tr><th>Activity</th><th>Dates</th><th>Budget</th><th>Progress</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($workplan->activities as $a)
<tr><td>{{ $a->title }}</td><td>{{ optional($a->start_date)->format('d M Y') ?: '—' }} — {{ optional($a->end_date)->format('d M Y') ?: '—' }}</td><td>{{ $a->currency ?: 'UGX' }} {{ number_format((float)$a->budget,0) }}</td><td>{{ number_format((float)$a->progress_percent,0) }}%</td><td>{{ ucfirst(str_replace('_',' ',$a->status)) }}</td><td><button type="button" class="btn btn-outline btn-sm" data-modal-open="activityProgress{{ $a->id }}">Update</button></td></tr>
@empty<tr><td colspan="6">No activities.</td></tr>@endforelse
</tbody></table>
</div>
</details>
</td></tr>
@endif
@empty<tr><td colspan="8"><div class="admin-empty">No workplans found.</div></td></tr>@endforelse
</tbody>
</table>
</div>
<div class="admin-pagination">{{ $workplans->links() }}</div>
</div>

<div class="eh-modal" id="createWorkplanModal" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>New Workplan</h2><p>Create a programme, project, department or staff workplan.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.workplans.store') }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Title *</label><input name="title" required></div>
<div class="form-group"><label>Financial Year</label><input name="financial_year" placeholder="2026/27"></div>
<div class="form-group"><label>Period Type *</label><select name="period_type">@foreach(['annual','quarterly','monthly','programme','project','department','staff'] as $p)<option value="{{ $p }}">{{ ucfirst($p) }}</option>@endforeach</select></div>
<div class="form-group"><label>Programme</label><select name="programme_id"><option value="">None</option>@foreach($programmes as $x)<option value="{{ $x->id }}">{{ $x->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Project</label><select name="project_id"><option value="">None</option>@foreach($projects as $x)<option value="{{ $x->id }}">{{ $x->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Cohort</label><select name="cohort_id"><option value="">None</option>@foreach($cohorts as $x)<option value="{{ $x->id }}">{{ $x->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Responsible Staff</label><select name="responsible_user_id"><option value="">None</option>@foreach($users as $x)<option value="{{ $x->id }}">{{ $x->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Start Date</label><input type="date" name="start_date"></div>
<div class="form-group"><label>End Date</label><input type="date" name="end_date"></div>
<div class="form-group full"><label>Description</label><textarea name="description"></textarea></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Workplan</button></div>
</form></div></div>

@foreach($workplans as $workplan)
<div class="eh-modal" id="addMilestone{{ $workplan->id }}" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Add Milestone</h2><p>{{ $workplan->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.milestones.store',$workplan) }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Title *</label><input name="title" required></div>
<div class="form-group"><label>Start Date</label><input type="date" name="start_date"></div>
<div class="form-group"><label>Due Date</label><input type="date" name="due_date"></div>
<div class="form-group"><label>Responsible Staff</label><select name="responsible_user_id"><option value="">None</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Weight</label><input type="number" name="weight" min=".01" step=".01"></div>
<div class="form-group full"><label>Expected Result</label><textarea name="expected_result"></textarea></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Add Milestone</button></div>
</form></div></div>

<div class="eh-modal" id="addActivity{{ $workplan->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>Add Activity</h2><p>{{ $workplan->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.activities.store',$workplan) }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Activity Code</label><input name="activity_code"></div>
<div class="form-group"><label>Milestone</label><select name="milestone_id"><option value="">None</option>@foreach($workplan->milestones as $m)<option value="{{ $m->id }}">{{ $m->title }}</option>@endforeach</select></div>
<div class="form-group full"><label>Title *</label><input name="title" required></div>
<div class="form-group"><label>Start Date</label><input type="date" name="start_date"></div>
<div class="form-group"><label>End Date</label><input type="date" name="end_date"></div>
<div class="form-group"><label>Location</label><input name="location"></div>
<div class="form-group"><label>Responsible Staff</label><select name="responsible_user_id"><option value="">None</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
<div class="form-group"><label>Budget</label><input type="number" name="budget" min="0" step=".01"></div>
<div class="form-group"><label>Currency</label><input name="currency" value="UGX" maxlength="3"></div>
<div class="form-group full"><label>Expected Output</label><textarea name="expected_output"></textarea></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Add Activity</button></div>
</form></div></div>

@if($workplan->status==='draft')
<div class="eh-modal" id="submitWorkplan{{ $workplan->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Submit Workplan?</h2><p>{{ $workplan->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><p>Submit this workplan for approval?</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.workplans.submit',$workplan) }}">@csrf<button class="btn btn-primary">Submit</button></form></div>
</div></div>
@endif

@if($workplan->status==='submitted')
<div class="eh-modal" id="approveWorkplan{{ $workplan->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2>Approve Workplan?</h2><p>{{ $workplan->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.workplans.approve',$workplan) }}">@csrf
<div class="eh-modal-body"><div class="form-group"><label>Approval Comments</label><textarea name="comments"></textarea></div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Approve</button></div>
</form></div></div>
@endif

@foreach($workplan->milestones as $m)
<div class="eh-modal" id="milestoneProgress{{ $m->id }}" aria-hidden="true"><div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Update Milestone</h2><p>{{ $m->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.milestones.update',$m) }}">@csrf @method('PUT')
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Progress %</label><input type="number" name="progress_percent" value="{{ $m->progress_percent }}" min="0" max="100" required></div>
<div class="form-group"><label>Status</label><select name="status">@foreach(['not_started','in_progress','at_risk','delayed','completed','cancelled'] as $s)<option value="{{ $s }}" @selected($m->status===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
<div class="form-group full"><label>Remarks</label><textarea name="remarks">{{ $m->remarks }}</textarea></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Save Progress</button></div>
</form></div></div>
@endforeach

@foreach($workplan->activities as $a)
<div class="eh-modal" id="activityProgress{{ $a->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>Update Activity Progress</h2><p>{{ $a->title }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.activities.progress',$a) }}">@csrf @method('PUT')
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Status</label><select name="status">@foreach(['planned','not_started','in_progress','delayed','completed','cancelled'] as $s)<option value="{{ $s }}" @selected($a->status===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
<div class="form-group"><label>Progress %</label><input type="number" name="progress_percent" value="{{ $a->progress_percent }}" min="0" max="100" required></div>
<div class="form-group full"><label>Actual Output</label><textarea name="actual_output">{{ $a->actual_output }}</textarea></div>
<div class="form-group full"><label>Challenges</label><textarea name="challenges">{{ $a->challenges }}</textarea></div>
<div class="form-group full"><label>Lessons Learned</label><textarea name="lessons_learned">{{ $a->lessons_learned }}</textarea></div>
<div class="form-group full"><label>Next Action</label><textarea name="next_action">{{ $a->next_action }}</textarea></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Save Progress</button></div>
</form></div></div>
@endforeach
@endforeach

@endsection
