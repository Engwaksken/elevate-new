@extends('layouts.app')
@section('content')
<div class="card">
<h1>{{ $user->exists ? 'Edit User' : 'Add User' }}</h1>
<form method="POST" action="{{ $user->exists ? route('admin.users.update',$user) : route('admin.users.store') }}">
@csrf @if($user->exists) @method('PUT') @endif
<label>Name</label><input name="name" value="{{ old('name',$user->name) }}" required>
<label>Email</label><input type="email" name="email" value="{{ old('email',$user->email) }}" required>
<label>Phone</label><input name="phone" value="{{ old('phone',$user->phone) }}">
<div class="grid">
<div><label>User type</label><select name="user_type"><option value="participant" @selected(old('user_type',$user->user_type)==='participant')>Participant</option><option value="staff" @selected(old('user_type',$user->user_type)==='staff')>Staff</option></select></div>
<div><label>Status</label><select name="status">@foreach(['active','inactive','suspended','pending'] as $s)<option value="{{ $s }}" @selected(old('status',$user->status)===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
</div>
<label>Password {{ $user->exists ? '(leave blank to keep current)' : '' }}</label><input type="password" name="password">
<label>Confirm Password</label><input type="password" name="password_confirmation">
<div class="card"><h3>Roles</h3>
@foreach($roles as $role)
<label style="display:block"><input style="width:auto" type="checkbox" name="roles[]" value="{{ $role->id }}" @checked($user->exists && $user->roles->contains($role->id))> {{ $role->name }}</label>
@endforeach
</div>
<button>Save User</button>
</form>
</div>
@endsection
