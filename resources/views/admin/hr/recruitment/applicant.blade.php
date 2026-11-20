@extends('layouts.admin')
@section('title',$applicant->name.' | Recruitment')
@section('content')
@php
    $canManage = auth()->user()->hasPermission('hr.manage');
    $b = $applicant->ai_breakdown ?? [];
    $recLabels = ['strong' => 'Strong match', 'good' => 'Good match', 'possible' => 'Possible', 'weak' => 'Weak match'];
    $questions = $vacancy->questions;
@endphp
<style>
.hr-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(280px,1fr);gap:16px}
@media(max-width:900px){.hr-grid{grid-template-columns:1fr}}
.hr-crit{display:grid;grid-template-columns:1fr 60px;gap:4px 12px;padding:8px 0;border-bottom:1px solid var(--ad-border,#eee)}
.hr-bar{grid-column:1/-1;height:6px;background:#eef0f3;border-radius:99px;overflow:hidden}
.hr-bar span{display:block;height:100%;background:var(--ad-primary,#800000)}
.hr-dl{display:grid;grid-template-columns:140px 1fr;gap:6px 10px;margin:0}
.hr-dl dt{font-weight:700;color:var(--ad-muted,#667085)}
.hr-dl dd{margin:0}
</style>

<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow"><a href="{{ route('admin.hr.recruitment.show', $vacancy) }}">{{ $vacancy->title }}</a></span>
        <h1>{{ $applicant->name }}</h1>
        <p>{{ $applicant->reference }} · applied {{ $applicant->created_at->format('d M Y H:i') }} <span class="status-chip {{ $applicant->status }}">{{ ucfirst($applicant->status) }}</span></p>
    </div>
    <div class="admin-page-actions">
        @if($applicant->cv_path)<a href="{{ route('admin.hr.recruitment.applicant.cv', $applicant) }}" class="btn btn-outline"><i class="fas fa-file-arrow-down" aria-hidden="true"></i> Download CV</a>@endif
        @if($canManage)
            <form method="POST" action="{{ route('admin.hr.recruitment.applicant.screen', $applicant) }}">@csrf<button class="btn btn-outline"><i class="fas fa-robot" aria-hidden="true"></i> {{ $applicant->ai_screened_at ? 'Re-screen' : 'Screen' }} with AI</button></form>
        @endif
    </div>
</div>
@include('admin.hr.partials.nav')

<div class="hr-grid">
<div>
    <section class="admin-panel" aria-labelledby="ai-h">
        <h2 id="ai-h" style="margin-top:0;font-size:1.05rem"><i class="fas fa-robot" aria-hidden="true"></i> Screening result</h2>
        @if($applicant->ai_screened_at)
            <p style="font-size:1.6rem;margin:0"><strong>{{ number_format((float) $applicant->ai_score, 0) }}</strong><span style="font-size:1rem"> / 100 · {{ $recLabels[$applicant->ai_recommendation] ?? $applicant->ai_recommendation }}</span></p>
            <small class="admin-cell-hint">{{ $applicant->ai_method === 'ai' ? 'Scored by AI' : 'Rule-based score (AI unavailable)' }} on {{ $applicant->ai_screened_at->format('d M Y H:i') }}@if(! empty($b['missed_must_have'])) · <strong style="color:#b42318">a must-have criterion was not met</strong>@endif</small>
            @if($applicant->ai_summary)<p>{{ $applicant->ai_summary }}</p>@endif
            @foreach($b['criteria'] ?? [] as $c)
                <div class="hr-crit"><strong>{{ $c['label'] }}</strong><span style="text-align:right">{{ $c['score'] }}</span>
                    <div class="hr-bar" aria-hidden="true"><span style="width:{{ (int) $c['score'] }}%"></span></div>
                    <small class="admin-cell-hint" style="grid-column:1/-1">{{ $c['note'] }}</small></div>
            @endforeach
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
                <div><strong>Strengths</strong><ul style="margin:4px 0;padding-left:18px">@forelse($b['strengths'] ?? [] as $s)<li>{{ $s }}</li>@empty<li class="admin-cell-hint">None noted</li>@endforelse</ul></div>
                <div><strong>Gaps</strong><ul style="margin:4px 0;padding-left:18px">@forelse($b['gaps'] ?? [] as $g)<li>{{ $g }}</li>@empty<li class="admin-cell-hint">None noted</li>@endforelse</ul></div>
            </div>
        @else
            <div class="admin-empty">Not screened yet.</div>
        @endif
    </section>

    @if($applicant->answers)
    <section class="admin-panel"><h2 style="margin-top:0;font-size:1.05rem">Application answers</h2>
        @foreach($applicant->answers as $a)<p><strong>{{ $a['question'] ?? '' }}</strong><br><span style="white-space:pre-line">{{ $a['answer'] ?? '' }}</span></p>@endforeach
    </section>
    @endif

    <section class="admin-panel"><h2 style="margin-top:0;font-size:1.05rem">Cover letter</h2>
        <div style="white-space:pre-line">{{ $applicant->cover_letter ?: 'No cover letter provided.' }}</div>
    </section>

    @foreach($applicant->interviews as $interview)
    <section class="admin-panel">
        <h2 style="margin-top:0;font-size:1.05rem">Interview · {{ $interview->scheduled_at->format('D d M Y, H:i') }} <span class="status-chip {{ $interview->status }}">{{ ucfirst($interview->status) }}</span></h2>
        <p>Average panel score: <strong>{{ $interview->average_score !== null ? number_format((float) $interview->average_score, 1).'%' : 'awaiting scores' }}</strong></p>
        <div class="admin-table-wrap"><table class="admin-table">
            <thead><tr><th>Question</th>@foreach($interview->panelists as $p)<th>{{ $p->user?->name }}{{ $p->is_chair ? ' (chair)' : '' }}</th>@endforeach</tr></thead>
            <tbody>
            @foreach($questions as $q)
                <tr><td>{{ \Illuminate\Support\Str::limit($q->question, 90) }}<small class="admin-cell-hint">out of {{ $q->max_score }}, weight {{ $q->weight }}</small></td>
                @foreach($interview->panelists as $p)
                    @php $s = $p->scores->firstWhere('question_id', $q->id); @endphp
                    <td>{{ $s?->score ?? '-' }}@if($s?->comment)<small class="admin-cell-hint">{{ $s->comment }}</small>@endif</td>
                @endforeach</tr>
            @endforeach
            <tr><td><strong>Total</strong></td>@foreach($interview->panelists as $p)<td><strong>{{ $p->total_score !== null ? number_format((float) $p->total_score, 1).'%' : '-' }}</strong><small class="admin-cell-hint">{{ $p->submitted_at ? 'submitted' : 'not submitted' }}</small></td>@endforeach</tr>
            <tr><td><strong>Recommendation</strong></td>@foreach($interview->panelists as $p)<td>{{ \App\Models\RecruitmentInterviewPanelist::RECOMMENDATIONS[$p->recommendation] ?? '-' }}@if($p->overall_comment)<small class="admin-cell-hint">{{ $p->overall_comment }}</small>@endif</td>@endforeach</tr>
            </tbody>
        </table></div>
    </section>
    @endforeach
</div>

<aside>
    <section class="admin-panel"><h2 style="margin-top:0;font-size:1.05rem">Candidate</h2>
        <dl class="hr-dl">
            <dt>Email</dt><dd><a href="mailto:{{ $applicant->email }}">{{ $applicant->email }}</a></dd>
            <dt>Phone</dt><dd>{{ $applicant->phone ?: '-' }}</dd>
            <dt>Location</dt><dd>{{ $applicant->location ?: '-' }}</dd>
            <dt>Education</dt><dd>{{ \App\Models\RecruitmentVacancy::EDUCATION_LEVELS[$applicant->education_level] ?? '-' }}</dd>
            <dt>Experience</dt><dd>{{ $applicant->years_experience !== null ? $applicant->years_experience.' years' : '-' }}</dd>
            <dt>Link</dt><dd>@if($applicant->linkedin_url)<a href="{{ $applicant->linkedin_url }}" target="_blank" rel="noopener noreferrer">{{ \Illuminate\Support\Str::limit($applicant->linkedin_url, 40) }}</a>@else - @endif</dd>
            <dt>Source</dt><dd>{{ $applicant->source === 'embed' ? 'Embedded widget'.($applicant->source_site ? ' on '.$applicant->source_site : '') : 'Careers page' }}</dd>
            <dt>CV</dt><dd>{{ $applicant->cv_original_name ?: 'None' }}@if($applicant->cv_path && ! $applicant->getRawOriginal('cv_text'))<small class="admin-cell-hint">Text could not be read (scanned?) - review manually.</small>@endif</dd>
            @if($applicant->shortlisted_at)<dt>Shortlisted</dt><dd>{{ $applicant->shortlisted_at->format('d M Y') }} by {{ $applicant->shortlister?->name }}</dd>@endif
        </dl>
    </section>

    @if($canManage)
    <section class="admin-panel"><h2 style="margin-top:0;font-size:1.05rem">Decision</h2>
        <form method="POST" action="{{ route('admin.hr.recruitment.applicant.update', $applicant) }}">@csrf @method('PUT')
            <div class="form-group"><label for="ap-status">Status</label><select id="ap-status" name="status">@foreach(\App\Models\RecruitmentApplicant::STATUSES as $s)<option value="{{ $s }}" @selected($applicant->status === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
            <div class="form-group"><label for="ap-notes">HR notes (internal)</label><textarea id="ap-notes" name="hr_notes" rows="4">{{ $applicant->hr_notes }}</textarea></div>
            <label style="display:flex;gap:8px;align-items:center;font-weight:500;margin-bottom:10px"><input type="checkbox" name="notify" value="1" style="width:auto"> Email the candidate if rejected</label>
            <button class="btn btn-primary btn-sm">Save</button>
        </form>
        @if(in_array($applicant->status, ['shortlisted', 'interview'], true))
            <p style="margin-top:12px"><a href="{{ route('admin.hr.recruitment.show', [$vacancy, 'tab' => 'interviews']) }}">Schedule an interview from the vacancy page &rarr;</a></p>
        @endif
    </section>
    @endif
</aside>
</div>
@endsection
