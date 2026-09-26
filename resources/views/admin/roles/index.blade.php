@extends('layouts.admin')
@section('title','Roles & Permissions | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">People & Access</span>
    <h1>Roles & Permissions</h1>
    <p>Review role usage and control module permissions.</p>
</div>
</div>

<div class="admin-stats-grid compact">
@foreach([
['roles','Total Roles','fa-user-shield'],
['system_roles','System Roles','fa-lock'],
['custom_roles','Custom Roles','fa-user-gear'],
['permissions','Permissions','fa-key']
] as [$key,$label,$icon])
<div class="admin-stat">
    <span class="admin-stat-icon"><i class="fas {{ $icon }}"></i></span>
    <div><small>{{ $label }}</small><strong>{{ number_format($stats[$key] ?? 0) }}</strong></div>
</div>
@endforeach
</div>

<div class="admin-panel">
<form method="GET" class="admin-toolbar">
    <div class="search-box">
        <i class="fas fa-magnifying-glass"></i>
        <input name="search" value="{{ request('search') }}" placeholder="Search role name...">
    </div>

    <select name="per_page">
        @foreach([10,20,25,50,100] as $n)
        <option value="{{ $n }}" @selected((int)request('per_page',20)===$n)>{{ $n }}/page</option>
        @endforeach
    </select>

    <button class="btn btn-primary btn-sm">Apply</button>
    <a href="{{ route('admin.roles.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Role</th><th>Type</th><th>Users</th><th>Permissions</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($roles as $role)
<tr>
    <td><strong>{{ $role->name }}</strong></td>
    <td>{{ $role->is_system ? 'System' : 'Custom' }}</td>
    <td>{{ number_format($role->users_count) }}</td>
    <td>{{ number_format($role->permissions_count) }}</td>
    <td class="table-actions">
        <button type="button" class="btn btn-outline btn-sm" data-modal-open="editRole{{ $role->id }}">
            <i class="fas fa-key"></i> Permissions
        </button>
    </td>
</tr>
@empty
<tr><td colspan="5"><div class="admin-empty">No roles found.</div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $roles->links() }}</div>
</div>

@foreach($roles as $role)
<div class="eh-modal" id="editRole{{ $role->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header">
    <div>
        <h2>{{ $role->name }}</h2>
        <p>{{ $role->is_system ? 'System role name is protected; permissions can still be updated.' : 'Update role name and permissions.' }}</p>
    </div>
    <button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>

<form method="POST" action="{{ route('admin.roles.update',$role) }}">
@csrf @method('PUT')
<div class="eh-modal-body">

<div class="form-group">
    <label>Role Name *</label>
    <input name="name" value="{{ $role->name }}" required @readonly($role->is_system)>
    @if($role->is_system)
    <small class="form-hint">The name of a system role cannot be changed.</small>
    @endif
</div>

<div class="permission-toolbar">
    <button type="button" class="btn btn-outline btn-sm" data-permission-select-all="#permissions{{ $role->id }}">Select All</button>
    <button type="button" class="btn btn-outline btn-sm" data-permission-clear-all="#permissions{{ $role->id }}">Clear All</button>
</div>

<div id="permissions{{ $role->id }}" class="permission-module-grid">
@foreach($permissions as $module=>$items)
<section class="permission-module-card">
    <div class="permission-module-head">
        <strong>{{ ucfirst($module ?: 'General') }}</strong>
        <label class="permission-check compact">
            <input type="checkbox" data-module-toggle>
            <span>Select module</span>
        </label>
    </div>

    <div class="permission-check-grid">
    @foreach($items as $permission)
        <label class="permission-check">
            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked($role->permissions->contains($permission->id))>
            <span>{{ $permission->name }}</span>
        </label>
    @endforeach
    </div>
</section>
@endforeach
</div>

</div>

<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
    <button class="btn btn-primary">Save Permissions</button>
</div>
</form>
</div>
</div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-permission-select-all]').forEach(function(button){
        button.addEventListener('click', function(){
            document.querySelectorAll(this.dataset.permissionSelectAll + ' input[type="checkbox"]').forEach(cb => cb.checked=true);
        });
    });

    document.querySelectorAll('[data-permission-clear-all]').forEach(function(button){
        button.addEventListener('click', function(){
            document.querySelectorAll(this.dataset.permissionClearAll + ' input[type="checkbox"]').forEach(cb => cb.checked=false);
        });
    });

    document.querySelectorAll('[data-module-toggle]').forEach(function(toggle){
        toggle.addEventListener('change', function(){
            this.closest('.permission-module-card')
                ?.querySelectorAll('input[name="permissions[]"]')
                .forEach(cb => cb.checked=this.checked);
        });
    });
});
</script>
@endsection
