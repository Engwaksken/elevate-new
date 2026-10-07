@extends('layouts.admin')
@section('title','Roles & Permissions | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">People & Access</span>
    <h1>Roles & Permissions</h1>
    <p>Manage access roles and the permissions available to assign to them.</p>
</div>
<div class="admin-page-actions"><x-export-buttons />@if(auth()->user()?->hasPermission('permissions.manage'))<button type="button" class="btn btn-outline" data-modal-open="createPermission"><i class="fas fa-key"></i> Add Permission</button>@endif<button type="button" class="btn btn-primary" data-modal-open="createRole"><i class="fas fa-plus"></i> Add Role</button></div>
</div>
@include('admin.shared.feedback')

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

<div class="admin-panel" style="margin-top:20px">
    <div class="admin-panel-header"><div><h2>Permission catalog</h2><p>Permissions can be assigned to custom and existing roles.</p></div></div>
    <div class="admin-table-wrap">
        <table class="admin-table"><thead><tr><th>Permission</th><th>Key</th><th>Module</th></tr></thead><tbody>
        @foreach($permissions as $module => $items)
            @foreach($items as $permission)
                <tr><td>{{ $permission->name }}</td><td><code>{{ $permission->slug }}</code></td><td>{{ ucfirst(str_replace('_', ' ', $module ?: 'General')) }}</td></tr>
            @endforeach
        @endforeach
        </tbody></table>
    </div>
</div>

<div class="eh-modal" id="createRole" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header"><div><h2>Add Role</h2><p>Create a custom access role and choose its permissions.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.roles.store') }}">@csrf
<div class="eh-modal-body">
    <div class="modal-grid">
        <div class="form-group"><label>Role Name *</label><input name="name" value="{{ old('name') }}" maxlength="100" required placeholder="e.g. Regional Coordinator"></div>
        <div class="form-group"><label>Role Key (optional)</label><input name="slug" value="{{ old('slug') }}" maxlength="100" placeholder="Generated from role name if blank"></div>
        <div class="form-group full"><label>Description</label><textarea name="description" rows="2" maxlength="2000">{{ old('description') }}</textarea></div>
    </div>
    <div class="permission-toolbar"><strong>Permissions</strong><button type="button" class="btn btn-outline btn-sm" data-permission-select-all="#newRolePermissions">Select All</button><button type="button" class="btn btn-outline btn-sm" data-permission-clear-all="#newRolePermissions">Clear All</button></div>
    <div id="newRolePermissions" class="permission-module-grid">
        @foreach($permissions as $module => $items)
            <section class="permission-module-card"><div class="permission-module-head"><strong>{{ ucfirst(str_replace('_', ' ', $module ?: 'General')) }}</strong><label class="permission-check compact"><input type="checkbox" data-module-toggle><span>Select module</span></label></div><div class="permission-check-grid">
                @foreach($items as $permission)
                    <label class="permission-check"><input type="checkbox" name="permissions[]" value="{{ $permission->id }}"><span>{{ $permission->name }}</span></label>
                @endforeach
            </div></section>
        @endforeach
    </div>
</div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Create Role</button></div>
</form>
</div>
</div>

@if(auth()->user()?->hasPermission('permissions.manage'))
<div class="eh-modal" id="createPermission" aria-hidden="true">
<div class="eh-modal-dialog">
<div class="eh-modal-header"><div><h2>Add Permission</h2><p>Add a permission key to the catalogue so it can be assigned to roles.</p></div><button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button></div>
<form method="POST" action="{{ route('admin.permissions.store') }}">@csrf
<div class="eh-modal-body"><div class="modal-grid">
    <div class="form-group"><label>Name *</label><input name="name" value="{{ old('name') }}" maxlength="100" required placeholder="e.g. View Reports"></div>
    <div class="form-group"><label>Permission Key *</label><input name="slug" value="{{ old('slug') }}" maxlength="120" required placeholder="e.g. reports.view"></div>
    <div class="form-group"><label>Module *</label><input name="module" value="{{ old('module') }}" maxlength="80" required placeholder="e.g. reports"></div>
    <div class="form-group"><label>Description</label><input name="description" value="{{ old('description') }}" maxlength="2000"></div>
</div><p class="form-hint">Permission keys use lowercase letters, numbers, dots, underscores or hyphens.</p></div>
<div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Add Permission</button></div>
</form>
</div>
</div>
@endif

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
