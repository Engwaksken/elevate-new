@extends('layouts.admin')

@section('title', 'Participant Progress | '.$course->title)

@section('content')
<div class="admin-page-header">
    <div>
        <span class="admin-eyebrow">Participant Progress</span>
        <h1>{{ $enrolment->user?->name ?? 'Participant' }}</h1>
        <p>{{ $course->title }} · {{ $enrolment->user?->email }}</p>
    </div>
    <a href="{{ route('instructor.courses.manage', ['course' => $course, 'tab' => 'progress']) }}" class="btn btn-outline">
        <i class="fas fa-arrow-left"></i> Back to Progress
    </a>
</div>

<div class="admin-stats-grid compact">
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-chart-line"></i></span><div><small>Overall Progress</small><strong>{{ number_format((float)$enrolment->progress_percent, 1) }}%</strong></div></div>
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-star"></i></span><div><small>Final Score</small><strong>{{ $enrolment->final_score !== null ? number_format((float)$enrolment->final_score, 1).'%' : '—' }}</strong></div></div>
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-user-check"></i></span><div><small>Status</small><strong>{{ ucfirst(str_replace('_', ' ', $enrolment->status)) }}</strong></div></div>
    <div class="admin-stat"><span class="admin-stat-icon"><i class="fas fa-certificate"></i></span><div><small>Certificate</small><strong>{{ $certificate ? 'Issued' : 'Not issued' }}</strong></div></div>
</div>

<div class="appraisal-admin-kra-tabs" data-progress-tabs>
    <button type="button" class="appraisal-admin-kra-tab active" data-progress-tab="lessons"><i class="fas fa-book-open"></i> Lessons <small>{{ $lessons->count() }}</small></button>
    <button type="button" class="appraisal-admin-kra-tab" data-progress-tab="assessments"><i class="fas fa-clipboard-check"></i> Assignments, Quizzes &amp; Exams <small>{{ $attempts->count() }}</small></button>
    <button type="button" class="appraisal-admin-kra-tab" data-progress-tab="attendance"><i class="fas fa-user-check"></i> Attendance <small>{{ $attendance->count() }}</small></button>
</div>

<div class="appraisal-admin-kra-panel active" data-progress-panel="lessons">
<section class="admin-panel">
    <div class="admin-panel-head"><div><h2>Lessons</h2><p>Lesson-by-lesson activity and completion.</p></div></div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Module</th><th>Lesson</th><th>Opened</th><th>Completed</th><th>Time Spent</th><th>Last Activity</th></tr></thead>
            <tbody>
            @forelse($lessons as $lesson)
                <tr>
                    <td>{{ $lesson->module_title }}</td>
                    <td>{{ $lesson->title }}</td>
                    <td>{{ $lesson->first_opened_at ? \Illuminate\Support\Carbon::parse($lesson->first_opened_at)->format('d M Y H:i') : '—' }}</td>
                    <td>{{ $lesson->completed_at ? \Illuminate\Support\Carbon::parse($lesson->completed_at)->format('d M Y H:i') : 'Incomplete' }}</td>
                    <td>{{ gmdate('H:i:s', (int)($lesson->time_spent_seconds ?? 0)) }}</td>
                    <td>{{ $lesson->last_opened_at ? \Illuminate\Support\Carbon::parse($lesson->last_opened_at)->format('d M Y H:i') : '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="6">No lesson activity recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
</div>

<div class="appraisal-admin-kra-panel" data-progress-panel="assessments">
<section class="admin-panel">
    <div class="admin-panel-head"><div><h2>Assignments, Quizzes & Exams</h2><p>Submission and grading history.</p></div></div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Assessment</th><th>Type</th><th>Attempt</th><th>Status</th><th>Score</th><th>Submitted</th><th>Feedback</th></tr></thead>
            <tbody>
            @forelse($attempts as $attempt)
                <tr>
                    <td>{{ $attempt->assessment?->title }}</td>
                    <td>{{ ucfirst($attempt->assessment?->type ?? '') }}</td>
                    <td>{{ $attempt->attempt_number }}</td>
                    <td>{{ ucfirst($attempt->status) }}</td>
                    <td>{{ $attempt->percentage !== null ? number_format((float)$attempt->percentage, 1).'%' : '—' }}</td>
                    <td>{{ $attempt->submitted_at?->format('d M Y H:i') ?? '—' }}</td>
                    <td>{{ $attempt->instructor_feedback ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No assessment attempts recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
</div>

<div class="appraisal-admin-kra-panel" data-progress-panel="attendance">
<section class="admin-panel">
    <div class="admin-panel-head"><div><h2>Attendance</h2><p>Course attendance records.</p></div></div>
    <div class="table-responsive">
        <table class="admin-table">
            <thead><tr><th>Session</th><th>Date</th><th>Venue</th><th>Status</th><th>Remarks</th></tr></thead>
            <tbody>
            @forelse($attendance as $record)
                <tr>
                    <td>{{ $record->title }}</td>
                    <td>{{ \Illuminate\Support\Carbon::parse($record->session_date)->format('d M Y') }}</td>
                    <td>{{ $record->venue ?: '—' }}</td>
                    <td>{{ $record->status ? ucfirst($record->status) : 'Not recorded' }}</td>
                    <td>{{ $record->remarks ?: '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No attendance sessions recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.querySelector('[data-progress-tabs]');
    if (!root) return;
    const buttons = [...root.querySelectorAll('[data-progress-tab]')];
    const panels = [...document.querySelectorAll('[data-progress-panel]')];
    buttons.forEach((button) => button.addEventListener('click', () => {
        buttons.forEach((b) => b.classList.toggle('active', b === button));
        panels.forEach((p) => p.classList.toggle('active', p.dataset.progressPanel === button.dataset.progressTab));
    }));
});
</script>
@endsection
