@extends('layouts.admin')
@section('title','Branches | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header"><div><span class="admin-eyebrow">Programme Management</span><h1>Branches</h1><p>Manage operational locations and active branch records.</p></div><div class="admin-page-actions"><button type="button" class="btn btn-primary" data-modal-open="createBranchModal"><i class="fas fa-plus"></i> New Branch</button></div></div>

<div class="admin-stats-grid compact">
@foreach([['total','Total Branches','fa-building'],['active','Active','fa-circle-check'],['inactive','Inactive','fa-circle-pause'],['districts','Districts','fa-map-location-dot']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
<div class="search-box"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search branch, district, country or code..."></div>
<select name="status"><option value="">All statuses</option><option value="active" @selected(request('status')==='active')>Active</option><option value="inactive" @selected(request('status')==='inactive')>Inactive</option></select>
<select name="period"><option value="">All periods</option>@foreach(['today'=>'Today','week'=>'This week','month'=>'This month','quarter'=>'This quarter','year'=>'This year'] as $v=>$l)<option value="{{ $v }}" @selected(request('period')===$v)>{{ $l }}</option>@endforeach</select>
<input class="date-input" type="date" name="from_date" value="{{ request('from_date') }}"><input class="date-input" type="date" name="to_date" value="{{ request('to_date') }}">
<select name="per_page">@foreach([10,15,25,50,100] as $size)<option value="{{ $size }}" @selected((int)request('per_page',15)===$size)>{{ $size }} / page</option>@endforeach</select>
<button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.branches.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div id="branchBulkBar" class="admin-bulk-bar"><strong><span data-selected-count>0</span> selected</strong><form method="POST" action="{{ route('admin.branches.bulk-destroy') }}" data-bulk-form data-table="branchesTable">@csrf @method('DELETE')<button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i> Delete Selected</button></form></div>

<div class="admin-table-wrap"><table class="admin-table" id="branchesTable"><thead><tr><th class="select-col"><input type="checkbox" data-select-all data-bulk-target="#branchBulkBar"></th><th>Branch</th><th>District</th><th>Country</th><th>Status</th><th class="table-actions">Actions</th></tr></thead><tbody>
@forelse($branches as $branch)
<tr><td><input type="checkbox" data-row-select value="{{ $branch->id }}"></td><td><strong>{{ $branch->name }}</strong><small class="admin-cell-hint">{{ $branch->code ?: 'No code' }}</small></td><td>{{ $branch->district ?: '—' }}</td><td>{{ $branch->country }}</td><td><span class="status-chip {{ $branch->is_active?'active':'inactive' }}">{{ $branch->is_active?'Active':'Inactive' }}</span></td><td class="table-actions"><div class="action-group"><button class="btn-icon" type="button" data-modal-open="editBranch{{ $branch->id }}"><i class="fas fa-pen"></i></button><button class="btn-icon danger" type="button" data-modal-open="deleteBranch{{ $branch->id }}"><i class="fas fa-trash"></i></button></div></td></tr>
@empty<tr><td colspan="6"><div class="admin-empty">No branches found.</div></td></tr>@endforelse
</tbody></table></div><div class="admin-pagination">{{ $branches->links() }}</div></div>

<div class="eh-modal" id="createBranchModal" aria-hidden="true"><div class="eh-modal-dialog"><div class="eh-modal-header"><div><h2>New Branch</h2><p>Add an operational location.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div><form method="POST" action="{{ route('admin.branches.store') }}">@csrf<div class="eh-modal-body"><div class="modal-grid">
<div class="form-group"><label>Branch name *</label><input name="name" required placeholder="e.g. Kampala Branch"><small class="form-hint">Use the official location or branch name.</small></div><div class="form-group"><label>Code</label><input name="code" placeholder="e.g. KLA"></div><div class="form-group"><label>District</label><input name="district" placeholder="e.g. Kampala"></div><div class="form-group"><label>Country *</label><input name="country" value="Uganda" required placeholder="e.g. Uganda"></div><div class="form-group full"><label><input type="checkbox" name="is_active" value="1" checked> Active branch</label><small class="form-hint">Inactive branches stay in records but should not be used for new assignments.</small></div>
</div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Branch</button></div></form></div></div>

@foreach($branches as $branch)
<div class="eh-modal" id="editBranch{{ $branch->id }}" aria-hidden="true"><div class="eh-modal-dialog"><div class="eh-modal-header"><div><h2>Edit Branch</h2><p>{{ $branch->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div><form method="POST" action="{{ route('admin.branches.update',$branch) }}">@csrf @method('PUT')<div class="eh-modal-body"><div class="modal-grid"><div class="form-group"><label>Name *</label><input name="name" value="{{ $branch->name }}" required></div><div class="form-group"><label>Code</label><input name="code" value="{{ $branch->code }}"></div><div class="form-group"><label>District</label><input name="district" value="{{ $branch->district }}"></div><div class="form-group"><label>Country *</label><input name="country" value="{{ $branch->country }}" required></div><div class="form-group full"><label><input type="checkbox" name="is_active" value="1" @checked($branch->is_active)> Active branch</label></div></div></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Save Changes</button></div></form></div></div>
<div class="eh-modal" id="deleteBranch{{ $branch->id }}" aria-hidden="true"><div class="eh-modal-dialog"><div class="eh-modal-header"><div><h2>Delete Branch?</h2><p>This cannot be undone.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div><div class="eh-modal-body"><p>Delete <strong>{{ $branch->name }}</strong>?</p></div><div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.branches.destroy',$branch) }}">@csrf @method('DELETE')<button class="btn btn-danger">Delete Branch</button></form></div></div></div>
@endforeach
@endsection
