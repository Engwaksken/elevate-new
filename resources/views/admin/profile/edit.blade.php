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

<style>
.eh-profile-tabs{display:flex;gap:8px;margin-bottom:18px;padding:6px;width:fit-content;max-width:100%;background:#f5f5f6;border:1px solid #e7e7e9;border-radius:12px;overflow-x:auto}
.eh-profile-tab{appearance:none;border:0;border-radius:9px;background:transparent;color:#5d626a;padding:10px 16px;font:inherit;font-weight:700;white-space:nowrap;cursor:pointer}
.eh-profile-tab.active{background:#800000;color:#fff;box-shadow:0 2px 8px rgba(128,0,0,.16)}
[data-profile-panel][hidden]{display:none!important}
.eh-profile-password-wrap{position:relative}
.eh-profile-password-wrap input{width:100%;padding-right:46px}
.eh-profile-password-toggle{position:absolute;right:7px;top:50%;transform:translateY(-50%);width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:8px;background:transparent;color:#62666d;cursor:pointer}
.eh-profile-password-toggle:hover,.eh-profile-password-toggle:focus-visible{color:#800000;background:rgba(128,0,0,.07);outline:none}
</style>

<div class="eh-profile-tabs" role="tablist" aria-label="Profile sections">
    <button type="button" class="eh-profile-tab active" id="profileDetailsTab" role="tab" aria-selected="true" aria-controls="profileDetailsPanel" data-profile-tab="details">
        <i class="fas fa-user"></i> Profile Details
    </button>
    <button type="button" class="eh-profile-tab" id="profilePasswordTab" role="tab" aria-selected="false" aria-controls="profilePasswordPanel" data-profile-tab="password">
        <i class="fas fa-key"></i> Change Password
    </button>
</div>

<section class="admin-panel" id="profileDetailsPanel" role="tabpanel" aria-labelledby="profileDetailsTab" data-profile-panel="details">
    <div class="admin-panel-head">
        <div><h2>Profile Details</h2><p>Update the name and email attached to your account.</p></div>
    </div>

    <form method="POST" action="{{ route('admin.profile.update') }}" data-profile-form="details">
        @csrf
        @method('PUT')
        <div class="modal-grid">
            <div class="form-group">
                <label for="profileName">Full Name *</label>
                <input id="profileName" name="name" value="{{ old('name',$user->name) }}" required placeholder="Enter your full name">
            </div>
            <div class="form-group">
                <label for="profileEmail">Email Address *</label>
                <input id="profileEmail" type="email" name="email" value="{{ old('email',$user->email) }}" required placeholder="name@example.com">
            </div>
        </div>
        <div class="eh-form-actions">
            <button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save Profile</button>
        </div>
    </form>
</section>

<section class="admin-panel" id="profilePasswordPanel" role="tabpanel" aria-labelledby="profilePasswordTab" data-profile-panel="password" hidden>
    <div class="admin-panel-head">
        <div><h2>Change Password</h2><p>Confirm your current password before setting a new one.</p></div>
    </div>

    <form method="POST" action="{{ route('admin.profile.password') }}" data-profile-form="password">
        @csrf
        @method('PUT')
        <div class="modal-grid">
            <div class="form-group full">
                <label for="currentPassword">Current Password *</label>
                <div class="eh-profile-password-wrap">
                    <input id="currentPassword" type="password" name="current_password" required autocomplete="current-password" placeholder="Enter current password">
                    <button type="button" class="eh-profile-password-toggle" data-password-toggle="currentPassword" aria-label="Show current password" aria-pressed="false"><i class="fas fa-eye" aria-hidden="true"></i></button>
                </div>
            </div>
            <div class="form-group">
                <label for="newPassword">New Password *</label>
                <div class="eh-profile-password-wrap">
                    <input id="newPassword" type="password" name="password" required minlength="8" autocomplete="new-password" placeholder="Minimum 8 characters">
                    <button type="button" class="eh-profile-password-toggle" data-password-toggle="newPassword" aria-label="Show new password" aria-pressed="false"><i class="fas fa-eye" aria-hidden="true"></i></button>
                </div>
            </div>
            <div class="form-group">
                <label for="confirmPassword">Confirm New Password *</label>
                <div class="eh-profile-password-wrap">
                    <input id="confirmPassword" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" placeholder="Repeat new password">
                    <button type="button" class="eh-profile-password-toggle" data-password-toggle="confirmPassword" aria-label="Show confirmed password" aria-pressed="false"><i class="fas fa-eye" aria-hidden="true"></i></button>
                </div>
            </div>
        </div>
        <div class="eh-form-actions">
            <button class="btn btn-primary"><i class="fas fa-key"></i> Change Password</button>
        </div>
    </form>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const tabs=[...document.querySelectorAll('[data-profile-tab]')];
    const panels=[...document.querySelectorAll('[data-profile-panel]')];
    const activate=(name)=>{
        const active=name==='password'?'password':'details';
        tabs.forEach((tab)=>{
            const selected=tab.dataset.profileTab===active;
            tab.classList.toggle('active',selected);
            tab.setAttribute('aria-selected',selected?'true':'false');
        });
        panels.forEach((panel)=>panel.hidden=panel.dataset.profilePanel!==active);
    };
    activate(sessionStorage.getItem('ehAdminProfileTab'));
    tabs.forEach((tab)=>tab.addEventListener('click',()=>{
        sessionStorage.setItem('ehAdminProfileTab',tab.dataset.profileTab);
        activate(tab.dataset.profileTab);
    }));
    document.querySelectorAll('[data-profile-form]').forEach((form)=>form.addEventListener('submit',()=>{
        sessionStorage.setItem('ehAdminProfileTab',form.dataset.profileForm);
    }));
    document.querySelectorAll('[data-password-toggle]').forEach((button)=>button.addEventListener('click',()=>{
        const input=document.getElementById(button.dataset.passwordToggle);
        if(!input)return;
        const show=input.type==='password';
        input.type=show?'text':'password';
        const icon=button.querySelector('i');
        icon?.classList.toggle('fa-eye',!show);
        icon?.classList.toggle('fa-eye-slash',show);
        button.setAttribute('aria-pressed',show?'true':'false');
    }));
});
</script>
@endpush
