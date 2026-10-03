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

<style>
.eh-profile-card{background:#fff;border:1px solid #eadede;border-radius:16px;padding:24px;box-shadow:0 1px 2px rgba(16,24,40,.04);margin-bottom:28px}
.eh-profile-card .eh-tabs{margin-bottom:0}
.eh-profile-card .eh-tab-content{padding-top:22px}
.eh-profile-card .eh-tab-section{padding:20px 0}
.eh-profile-card .eh-form-grid label{display:block;font-weight:600;color:#344054;margin-bottom:6px}
.eh-profile-card .eh-form-grid input,
.eh-profile-card .eh-form-grid select,
.eh-profile-card .eh-form-grid textarea{width:100%;background:#fff;border:1px solid #d0d5dd;border-radius:9px;padding:10px 12px;min-height:44px;color:#101828;font:inherit}
.eh-profile-card .eh-form-grid textarea{min-height:120px;resize:vertical}
.eh-profile-card .eh-form-grid input:focus,
.eh-profile-card .eh-form-grid select:focus,
.eh-profile-card .eh-form-grid textarea:focus{border-color:#800000;box-shadow:0 0 0 3px rgba(128,0,0,.08);outline:none;background:#fff}
.eh-profile-card .eh-form-actions{margin-top:20px;padding-top:18px;border-top:1px solid #f0e7e7;display:flex;justify-content:flex-end}
.eh-profile-card .password-wrap{position:relative}
.eh-profile-card .password-wrap input{padding-right:46px}
@media(max-width:575px){.eh-profile-card{padding:18px}}
</style>

<div class="eh-profile-card">
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
    'goals' => ['label' => 'My Goals', 'icon' => 'fa-flag', 'fields' => ['title', 'description', 'category', 'baseline_value', 'target_value', 'current_value', 'unit', 'start_date', 'target_date', 'priority', 'progress_percent', 'status']],
];
if ($aiEnabled ?? false) {
    $profileFormTabs['mentor'] = ['label' => 'AI Career Mentor', 'icon' => 'fa-wand-magic-sparkles', 'fields' => []];
}
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

<x-form-tab name="goals">
<div class="eh-tab-section">
    <p class="eh-section-note">Set and track your personal goals below.</p>
</div>
</x-form-tab>

@if($aiEnabled ?? false)
<x-form-tab name="mentor">
<div class="eh-tab-section">
    <p class="eh-section-note">Ask the AI career mentor below.</p>
</div>
</x-form-tab>
@endif

</x-form-tabs>

<div class="eh-form-actions" data-profile-form-actions>
    <button class="btn btn-primary" type="submit"><i class="fas fa-floppy-disk"></i> Save Profile</button>
</div>
</form>
</div>

<div id="profileGoalsPanel" hidden>

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

            @if($goal->mentor_comment)
            <div style="background:#eef7ee;border:1px solid #cfe8cf;border-radius:10px;padding:10px 12px;font-size:.87rem;color:#0b3d1f">
                <strong><i class="fas fa-comment-dots"></i> Mentor feedback:</strong> {{ $goal->mentor_comment }}
                @if($goal->mentor_reviewed_at)<div style="color:#4b7a4b;font-size:.78rem;margin-top:4px">{{ $goal->mentor_reviewed_at->format('d M Y') }}@if($goal->mentorReviewer) · {{ $goal->mentorReviewer->name }}@endif</div>@endif
            </div>
            @endif

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
</div>

@if($aiEnabled ?? false)
<div id="profileAiPanel" hidden>
<section class="eh-profile-ai" aria-labelledby="profile-ai-heading">
<style>
.eh-profile-ai{background:#fff;border:1px solid #eadede;border-radius:16px;padding:22px}
.eh-profile-ai h2{margin:0 0 4px;display:flex;align-items:center;gap:10px}
.eh-profile-ai h2 i{color:#800000}
.eh-profile-ai .eh-ai-sub{color:#667085;margin:0 0 14px}
.eh-ai-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
.eh-ai-chips button{border:1px solid #e0cfcf;background:#fffaf3;color:#800000;border-radius:20px;padding:6px 12px;cursor:pointer;font-size:.82rem}
.eh-ai-log{display:flex;flex-direction:column;gap:12px;max-height:420px;overflow:auto;padding:6px;margin-bottom:14px}
.eh-ai-msg{padding:12px 14px;border-radius:14px;max-width:85%;line-height:1.5;white-space:pre-wrap}
.eh-ai-msg.user{align-self:flex-end;background:#800000;color:#fff}
.eh-ai-msg.assistant{align-self:flex-start;background:#f6f1ea;color:#101828}
.eh-ai-msg.thinking{align-self:flex-start;background:#f6f1ea;color:#667085;font-style:italic}
.eh-ai-form{display:flex;gap:10px;align-items:flex-end}
.eh-ai-form textarea{flex:1;min-height:52px;resize:vertical}
.eh-ai-error{color:#b42318;font-size:.85rem;margin-top:8px;min-height:18px}
</style>
<h2 id="profile-ai-heading"><i class="fas fa-wand-magic-sparkles"></i> AI Career Mentor</h2>
<p class="eh-ai-sub">Ask about your career path, interviews, CVs, skills or how to use your mentorship. Your goals and sessions personalise the guidance.</p>
<div class="eh-ai-chips" data-ai-chips>
    <button type="button">How do I choose a career path?</button>
    <button type="button">How should I prepare for an interview?</button>
    <button type="button">What skills should I build next?</button>
</div>
<div class="eh-ai-log" data-ai-log aria-live="polite"></div>
<form class="eh-ai-form" data-ai-form data-endpoint="{{ route('mentorship.assistant.message') }}">
    @csrf
    <textarea name="message" data-ai-input maxlength="1500" placeholder="Type your career question..." aria-label="Your question"></textarea>
    <button class="btn btn-primary" type="submit" data-ai-send><i class="fas fa-paper-plane"></i> Ask</button>
</form>
<div class="eh-ai-error" data-ai-error role="alert"></div>
</section>
</div>
@endif

<script>
(function () {
    var tabsRoot = document.getElementById('profile');
    if (!tabsRoot) return;
    var content = tabsRoot.querySelector('.eh-tab-content');
    var actions = document.querySelector('[data-profile-form-actions]');
    var goalsPanel = document.getElementById('profileGoalsPanel');
    var aiPanel = document.getElementById('profileAiPanel');

    function sync(name) {
        var external = name === 'goals' ? goalsPanel : (name === 'mentor' ? aiPanel : null);
        if (content) content.hidden = !!external;
        if (goalsPanel) goalsPanel.hidden = external !== goalsPanel;
        if (aiPanel) aiPanel.hidden = external !== aiPanel;
        if (actions) actions.style.display = external ? 'none' : '';
    }

    tabsRoot.querySelectorAll('[data-form-tab]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            // Run after the component has toggled its panes.
            setTimeout(function () { sync(btn.dataset.formTab); }, 0);
        });
    });

    // Open the right panel for goal errors, deep links, or a restored tab.
    function initialSync() {
        var active = tabsRoot.querySelector('[data-form-tab].active');
        var name = (active && active.dataset.formTab) || tabsRoot.dataset.errorTab || '';
        if (!name) {
            var hash = window.location.hash || '';
            if (hash.indexOf('goals') !== -1) name = 'goals';
            else if (hash.indexOf('mentor') !== -1) name = 'mentor';
        }
        sync(name);
    }
    setTimeout(initialSync, 0);
    window.addEventListener('hashchange', initialSync);
})();
</script>

<script>
(function () {
    var form = document.querySelector('#profileAiPanel [data-ai-form]');
    if (!form) return;
    var log = form.closest('.eh-profile-ai').querySelector('[data-ai-log]');
    var input = form.querySelector('[data-ai-input]');
    var send = form.querySelector('[data-ai-send]');
    var error = form.closest('.eh-profile-ai').querySelector('[data-ai-error]');
    var token = form.querySelector('input[name="_token"]');
    var history = [];

    function add(role, text) {
        var el = document.createElement('div');
        el.className = 'eh-ai-msg ' + role;
        el.textContent = text;
        log.appendChild(el);
        log.scrollTop = log.scrollHeight;
        return el;
    }

    function ask(message) {
        if (!message) return;
        error.textContent = '';
        add('user', message);
        var thinking = add('thinking', 'Thinking...');
        send.disabled = true;
        fetch(form.dataset.endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token ? token.value : '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ message: message, history: history.slice(-8) })
        }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
        .then(function (result) {
            thinking.remove();
            var reply = result.data && result.data.message ? result.data.message : 'Sorry, I could not answer that right now.';
            add('assistant', reply);
            history.push({ role: 'user', content: message });
            history.push({ role: 'assistant', content: reply });
        }).catch(function () {
            thinking.remove();
            error.textContent = 'The assistant is unavailable right now. Please try again shortly.';
        }).finally(function () {
            send.disabled = false;
            input.focus();
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var message = input.value.trim();
        if (!message) return;
        input.value = '';
        ask(message);
    });

    form.querySelector('[data-ai-input]').addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
    });

    form.closest('.eh-profile-ai').querySelectorAll('[data-ai-chips] button').forEach(function (chip) {
        chip.addEventListener('click', function () { ask(chip.textContent.trim()); });
    });
})();
</script>
@endsection
