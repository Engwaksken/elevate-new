@extends('layouts.admin')
@section('title','My Profile | ElevateHer360 Administration')
@section('content')
<div class="admin-page-header">
<div>
    <span class="admin-eyebrow">Account</span>
    <h1>My Profile</h1>
    <p>Manage your account details and password.</p>
</div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="alert alert-error">
    @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
    @endforeach
</div>
@endif

<div class="profile-admin-grid">
<section class="admin-panel">
    <div class="admin-panel-head">
        <div>
            <h2>Profile Details</h2>
            <p>Update the name and email attached to your account.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.profile.update') }}">
        @csrf
        @method('PUT')

        <div class="modal-grid">
            <div class="form-group">
                <label>Full Name *</label>
                <input name="name"
                       value="{{ old('name',$user->name) }}"
                       required
                       placeholder="Enter your full name">
                <small class="form-hint">This name is displayed across ElevateHer360.</small>
            </div>

            <div class="form-group">
                <label>Email Address *</label>
                <input type="email"
                       name="email"
                       value="{{ old('email',$user->email) }}"
                       required
                       placeholder="name@example.com">
                <small class="form-hint">Used for sign-in and system notifications.</small>
            </div>
        </div>

        <div class="eh-form-actions">
            <button class="btn btn-primary">
                <i class="fas fa-floppy-disk"></i>
                Save Profile
            </button>
        </div>
    </form>
</section>

<section class="admin-panel">
    <div class="admin-panel-head">
        <div>
            <h2>Change Password</h2>
            <p>Confirm your current password before setting a new one.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.profile.password') }}">
        @csrf
        @method('PUT')

        <div class="modal-grid">
            <div class="form-group full">
                <label>Current Password *</label>
                <input type="password"
                       name="current_password"
                       required
                       autocomplete="current-password"
                       placeholder="Enter current password">
            </div>

            <div class="form-group">
                <label>New Password *</label>
                <input type="password"
                       name="password"
                       required
                       minlength="8"
                       autocomplete="new-password"
                       placeholder="Minimum 8 characters">
            </div>

            <div class="form-group">
                <label>Confirm New Password *</label>
                <input type="password"
                       name="password_confirmation"
                       required
                       minlength="8"
                       autocomplete="new-password"
                       placeholder="Repeat new password">
            </div>
        </div>

        <div class="eh-form-actions">
            <button class="btn btn-primary">
                <i class="fas fa-key"></i>
                Change Password
            </button>
        </div>
    </form>
</section>
</div>
@endsection
