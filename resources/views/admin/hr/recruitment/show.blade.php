@extends('layouts.admin')
@section('title',$vacancy->title.' | Recruitment')
@section('content')
@php
    $canManage = auth()->user()->hasPermission('hr.manage');
    $tabs = ['applicants' => 'Applicants', 'interviews' => 'Interviews', 'questions' => 'Interview questions', 'embed' => 'Share & embed', 'details' => 'Job details'];
    $tab = array_key_exists($tab, $tabs) ? $tab : 'applicants';
    $recColours = ['strong' => '#067647', 'good' => '#2e7d32', 'possible' => '#b54708', 'weak' => '#b42318'];
    $recLabels = ['strong' => 'Strong match', 'good' => 'Good match', 'possible' => 'Possible', 'weak' => 'Weak match'];
    $shortlisted = $vacancy->applicants()->whereIn('status', ['shortlisted', 'interview'])->orderByDesc('ai_score')->get();
    $scriptSrc = route('careers.embed.script');
    $jobSnippet = '<div data-elevateher-jobs data-job="'.$vacancy->slug.'"></div>'."\n".'<script src="'.$scriptSrc.'" async></script>';
    $allSnippet = '<div data-elevateher-jobs></div>'."\n".'<script src="'.$scriptSrc.'" async></script>';
    $iframeSnippet = '<iframe src="'.route('careers.embed.show', $vacancy->slug).'" title="'.e($vacancy->title).'" style="width:100%;min-height:1400px;border:0" loading="lazy"></iframe>';
@endphp
<style>
.hr-score{display:flex;align-items:center;gap:8px;min-width:150px}
.hr-score-bar{flex:1;height:8px;border-radius:99px;background:#eef0f3;overflow:hidden}
.hr-score-bar span{display:block;height:100%;border-radius:99px}
.hr-rec{display:inline-block;font-size:.7rem;font-weight:800;padding:2px 8px;border-radius:99px;color:#fff}
.hr-code{width:100%;font-family:ui-monospace,Consolas,monospace;font-size:.8rem;min-height:70px;background:#f8f9fb}
.hr-chips{display:flex;flex-wrap:wrap;gap:6px;margin:0 0 12px}
.hr-chips a{padding:4px 10px;border-radius:99px;border:1px solid var(--ad-border,#e3e5ea);text-decoration:none;color:inherit;font-size:.8rem}
.hr-chips a.active{background:var(--ad-primary,#800000);color:#fff;border-color:transparent}
.hr-toolbar-forms{display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;margin-bottom:14px}
.hr-toolbar-forms form{display:flex;flex-wrap:wrap;gap:8px;align-items:flex-end;padding:10px;border:1px solid var(--ad-border,#e3e5ea);border-radius:10px}
.hr-toolbar-forms label{font-size:.75rem;font-weight:700;display:block}
</style>

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow"><a href="{{ route('admin.hr.recruitment.index') }}">Recruitment</a></span>
        <h1>{{ $vacancy->title }}</h1>
        <p>{{ collect([$vacancy->reference, $vacancy->department?->name, $vacancy->location, $vacancy->employment_type, $vacancy->closing_date ? 'Closes '.$vacancy->closing_date->format('d M Y') : null])->filter()->join(' · ') }}
            <span class="status-chip {{ $vacancy->status }}">{{ ucfirst($vacancy->status) }}</span></p>
    </div>
    <div class="admin-page-actions">
        @if($vacancy->status !== 'draft')<a href="{{ $vacancy->publicUrl() }}" target="_blank" rel="noopener" class="btn btn-outline"><i class="fas fa-arrow-up-right-from-square" aria-hidden="true"></i> Public page</a>@endif
        @if($canManage)<button type="button" class="btn btn-primary" data-modal-open="scheduleModal"><i class="fas fa-calendar-plus" aria-hidden="true"></i> Schedule interviews</button>@endif
    </div>
</div>
@include('admin.hr.partials.nav')

@if($vacancy->status === 'draft')
<div class="alert alert-warning" role="status">This vacancy is a draft and is not visible to job seekers. Set its status to <strong>Open</strong> under Job details to publish it.</div>
@endif

<nav class="admin-tab-nav" aria-label="Vacancy sections" style="margin-bottom:16px">
@foreach($tabs as $key => $label)
    <a href="{{ route('admin.hr.recruitment.show', [$vacancy, 'tab' => $key]) }}" class="admin-tab-button {{ $tab === $key ? 'active' : '' }}" @if($tab === $key) aria-current="page" @endif style="text-decoration:none;display:inline-flex;align-items:center">
        {{ $label }}
        @if($key === 'applicants')<span class="admin-cell-hint" style="display:inline;margin-left:4px">({{ $counts->sum() }})</span>@endif
        @if($key === 'questions')<span class="admin-cell-hint" style="display:inline;margin-left:4px">({{ $vacancy->questions->count() }})</span>@endif
    </a>
@endforeach
</nav>

{{-- ============ APPLICANTS ============ --}}
@if($tab === 'applicants')
<div class="admin-panel">
    @if($canManage)
    <div class="hr-toolbar-forms">
        <form method="POST" action="{{ route('admin.hr.recruitment.screen', $vacancy) }}">@csrf
            <div>
                <strong style="display:block;margin-bottom:4px"><i class="fas fa-robot" aria-hidden="true"></i> AI screening</strong>
                <small class="admin-cell-hint">{{ $aiReady ? 'Uses the active AI provider (Settings > AI).' : 'No AI provider is active - rule-based scoring will be used.' }}</small>
            </div>
            <label style="display:flex;gap:6px;align-items:center;font-weight:500"><input type="checkbox" name="rescreen" value="1" style="width:auto"> Re-screen everyone</label>
            <button class="btn btn-primary btn-sm">Run screening</button>
        </form>
        <form method="POST" action="{{ route('admin.hr.recruitment.shortlist-top', $vacancy) }}">@csrf
            <div><label for="top-n">Shortlist top</label><input id="top-n" type="number" name="top" min="1" max="100" value="{{ max(3, $vacancy->positions * 5) }}" style="width:80px"></div>
            <div><label for="min-score">Min. score</label><input id="min-score" type="number" name="min_score" min="0" max="100" placeholder="any" style="width:80px"></div>
            <button class="btn btn-outline btn-sm"><i class="fas fa-ranking-star" aria-hidden="true"></i> Shortlist</button>
        </form>
    </div>
    @endif

    <div class="hr-chips" role="navigation" aria-label="Filter by status">
        <a href="{{ route('admin.hr.recruitment.show', [$vacancy, 'tab' => 'applicants']) }}" class="{{ request('status') ? '' : 'active' }}">All ({{ $counts->sum() }})</a>
        @foreach(\App\Models\RecruitmentApplicant::STATUSES as $s)
            @if(($counts[$s] ?? 0) > 0)
                <a href="{{ route('admin.hr.recruitment.show', [$vacancy, 'tab' => 'applicants', 'status' => $s]) }}" class="{{ request('status') === $s ? 'active' : '' }}">{{ ucfirst($s) }} ({{ $counts[$s] }})</a>
            @endif
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.hr.recruitment.bulk', $vacancy) }}" id="bulkApplicants">@csrf
    @if($canManage)
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:8px">
        <label for="bulk-action" class="admin-cell-hint" style="display:inline">With selected:</label>
        <select id="bulk-action" name="action" style="width:auto"><option value="shortlist">Shortlist</option><option value="reject">Reject</option><option value="screen">Screen with AI</option><option value="reset">Move back to screened</option></select>
        <label style="display:flex;gap:6px;align-items:center;font-weight:500"><input type="checkbox" name="notify" value="1" style="width:auto"> Email rejected candidates</label>
        <button class="btn btn-outline btn-sm">Apply</button>
    </div>
    @endif
    <div class="admin-table-wrap"><table class="admin-table">
        <thead><tr>
            @if($canManage)<th style="width:34px"><input type="checkbox" aria-label="Select all" onclick="document.querySelectorAll('#bulkApplicants [name=&quot;ids[]&quot;]').forEach(c=>c.checked=this.checked)"></th>@endif
            <th>#</th><th>Applicant</th><th>AI score</th><th>Education / experience</th><th>Status</th><th>Applied</th><th class="table-actions">Actions</th>
        </tr></thead>
        <tbody>
        @forelse($applicants as $i => $applicant)
            @php $rec = $applicant->ai_recommendation; @endphp
            <tr>
                @if($canManage)<td><input type="checkbox" name="ids[]" value="{{ $applicant->id }}" aria-label="Select {{ $applicant->name }}"></td>@endif
                <td>{{ $applicant->ai_score !== null ? $i + 1 : '-' }}</td>
                <td><a href="{{ route('admin.hr.recruitment.applicant', $applicant) }}"><strong>{{ $applicant->name }}</strong></a><small class="admin-cell-hint">{{ $applicant->email }} · {{ $applicant->reference }}</small></td>
                <td>
                    @if($applicant->ai_score !== null)
                        <div class="hr-score"><strong>{{ number_format((float) $applicant->ai_score, 0) }}</strong><div class="hr-score-bar" aria-hidden="true"><span style="width:{{ (float) $applicant->ai_score }}%;background:{{ $recColours[$rec] ?? '#667085' }}"></span></div></div>
                        <span class="hr-rec" style="background:{{ $recColours[$rec] ?? '#667085' }}">{{ $recLabels[$rec] ?? ucfirst((string) $rec) }}</span>
                        @if($applicant->ai_method === 'rules')<small class="admin-cell-hint" style="display:inline">rule-based</small>@endif
                    @else
                        <span class="admin-cell-hint">Not screened</span>
                    @endif
                </td>
                <td>{{ \App\Models\RecruitmentVacancy::EDUCATION_LEVELS[$applicant->education_level] ?? '-' }}<small class="admin-cell-hint">{{ $applicant->years_experience !== null ? $applicant->years_experience.' yrs' : '' }}</small></td>
                <td><span class="status-chip {{ $applicant->status }}">{{ ucfirst($applicant->status) }}</span></td>
                <td>{{ $applicant->created_at->format('d M Y') }}<small class="admin-cell-hint">{{ $applicant->source === 'embed' ? 'Embed'.($applicant->source_site ? ': '.$applicant->source_site : '') : 'Careers page' }}</small></td>
                <td class="table-actions"><a href="{{ route('admin.hr.recruitment.applicant', $applicant) }}" class="btn btn-outline btn-sm">Review</a></td>
            </tr>
        @empty
            <tr><td colspan="8"><div class="admin-empty">No applicants {{ request('status') ? 'with this status' : 'yet' }}. Share the job link or embed code to start receiving applications.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    </form>
    <p class="form-hint">AI scores are a guide to help prioritise reading, not a decision. Review each shortlisted application before inviting candidates.</p>
</div>
@endif

{{-- ============ INTERVIEWS ============ --}}
@if($tab === 'interviews')
<div class="admin-panel">
    <div class="admin-table-wrap"><table class="admin-table">
        <thead><tr><th>Candidate</th><th>When</th><th>Mode</th><th>Panel scores</th><th>Average</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
        <tbody>
        @forelse($vacancy->interviews->sortBy('scheduled_at') as $interview)
            @php $submitted = $interview->panelists->whereNotNull('submitted_at')->count(); @endphp
            <tr>
                <td><a href="{{ route('admin.hr.recruitment.applicant', $interview->applicant_id) }}"><strong>{{ $interview->applicant->name }}</strong></a><small class="admin-cell-hint">AI {{ $interview->applicant->ai_score !== null ? number_format((float) $interview->applicant->ai_score, 0) : '-' }}</small></td>
                <td>{{ $interview->scheduled_at->format('D d M Y, H:i') }}<small class="admin-cell-hint">{{ $interview->duration_minutes }} min{{ $interview->candidate_notified_at ? ' · candidate notified' : '' }}</small></td>
                <td>{{ \App\Models\RecruitmentInterview::MODES[$interview->mode] ?? $interview->mode }}<small class="admin-cell-hint">{{ $interview->location ?: $interview->meeting_link }}</small></td>
                <td>{{ $submitted }} / {{ $interview->panelists->count() }}<small class="admin-cell-hint">{{ $interview->panelists->map(fn($p) => $p->user?->name.($p->submitted_at ? ' ✓' : ''))->join(', ') }}</small></td>
                <td>{{ $interview->average_score !== null ? number_format((float) $interview->average_score, 1).'%' : '-' }}</td>
                <td><span class="status-chip {{ $interview->status }}">{{ ucfirst($interview->status) }}</span></td>
                <td class="table-actions">
                    @if($canManage)
                        <button type="button" class="btn btn-outline btn-sm" data-modal-open="interview{{ $interview->id }}">Edit</button>
                        @if($submitted < $interview->panelists->count() && $interview->status !== 'cancelled')
                            <form method="POST" action="{{ route('admin.hr.recruitment.interviews.remind', $interview) }}" style="display:inline">@csrf<button class="btn btn-outline btn-sm" title="Remind panel members who have not scored">Remind panel</button></form>
                        @endif
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7"><div class="admin-empty">No interviews yet. Shortlist applicants, then use <strong>Schedule interviews</strong>.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>

@if($canManage)
@foreach($vacancy->interviews as $interview)
<div class="eh-modal" id="interview{{ $interview->id }}" aria-hidden="true"><div class="eh-modal-dialog">
    <div class="eh-modal-header"><div><h2>Edit interview</h2><p>{{ $interview->applicant->name }}</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
    <form method="POST" action="{{ route('admin.hr.recruitment.interviews.update', $interview) }}">@csrf @method('PUT')
        <div class="eh-modal-body"><div class="modal-grid">
            <div class="form-group"><label>Date &amp; time</label><input type="datetime-local" name="scheduled_at" value="{{ $interview->scheduled_at->format('Y-m-d\TH:i') }}" required></div>
            <div class="form-group"><label>Duration (minutes)</label><input type="number" name="duration_minutes" min="10" max="480" value="{{ $interview->duration_minutes }}"></div>
            <div class="form-group"><label>Mode</label><select name="mode">@foreach(\App\Models\RecruitmentInterview::MODES as $k => $l)<option value="{{ $k }}" @selected($interview->mode === $k)>{{ $l }}</option>@endforeach</select></div>
            <div class="form-group"><label>Status</label><select name="status">@foreach(['scheduled','completed','cancelled'] as $s)<option value="{{ $s }}" @selected($interview->status === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
            <div class="form-group"><label>Venue</label><input name="location" value="{{ $interview->location }}"></div>
            <div class="form-group"><label>Meeting link</label><input type="url" name="meeting_link" value="{{ $interview->meeting_link }}"></div>
            <div class="form-group" style="grid-column:1/-1"><label>Add panel members</label>@include('admin.hr.partials.staff-checklist', ['name' => 'add_panel_ids', 'staff' => $staff, 'exclude' => $interview->panelists->pluck('user_id')->all()])</div>
            <div class="form-group" style="grid-column:1/-1"><label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="notify" value="1" checked style="width:auto"> Notify the candidate and panel if the time changes or it is cancelled</label></div>
        </div></div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Save</button></div>
    </form>
</div></div>
@endforeach
@endif
@endif

{{-- ============ QUESTIONS ============ --}}
@if($tab === 'questions')
<div class="admin-panel">
    <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start">
        <p style="margin:0;max-width:640px">Panel members score each question during the interview. The candidate's interview score is the weighted total as a percentage, averaged across the panel.</p>
        @if($canManage)
        <form method="POST" action="{{ route('admin.hr.recruitment.questions.suggest', $vacancy) }}">@csrf<button class="btn btn-outline btn-sm"><i class="fas fa-wand-magic-sparkles" aria-hidden="true"></i> Suggest questions with AI</button></form>
        @endif
    </div>
    <div class="admin-table-wrap" style="margin-top:12px"><table class="admin-table">
        <thead><tr><th>#</th><th>Question</th><th>Max score</th><th>Weight</th>@if($canManage)<th class="table-actions">Actions</th>@endif</tr></thead>
        <tbody>
        @forelse($vacancy->questions as $i => $question)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $question->question }}</strong>@if($question->guidance)<small class="admin-cell-hint">Look for: {{ $question->guidance }}</small>@endif</td>
                <td>{{ $question->max_score }}</td><td>{{ $question->weight }}</td>
                @if($canManage)
                <td class="table-actions">
                    <button type="button" class="btn btn-outline btn-sm" data-modal-open="q{{ $question->id }}">Edit</button>
                    <form method="POST" action="{{ route('admin.hr.recruitment.questions.destroy', $question) }}" style="display:inline" onsubmit="return confirm('Remove this question? Scores already given for it are deleted too.')">@csrf @method('DELETE')<button class="btn btn-outline btn-sm">Remove</button></form>
                </td>
                @endif
            </tr>
        @empty
            <tr><td colspan="5"><div class="admin-empty">No questions yet. Add them below or let AI suggest some from the job description.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>

    @if($canManage)
    <h2 style="font-size:1rem;margin:18px 0 8px">Add a question</h2>
    <form method="POST" action="{{ route('admin.hr.recruitment.questions.store', $vacancy) }}">@csrf
        <div class="modal-grid">
            <div class="form-group" style="grid-column:1/-1"><label for="nq">Question *</label><textarea id="nq" name="question" rows="2" required></textarea></div>
            <div class="form-group" style="grid-column:1/-1"><label for="ng">What a strong answer includes</label><textarea id="ng" name="guidance" rows="2"></textarea></div>
            <div class="form-group"><label for="nm">Max score</label><select id="nm" name="max_score">@foreach([5,10,3,4] as $m)<option value="{{ $m }}">{{ $m }}</option>@endforeach</select></div>
            <div class="form-group"><label for="nw">Weight</label><select id="nw" name="weight">@for($w = 1; $w <= 5; $w++)<option value="{{ $w }}">{{ $w }}</option>@endfor</select></div>
        </div>
        <button class="btn btn-primary btn-sm">Add question</button>
    </form>

    @foreach($vacancy->questions as $question)
    <div class="eh-modal" id="q{{ $question->id }}" aria-hidden="true"><div class="eh-modal-dialog">
        <div class="eh-modal-header"><div><h2>Edit question</h2></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
        <form method="POST" action="{{ route('admin.hr.recruitment.questions.update', $question) }}">@csrf @method('PUT')
            <div class="eh-modal-body"><div class="modal-grid">
                <div class="form-group" style="grid-column:1/-1"><label>Question *</label><textarea name="question" rows="3" required>{{ $question->question }}</textarea></div>
                <div class="form-group" style="grid-column:1/-1"><label>What a strong answer includes</label><textarea name="guidance" rows="3">{{ $question->guidance }}</textarea></div>
                <div class="form-group"><label>Max score</label><input type="number" name="max_score" min="1" max="10" value="{{ $question->max_score }}"></div>
                <div class="form-group"><label>Weight</label><input type="number" name="weight" min="1" max="10" value="{{ $question->weight }}"></div>
            </div></div>
            <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Save</button></div>
        </form>
    </div></div>
    @endforeach
    @endif
</div>
@endif

{{-- ============ EMBED ============ --}}
@if($tab === 'embed')
<div class="admin-panel">
    @if($vacancy->status !== 'open')<div class="alert alert-warning" role="status">Only <strong>Open</strong> vacancies accept applications. Embeds of this job will show it as closed until you open it.</div>@endif
    <h2 style="font-size:1rem;margin-top:0">Direct link</h2>
    <div style="display:flex;gap:8px"><input readonly value="{{ $vacancy->publicUrl() }}" id="pub-link" aria-label="Public job link"><button type="button" class="btn btn-outline btn-sm" data-copy="pub-link">Copy</button></div>

    <h2 style="font-size:1rem;margin-top:20px">Embed this job on another website</h2>
    <p class="form-hint">Paste into any web page (WordPress "Custom HTML" block, Wix "Embed code", plain HTML). Shows the job description and application form; the frame resizes automatically.</p>
    <textarea readonly class="hr-code" id="snip-job" aria-label="Embed code for this job">{{ $jobSnippet }}</textarea>
    <button type="button" class="btn btn-outline btn-sm" data-copy="snip-job">Copy code</button>

    <h2 style="font-size:1rem;margin-top:20px">Embed all open jobs</h2>
    <p class="form-hint">Lists every open vacancy; visitors pick one and apply without leaving the site.</p>
    <textarea readonly class="hr-code" id="snip-all" aria-label="Embed code for all jobs">{{ $allSnippet }}</textarea>
    <button type="button" class="btn btn-outline btn-sm" data-copy="snip-all">Copy code</button>

    <details style="margin-top:20px"><summary><strong>Website does not allow scripts? Use an iframe</strong></summary>
        <textarea readonly class="hr-code" id="snip-iframe" aria-label="Iframe embed code" style="margin-top:8px">{{ $iframeSnippet }}</textarea>
        <button type="button" class="btn btn-outline btn-sm" data-copy="snip-iframe">Copy code</button>
    </details>
</div>
<script>
document.querySelectorAll('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
        var el = document.getElementById(b.dataset.copy); el.select();
        (navigator.clipboard ? navigator.clipboard.writeText(el.value) : Promise.resolve(document.execCommand('copy'))).then(function () { b.textContent = 'Copied'; setTimeout(function () { b.textContent = 'Copy'; }, 1500); });
    });
});
</script>
@endif

{{-- ============ DETAILS ============ --}}
@if($tab === 'details')
<div class="admin-panel">
    @if($canManage)
    <form method="POST" action="{{ route('admin.hr.recruitment.update', $vacancy) }}">@csrf @method('PUT')
        @include('admin.hr.recruitment._form', ['vacancy' => $vacancy])
        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px"><button class="btn btn-primary">Save changes</button></div>
    </form>
    <form method="POST" action="{{ route('admin.hr.recruitment.destroy', $vacancy) }}" onsubmit="return confirm('Archive this vacancy? It disappears from the careers page and embeds.')" style="margin-top:12px">@csrf @method('DELETE')<button class="btn btn-outline btn-sm" style="color:#b42318">Archive vacancy</button></form>
    @else
        <p><strong>Responsibilities</strong></p><div style="white-space:pre-line">{{ $vacancy->responsibilities }}</div>
        <p><strong>Requirements</strong></p><div style="white-space:pre-line">{{ $vacancy->requirements }}</div>
    @endif
</div>
@endif

{{-- ============ SCHEDULE INTERVIEWS ============ --}}
@if($canManage)
<div class="eh-modal" id="scheduleModal" aria-hidden="true"><div class="eh-modal-dialog eh-modal-lg">
    <div class="eh-modal-header"><div><h2>Schedule interviews</h2><p>Each selected candidate gets their own slot; the panel scores them with this vacancy's questions.</p></div><button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark" aria-hidden="true"></i></button></div>
    <form method="POST" action="{{ route('admin.hr.recruitment.interviews.store', $vacancy) }}">@csrf
        <div class="eh-modal-body">
            @if($vacancy->questions->isEmpty())
                <div class="alert alert-warning">Add <a href="{{ route('admin.hr.recruitment.show', [$vacancy, 'tab' => 'questions']) }}">interview questions</a> first so the panel can score candidates.</div>
            @endif
            <fieldset style="border:0;padding:0;margin:0 0 12px"><legend style="font-weight:700">Candidates (shortlisted)</legend>
                @forelse($shortlisted as $a)
                    <label style="display:flex;gap:8px;align-items:center;font-weight:500;padding:3px 0"><input type="checkbox" name="applicant_ids[]" value="{{ $a->id }}" @checked($a->status === 'shortlisted') style="width:auto"> {{ $a->name }} <small class="admin-cell-hint" style="display:inline">AI {{ $a->ai_score !== null ? number_format((float) $a->ai_score, 0) : '-' }} · {{ $a->status }}</small></label>
                @empty
                    <p class="admin-cell-hint">No shortlisted applicants yet. Shortlist candidates on the Applicants tab first.</p>
                @endforelse
            </fieldset>
            <div class="modal-grid">
                <div class="form-group"><label for="si-at">First interview starts *</label><input id="si-at" type="datetime-local" name="scheduled_at" required min="{{ now()->format('Y-m-d\TH:i') }}"></div>
                <div class="form-group"><label for="si-dur">Duration (minutes) *</label><input id="si-dur" type="number" name="duration_minutes" value="45" min="10" max="480" required></div>
                <div class="form-group"><label for="si-slot">Minutes between candidates</label><input id="si-slot" type="number" name="slot_minutes" value="60" min="0" max="480"><small class="form-hint">Next candidate starts this many minutes after the previous one.</small></div>
                <div class="form-group"><label for="si-mode">Mode *</label><select id="si-mode" name="mode">@foreach(\App\Models\RecruitmentInterview::MODES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                <div class="form-group"><label for="si-loc">Venue</label><input id="si-loc" name="location" placeholder="e.g. Board room, Head office"></div>
                <div class="form-group"><label for="si-link">Meeting link</label><input id="si-link" type="url" name="meeting_link" placeholder="https://meet..."></div>
                <div class="form-group" style="grid-column:1/-1"><label for="si-msg">Message to candidates</label><textarea id="si-msg" name="candidate_message" rows="2" placeholder="e.g. Please bring originals of your academic documents."></textarea></div>
                <div class="form-group" style="grid-column:1/-1"><label>Interview panel (staff) *</label>@include('admin.hr.partials.staff-checklist', ['name' => 'panel_ids', 'staff' => $staff])</div>
                <div class="form-group"><label for="si-chair">Panel chair</label><select id="si-chair" name="chair_id"><option value="">None</option>@foreach($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select></div>
                <div class="form-group"><label style="display:flex;gap:8px;align-items:center;margin-top:24px"><input type="checkbox" name="notify_candidate" value="1" checked style="width:auto"> Email invitations to candidates</label></div>
            </div>
        </div>
        <div class="eh-modal-footer"><button type="button" class="btn btn-outline" data-modal-close>Cancel</button><button class="btn btn-primary">Schedule &amp; invite</button></div>
    </form>
</div></div>
@endif
@endsection
