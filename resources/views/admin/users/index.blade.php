@extends('layouts.admin')
@section('title','Users | ElevateHer360 Administration')
@section('content')

<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">People & Access</span>
    <h1>Users</h1>
    <p>Manage participant and staff accounts, access status and assigned roles.</p>
</div>
<div class="admin-page-actions">
    <button type="button" class="btn btn-primary" data-modal-open="createUserModal">
        <i class="fas fa-user-plus"></i> Add User
    </button>
</div>
</div>

<div class="admin-stats-grid compact">
@foreach([
['total','Total Users','fa-users'],
['participants','Participants','fa-user-graduate'],
['staff','Staff','fa-user-tie'],
['inactive','Inactive / Pending','fa-user-clock']
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
        <input name="search" value="{{ request('search') }}" placeholder="Search name, email or phone...">
    </div>

    <select name="user_type">
        <option value="">All user types</option>
        <option value="participant" @selected(request('user_type')==='participant')>Participant</option>
        <option value="staff" @selected(request('user_type')==='staff')>Staff</option>
    </select>

    <select name="status">
        <option value="">All statuses</option>
        @foreach(['active','inactive','suspended','pending'] as $status)
        <option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>
        @endforeach
    </select>

    <select name="per_page">
        @foreach([10,20,25,50,100] as $n)
        <option value="{{ $n }}" @selected((int)request('per_page',20)===$n)>{{ $n }}/page</option>
        @endforeach
    </select>

    <button class="btn btn-primary btn-sm">Apply</button>
    <a href="{{ route('admin.users.index') }}" class="btn btn-outline btn-sm">Reset</a>
</form>

<div class="admin-table-wrap">
<table class="admin-table">
<thead>
<tr>
    <th>User</th>
    <th>Phone</th>
    <th>Type</th>
    <th>Status</th>
    <th>Roles</th>
    <th>Last Login</th>
    <th class="table-actions">Actions</th>
</tr>
</thead>
<tbody>
@forelse($users as $user)
<tr>
    <td>
        <strong>{{ $user->name }}</strong>
        <small class="admin-cell-hint">{{ $user->email }}</small>
    </td>
    <td>{{ $user->phone ?: '—' }}</td>
    <td>{{ ucfirst($user->user_type) }}</td>
    <td><span class="status-chip {{ $user->status }}">{{ ucfirst($user->status) }}</span></td>
    <td>
        @if($user->roles->count())
            {{ $user->roles->pluck('name')->join(', ') }}
        @else
            <span class="admin-cell-hint">No roles</span>
        @endif
    </td>
    <td>{{ optional($user->last_login_at)->format('d M Y H:i') ?: '—' }}</td>
    <td class="table-actions">
        <button type="button" class="btn btn-outline btn-sm" data-modal-open="editUser{{ $user->id }}">
            <i class="fas fa-pen"></i> Edit
        </button>
    </td>
</tr>
@empty
<tr><td colspan="7"><div class="admin-empty">No users found.</div></td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="admin-pagination">{{ $users->links() }}</div>
</div>

<div class="eh-modal" id="createUserModal" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header">
    <div><h2>Add User</h2><p>Create a participant or staff account.</p></div>
    <button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>

<form method="POST" action="{{ route('admin.users.store') }}">
@csrf
<div class="eh-modal-body">
<div class="modal-grid">
    <div class="form-group full"><label>Full Name *</label><input name="name" value="{{ old('name') }}" required></div>
    <div class="form-group"><label>Email *</label><input type="email" name="email" value="{{ old('email') }}" required></div>
    <div class="form-group"><label>Phone</label><input name="phone" value="{{ old('phone') }}"></div>

    <div class="form-group"><label>User Type *</label>
        <select name="user_type" required>
            <option value="participant" @selected(old('user_type')==='participant')>Participant</option>
            <option value="staff" @selected(old('user_type')==='staff')>Staff</option>
        </select>
    </div>

    <div class="form-group"><label>Status *</label>
        <select name="status" required>
            @foreach(['active','inactive','suspended','pending'] as $status)
            <option value="{{ $status }}" @selected(old('status','active')===$status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group"><label>Password *</label><input type="password" name="password" required autocomplete="new-password"><small class="form-hint">Minimum 8 characters, mixed case and a number.</small></div>
    <div class="form-group"><label>Confirm Password *</label><input type="password" name="password_confirmation" required autocomplete="new-password"></div>

    <div class="form-group full">
        <label>Roles</label>
        <div class="permission-check-grid">
            @foreach($roles as $role)
            <label class="permission-check">
                <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id,old('roles',[])))>
                <span>{{ $role->name }}</span>
            </label>
            @endforeach
        </div>
    </div>
</div>
</div>

<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
    <button class="btn btn-primary">Create User</button>
</div>
</form>
</div>
</div>

@foreach($users as $user)
<div class="eh-modal" id="editUser{{ $user->id }}" aria-hidden="true">
<div class="eh-modal-dialog eh-modal-lg">
<div class="eh-modal-header">
    <div><h2>Edit User</h2><p>{{ $user->name }}</p></div>
    <button type="button" class="eh-modal-close" data-modal-close><i class="fas fa-xmark"></i></button>
</div>

<form method="POST" action="{{ route('admin.users.update',$user) }}">
@csrf @method('PUT')
<div class="eh-modal-body">
<div class="modal-grid">
    <div class="form-group full"><label>Full Name *</label><input name="name" value="{{ $user->name }}" required></div>
    <div class="form-group"><label>Email *</label><input type="email" name="email" value="{{ $user->email }}" required></div>
    <div class="form-group"><label>Phone</label><input name="phone" value="{{ $user->phone }}"></div>

    <div class="form-group"><label>User Type *</label>
        <select name="user_type" required>
            <option value="participant" @selected($user->user_type==='participant')>Participant</option>
            <option value="staff" @selected($user->user_type==='staff')>Staff</option>
        </select>
    </div>

    <div class="form-group"><label>Status *</label>
        <select name="status" required>
            @foreach(['active','inactive','suspended','pending'] as $status)
            <option value="{{ $status }}" @selected($user->status===$status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group"><label>New Password</label><input type="password" name="password" autocomplete="new-password"><small class="form-hint">Leave blank to keep the current password.</small></div>
    <div class="form-group"><label>Confirm New Password</label><input type="password" name="password_confirmation" autocomplete="new-password"></div>

    <div class="form-group full">
        <label>Roles</label>
        <div class="permission-check-grid">
            @foreach($roles as $role)
            <label class="permission-check">
                <input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($user->roles->contains($role->id))>
                <span>{{ $role->name }}</span>
            </label>
            @endforeach
        </div>
    </div>
</div>
</div>

<div class="eh-modal-footer">
    <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
    <button class="btn btn-primary">Save Changes</button>
</div>
</form>
</div>
</div>
@endforeach

@if($errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelector('[data-modal-open="createUserModal"]')?.click();
});
</script>
@endif
@endsection
