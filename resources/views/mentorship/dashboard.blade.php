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
            <button class="eh-tab-button" data-eh-tab="action-plan"><i class="fas fa-list-check"></i> My action plan</button>
            <button class="eh-tab-button" data-eh-tab="saved-answers"><i class="fas fa-bookmark"></i> Saved answers</button>
        @endif
        @if($hasMentees ?? false)
            <button class="eh-tab-button" data-eh-tab="mentee-goals"><i class="fas fa-flag"></i> Mentee goals</button>
        @endif
    </div>

    <div class="eh-tab-content">

        <section class="eh-tab-pane active" data-eh-pane="overview">
            <div class="eh-tab-section">
                <div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
                    <thead><tr><th>Mentor</th><th>Relationship</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse($menteeMatches as $match)
                        <tr>
                            <td><strong>{{ $match->mentor?->name??'Assigned Mentor' }}</strong></td>
                            <td>Mentor relationship</td>
                            <td><span class="status-chip {{ $match->status }}">{{ ucfirst($match->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3"><div class="admin-empty">No mentor assigned yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table></div></div>
            </div>
        </section>

        <section class="eh-tab-pane" data-eh-pane="sessions">
            <div class="eh-tab-section">
                <div style="display:flex;justify-content:flex-end;margin-bottom:10px"><x-export-buttons :params="['list' => 'sessions']" /></div>
                <div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
                    <thead><tr><th>Session</th><th>Scheduled</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
                    <tbody>
                    @forelse($sessions as $session)
                        <tr>
                            <td><strong>{{ $session->title?:'Mentorship Session' }}</strong></td>
                            <td>{{ $session->scheduled_at?->format('d M Y H:i')??'Date not set' }}</td>
                            <td><span class="status-chip {{ $session->status }}">{{ ucfirst((string) $session->status) }}</span></td>
                            <td class="table-actions"><button type="button" class="btn btn-outline btn-sm" data-modal-open="session-report-{{ $session->id }}"><i class="fas fa-file-pen"></i> Submit report</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="admin-empty">No mentorship sessions yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table></div></div>

                @foreach($sessions as $session)
                <div class="eh-modal" id="session-report-{{ $session->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="session-report-{{ $session->id }}-title">
                <div class="eh-modal-dialog">
                    <form method="POST" action="{{ route('mentorship.sessions.reports.store',$session) }}">
                        @csrf
                        <div class="eh-modal-header">
                            <div><h2 id="session-report-{{ $session->id }}-title">Submit session report</h2><p>{{ $session->title?:'Mentorship Session' }} · {{ $session->scheduled_at?->format('d M Y H:i')??'Date not set' }}</p></div>
                            <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
                        </div>
                        <div class="eh-modal-body">
                            <div><label>Summary *</label><textarea name="summary" required maxlength="5000">{{ old('summary') }}</textarea></div>
                            <div class="form-grid">
                                <div><label>Challenges</label><textarea name="challenges" maxlength="5000">{{ old('challenges') }}</textarea></div>
                                <div><label>Achievements</label><textarea name="achievements" maxlength="5000">{{ old('achievements') }}</textarea></div>
                            </div>
                            <label class="modal-check"><input type="checkbox" name="mentor_attended" value="1"><span>Mentor attended</span></label>
                            <label class="modal-check"><input type="checkbox" name="mentee_attended" value="1"><span>Mentee attended</span></label>
                        </div>
                        <div class="eh-modal-footer">
                            <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
                            <button class="btn btn-primary">Submit report</button>
                        </div>
                    </form>
                </div>
                </div>
                @endforeach
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
                    .eh-ai-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap}
                    .eh-ai-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
                    .eh-ai-chips button{border:1px solid #e0cfcf;background:#fffaf3;color:#800000;border-radius:20px;padding:6px 12px;cursor:pointer;font-size:.82rem}
                    .eh-ai-log{display:flex;flex-direction:column;gap:12px;max-height:520px;overflow:auto;padding:6px;margin-bottom:14px}
                    .eh-ai-msg{padding:12px 14px;border-radius:14px;max-width:85%;line-height:1.5;white-space:pre-wrap}
                    .eh-ai-msg.user{align-self:flex-end;background:#800000;color:#fff;border-bottom-right-radius:4px}
                    .eh-ai-msg.assistant{align-self:flex-start;background:#f6f1ea;color:#101828;border-bottom-left-radius:4px}
                    .eh-ai-msg.thinking{align-self:flex-start;background:#f6f1ea;color:#667085;font-style:italic}
                    .eh-ai-tools{align-self:flex-start;display:flex;gap:6px;margin-top:-6px}
                    .eh-ai-tools button{border:0;background:none;color:#800000;font-size:.8rem;font-weight:600;cursor:pointer;padding:2px 4px}
                    .eh-ai-steps{align-self:flex-start;width:85%;border:1px solid #eadede;border-radius:12px;padding:10px 12px;background:#fff}
                    .eh-ai-steps > strong{display:block;font-size:.85rem;margin-bottom:4px;color:#344054}
                    .eh-ai-step{display:grid;grid-template-columns:minmax(0,1fr) 150px auto;gap:8px;align-items:center;padding:8px 0;border-top:1px solid #f2e6e6}
                    .eh-ai-step:first-of-type{border-top:0}
                    .eh-ai-step input{margin:0}
                    .eh-ai-step-buttons{display:flex;gap:6px}
                    .eh-ai-step-state{font-size:.85rem;color:#475467}
                    .eh-ai-step-state i{color:#067647;margin-right:4px}
                    .eh-ai-form{display:flex;gap:10px;align-items:flex-end}
                    .eh-ai-form textarea{flex:1;min-height:52px;resize:vertical}
                    .eh-ai-error{color:#b42318;font-size:.85rem;margin-top:8px;min-height:18px}
                    @media(max-width:640px){.eh-ai-step{grid-template-columns:1fr}.eh-ai-steps,.eh-ai-msg{width:100%;max-width:100%}}
                    </style>
                    <div class="eh-ai-head">
                        <div>
                            <h2><i class="fas fa-wand-magic-sparkles"></i> AI Career Mentor</h2>
                            <p class="eh-ai-sub">Ask about your career path, interviews, CVs, skills or how to make the most of your mentorship. Your goals and sessions help personalise the guidance. Approve a suggested step to add it to your calendar.</p>
                        </div>
                        <button type="button" class="btn btn-outline btn-sm" data-ai-clear><i class="fas fa-broom"></i> Clear chat</button>
                    </div>
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

            <section class="eh-tab-pane" data-eh-pane="action-plan">
                <div class="eh-tab-section">
                    <div class="eh-tab-section-header">
                        <div><h2>My action plan</h2><p>Steps you approved from the mentor's answers. Each one is also on your calendar.</p></div>
                        @if(Route::has('calendar.index'))<a class="btn btn-outline btn-sm" href="{{ route('calendar.index', ['event_type' => 'career_action']) }}"><i class="fas fa-calendar-days"></i> View in calendar</a>@endif
                    </div>
                    <div class="admin-panel">
                    @include('mentorship.partials.list-filters', ['prefix' => 'plan_', 'tab' => 'action-plan', 'placeholder' => 'Search steps or goals...', 'periodLabel' => 'Due', 'statuses' => ['' => 'All statuses', 'approved' => 'Planned', 'completed' => 'Done']])
                    @if($actionPlan->total() > 0)<p class="admin-cell-hint" style="margin:0 0 10px">Showing {{ $actionPlan->firstItem() }}–{{ $actionPlan->lastItem() }} of {{ number_format($actionPlan->total()) }} steps</p>@endif
                    <div class="admin-table-wrap"><table class="admin-table">
                        <thead><tr><th>Step</th><th>Goal</th><th>Due</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
                        <tbody data-action-plan>
                        @forelse($actionPlan as $item)
                            @php($isDone = $item->status === \App\Models\CareerActionItem::COMPLETED)
                            <tr>
                                <td><strong>{{ $item->title }}</strong></td>
                                <td>{{ $item->goal?->title ?? '-' }}</td>
                                <td>{{ $item->due_on->format('d M Y') }}@if(! $isDone && $item->due_on->isPast() && ! $item->due_on->isToday())<small class="admin-cell-hint" style="color:#b42318">Overdue</small>@endif</td>
                                <td><span class="status-chip {{ $isDone ? 'completed' : 'active' }}" data-plan-status>{{ $isDone ? 'Done' : 'Planned' }}</span></td>
                                <td class="table-actions"><button type="button" class="btn btn-outline btn-sm" data-plan-toggle="{{ route('mentorship.assistant.actions.complete', $item) }}">{{ $isDone ? 'Reopen' : 'Mark done' }}</button></td>
                            </tr>
                        @empty
                            <tr data-plan-empty><td colspan="5"><div class="admin-empty">{{ request()->hasAny(['plan_q', 'plan_period', 'plan_status']) ? 'No steps match these filters.' : 'No approved steps yet. Approve a suggested step from the mentor\'s answers to add it here and to your calendar.' }}</div></td></tr>
                        @endforelse
                        </tbody>
                    </table></div>
                    <div class="admin-pagination">{{ $actionPlan->links() }}</div>
                    </div>
                </div>
            </section>

            <section class="eh-tab-pane" data-eh-pane="saved-answers">
                <div class="eh-tab-section">
                    <div class="eh-tab-section-header"><div><h2>Saved answers</h2><p>Answers you saved stay here even when you clear the chat.</p></div></div>
                    <div class="admin-panel">
                    @include('mentorship.partials.list-filters', ['prefix' => 'saved_', 'tab' => 'saved-answers', 'placeholder' => 'Search saved answers...', 'periodLabel' => 'Saved'])
                    @if($aiSaved->total() > 0)<p class="admin-cell-hint" style="margin:0 0 10px">Showing {{ $aiSaved->firstItem() }}–{{ $aiSaved->lastItem() }} of {{ number_format($aiSaved->total()) }} answers</p>@endif
                    <div class="admin-table-wrap"><table class="admin-table">
                        <thead><tr><th>Answer</th><th>Saved</th><th class="table-actions">Actions</th></tr></thead>
                        <tbody>
                        @forelse($aiSaved as $saved)
                            <tr>
                                <td><details><summary style="cursor:pointer">{{ \Illuminate\Support\Str::limit($saved->content, 140) }}</summary><div style="white-space:pre-wrap;margin-top:8px">{{ $saved->content }}</div></details></td>
                                <td>{{ $saved->saved_at->format('d M Y') }}</td>
                                <td class="table-actions"><button type="button" class="btn btn-outline btn-sm" data-saved-remove="{{ route('mentorship.assistant.save', $saved) }}"><i class="fas fa-bookmark"></i> Remove</button></td>
                            </tr>
                        @empty
                            <tr><td colspan="3"><div class="admin-empty">{{ request()->hasAny(['saved_q', 'saved_period']) ? 'No saved answers match these filters.' : 'No saved answers yet. Use "Save" under any answer to keep it.' }}</div></td></tr>
                        @endforelse
                        </tbody>
                    </table></div>
                    <div class="admin-pagination">{{ $aiSaved->links() }}</div>
                    </div>
                </div>
            </section>
        @endif

        @if($hasMentees ?? false)
            <section class="eh-tab-pane" data-eh-pane="mentee-goals">
                @include('mentorship.partials.mentee-goals')
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
    var stored = @json($aiHistory);
    var urls = {
        save: @json(route('mentorship.assistant.save', ['message' => '__ID__'])),
        clear: @json(route('mentorship.assistant.clear')),
        approve: @json(route('mentorship.assistant.actions.approve', ['action' => '__ID__'])),
        dismiss: @json(route('mentorship.assistant.actions.dismiss', ['action' => '__ID__'])),
        complete: @json(route('mentorship.assistant.actions.complete', ['action' => '__ID__']))
    };
    var today = new Date().toISOString().slice(0, 10);
    var history = stored.map(function (m) { return { role: m.role, content: m.content }; });

    function url(name, id) { return urls[name].replace('__ID__', id); }

    function request(method, target, body) {
        return fetch(target, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token ? token.value : '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: body ? JSON.stringify(body) : null
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                if (!response.ok) throw new Error(data.message || 'Something went wrong. Please try again.');
                return data;
            });
        });
    }

    function el(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = text;
        return node;
    }

    function scrollDown() { log.scrollTop = log.scrollHeight; }

    function stepState(action) {
        var labels = { approved: 'Added to your calendar for ', completed: 'Done, it was due ', dismissed: 'Dismissed' };
        var state = el('span', 'eh-ai-step-state');
        if (action.status === 'approved' || action.status === 'completed') {
            state.innerHTML = '<i class="fas fa-circle-check"></i>';
            state.appendChild(document.createTextNode(labels[action.status] + action.due_label));
        } else {
            state.textContent = labels[action.status] || action.status;
        }
        return state;
    }

    function renderStep(action) {
        var row = el('div', 'eh-ai-step');
        if (action.status !== 'suggested') {
            row.appendChild(el('span', '', action.title));
            row.appendChild(stepState(action));
            return row;
        }
        var title = el('input'); title.type = 'text'; title.value = action.title; title.maxLength = 190; title.setAttribute('aria-label', 'Step');
        var due = el('input'); due.type = 'date'; due.value = action.due_on; due.min = today; due.setAttribute('aria-label', 'Due date');
        var buttons = el('div', 'eh-ai-step-buttons');
        var approve = el('button', 'btn btn-primary btn-sm', 'Approve'); approve.type = 'button';
        var dismiss = el('button', 'btn btn-outline btn-sm', 'Dismiss'); dismiss.type = 'button';
        approve.addEventListener('click', function () {
            approve.disabled = dismiss.disabled = true;
            request('POST', url('approve', action.id), { title: title.value.trim(), due_on: due.value }).then(function (updated) {
                row.replaceWith(renderStep(updated));
                addPlanRow(updated);
            }).catch(function (err) { error.textContent = err.message; approve.disabled = dismiss.disabled = false; });
        });
        dismiss.addEventListener('click', function () {
            approve.disabled = dismiss.disabled = true;
            request('POST', url('dismiss', action.id)).then(function (updated) {
                row.replaceWith(renderStep(updated));
            }).catch(function (err) { error.textContent = err.message; approve.disabled = dismiss.disabled = false; });
        });
        buttons.appendChild(approve); buttons.appendChild(dismiss);
        row.appendChild(title); row.appendChild(due); row.appendChild(buttons);
        return row;
    }

    function renderMessage(m) {
        log.appendChild(el('div', 'eh-ai-msg ' + m.role, m.content));
        if (m.role !== 'assistant' || !m.id) return;

        var tools = el('div', 'eh-ai-tools');
        var save = el('button', '', m.saved ? 'Saved' : 'Save'); save.type = 'button';
        save.setAttribute('aria-pressed', m.saved ? 'true' : 'false');
        save.addEventListener('click', function () {
            request('PATCH', url('save', m.id)).then(function (data) {
                m.saved = data.saved;
                save.textContent = data.saved ? 'Saved' : 'Save';
                save.setAttribute('aria-pressed', data.saved ? 'true' : 'false');
            }).catch(function (err) { error.textContent = err.message; });
        });
        tools.appendChild(save);
        log.appendChild(tools);

        if (m.actions && m.actions.length) {
            var box = el('div', 'eh-ai-steps');
            box.appendChild(el('strong', '', 'Suggested steps'));
            m.actions.forEach(function (action) { box.appendChild(renderStep(action)); });
            log.appendChild(box);
        }
    }

    function addPlanRow(action) {
        var body = document.querySelector('[data-action-plan]');
        if (!body) return;
        var empty = body.querySelector('[data-plan-empty]');
        if (empty) empty.remove();
        var row = el('tr');
        var title = el('td'); title.appendChild(el('strong', '', action.title));
        var status = el('td'); status.appendChild(el('span', 'status-chip active', 'Planned'));
        status.firstChild.setAttribute('data-plan-status', '');
        var actions = el('td', 'table-actions');
        var toggle = el('button', 'btn btn-outline btn-sm', 'Mark done'); toggle.type = 'button';
        toggle.setAttribute('data-plan-toggle', url('complete', action.id));
        bindPlanToggle(toggle);
        actions.appendChild(toggle);
        [title, el('td', '', action.goal || '-'), el('td', '', action.due_label), status, actions].forEach(function (cell) { row.appendChild(cell); });
        body.appendChild(row);
    }

    function bindPlanToggle(button) {
        button.addEventListener('click', function () {
            button.disabled = true;
            request('POST', button.getAttribute('data-plan-toggle')).then(function (updated) {
                var done = updated.status === 'completed';
                var chip = button.closest('tr').querySelector('[data-plan-status]');
                chip.textContent = done ? 'Done' : 'Planned';
                chip.className = 'status-chip ' + (done ? 'completed' : 'active');
                button.textContent = done ? 'Reopen' : 'Mark done';
            }).catch(function (err) { window.alert(err.message); }).finally(function () { button.disabled = false; });
        });
    }

    function ask(message) {
        if (!message) return;
        error.textContent = '';
        log.appendChild(el('div', 'eh-ai-msg user', message));
        var thinking = el('div', 'eh-ai-msg thinking', 'Thinking...');
        log.appendChild(thinking);
        scrollDown();
        send.disabled = true;

        request('POST', form.dataset.endpoint, { message: message, history: history.slice(-8) }).then(function (data) {
            thinking.remove();
            renderMessage(data);
            history.push({ role: 'user', content: message });
            history.push({ role: 'assistant', content: data.content || data.message });
            scrollDown();
        }).catch(function (err) {
            thinking.remove();
            error.textContent = err.message || 'The assistant is unavailable right now. Please try again shortly.';
            input.value = input.value || message;
        }).finally(function () {
            send.disabled = false;
            input.focus();
        });
    }

    stored.forEach(renderMessage);
    scrollDown();

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
        chip.addEventListener('click', function () { ask(chip.textContent.trim()); });
    });

    section.querySelector('[data-ai-clear]').addEventListener('click', function () {
        if (!window.confirm('Clear this chat? Saved answers and approved steps are kept.')) return;
        request('DELETE', urls.clear).then(function () { window.location.hash = 'ai'; window.location.reload(); })
            .catch(function (err) { error.textContent = err.message; });
    });

    document.querySelectorAll('[data-plan-toggle]').forEach(bindPlanToggle);

    document.querySelectorAll('[data-saved-remove]').forEach(function (button) {
        button.addEventListener('click', function () {
            request('PATCH', button.getAttribute('data-saved-remove')).then(function () {
                button.closest('tr').remove();
            }).catch(function (err) { window.alert(err.message); });
        });
    });
})();
</script>
@endif


<script>
document.addEventListener('DOMContentLoaded', function () {
    // "Custom range" shows its from/to date boxes.
    document.querySelectorAll('[data-period-select]').forEach(function (select) {
        select.addEventListener('change', function () {
            select.form.querySelectorAll('[data-period-custom]').forEach(function (input) { input.hidden = select.value !== 'custom'; });
        });
    });

    var name = (window.location.hash || '').replace('#', '');
    if (!name) return;
    var button = document.querySelector('[data-eh-tab="' + name + '"]');
    if (button) button.click();
});
</script>
</div>
@endsection
