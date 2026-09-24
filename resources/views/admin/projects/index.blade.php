@extends('layouts.admin')
@section('title','Projects | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">Programme Management</span><h1>Projects</h1><p>Manage projects, programme links, status and implementation periods.</p></div><div class="admin-page-actions"><button type="button" class="btn btn-primary" data-modal-open="createProjectModal"><i class="fas fa-plus"></i> New Project</button></div></div>

<div class="admin-stats-grid compact">
@foreach([['total','Total Projects','fa-folder-tree'],['active','Active','fa-circle-check'],['draft','Draft','fa-pen-ruler'],['completed','Completed','fa-flag-checkered']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search project name, code or description..."></div>
<select name="programme_id"><option value="">All programmes</option>@foreach($programmes as $p)<option value="{{ $p->id }}" @selected((string)request('programme_id')===(string)$p->id)>{{ $p->name }}</option>@endforeach</select>
<select name="status"><option value="">All statuses</option>@foreach(['draft','active','completed','on_hold','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
<select name="period"><option value="">All periods</option>@foreach(['today'=>'Today','week'=>'This week','month'=>'This month','quarter'=>'This quarter','year'=>'This year'] as $v=>$l)<option value="{{ $v }}" @selected(request('period')===$v)>{{ $l }}</option>@endforeach</select>
<input class="date-input" type="date" name="from_date" value="{{ request('from_date') }}"><input class="date-input" type="date" name="to_date" value="{{ request('to_date') }}">
<select name="per_page">@foreach([10,15,25,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page',15)===$size)>{{ $size }} / page</option>@endforeach</select>
<button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button><a href="{{ route('admin.projects.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div id="projectBulkBar" class="admin-bulk-bar"><strong><span data-selected-count>0</span> selected</strong><form method="POST" action="{{ route('admin.projects.bulk-destroy') }}" data-bulk-form data-table="projectsTable">@csrf @method('DELETE')<button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete Selected</button></form></div>

<div class="admin-table-wrap"><table class="admin-table" id="projectsTable"><thead><tr><th class="select-col"><input type="checkbox" data-select-all data-bulk-target="#projectBulkBar"></th><th>Project</th><th>Programme</th><th>Period</th><th>Status</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($projects as $project)
<tr><td><input type="checkbox" data-row-select value="{{ $project->id }}"></td><td><strong>{{ $project->name }}</strong><small class="admin-cell-hint">{{ $project->code ?: 'No code' }}</small></td><td>{{ optional($project->programme)->name ?: '—' }}</td><td>{{ optional($project->start_date)->format('d M Y') ?: '—' }} — {{ optional($project->end_date)->format('d M Y') ?: '—' }}</td><td><span class="status-chip {{ $project->status }}">{{ ucfirst(str_replace('_',' ',$project->status)) }}</span></td><td class="table-actions"><div class="action-group"><button class="btn-icon" type="button" data-modal-open="editProject{{ $project->id }}"><i class="fas fa-pen"></i></button><button class="btn-icon danger" type="button" data-modal-open="deleteProject{{ $project->id }}"><i class="fas fa-trash"></i></button></div></td></tr>
@empty<tr><td colspan="6"><div class="admin-empty">No projects found.</div></td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $projects->links() }}</div></div>

<div class="eh-modal" id="createProjectModal" aria-hidden="true"><div class="eh-modal-dialog"><div class="eh-modal-header"><div><h2>New Project</h2><p>Create a project without leaving this page.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div><form method="POST" action="{{ route('admin.projects.store') }}">@csrf<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Programme</label><select name="programme_id"><option value="">Select programme</option>@foreach($programmes as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select><small class="form-hint">Optional parent programme.</small></div>
<div class="form-group"><label>Project name *</label><input name="name" required placeholder="e.g. Digital Employability Project"></div><div class="form-group"><label>Code</label><input name="code" placeholder="e.g. DEP-2026"></div>
<div class="form-group"><label>Status *</label><select name="status" required><option value="draft">Draft</option><option value="active">Active</option><option value="completed">Completed</option><option value="on_hold">On Hold</option><option value="cancelled">Cancelled</option></select></div>
<div class="form-group"><label>Start date</label><input type="date" name="start_date"></div><div class="form-group"><label>End date</label><input type="date" name="end_date"></div><div class="form-group full"><label>Description</label><textarea name="description" placeholder="Describe the project objectives, scope and expected results..."></textarea></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Project</button></div></form></div></div>

@foreach($projects as $project)
<div class="eh-modal" id="editProject{{ $project->id }}" aria-hidden="true"><div class="eh-modal-dialog"><div class="eh-modal-header"><div><h2>Edit Project</h2><p>{{ $project->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div><form method="POST" action="{{ route('admin.projects.update',$project) }}">@csrf @method('PUT')<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Programme</label><select name="programme_id"><option value="">Select programme</option>@foreach($programmes as $p)<option value="{{ $p->id }}" @selected($project->programme_id===$p->id)>{{ $p->name }}</option>@endforeach</select></div><div class="form-group"><label>Name *</label><input name="name" value="{{ $project->name }}" required></div><div class="form-group"><label>Code</label><input name="code" value="{{ $project->code }}"></div><div class="form-group"><label>Status *</label><select name="status" required>@foreach(['draft','active','completed','on_hold','cancelled'] as $s)<option value="{{ $s }}" @selected($project->status===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div><div class="form-group"><label>Start date</label><input type="date" name="start_date" value="{{ optional($project->start_date)->format('Y-m-d') }}"></div><div class="form-group"><label>End date</label><input type="date" name="end_date" value="{{ optional($project->end_date)->format('Y-m-d') }}"></div><div class="form-group full"><label>Description</label><textarea name="description">{{ $project->description }}</textarea></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Save Changes</button></div></form></div></div>
<div class="eh-modal" id="deleteProject{{ $project->id }}" aria-hidden="true"><div class="eh-modal-dialog"><div class="eh-modal-header"><div><h2>Delete Project?</h2><p>This cannot be undone.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div><div class="eh-modal-body"><p>Delete <strong>{{ $project->name }}</strong>?</p></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.projects.destroy',$project) }}">@csrf @method('DELETE')<button class="btn btn-danger">Delete Project</button></form></div></div></div>
@endforeach
@endsection
