@extends('layouts.app')
@section('title','Profile - ElevateHer360')
@section('content')


<div class="page-header">
<div>
    <span class="eh-kicker">Account</span>
    <h1>My Profile</h1>
    <p>Keep your personal, location, career, preferences and security information up to date.</p>
</div>
@if(auth()->user()->participant_code)
<div class="participant-id-card" aria-label="Your participant ID">
    <small>Participant ID</small>
    <strong>{{ auth()->user()->participant_code }}</strong>
    <span>Quote this when you contact us or apply for another course.</span>
</div>
@endif
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="alert alert-error">
    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
</div>
@endif

<form method="POST" action="{{ route('profile.update') }}">
@csrf
@method('PUT')

@php
$profile = $user->profile;
$profileFormTabs = [
    'account' => ['label' => 'Account', 'icon' => 'fa-id-card', 'fields' => ['name', 'email', 'phone']],
    'personal' => ['label' => 'Personal', 'icon' => 'fa-user', 'fields' => ['surname', 'given_name', 'other_name', 'gender', 'date_of_birth', 'branch_id']],
    'location' => ['label' => 'Location', 'icon' => 'fa-location-dot', 'fields' => ['country', 'district', 'location', 'is_pwd']],
    'career' => ['label' => 'Career', 'icon' => 'fa-briefcase', 'fields' => ['education_level', 'employment_status', 'career_interests']],
    'preferences' => ['label' => 'Preferences', 'icon' => 'fa-language', 'fields' => ['preferred_language']],
    'security' => ['label' => 'Security', 'icon' => 'fa-shield-halved', 'fields' => ['current_password', 'password', 'password_confirmation']],
];
@endphp
<x-form-tabs id="profile" label="Profile sections" :tabs="$profileFormTabs">


<x-form-tab name="account">
<div class="eh-tab-section">
<div class="eh-form-grid">
    <div class="full"><label>Display Name *</label><input name="name" value="{{ old('name',$user->name) }}" required></div>
    <div><label>Email *</label><input type="email" name="email" value="{{ old('email',$user->email) }}" required></div>
    <div><label>Phone</label><input name="phone" value="{{ old('phone',$user->phone) }}"></div>
</div>
</div>
</x-form-tab>

<x-form-tab name="personal">
<div class="eh-tab-section">
<div class="eh-form-grid">
    <div><label>Surname</label><input name="surname" value="{{ old('surname',$profile?->surname) }}" placeholder="Enter your surname"></div>
    <div><label>Given Name</label><input name="given_name" value="{{ old('given_name',$profile?->given_name) }}" placeholder="Enter your given name"></div>
    <div><label>Other Name</label><input name="other_name" value="{{ old('other_name',$profile?->other_name) }}" placeholder="Enter other name if applicable"></div>
    <div><label>Gender</label><select name="gender"><option value="">Select your gender</option>@foreach(['female'=>'Female','male'=>'Male','other'=>'Other','prefer_not_to_say'=>'Prefer not to say'] as $v=>$l)<option value="{{ $v }}" @selected(old('gender',$profile?->gender)===$v)>{{ $l }}</option>@endforeach</select></div>
    <div><label>Date of Birth</label><input type="date" name="date_of_birth" value="{{ old('date_of_birth',optional($profile?->date_of_birth)->format('Y-m-d')) }}"></div>
    <div><label>Branch</label><select name="branch_id"><option value="">Select your branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string)old('branch_id',$profile?->branch_id)===(string)$branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
</div>
</div>
</x-form-tab>

<x-form-tab name="location">
<div class="eh-tab-section">
<div class="eh-form-grid">
    <div><label>Country</label><input name="country" value="{{ old('country',$profile?->country) }}" placeholder="e.g. Uganda"></div>
    <div><label>District</label><input name="district" value="{{ old('district',$profile?->district) }}" placeholder="e.g. Kampala"></div>
    <div class="full"><label>Location</label><input name="location" value="{{ old('location',$profile?->location) }}" placeholder="Town, city or community"></div>
    <div><label>Person with Disability</label><select name="is_pwd"><option value="0" @selected(!(bool)old('is_pwd',$profile?->is_pwd ?? false))>No</option><option value="1" @selected((bool)old('is_pwd',$profile?->is_pwd ?? false))>Yes</option></select></div>
</div>
</div>
</x-form-tab>

<x-form-tab name="career">
<div class="eh-tab-section">
<div class="eh-form-grid">
    <div><label>Education Level</label><input name="education_level" value="{{ old('education_level',$profile?->education_level) }}" placeholder="e.g. Diploma, Bachelor's degree"></div>
    <div><label>Employment Status</label><input name="employment_status" value="{{ old('employment_status',$profile?->employment_status) }}" placeholder="e.g. Student, Employed, Self-employed"></div>
    <div class="full"><label>Career Interests</label><textarea name="career_interests" rows="5" placeholder="Software development, data analysis, entrepreneurship...">{{ old('career_interests',$profile?->career_interests) }}</textarea></div>
</div>
</div>
</x-form-tab>

<x-form-tab name="preferences">
<div class="eh-tab-section">
<div class="eh-form-grid">
    <div><label>Preferred Language</label><select name="preferred_language"><option value="en" @selected(old('preferred_language',$profile?->preferred_language ?? 'en')==='en')>English</option><option value="lg" @selected(old('preferred_language',$profile?->preferred_language)==='lg')>Luganda</option><option value="sw" @selected(old('preferred_language',$profile?->preferred_language)==='sw')>Kiswahili</option></select></div>
</div>
</div>
</x-form-tab>

<x-form-tab name="security">
<div class="eh-tab-section">
<p class="eh-section-note">Leave the password fields blank if you do not want to change your password.</p>
<div class="eh-form-grid">
    <div class="full"><label>Current Password</label><div class="password-wrap"><input id="profile_current_password" type="password" name="current_password" autocomplete="current-password"><button type="button" class="password-toggle" data-password-toggle="profile_current_password" aria-label="Show or hide password"><i class="fas fa-eye"></i></button></div></div>
    <div><label>New Password</label><div class="password-wrap"><input id="profile_password" type="password" name="password" autocomplete="new-password"><button type="button" class="password-toggle" data-password-toggle="profile_password" aria-label="Show or hide password"><i class="fas fa-eye"></i></button></div><small class="form-hint">Minimum 8 characters, mixed case and a number.</small></div>
    <div><label>Confirm New Password</label><div class="password-wrap"><input id="profile_password_confirmation" type="password" name="password_confirmation" autocomplete="new-password"><button type="button" class="password-toggle" data-password-toggle="profile_password_confirmation" aria-label="Show or hide password"><i class="fas fa-eye"></i></button></div></div>
</div>
</div>
</x-form-tab>

</x-form-tabs>

<div class="eh-form-actions">
    <button class="btn btn-primary" type="submit"><i class="fas fa-floppy-disk"></i> Save Profile</button>
</div>
</form>
@endsection
