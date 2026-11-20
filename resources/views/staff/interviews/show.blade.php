@extends('layouts.admin')
@section('title','Interview: '.$interview->applicant->name)
@section('content')
@php
    $applicant = $interview->applicant;
    $vacancy = $interview->vacancy;
    $locked = $panelist->submitted_at !== null;
    $cancelled = $interview->status === 'cancelled';
@endphp
<style>
.hr-grid{display:grid;grid-template-columns:minmax(0,2fr) minmax(260px,1fr);gap:16px}
@media(max-width:900px){.hr-grid{grid-template-columns:1fr}}
.hr-q{padding:14px 0;border-bottom:1px solid var(--ad-border,#eee)}
.hr-scale{display:flex;flex-wrap:wrap;gap:6px;margin:8px 0}
.hr-scale label{display:inline-flex;align-items:center;justify-content:center;min-width:40px;height:40px;border:1px solid var(--ad-border,#d0d5dd);border-radius:8px;cursor:pointer;font-weight:700}
.hr-scale input{position:absolute;opacity:0;width:1px;height:1px}
.hr-scale input:checked + span{color:#fff}
.hr-scale label:has(input:checked){background:var(--ad-primary,#800000);border-color:var(--ad-primary,#800000);color:#fff}
.hr-scale label:has(input:focus-visible){outline:2px solid var(--ad-primary,#800000);outline-offset:2px}
</style>
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow"><a href="{{ route('staff.interviews.index') }}">My Interview Panels</a></span>
        <h1>{{ $applicant->name }}</h1>
        <p>{{ $vacancy->title }} · {{ $interview->scheduled_at->format('l d M Y, H:i') }} · {{ \App\Models\RecruitmentInterview::MODES[$interview->mode] ?? $interview->mode }}{{ $interview->location ? ' · '.$interview->location : '' }}</p>
    </div>
    <div class="admin-page-actions">
        @if($interview->meeting_link)<a href="{{ $interview->meeting_link }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline"><i class="fas fa-video" aria-hidden="true"></i> Join meeting</a>@endif
        @if($applicant->cv_path)<a href="{{ route('staff.interviews.cv', $interview) }}" class="btn btn-outline"><i class="fas fa-file-arrow-down" aria-hidden="true"></i> CV</a>@endif
    </div>
</div>

@if($cancelled)<div class="alert alert-warning" role="status">This interview was cancelled.</div>@endif
@if($locked)<div class="alert alert-success" role="status">You submitted your scores on {{ $panelist->submitted_at->format('d M Y H:i') }} ({{ number_format((float) $panelist->total_score, 1) }}%).</div>@endif

<div class="hr-grid">
<div class="admin-panel">
    <h2 style="margin-top:0;font-size:1.05rem">Score the candidate</h2>
    <p class="form-hint" style="margin-top:0">Score each answer on the scale shown. Add a short note on the evidence you heard. Save a draft during the interview and submit when you are done.</p>
    @if($vacancy->questions->isEmpty())
        <div class="admin-empty">HR has not added interview questions for this position yet.</div>
    @else
    <form method="POST" action="{{ route('staff.interviews.score', $interview) }}">@csrf
        @foreach($vacancy->questions as $n => $q)
            @php $current = old('scores.'.$q->id, $scores[$q->id]->score ?? null); @endphp
            <fieldset class="hr-q" style="border:0;margin:0" @disabled($locked || $cancelled)>
                <legend style="font-weight:700">{{ $n + 1 }}. {{ $q->question }}</legend>
                @if($q->guidance)<small class="admin-cell-hint">Look for: {{ $q->guidance }}</small>@endif
                <div class="hr-scale" role="radiogroup" aria-label="Score for question {{ $n + 1 }}">
                    @for($s = 0; $s <= $q->max_score; $s++)
                        <label><input type="radio" name="scores[{{ $q->id }}]" value="{{ $s }}" @checked($current !== null && (int) $current === $s)><span>{{ $s }}</span></label>
                    @endfor
                </div>
                <small class="admin-cell-hint">0 = no evidence · {{ $q->max_score }} = excellent{{ $q->weight > 1 ? ' · weight '.$q->weight : '' }}</small>
                @error('scores.'.$q->id)<div class="form-error" style="color:#b42318">{{ $message }}</div>@enderror
                <textarea name="comments[{{ $q->id }}]" rows="2" placeholder="Evidence / notes" aria-label="Notes for question {{ $n + 1 }}" style="margin-top:6px">{{ old('comments.'.$q->id, $scores[$q->id]->comment ?? '') }}</textarea>
            </fieldset>
        @endforeach
        <fieldset style="border:0;margin:14px 0 0;padding:0" @disabled($locked || $cancelled)>
            <div class="form-group"><label for="rec">Your recommendation</label><select id="rec" name="recommendation"><option value="">Select</option>@foreach(\App\Models\RecruitmentInterviewPanelist::RECOMMENDATIONS as $k => $l)<option value="{{ $k }}" @selected(old('recommendation', $panelist->recommendation) === $k)>{{ $l }}</option>@endforeach</select>@error('recommendation')<div style="color:#b42318">{{ $message }}</div>@enderror</div>
            <div class="form-group"><label for="oc">Overall comment</label><textarea id="oc" name="overall_comment" rows="3">{{ old('overall_comment', $panelist->overall_comment) }}</textarea></div>
            @unless($locked || $cancelled)
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button class="btn btn-outline">Save draft</button>
                <button class="btn btn-primary" name="submit" value="1" onclick="return confirm('Submit your scores to HR? You cannot change them afterwards.')">Submit scores</button>
            </div>
            @endunless
        </fieldset>
    </form>
    @endif
</div>

<aside>
    <section class="admin-panel"><h2 style="margin-top:0;font-size:1.05rem">Application</h2>
        <p style="margin:0 0 6px"><strong>Education:</strong> {{ \App\Models\RecruitmentVacancy::EDUCATION_LEVELS[$applicant->education_level] ?? '-' }}</p>
        <p style="margin:0 0 6px"><strong>Experience:</strong> {{ $applicant->years_experience !== null ? $applicant->years_experience.' years' : '-' }}</p>
        @if($applicant->linkedin_url)<p style="margin:0 0 6px"><a href="{{ $applicant->linkedin_url }}" target="_blank" rel="noopener noreferrer">Profile link</a></p>@endif
        @if($applicant->ai_summary)<p style="margin:10px 0 0"><strong>Screening summary:</strong> {{ $applicant->ai_summary }}</p>@endif
        @foreach($applicant->answers ?? [] as $a)<p style="margin:10px 0 0"><strong>{{ $a['question'] ?? '' }}</strong><br><span style="white-space:pre-line">{{ $a['answer'] ?? '' }}</span></p>@endforeach
        @if($applicant->cover_letter)<details style="margin-top:10px"><summary><strong>Cover letter</strong></summary><div style="white-space:pre-line;margin-top:6px">{{ $applicant->cover_letter }}</div></details>@endif
    </section>
    <section class="admin-panel"><h2 style="margin-top:0;font-size:1.05rem">Panel</h2>
        @foreach($interview->panelists as $p)
            <div style="display:flex;justify-content:space-between;padding:4px 0"><span>{{ $p->user?->name }}{{ $p->is_chair ? ' (chair)' : '' }}</span><span class="admin-cell-hint">{{ $p->submitted_at ? 'submitted' : 'pending' }}</span></div>
        @endforeach
    </section>
</aside>
</div>
@endsection
