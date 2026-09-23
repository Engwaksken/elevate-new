@extends('layouts.admin')
@section('title',($user->exists?'Edit User':'Add User').' | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div><span class="admin-eyebrow">People & Access</span><h1>{{ $user->exists?'Edit User':'Add User' }}</h1><p>{{ $user->exists?'Update account details, status and roles.':'Create a participant or staff account and assign appropriate access.' }}</p></div>
<div class="admin-page-actions"><a href="{{ route('admin.users.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Users</a></div>
</div>

<div class="admin-panel admin-form-panel">
<form method="POST" action="{{ $user->exists?route('admin.users.update',$user):route('admin.users.store') }}">
@csrf
@if($user->exists)@method('PUT')@endif

<div class="admin-form-section">
<div class="admin-panel-head"><div><h2>Account Information</h2><p>Basic identity and contact details.</p></div></div>
<div class="form-grid">
<div class="form-group"><label>Full name <span class="required">*</span></label><input name="name" value="{{ old('name',$user->name) }}" required placeholder="e.g. Sarah Nansubuga"><small class="form-hint">Enter the user's preferred full name.</small>@error('name')<small class="field-error">{{ $message }}</small>@enderror</div>
<div class="form-group"><label>Email <span class="required">*</span></label><input type="email" name="email" value="{{ old('email',$user->email) }}" required placeholder="e.g. sarah@example.com"><small class="form-hint">This email is used for authentication and notifications.</small>@error('email')<small class="field-error">{{ $message }}</small>@enderror</div>
<div class="form-group"><label>Phone</label><input name="phone" value="{{ old('phone',$user->phone) }}" placeholder="e.g. +256 700 000000"></div>
<div class="form-group"><label>User type <span class="required">*</span></label><select name="user_type" required><option value="">Select user type</option><option value="participant" @selected(old('user_type',$user->user_type)==='participant')>Participant</option><option value="staff" @selected(old('user_type',$user->user_type)==='staff')>Staff</option></select><small class="form-hint">Staff accounts can only access admin features allowed by their roles.</small></div>
<div class="form-group"><label>Status <span class="required">*</span></label><select name="status" required>@foreach(['active','inactive','suspended','pending'] as $status)<option value="{{ $status }}" @selected(old('status',$user->status ?: 'active')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
</div>
</div>

<div class="admin-form-section">
<div class="admin-panel-head"><div><h2>Security</h2><p>{{ $user->exists?'Leave password blank to keep the current password.':'Set a strong initial password.' }}</p></div></div>
<div class="form-grid">
<div class="form-group"><label>Password @unless($user->exists)<span class="required">*</span>@endunless</label><div class="password-wrap"><input id="admin_user_password" type="password" name="password" placeholder="{{ $user->exists?'Leave blank to keep current password':'Create a strong password' }}" @required(!$user->exists)><button type="button" class="password-toggle" data-password-toggle="admin_user_password"><i class="fas fa-eye"></i></button></div><small class="form-hint">Minimum 8 characters with mixed case and numbers.</small></div>
<div class="form-group"><label>Confirm password @unless($user->exists)<span class="required">*</span>@endunless</label><div class="password-wrap"><input id="admin_user_password_confirmation" type="password" name="password_confirmation" placeholder="Repeat the password" @required(!$user->exists)><button type="button" class="password-toggle" data-password-toggle="admin_user_password_confirmation"><i class="fas fa-eye"></i></button></div></div>
</div>
</div>

<div class="admin-form-section">
<div class="admin-panel-head"><div><h2>Roles</h2><p>Assign one or more roles. Permissions are inherited from the selected roles.</p></div></div>
<div class="admin-role-grid">
@foreach($roles as $role)
<label class="admin-role-option"><input type="checkbox" name="roles[]" value="{{ $role->id }}" @checked(in_array($role->id,old('roles',$user->exists?$user->roles->pluck('id')->all():[])))><span><strong>{{ $role->name }}</strong><small>Assign this role</small></span></label>
@endforeach
</div>
</div>

<div class="admin-form-actions"><a href="{{ route('admin.users.index') }}" class="btn btn-outline">Cancel</a><button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> {{ $user->exists?'Save Changes':'Create User' }}</button></div>
</form>
</div>
@endsection
