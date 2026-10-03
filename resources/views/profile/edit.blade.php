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

<section class="eh-profile-goals" aria-labelledby="my-goals-heading">
<style>
.eh-profile-goals{margin-top:32px}
.eh-goal-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:0 0 20px}
.eh-goal-stat{background:#fff;border:1px solid #eadede;border-left:4px solid #800000;border-radius:12px;padding:16px;display:flex;align-items:center;gap:12px}
.eh-goal-stat i{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;background:#fff7da;color:#800000}
.eh-goal-stat small{display:block;color:#667085;font-weight:700}
.eh-goal-stat strong{font-size:1.3rem;color:#101828}
.eh-goal-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.eh-goal-card{background:#fff;border:1px solid #eadede;border-radius:14px;padding:18px;display:flex;flex-direction:column;gap:10px}
.eh-goal-card h3{margin:0;font-size:1.05rem;color:#101828}
.eh-goal-meta{display:flex;flex-wrap:wrap;gap:8px;font-size:.8rem;color:#667085}
.eh-goal-meta span{background:#f6f1ea;border-radius:20px;padding:3px 10px}
.eh-goal-bar{height:9px;background:#eee;border-radius:9px;overflow:hidden}
.eh-goal-bar > span{display:block;height:100%;background:linear-gradient(90deg,#800000,#b03a3a)}
.eh-goal-actions{display:flex;gap:8px;flex-wrap:wrap}
.eh-goal-actions form{margin:0}
.eh-goal-add{margin-top:18px;background:#fff;border:1px dashed #d9c6c6;border-radius:14px;padding:18px}
.eh-goal-add summary{cursor:pointer;font-weight:700;color:#800000}
@media(max-width:900px){.eh-goal-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.eh-goal-list{grid-template-columns:1fr}}
@media(max-width:560px){.eh-goal-grid{grid-template-columns:1fr}}
</style>

<div class="page-header" style="margin-top:8px">
    <div>
        <span class="eh-kicker">Growth</span>
        <h2 id="my-goals-heading">My Goals</h2>
        <p>Set personal and career goals, then track your progress over time.</p>
    </div>
</div>

<div class="eh-goal-grid">
    <div class="eh-goal-stat"><i class="fas fa-bullseye"></i><div><small>Total Goals</small><strong>{{ number_format($goalStats['total'] ?? 0) }}</strong></div></div>
    <div class="eh-goal-stat"><i class="fas fa-person-running"></i><div><small>In Progress</small><strong>{{ number_format($goalStats['in_progress'] ?? 0) }}</strong></div></div>
    <div class="eh-goal-stat"><i class="fas fa-trophy"></i><div><small>Completed</small><strong>{{ number_format($goalStats['completed'] ?? 0) }}</strong></div></div>
    <div class="eh-goal-stat"><i class="fas fa-chart-line"></i><div><small>Average Progress</small><strong>{{ number_format((float)($goalStats['average'] ?? 0),1) }}%</strong></div></div>
</div>

@if(($goals ?? collect())->isEmpty())
    <div class="eh-empty" style="background:#fff;border:1px solid #eadede;border-radius:14px;padding:24px;text-align:center;color:#667085">
        <p>You have not set any goals yet. Add your first goal below to start tracking progress.</p>
    </div>
@else
    <div class="eh-goal-list">
        @foreach($goals as $goal)
        <article class="eh-goal-card">
            <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
                <h3>{{ $goal->title }}</h3>
                <span class="eh-status" style="white-space:nowrap">{{ ucfirst(str_replace('_',' ',$goal->status)) }}</span>
            </div>
            @if($goal->description)<p style="margin:0;color:#475467">{{ $goal->description }}</p>@endif
            <div class="eh-goal-meta">
                <span>{{ ucfirst($goal->category) }}</span>
                @if($goal->target_value !== null)<span>{{ rtrim(rtrim(number_format((float)$goal->current_value,2),'0'),'.') }} / {{ rtrim(rtrim(number_format((float)$goal->target_value,2),'0'),'.') }} {{ $goal->unit }}</span>@endif
                @if($goal->target_date)<span>Due {{ $goal->target_date->format('d M Y') }}</span>@endif
                <span>{{ ucfirst($goal->priority) }} priority</span>
            </div>
            <div class="eh-goal-bar" role="progressbar" aria-valuenow="{{ (int)$goal->progress_percent }}" aria-valuemin="0" aria-valuemax="100"><span style="width:{{ max(0,min(100,(float)$goal->progress_percent)) }}%"></span></div>
            <div style="font-size:.85rem;color:#667085"><strong style="color:#101828">{{ number_format((float)$goal->progress_percent,0) }}%</strong> complete</div>

            <details style="background:#faf7f2;border-radius:10px;padding:10px 12px">
                <summary style="cursor:pointer;font-weight:600;color:#800000">Update progress</summary>
                <form method="POST" action="{{ route('profile.goals.progress',$goal) }}" style="margin-top:10px;display:grid;gap:10px">
                    @csrf @method('PUT')
                    <label>Current value
                        <input type="number" step="any" min="0" name="current_value" value="{{ $goal->current_value }}">
                    </label>
                    <label>Progress %
                        <input type="number" min="0" max="100" name="progress_percent" value="{{ number_format((float)$goal->progress_percent,0) }}">
                    </label>
                    <label>Status
                        <select name="status">
                            @foreach(['not_started'=>'Not started','in_progress'=>'In progress','completed'=>'Completed','cancelled'=>'Cancelled'] as $v=>$l)
                                <option value="{{ $v }}" @selected($goal->status===$v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </label>
                    <button class="btn btn-primary btn-sm" type="submit"><i class="fas fa-floppy-disk"></i> Save progress</button>
                </form>
                <form method="POST" action="{{ route('profile.goals.destroy',$goal) }}" style="margin-top:8px" onsubmit="return confirm('Remove this goal?');">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline btn-sm" type="submit"><i class="fas fa-trash"></i> Remove goal</button>
                </form>
            </details>
        </article>
        @endforeach
    </div>
@endif

<details class="eh-goal-add" @if($errors->has('title')) open @endif>
    <summary><i class="fas fa-plus"></i> Add a new goal</summary>
    <form method="POST" action="{{ route('profile.goals.store') }}" style="margin-top:14px">
        @csrf
        <div class="eh-form-grid">
            <div class="full"><label>Goal title *</label><input name="title" value="{{ old('title') }}" required maxlength="190" placeholder="e.g. Get a data analyst internship"></div>
            <div class="full"><label>Description</label><textarea name="description" rows="3" placeholder="What does success look like?">{{ old('description') }}</textarea></div>
            <div><label>Category</label><select name="category">@foreach(['career'=>'Career','learning'=>'Learning','personal'=>'Personal','mentorship'=>'Mentorship','other'=>'Other'] as $v=>$l)<option value="{{ $v }}" @selected(old('category','career')===$v)>{{ $l }}</option>@endforeach</select></div>
            <div><label>Priority</label><select name="priority">@foreach(['low'=>'Low','medium'=>'Medium','high'=>'High'] as $v=>$l)<option value="{{ $v }}" @selected(old('priority','medium')===$v)>{{ $l }}</option>@endforeach</select></div>
            <div><label>Baseline value</label><input type="number" step="any" name="baseline_value" value="{{ old('baseline_value') }}" placeholder="Starting point"></div>
            <div><label>Target value</label><input type="number" step="any" min="0" name="target_value" value="{{ old('target_value') }}" placeholder="Goal"></div>
            <div><label>Current value</label><input type="number" step="any" min="0" name="current_value" value="{{ old('current_value') }}"></div>
            <div><label>Unit</label><input name="unit" value="{{ old('unit') }}" maxlength="40" placeholder="e.g. sessions, applications"></div>
            <div><label>Start date</label><input type="date" name="start_date" value="{{ old('start_date') }}"></div>
            <div><label>Target date</label><input type="date" name="target_date" value="{{ old('target_date') }}"></div>
        </div>
        <div class="eh-form-actions" style="margin-top:14px">
            <button class="btn btn-primary" type="submit"><i class="fas fa-plus"></i> Add goal</button>
        </div>
    </form>
</details>
</section>
@endsection
