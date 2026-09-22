@extends('layouts.app')
@section('content')
<div class="card">
<h1>Users</h1>
<form method="GET" class="grid">
<div><label>Search</label><input name="search" value="{{ request('search') }}"></div>
<div><label>User type</label><select name="user_type"><option value="">All</option><option value="participant">Participant</option><option value="staff">Staff</option></select></div>
<div><label>Status</label><select name="status"><option value="">All</option><option>active</option><option>inactive</option><option>suspended</option><option>pending</option></select></div>
<div style="align-self:end"><button>Filter</button></div>
</form>
<p><a class="btn" href="{{ route('admin.users.create') }}">Add User</a></p>
<table width="100%" cellpadding="8">
<tr><th align="left">Name</th><th>Email</th><th>Type</th><th>Status</th><th>Roles</th><th></th></tr>
@foreach($users as $user)
<tr>
<td>{{ $user->name }}</td><td>{{ $user->email }}</td><td>{{ $user->user_type }}</td><td>{{ $user->status }}</td>
<td>{{ $user->roles->pluck('name')->join(', ') }}</td>
<td><a href="{{ route('admin.users.edit',$user) }}">Edit</a></td>
</tr>
@endforeach
</table>
{{ $users->links() }}
</div>
@endsection
