@extends('layouts.admin')
@section('title','Programmes | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Programme Management</span><h1>Programmes</h1><p>Create, monitor and manage programme records from one page.</p></div>
<div class="admin-page-actions"><button type="button" class="btn btn-primary" data-modal-open="createProgrammeModal"><i class="fas fa-plus"></i> New Programme</button></div>
</div>

<div class="admin-stats-grid compact">
@foreach([
 ['total','Total Programmes','fa-diagram-project'],
 ['active','Active','fa-circle-check'],
 ['draft','Draft','fa-pen-ruler'],
 ['completed','Completed','fa-flag-checkered'],
] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search programme name, code or description..."></div>
<select name="status"><option value="">All statuses</option>@foreach(['draft','active','completed','on_hold','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
<select name="period"><option value="">All periods</option>@foreach(['today'=>'Today','week'=>'This week','month'=>'This month','quarter'=>'This quarter','year'=>'This year'] as $v=>$l)<option value="{{ $v }}" @selected(request('period')===$v)>{{ $l }}</option>@endforeach</select>
<input class="date-input" type="date" name="from_date" value="{{ request('from_date') }}" title="From date">
<input class="date-input" type="date" name="to_date" value="{{ request('to_date') }}" title="To date">
<select name="per_page">@foreach([10,15,25,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page',15)===$size)>{{ $size }} / page</option>@endforeach</select>
<button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Apply</button>
<a href="{{ route('admin.programmes.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-rotate-left"></i> Reset</a>
</form>

<div id="programmeBulkBar" class="admin-bulk-bar"><strong><span data-selected-count>0</span> selected</strong>
<form method="POST" action="{{ route('admin.programmes.bulk-destroy') }}" data-bulk-form data-table="programmesTable">@csrf @method('DELETE')
<button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete Selected</button></form></div>

<div class="admin-table-wrap">
<table class="admin-table" id="programmesTable">
<thead><tr><th class="select-col"><input type="checkbox" data-select-all data-bulk-target="#programmeBulkBar"></th><th>Programme</th><th>Period</th><th>Status</th><th>Created</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($programmes as $programme)
<tr>
<td><input type="checkbox" data-row-select value="{{ $programme->id }}"></td>
<td><strong>{{ $programme->name }}</strong><small class="admin-cell-hint">{{ $programme->code ?: 'No code' }}</small></td>
<td>{{ optional($programme->start_date)->format('d M Y') ?: '—' }} — {{ optional($programme->end_date)->format('d M Y') ?: '—' }}</td>
<td><span class="status-chip {{ $programme->status }}">{{ ucfirst(str_replace('_',' ',$programme->status)) }}</span></td>
<td>{{ optional($programme->created_at)->format('d M Y') }}</td>
<td class="table-actions"><div class="action-group">
<button type="button" class="btn-icon" title="Edit" data-modal-open="editProgramme{{ $programme->id }}"><i class="fas fa-pen"></i></button>
<button type="button" class="btn-icon danger" title="Delete" data-modal-open="deleteProgramme{{ $programme->id }}"><i class="fas fa-trash"></i></button>
</div></td>
</tr>
@empty
<tr><td colspan="6"><div class="admin-empty"><i class="fas fa-diagram-project"></i><strong>No programmes found</strong><span>Change the filters or create a new programme.</span></div></td></tr>
@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $programmes->links() }}</div>
</div>

<div class="eh-modal" id="createProgrammeModal" aria-hidden="true"><div class="eh-modal-dialog"><div class="eh-modal-header"><div><h2>New Programme</h2><p>Create a programme without leaving this page.</p></div><button class="eh-modal-close" type="button" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.programmes.store') }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Name *</label><input name="name" required placeholder="e.g. ElevateHer360 Digital Skills"><small class="form-hint">Use the official programme name.</small></div>
<div class="form-group"><label>Code</label><input name="code" placeholder="e.g. EH360-DS"><small class="form-hint">Optional short unique reference code.</small></div>
<div class="form-group"><label>Start date</label><input type="date" name="start_date"></div><div class="form-group"><label>End date</label><input type="date" name="end_date"></div>
<div class="form-group"><label>Status *</label><select name="status" required><option value="draft">Draft</option><option value="active">Active</option><option value="completed">Completed</option><option value="on_hold">On Hold</option><option value="cancelled">Cancelled</option></select><small class="form-hint">Choose the programme's current lifecycle status.</small></div>
<div class="form-group full"><label>Description</label><textarea name="description" placeholder="Briefly describe the programme purpose, target participants and expected outcomes..."></textarea></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Create Programme</button></div></form></div></div>

@foreach($programmes as $programme)
<div class="eh-modal" id="editProgramme{{ $programme->id }}" aria-hidden="true"><div class="eh-modal-dialog"><div class="eh-modal-header"><div><h2>Edit Programme</h2><p>Update {{ $programme->name }}.</p></div><button class="eh-modal-close" type="button" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.programmes.update',$programme) }}">@csrf @method('PUT')
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Name *</label><input name="name" value="{{ $programme->name }}" required placeholder="Programme name"></div>
<div class="form-group"><label>Code</label><input name="code" value="{{ $programme->code }}" placeholder="Programme code"></div>
<div class="form-group"><label>Start date</label><input type="date" name="start_date" value="{{ optional($programme->start_date)->format('Y-m-d') }}"></div>
<div class="form-group"><label>End date</label><input type="date" name="end_date" value="{{ optional($programme->end_date)->format('Y-m-d') }}"></div>
<div class="form-group"><label>Status *</label><select name="status" required>@foreach(['draft','active','completed','on_hold','cancelled'] as $s)<option value="{{ $s }}" @selected($programme->status===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
<div class="form-group full"><label>Description</label><textarea name="description" placeholder="Programme description">{{ $programme->description }}</textarea></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Changes</button></div></form></div></div>

<div class="eh-modal" id="deleteProgramme{{ $programme->id }}" aria-hidden="true"><div class="eh-modal-dialog"><div class="eh-modal-header"><div><h2>Delete Programme?</h2><p>This action cannot be undone.</p></div><button class="eh-modal-close" type="button" data-modal-close><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><p>Delete <strong>{{ $programme->name }}</strong>? Related database constraints may prevent deletion where the programme is already in use.</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.programmes.destroy',$programme) }}">@csrf @method('DELETE')<button class="btn btn-danger"><i class="fas fa-trash"></i> Delete Programme</button></form></div></div></div>
@endforeach
@endsection
