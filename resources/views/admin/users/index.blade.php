@extends('layouts.admin')
@section('title','Users | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">People & Access</span><h1>Users</h1><p>Manage participant and staff accounts, status and role assignments.</p></div>
<div class="admin-page-actions"><a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add User</a></div>
</div>

<div class="admin-panel">
<form method="GET" class="admin-filter-bar">
<div class="admin-filter-search"><i class="fas fa-magnifying-glass"></i><input name="search" value="{{ request('search') }}" placeholder="Search name, email or phone..."></div>
<select name="user_type"><option value="">All user types</option><option value="participant" @selected(request('user_type')==='participant')>Participants</option><option value="staff" @selected(request('user_type')==='staff')>Staff</option></select>
<select name="status"><option value="">All statuses</option>@foreach(['active','inactive','suspended','pending'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select>
<button class="btn btn-primary btn-sm"><i class="fas fa-filter"></i> Filter</button>
@if(request()->hasAny(['search','user_type','status']))<a href="{{ route('admin.users.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-xmark"></i> Clear</a>@endif
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>User</th><th>Type</th><th>Status</th><th>Roles</th><th>Phone</th><th class="table-actions">Actions</th></tr></thead>
<tbody>
@forelse($users as $user)
<tr>
<td><div class="admin-user-cell"><span>{{ strtoupper(substr($user->name,0,1)) }}</span><div><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></div></div></td>
<td><span class="admin-badge">{{ ucfirst($user->user_type) }}</span></td>
<td><span class="admin-status admin-status-{{ $user->status }}">{{ ucfirst($user->status) }}</span></td>
<td>{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
<td>{{ $user->phone ?: '—' }}</td>
<td class="table-actions"><a href="{{ route('admin.users.edit',$user) }}" class="btn btn-outline btn-sm"><i class="fas fa-pen"></i> Edit</a></td>
</tr>
@empty
<tr><td colspan="6"><div class="admin-empty"><i class="fas fa-users"></i><strong>No users found</strong><span>Try changing the search or filter options.</span></div></td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="admin-pagination">{{ $users->links() }}</div>
</div>
@endsection
