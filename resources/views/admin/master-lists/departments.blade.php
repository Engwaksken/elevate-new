@extends('layouts.admin')
@section('title','Departments | ElevateHer360 Administration')
@section('content')
@php
    $bulkRoute = route('admin.departments.bulk-destroy');
    $bulkTableId = 'departmentsTable';
    $blank = new \App\Models\Department(['is_active' => true]);
@endphp
<div class="admin-page-header">
    <div><span class="admin-eyebrow">Administration</span><h1>Departments</h1><p>The department list staff choose from on employee records and purchase requests.</p></div>
    <div class="admin-page-actions"><x-export-buttons /><button type="button" class="btn btn-primary" data-modal-open="departmentCreate"><i class="fas fa-plus"></i> Add Department</button></div>
</div>

<div class="admin-stats-grid compact">
@foreach([['total','Total Departments','fa-sitemap'],['active','Active','fa-circle-check'],['inactive','Inactive','fa-circle-pause']] as [$key,$label,$icon])
<div class="admin-stat"><span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span><div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div></div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar" role="search">
    <div class="search-box"><i class="fas fa-magnifying-glass" aria-hidden="true"></i><input name="search" value="{{ request('search') }}" placeholder="Search department name or code..." aria-label="Search departments"></div>
    <select name="status" aria-label="Status"><option value="">All statuses</option><option value="active" @selected(request('status')==='active')>Active</option><option value="inactive" @selected(request('status')==='inactive')>Inactive</option></select>
    <select name="per_page" aria-label="Rows per page">@foreach([10,20,25,50,100] as $n)<option value="{{ $n }}" @selected((int)request('per_page',25)===$n)>{{ $n }}/page</option>@endforeach</select>
    <button class="btn btn-primary btn-sm">Apply</button><a href="{{ route('admin.departments.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

@include('partials.admin-bulk-bar', ['bulkRoute' => $bulkRoute, 'bulkTableId' => $bulkTableId])
<div class="admin-table-wrap"><table class="admin-table" id="{{ $bulkTableId }}">
<thead><tr><th style="width:34px"><input type="checkbox" data-select-all data-bulk-target="#{{ $bulkTableId }}-bar" aria-label="Select all"></th><th>Department</th><th>Head</th><th>Used by</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($records as $department)
<tr>
    <td>@if($department->usage_count > 0)<input type="checkbox" disabled aria-label="{{ $department->name }} is in use and cannot be deleted" title="In use — cannot be deleted">@else<input type="checkbox" data-row-select value="{{ $department->id }}" aria-label="Select {{ $department->name }}">@endif</td>
    <td><strong>{{ $department->name }}</strong><small class="admin-cell-hint">{{ $department->code ?: 'No code' }}</small></td>
    <td>{{ $department->head?->name ?: '—' }}</td>
    <td>{{ $department->usage_count ? number_format($department->usage_count).' record(s)' : 'Not used yet' }}</td>
    <td><span class="status-chip {{ $department->is_active ? 'active' : 'inactive' }}">{{ $department->is_active ? 'Active' : 'Inactive' }}</span></td>
    <td class="table-actions"><div class="action-group">
        <button class="btn-icon" type="button" data-modal-open="department{{ $department->id }}" aria-label="Edit {{ $department->name }}" title="Edit"><i class="fas fa-pen"></i></button>
        <form method="POST" action="{{ route('admin.departments.toggle', $department) }}">@csrf @method('PATCH')
            <button class="btn-icon" type="submit" aria-label="{{ $department->is_active ? 'Deactivate' : 'Activate' }} {{ $department->name }}" title="{{ $department->is_active ? 'Deactivate' : 'Activate' }}"><i class="fas {{ $department->is_active ? 'fa-circle-pause' : 'fa-circle-play' }}"></i></button>
        </form>
        @if($department->usage_count > 0)
        <button class="btn-icon danger" type="button" disabled aria-label="{{ $department->name }} is in use and cannot be deleted" title="In use — deactivate instead"><i class="fas fa-trash"></i></button>
        @else
        <button class="btn-icon danger" type="button" data-modal-open="departmentDelete{{ $department->id }}" aria-label="Delete {{ $department->name }}" title="Delete"><i class="fas fa-trash"></i></button>
        @endif
    </div></td>
</tr>
@empty
<tr><td colspan="6"><div class="admin-empty">No departments found. Use “Add Department” to create one.</div></td></tr>
@endforelse
</tbody></table></div>
<div class="admin-pagination">{{ $records->links() }}</div>
</div>

@include('admin.master-lists.department-modal', ['id' => 'departmentCreate', 'title' => 'Add Department', 'department' => $blank, 'action' => route('admin.departments.store'), 'method' => 'POST'])
@foreach($records as $department)
@include('admin.master-lists.department-modal', ['id' => 'department'.$department->id, 'title' => 'Edit Department', 'department' => $department, 'action' => route('admin.departments.update', $department), 'method' => 'PUT'])
@if(! $department->usage_count)
<div class="eh-modal" id="departmentDelete{{ $department->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="departmentDelete{{ $department->id }}-title"><div class="eh-modal-dialog eh-modal-sm">
<div class="eh-modal-header"><div><h2 id="departmentDelete{{ $department->id }}-title">Delete Department?</h2><p>{{ $department->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button></div>
<div class="eh-modal-body"><p>No employees, positions or purchase requests use this department. Delete it permanently?</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><form method="POST" action="{{ route('admin.departments.destroy', $department) }}">@csrf @method('DELETE')<button class="btn btn-danger">Delete</button></form></div>
</div></div>
@endif
@endforeach
@endsection
