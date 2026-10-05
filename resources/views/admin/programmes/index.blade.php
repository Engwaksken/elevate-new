@extends('layouts.admin')
@section('title','Programmes | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">Programme Management</span><h1>Programmes</h1><p>Manage organisation programmes, dates and implementation status.</p></div>
<div class="admin-page-actions"><button type="button" class="btn btn-primary" data-modal-open="programmeCreate"><i class="fas fa-plus"></i> Add Programme</button></div>
</div>
@include('admin.shared.feedback')
<div class="admin-stats-grid compact">
@foreach([['total','Total Programmes','fa-diagram-project'],['active','Active','fa-circle-check'],['draft','Draft','fa-pen'],['completed','Completed','fa-flag-checkered']] as [$k,$l,$i])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $i }}"></i></span><div><small>{{ $l }}</small><strong>{{ number_format($stats[$k]??0) }}</strong></div></div>
@endforeach
</div>
<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search programme name, code or description..."></div>
<select name="status"><option value="">All statuses</option>@foreach(['draft','active','completed','on_hold','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select>
<input type="date" name="from" value="{{ request('from') }}" title="Programme start date from">
<input type="date" name="to" value="{{ request('to') }}" title="Programme end date to">
<select name="per_page">@foreach([10,20,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',20)===$n)>{{ $n }}/page</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.programmes.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>
@php($bulkRoute = route('admin.programmes.bulk-destroy')) @php($bulkTableId = 'programmesTable')
@include('partials.admin-bulk-bar', ['bulkRoute' => $bulkRoute, 'bulkTableId' => $bulkTableId])
<div class="admin-table-wrap"><table class="admin-table" id="{{ $bulkTableId }}"><thead><tr><th style="width:34px"><input type="checkbox" data-select-all data-bulk-target="#{{ $bulkTableId }}-bar" aria-label="Select all"></th><th>Programme</th><th>Dates</th><th>Targets &amp; Progress</th><th>Status</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($programmes as $programme)
<tr>
<td><input type="checkbox" data-row-select value="{{ $programme->id }}" aria-label="Select {{ $programme->name }}"></td>
<td><strong>{{ $programme->name }}</strong><small class="admin-cell-hint">{{ $programme->code ?: 'No code' }}</small></td>
<td>{{ optional($programme->start_date)->format('d M Y') ?: '—' }} — {{ optional($programme->end_date)->format('d M Y') ?: '—' }}</td>
<td style="min-width:170px">
    <strong>{{ number_format((float)$programme->progress_percent,0) }}%</strong>
    <div style="height:6px;background:#eee;border-radius:6px;margin-top:5px;overflow:hidden"><div style="height:100%;width:{{ max(0,min(100,(float)$programme->progress_percent)) }}%;background:#800000"></div></div>
    <small class="admin-cell-hint">{{ $programme->targets->count() }} target{{ $programme->targets->count() === 1 ? '' : 's' }}</small>
</td>
<td><span class="status-chip {{ $programme->status }}">{{ ucfirst(str_replace('_',' ',$programme->status)) }}</span></td>
<td class="table-actions"><div class="action-group"><button class="btn-icon" type="button" data-modal-open="programmeTargets{{ $programme->id }}" title="Targets and progress"><i class="fas fa-bullseye"></i></button><button class="btn-icon" type="button" data-modal-open="programme{{ $programme->id }}" title="Edit"><i class="fas fa-pen"></i></button><button class="btn-icon danger" type="button" data-modal-open="programmeDelete{{ $programme->id }}" title="Delete"><i class="fas fa-trash"></i></button></div></td>
</tr>
@empty<tr><td colspan="6"><div class="admin-empty">No programmes found.</div></td></tr>@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $programmes->links() }}</div>
</div>

<div class="eh-modal" id="programmeCreate" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>Add Programme</h2><p>Create a new programme record.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.programmes.store') }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Name *</label><input name="name" value="{{ old('name') }}" placeholder="e.g. ElevateHer360 Digital Skills Programme" required><small class="form-hint">Required. Use the official programme name.</small></div>
<div class="form-group"><label>Code</label><input name="code" value="{{ old('code') }}" placeholder="e.g. EH360-2026"><small class="form-hint">Optional. Enter a short unique programme code.</small></div>
<div class="form-group"><label>Status *</label><select name="status" required>@foreach(['draft','active','completed','on_hold','cancelled'] as $s)<option value="{{ $s }}">{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select><small class="form-hint">Required. Select the current programme status.</small></div>
<div class="form-group"><label>Start Date</label><input type="date" name="start_date"><small class="form-hint">Optional. Choose the programme start date.</small></div>
<div class="form-group"><label>End Date</label><input type="date" name="end_date"><small class="form-hint">Optional. Must be on or after the start date.</small></div>
<div class="form-group full"><label>Description</label><textarea name="description" rows="5" placeholder="Describe the programme purpose, target group and scope...">{{ old('description') }}</textarea><small class="form-hint">Optional. Provide a concise programme overview.</small></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Programme</button></div>
</form></div></div>

@foreach($programmes as $programme)
<div class="eh-modal" id="programme{{ $programme->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg"><div class="eh-modal-header"><div><h2>Edit Programme</h2><p>{{ $programme->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.programmes.update',$programme) }}">@csrf @method('PUT')
<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group full"><label>Name *</label><input name="name" value="{{ $programme->name }}" placeholder="e.g. ElevateHer360 Digital Skills Programme" required><small class="form-hint">Required. Use the official programme name.</small></div>
<div class="form-group"><label>Code</label><input name="code" value="{{ $programme->code }}" placeholder="e.g. EH360-2026"><small class="form-hint">Optional. Keep this unique.</small></div>
<div class="form-group"><label>Status *</label><select name="status">@foreach(['draft','active','completed','on_hold','cancelled'] as $s)<option value="{{ $s }}" @selected($programme->status===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select><small class="form-hint">Required. Select the current programme status.</small></div>
<div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="{{ optional($programme->start_date)->format('Y-m-d') }}"><small class="form-hint">Optional. Choose the programme start date.</small></div>
<div class="form-group"><label>End Date</label><input type="date" name="end_date" value="{{ optional($programme->end_date)->format('Y-m-d') }}"><small class="form-hint">Optional. Must be on or after the start date.</small></div>
<div class="form-group full"><label>Description</label><textarea name="description" rows="5" placeholder="Describe the programme purpose, target group and scope...">{{ $programme->description }}</textarea><small class="form-hint">Optional. Provide a concise programme overview.</small></div>
</div></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Save Changes</button></div></form></div></div>
<div class="eh-modal" id="programmeDelete{{ $programme->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-sm"><div class="eh-modal-header"><div><h2>Delete Programme?</h2><p>{{ $programme->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div><div class="eh-modal-body"><p>Delete only if this programme has no dependent records. Otherwise the system will block the deletion.</p></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.programmes.destroy',$programme) }}">@csrf @method('DELETE')<button class="btn btn-danger">Delete</button></form></div></div></div>
<div class="eh-modal" id="programmeTargets{{ $programme->id }}" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>Targets &amp; Progress</h2><p>{{ $programme->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body">
<div style="margin-bottom:14px"><strong>Overall progress:</strong> {{ number_format((float)$programme->progress_percent,1) }}%
<div style="height:7px;background:#eee;border-radius:7px;margin-top:6px;overflow:hidden"><div style="height:100%;width:{{ max(0,min(100,(float)$programme->progress_percent)) }}%;background:#800000"></div></div></div>

@forelse($programme->targets as $target)
<div style="border:1px solid #eadede;border-radius:10px;padding:12px;margin-bottom:10px">
<div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
    <div><strong>{{ $target->name }}</strong>
        @if($target->result_area)<small class="admin-cell-hint">{{ $target->result_area }}</small>@endif
        <div style="font-size:.82rem;color:#667085">{{ rtrim(rtrim(number_format((float)$target->achieved_value,2),'0'),'.') }} / {{ rtrim(rtrim(number_format((float)$target->target_value,2),'0'),'.') }} {{ $target->unit }} · {{ ucfirst(str_replace('_',' ',$target->status)) }} · weight {{ $target->weight }}</div>
    </div>
    <strong>{{ number_format((float)$target->progress_percent,0) }}%</strong>
</div>
<form method="POST" action="{{ route('admin.programme-targets.update',$target) }}" style="margin-top:10px">
    @csrf @method('PUT')
    <div class="modal-grid">
        <div class="form-group"><label>Target</label><input type="number" step="any" min="0" name="target_value" value="{{ (float)$target->target_value }}"></div>
        <div class="form-group"><label>Achieved</label><input type="number" step="any" min="0" name="achieved_value" value="{{ (float)$target->achieved_value }}"></div>
        <div class="form-group"><label>Weight</label><input type="number" step="0.01" min="0.01" name="weight" value="{{ (float)$target->weight }}"></div>
        <div class="form-group"><label>Status</label><select name="status">@foreach(['not_started','in_progress','achieved','at_risk','cancelled'] as $s)<option value="{{ $s }}" @selected($target->status===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach</select></div>
        <div class="form-group"><label>End date</label><input type="date" name="end_date" value="{{ optional($target->end_date)->format('Y-m-d') }}"></div>
        <div class="form-group"><label>Responsible</label><select name="responsible_user_id"><option value="">None</option>@foreach($users->where('user_type','staff') as $u)<option value="{{ $u->id }}" @selected($target->responsible_user_id===$u->id)>{{ $u->name }}</option>@endforeach</select></div>
        <input type="hidden" name="name" value="{{ $target->name }}">
        <input type="hidden" name="unit" value="{{ $target->unit }}">
    </div>
    <div style="display:flex;gap:8px;margin-top:8px">
        <button class="btn btn-primary btn-sm">Save progress</button>
    </div>
</form>
<form method="POST" action="{{ route('admin.programme-targets.destroy',$target) }}" onsubmit="return confirm('Remove this target?');" style="margin-top:6px">@csrf @method('DELETE')
    <button class="btn btn-outline btn-sm"><i class="fas fa-trash"></i> Remove</button>
</form>
</div>
@empty
<div class="admin-empty" style="margin-bottom:14px">No targets yet. Add the first target below.</div>
@endforelse

<form method="POST" action="{{ route('admin.programmes.targets.store',$programme) }}" style="border-top:1px solid #eee;padding-top:14px">
@csrf
<h3 style="margin:0 0 10px">Add target</h3>
<div class="modal-grid">
    <div class="form-group full"><label>Target name *</label><input name="name" required maxlength="190" placeholder="e.g. 500 women trained"></div>
    <div class="form-group"><label>Result area</label><input name="result_area" maxlength="190" placeholder="e.g. Skills"></div>
    <div class="form-group"><label>Unit</label><input name="unit" maxlength="40" placeholder="e.g. women"></div>
    <div class="form-group"><label>Baseline</label><input type="number" step="any" name="baseline_value"></div>
    <div class="form-group"><label>Target value *</label><input type="number" step="any" min="0" name="target_value" required></div>
    <div class="form-group"><label>Achieved</label><input type="number" step="any" min="0" name="achieved_value" value="0"></div>
    <div class="form-group"><label>Weight</label><input type="number" step="0.01" min="0.01" name="weight" value="1"></div>
    <div class="form-group"><label>Start date</label><input type="date" name="start_date"></div>
    <div class="form-group"><label>End date</label><input type="date" name="end_date"></div>
    <div class="form-group"><label>Responsible</label><select name="responsible_user_id"><option value="">None</option>@foreach($users->where('user_type','staff') as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
    <div class="form-group full"><label>Description</label><textarea name="description" rows="2"></textarea></div>
</div>
<div style="margin-top:10px"><button class="btn btn-primary"><i class="fas fa-plus"></i> Add target</button></div>
</form>
</div>
</div></div>
@endforeach
@endsection
