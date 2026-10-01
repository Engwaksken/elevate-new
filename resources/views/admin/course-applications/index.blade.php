@extends('layouts.admin')
@section('title','Course Applications | ElevateHer360')
@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Course Applications</span>
        <h1>{{ $courseCall->title }}</h1>
        <p>{{ $courseCall->courses->pluck('title')->join(', ') ?: 'No courses linked' }}</p>
    </div>
    <div class="admin-page-actions"><a href="{{ route('admin.course-calls.index') }}" class="btn btn-outline">Back</a></div>
</div>

<div class="admin-panel">
<div class="admin-table-wrap">
<table class="admin-table">
<thead><tr><th>Participant</th><th>History</th><th>Status</th><th>Entry Score</th><th>Submitted</th><th class="table-actions">Review</th></tr></thead>
<tbody>
@forelse($applications as $application)
<tr>
    <td>
        <strong>{{ $application->user?->name }}</strong>
        <small class="admin-cell-hint">{{ $application->user?->participant_code ?? '—' }} · {{ $application->user?->email }}</small>
    </td>
    <td>@include('partials.participant-history-badge', ['history' => $history[$application->user_id] ?? null, 'duplicates' => $duplicates[$application->user_id] ?? null])</td>
    <td><span class="status-chip {{ $application->status === 'approved' ? 'active' : ($application->status === 'rejected' ? 'inactive' : 'draft') }}">{{ ucfirst($application->status) }}</span></td>
    <td>{{ $application->assessmentAttempt?->percentage ?? $application->entry_assessment_score ?? '—' }}</td>
    <td>{{ $application->submitted_at?->format('d M Y H:i') ?? 'Draft' }}</td>
    <td class="table-actions"><button type="button" class="btn btn-outline btn-sm" data-modal-open="application-{{ $application->id }}"><i class="fas fa-gavel"></i> Review</button></td>
</tr>
@empty
<tr><td colspan="6"><div class="admin-empty">No applications found.</div></td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="admin-pagination">{{ $applications->links() }}</div>
</div>

@foreach($applications as $application)
@php $reopen = $errors->any() && (int) old('application_id') === (int) $application->id; @endphp
<div class="eh-modal" id="application-{{ $application->id }}" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="application-{{ $application->id }}-title" @if($reopen) data-modal-autoopen @endif>
<div class="eh-modal-dialog">
<form method="POST" action="{{ route('admin.course-applications.review',$application) }}">
    @csrf @method('PUT')
    <input type="hidden" name="application_id" value="{{ $application->id }}">
    <div class="eh-modal-header">
        <div>
            <h2 id="application-{{ $application->id }}-title">{{ $application->user?->name }}</h2>
            <p>{{ $application->user?->participant_code ?? 'No participant ID' }} · {{ $application->user?->email }}{{ $application->user?->phone ? ' · '.$application->user->phone : '' }}</p>
        </div>
        <button type="button" class="eh-modal-close" data-modal-close aria-label="Close"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="eh-modal-body">
        @if($reopen)
            <div class="icm-notice" role="alert"><span><i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}</span></div>
        @endif

        @include('partials.participant-history-details', ['history' => $history[$application->user_id] ?? null, 'duplicates' => $duplicates[$application->user_id] ?? null])

        <div class="modal-grid">
            <div class="form-group">
                <label for="app-{{ $application->id }}-status">Decision</label>
                @php $status = $reopen ? old('status') : $application->status; @endphp
                <select id="app-{{ $application->id }}-status" name="status">
                    @foreach(['submitted','shortlisted','approved','waitlisted','rejected'] as $s)
                        <option value="{{ $s }}" @selected($status===$s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="app-{{ $application->id }}-course">Enrol into course <small class="form-hint" style="display:inline">(required to approve)</small></label>
                @php $approvedCourse = $reopen ? (int) old('approved_course_id') : ($courseCall->courses->count() === 1 ? $courseCall->courses->first()->id : null); @endphp
                <select id="app-{{ $application->id }}-course" name="approved_course_id">
                    <option value="">Select course</option>
                    @foreach($courseCall->courses as $course)
                        <option value="{{ $course->id }}" @selected($approvedCourse === $course->id)>{{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label for="app-{{ $application->id }}-score">Application score</label>
                <input id="app-{{ $application->id }}-score" type="number" name="application_score" min="0" max="100" step=".01" value="{{ $reopen ? old('application_score') : $application->application_score }}">
            </div>
            <div class="form-group">
                <label>Entry assessment</label>
                <div class="admin-readonly">{{ $application->assessmentAttempt?->percentage ?? $application->entry_assessment_score ?? 'Not taken' }}</div>
            </div>
            <div class="form-group full">
                <label for="app-{{ $application->id }}-comments">Reviewer comments</label>
                <textarea id="app-{{ $application->id }}-comments" name="reviewer_comments" rows="3" maxlength="5000">{{ $reopen ? old('reviewer_comments') : $application->reviewer_comments }}</textarea>
            </div>
        </div>
    </div>
    <div class="eh-modal-footer">
        <button type="button" class="btn btn-outline" data-modal-close>Cancel</button>
        <button class="btn btn-primary"><i class="fas fa-floppy-disk"></i> Save review</button>
    </div>
</form>
</div>
</div>
@endforeach
@endsection
