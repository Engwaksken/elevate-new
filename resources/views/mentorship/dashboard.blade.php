@extends(auth()->check() && method_exists(auth()->user(), 'isStaff') && auth()->user()->isStaff() ? 'layouts.admin' : 'layouts.app')
@section('title','Mentorship - ElevateHer360')
@section('content')
<div class="mentorship-dashboard-page">
<style>
.mentorship-dashboard-page form,
.mentorship-dashboard-page .eh-tab-section form{
    background:#ffffff;
    border:1px solid #eadede;
    border-radius:12px;
    padding:16px;
}

.mentorship-dashboard-page input,
.mentorship-dashboard-page textarea,
.mentorship-dashboard-page select{
    width:100%;
    min-height:44px;
    padding:10px 12px;
    border:1px solid #d0d5dd;
    border-radius:9px;
    background:#fffdf7 !important;
    color:#101828 !important;
    font:inherit;
}

.mentorship-dashboard-page textarea{
    min-height:110px;
}

.mentorship-dashboard-page input::placeholder,
.mentorship-dashboard-page textarea::placeholder{
    color:#98a2b3 !important;
    opacity:1 !important;
}

.mentorship-dashboard-page input:focus,
.mentorship-dashboard-page textarea:focus,
.mentorship-dashboard-page select:focus{
    border-color:#800000 !important;
    background:#ffffff !important;
    box-shadow:0 0 0 3px rgba(128,0,0,.08);
    outline:none;
}
</style>
<style>.eh-track-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin:0 0 22px}.eh-track-card{background:#fff;border:1px solid #eadede;border-left:4px solid #800000;border-radius:12px;padding:16px;display:flex;align-items:center;gap:12px}.eh-track-card i{width:38px;height:38px;border-radius:10px;display:grid;place-items:center;background:#fff7da;color:#800000}.eh-track-card small{display:block;color:#667085;font-weight:700}.eh-track-card strong{font-size:1.3rem;color:#101828}@media(max-width:900px){.eh-track-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:560px){.eh-track-grid{grid-template-columns:1fr}}</style>
<div class="page-header"><div><span class="eh-kicker">Mentorship</span><h1>My Mentorship</h1><p>Track mentors, sessions and your growth goals.</p></div><div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center">@if(auth()->user()?->canBeMentee() && Route::has('mentorship.mentors.index'))<a href="{{ route('mentorship.mentors.index') }}" class="btn btn-primary"><i class="fas fa-user-plus"></i> Find a Mentor</a>@endif
@if(auth()->user()?->hasAnyRole(['mentor','Mentor']) && Route::has('mentorship.mentor-profile.edit'))
<a href="{{ route('mentorship.mentor-profile.edit') }}" class="btn btn-outline"><i class="fas fa-user-pen"></i> Mentor Profile</a>
@elseif(auth()->user()?->isStaff() && Route::has('admin.profile.edit'))
<a href="{{ route('admin.profile.edit') }}" class="btn btn-outline"><i class="fas fa-user-pen"></i> My Profile</a>
@elseif(Route::has('profile.edit'))
<a href="{{ route('profile.edit') }}" class="btn btn-outline"><i class="fas fa-user-pen"></i> My Profile</a>
@endif</div></div>
<div class="eh-track-grid"><div class="eh-track-card"><i class="fas fa-user-tie"></i><div><small>Assigned Mentors</small><strong>{{ number_format($stats['mentors']??0) }}</strong></div></div><div class="eh-track-card"><i class="fas fa-calendar-days"></i><div><small>Total Sessions</small><strong>{{ number_format($stats['sessions']??0) }}</strong></div></div><div class="eh-track-card"><i class="fas fa-circle-check"></i><div><small>Completed Sessions</small><strong>{{ number_format($stats['completed_sessions']??0) }}</strong></div></div><div class="eh-track-card"><i class="fas fa-clock"></i><div><small>Upcoming Sessions</small><strong>{{ number_format($stats['upcoming_sessions']??0) }}</strong></div></div></div>

<div class="eh-tabs" data-eh-tabs>
    <div class="eh-tab-nav">
        <button class="eh-tab-button active" data-eh-tab="overview"><i class="fas fa-house"></i> Overview</button>
        <button class="eh-tab-button" data-eh-tab="sessions"><i class="fas fa-calendar-check"></i> Sessions</button>
        <button class="eh-tab-button" data-eh-tab="goals"><i class="fas fa-bullseye"></i> Goals</button>
        @if($aiEnabled ?? false)
            <button class="eh-tab-button" data-eh-tab="ai"><i class="fas fa-wand-magic-sparkles"></i> AI Career Mentor</button>
        @endif
    </div>

    <div class="eh-tab-content">

        <section class="eh-tab-pane active" data-eh-pane="overview">
            <div class="eh-tab-section">
                <div class="eh-data-list">
                    @forelse($menteeMatches as $match)
                        <div class="eh-data-row">
                            <div class="eh-data-row-main">
                                <span class="eh-data-row-icon"><i class="fas fa-user-tie"></i></span>
                                <div class="eh-data-row-copy"><strong>{{ $match->mentor?->name??'Assigned Mentor' }}</strong><span>Mentor relationship</span></div>
                            </div>
                            <span class="eh-status">{{ $match->status }}</span>
                        </div>
                    @empty
                        <div class="eh-empty"><p>No mentor assigned yet.</p></div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="eh-tab-pane" data-eh-pane="sessions">
            <div class="eh-tab-section">
                <div style="display:flex;justify-content:flex-end;margin-bottom:10px"><x-export-buttons :params="['list' => 'sessions']" /></div>
                <div class="eh-data-list">
                    @forelse($sessions as $session)
                        <div class="eh-data-row">
                            <div class="eh-data-row-main">
                                <span class="eh-data-row-icon"><i class="fas fa-calendar-days"></i></span>
                                <div class="eh-data-row-copy"><strong>{{ $session->title?:'Mentorship Session' }}</strong><span>{{ $session->scheduled_at?->format('d M Y H:i')??'Date not set' }}</span></div>
                            </div>
                            <span class="eh-status">{{ $session->status }}</span>
                            <details style="flex-basis:100%;margin-top:8px">
                                <summary style="cursor:pointer;color:#800000;font-weight:600;font-size:.85rem">Submit session report</summary>
                                <form method="POST" action="{{ route('mentorship.sessions.reports.store',$session) }}" style="margin-top:10px">
                                    @csrf
                                    <div><label>Summary *</label><textarea name="summary" required maxlength="5000">{{ old('summary') }}</textarea></div>
                                    <div class="form-grid">
                                        <div><label>Challenges</label><textarea name="challenges" maxlength="5000">{{ old('challenges') }}</textarea></div>
                                        <div><label>Achievements</label><textarea name="achievements" maxlength="5000">{{ old('achievements') }}</textarea></div>
                                    </div>
                                    <label class="modal-check"><input type="checkbox" name="mentor_attended" value="1"><span>Mentor attended</span></label>
                                    <label class="modal-check"><input type="checkbox" name="mentee_attended" value="1"><span>Mentee attended</span></label>
                                    <button class="btn btn-primary btn-sm">Submit report</button>
                                </form>
                            </details>
                        </div>
                    @empty
                        <div class="eh-empty"><p>No mentorship sessions yet.</p></div>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="eh-tab-pane" data-eh-pane="goals">
            <div class="eh-tab-section">
                <style>
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
                .eh-goal-bar>span{display:block;height:100%;background:linear-gradient(90deg,#800000,#b03a3a)}
                .eh-goal-add{margin-top:18px;background:#fff;border:1px dashed #d9c6c6;border-radius:14px;padding:18px}
                .eh-goal-add summary{cursor:pointer;font-weight:700;color:#800000}
                .eh-goal-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
                .eh-goal-form-grid .full{grid-column:1/-1}
                .eh-goal-form-grid label{display:block;font-weight:600;color:#344054;margin-bottom:5px;font-size:.82rem}
                @media(max-width:900px){.eh-goal-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.eh-goal-list{grid-template-columns:1fr}}
                @media(max-width:560px){.eh-goal-grid{grid-template-columns:1fr}.eh-goal-form-grid{grid-template-columns:1fr}}
                </style>

                <div class="eh-goal-grid">
                    <div class="eh-goal-stat"><i class="fas fa-bullseye"></i><div><small>Total Goals</small><strong>{{ number_format($myGoalStats['total'] ?? 0) }}</strong></div></div>
                    <div class="eh-goal-stat"><i class="fas fa-person-running"></i><div><small>In Progress</small><strong>{{ number_format($myGoalStats['in_progress'] ?? 0) }}</strong></div></div>
                    <div class="eh-goal-stat"><i class="fas fa-trophy"></i><div><small>Completed</small><strong>{{ number_format($myGoalStats['completed'] ?? 0) }}</strong></div></div>
                    <div class="eh-goal-stat"><i class="fas fa-chart-line"></i><div><small>Average Progress</small><strong>{{ number_format((float)($myGoalStats['average'] ?? 0),1) }}%</strong></div></div>
                </div>

                <details class="eh-goal-add" style="margin-top:0;margin-bottom:18px" @if($errors->has('title')) open @endif>
                    <summary><i class="fas fa-plus"></i> Add a new goal</summary>
                    <form method="POST" action="{{ route('profile.goals.store') }}" style="margin-top:14px">
                        @csrf
                        <div class="eh-goal-form-grid">
                            <div class="full"><label>Goal title *</label><input name="title" value="{{ old('title') }}" required maxlength="190" placeholder="e.g. Get a data analyst internship"></div>
                            <div class="full"><label>Description</label><textarea name="description" rows="3" placeholder="What does success look like?">{{ old('description') }}</textarea></div>
                            <div><label>Category</label><select name="category">@foreach(['career'=>'Career','learning'=>'Learning','personal'=>'Personal','mentorship'=>'Mentorship','other'=>'Other'] as $v=>$l)<option value="{{ $v }}" @selected(old('category','career')===$v)>{{ $l }}</option>@endforeach</select></div>
                            <div><label>Priority</label><select name="priority">@foreach(['low'=>'Low','medium'=>'Medium','high'=>'High'] as $v=>$l)<option value="{{ $v }}" @selected(old('priority','medium')===$v)>{{ $l }}</option>@endforeach</select></div>
                            <div><label>Baseline value</label><input type="number" step="any" name="baseline_value" value="{{ old('baseline_value') }}"></div>
                            <div><label>Target value</label><input type="number" step="any" min="0" name="target_value" value="{{ old('target_value') }}"></div>
                            <div><label>Current value</label><input type="number" step="any" min="0" name="current_value" value="{{ old('current_value') }}"></div>
                            <div><label>Unit</label><input name="unit" value="{{ old('unit') }}" maxlength="40" placeholder="e.g. sessions, applications"></div>
                            <div><label>Start date</label><input type="date" name="start_date" value="{{ old('start_date') }}"></div>
                            <div><label>Target date</label><input type="date" name="target_date" value="{{ old('target_date') }}"></div>
                        </div>
                        <div style="margin-top:14px;text-align:right"><button class="btn btn-primary" type="submit"><i class="fas fa-plus"></i> Add goal</button></div>
                    </form>
                </details>

                @if(($myGoals ?? collect())->isNotEmpty())<div style="display:flex;justify-content:flex-end;margin:10px 0"><x-export-buttons :params="['list' => 'goals']" /></div>@endif
                @if(($myGoals ?? collect())->isEmpty())
                    <div style="background:#faf7f2;border-radius:12px;padding:20px;text-align:center;color:#667085">
                        <p style="margin:0">You have not set any goals yet. Add your first goal below.</p>
                    </div>
                @else
                    <div class="eh-goal-list">
                        @foreach($myGoals as $goal)
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
                                <div class="eh-goal-bar"><span style="width:{{ max(0,min(100,(float)$goal->progress_percent)) }}%"></span></div>
                                <div style="font-size:.85rem;color:#667085"><strong style="color:#101828">{{ number_format((float)$goal->progress_percent,0) }}%</strong> complete</div>
                                @if($goal->mentor_comment)
                                    <div style="background:#eef7ee;border:1px solid #cfe8cf;border-radius:10px;padding:10px 12px;font-size:.87rem;color:#0b3d1f">
                                        <strong><i class="fas fa-comment-dots"></i> Mentor feedback:</strong> {{ $goal->mentor_comment }}
                                    </div>
                                @endif
                                <details style="background:#faf7f2;border-radius:10px;padding:10px 12px">
                                    <summary style="cursor:pointer;font-weight:600;color:#800000">Update progress</summary>
                                    <form method="POST" action="{{ route('profile.goals.progress',$goal) }}" style="margin-top:10px;display:grid;gap:10px">
                                        @csrf @method('PUT')
                                        <label>Current value<input type="number" step="any" min="0" name="current_value" value="{{ $goal->current_value }}"></label>
                                        <label>Progress %<input type="number" min="0" max="100" name="progress_percent" value="{{ number_format((float)$goal->progress_percent,0) }}"></label>
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
            </div>
        </section>

        @if($aiEnabled ?? false)
            <section class="eh-tab-pane" data-eh-pane="ai">
                <div class="eh-tab-section eh-ai-mentor">
                    <style>
                    .eh-ai-mentor h2{margin:0 0 4px;display:flex;align-items:center;gap:10px}
                    .eh-ai-mentor h2 i{color:#800000}
                    .eh-ai-mentor .eh-ai-sub{color:#667085;margin:0 0 14px}
                    .eh-ai-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
                    .eh-ai-chips button{border:1px solid #e0cfcf;background:#fffaf3;color:#800000;border-radius:20px;padding:6px 12px;cursor:pointer;font-size:.82rem}
                    .eh-ai-log{display:flex;flex-direction:column;gap:12px;max-height:420px;overflow:auto;padding:6px;margin-bottom:14px}
                    .eh-ai-msg{padding:12px 14px;border-radius:14px;max-width:85%;line-height:1.5;white-space:pre-wrap}
                    .eh-ai-msg.user{align-self:flex-end;background:#800000;color:#fff;border-bottom-right-radius:4px}
                    .eh-ai-msg.assistant{align-self:flex-start;background:#f6f1ea;color:#101828;border-bottom-left-radius:4px}
                    .eh-ai-msg.thinking{align-self:flex-start;background:#f6f1ea;color:#667085;font-style:italic}
                    .eh-ai-form{display:flex;gap:10px;align-items:flex-end}
                    .eh-ai-form textarea{flex:1;min-height:52px;resize:vertical}
                    .eh-ai-error{color:#b42318;font-size:.85rem;margin-top:8px;min-height:18px}
                    </style>
                    <h2><i class="fas fa-wand-magic-sparkles"></i> AI Career Mentor</h2>
                    <p class="eh-ai-sub">Ask about your career path, interviews, CVs, skills or how to make the most of your mentorship. Your goals and sessions help personalise the guidance.</p>
                    <div class="eh-ai-chips" data-ai-chips>
                        <button type="button">How do I choose a career path?</button>
                        <button type="button">How should I prepare for an interview?</button>
                        <button type="button">What skills should I build next?</button>
                        <button type="button">How can I make the most of my mentor?</button>
                    </div>
                    <div class="eh-ai-log" data-ai-log aria-live="polite"></div>
                    <form class="eh-ai-form" data-ai-form data-endpoint="{{ route('mentorship.assistant.message') }}">
                        @csrf
                        <textarea name="message" data-ai-input maxlength="1500" placeholder="Type your career question..." aria-label="Your question"></textarea>
                        <button class="btn btn-primary" type="submit" data-ai-send><i class="fas fa-paper-plane"></i> Ask</button>
                    </form>
                    <div class="eh-ai-error" data-ai-error role="alert"></div>
                </div>
            </section>
        @endif

    </div>
</div>

@if($aiEnabled ?? false)
<script>
(function () {
    var form = document.querySelector('[data-ai-form]');
    if (!form) return;
    var section = form.closest('.eh-ai-mentor');
    var log = section.querySelector('[data-ai-log]');
    var input = form.querySelector('[data-ai-input]');
    var send = form.querySelector('[data-ai-send]');
    var error = section.querySelector('[data-ai-error]');
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
        }).then(function (response) {
            return response.json().then(function (data) { return { ok: response.ok, data: data }; });
        }).then(function (result) {
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

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        var message = input.value.trim();
        if (!message) return;
        input.value = '';
        ask(message);
    });

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });

    section.querySelectorAll('[data-ai-chips] button').forEach(function (chip) {
        chip.addEventListener('click', function () {
            ask(chip.textContent.trim());
        });
    });
})();
</script>
@endif

@isset($menteeGoals)
@if($menteeGoals->isNotEmpty())
<section id="mentee-goals" class="eh-mentee-goals" aria-labelledby="mentee-goals-heading" style="margin-top:26px">
<style>
.eh-mentee-goals{background:#fff;border:1px solid #eadede;border-radius:16px;padding:22px}
.eh-mentee-goals h2{margin:0 0 4px;display:flex;align-items:center;gap:10px}
.eh-mentee-goals h2 i{color:#800000}
.eh-mentee-goals .eh-mg-sub{color:#667085;margin:0 0 14px}
.eh-mg-group{margin-top:14px}
.eh-mg-group>h3{margin:0 0 8px;font-size:1rem;color:#101828}
.eh-mg-card{border:1px solid #eadede;border-radius:12px;padding:14px;margin-bottom:12px}
.eh-mg-bar{height:8px;background:#eee;border-radius:8px;overflow:hidden;margin:8px 0}
.eh-mg-bar>span{display:block;height:100%;background:linear-gradient(90deg,#800000,#b03a3a)}
.eh-mg-meta{display:flex;flex-wrap:wrap;gap:8px;font-size:.8rem;color:#667085}
.eh-mg-meta span{background:#f6f1ea;border-radius:20px;padding:3px 10px}
.eh-mg-review{margin-top:10px;background:#faf7f2;border-radius:10px;padding:10px 12px}
.eh-mg-review textarea{width:100%;min-height:70px;border:1px solid #d0d5dd;border-radius:8px;padding:8px 10px;font:inherit}
.eh-mg-note{margin-top:8px;color:#475467;font-size:.85rem}
</style>
<h2 id="mentee-goals-heading"><i class="fas fa-flag"></i> Mentee goals</h2>
<p class="eh-mg-sub">Review your mentees' personal goals and leave encouraging, actionable feedback.</p>
<div style="margin-bottom:10px"><x-export-buttons :params="['list' => 'mentee-goals']" /></div>
@foreach($menteeGoals->groupBy('user_id') as $userId => $goals)
<div class="eh-mg-group">
    <h3>{{ $goals->first()->user?->name ?? 'Mentee' }} <span style="color:#98a2b3;font-weight:400">({{ $goals->count() }} goal{{ $goals->count() === 1 ? '' : 's' }})</span></h3>
    @foreach($goals as $goal)
    <div class="eh-mg-card">
        <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
            <strong>{{ $goal->title }}</strong>
            <span class="eh-status">{{ ucfirst(str_replace('_',' ',$goal->status)) }}</span>
        </div>
        @if($goal->description)<p style="margin:6px 0 0;color:#475467">{{ $goal->description }}</p>@endif
        <div class="eh-mg-bar"><span style="width:{{ max(0,min(100,(float)$goal->progress_percent)) }}%"></span></div>
        <div class="eh-mg-meta">
            <span>{{ ucfirst($goal->category) }}</span>
            @if($goal->target_value !== null)<span>{{ rtrim(rtrim(number_format((float)$goal->current_value,2),'0'),'.') }} / {{ rtrim(rtrim(number_format((float)$goal->target_value,2),'0'),'.') }} {{ $goal->unit }}</span>@endif
            @if($goal->target_date)<span>Due {{ $goal->target_date->format('d M Y') }}</span>@endif
            <span>{{ number_format((float)$goal->progress_percent,0) }}% complete</span>
        </div>
        @if($goal->mentor_comment)
            <div class="eh-mg-note"><strong>Your last feedback:</strong> {{ $goal->mentor_comment }}<br><small>{{ optional($goal->mentor_reviewed_at)->format('d M Y') }} · {{ $goal->mentorReviewer?->name }}</small></div>
        @endif
        <form method="POST" action="{{ route('mentorship.participant-goals.review',$goal) }}" class="eh-mg-review">
            @csrf
            <label for="mentor_comment_{{ $goal->id }}" style="font-weight:600;color:#344054;display:block;margin-bottom:6px">Feedback for {{ $goal->user?->name }}</label>
            <textarea id="mentor_comment_{{ $goal->id }}" name="mentor_comment" maxlength="2000" placeholder="Add guidance, next steps or encouragement...">{{ $goal->mentor_comment }}</textarea>
            <div style="margin-top:8px"><button class="btn btn-primary btn-sm"><i class="fas fa-comment-dots"></i> Save feedback</button></div>
        </form>
    </div>
    @endforeach
</div>
@endforeach
</section>
@endif
@endisset

<script>
document.addEventListener('DOMContentLoaded', function () {
    var name = (window.location.hash || '').replace('#', '');
    if (!name) return;
    var button = document.querySelector('[data-eh-tab="' + name + '"]');
    if (button) button.click();
});
</script>
</div>
@endsection
