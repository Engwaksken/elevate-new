@extends('layouts.app')
@section('title',$course->title.' | ElevateHer360')
@section('content')
<div class="page-header">
<div><span class="eh-kicker">Learning</span><h1>{{ $course->title }}</h1><p>{{ $course->summary ?: $course->description }}</p>@if($course->branches->isNotEmpty())<p class="eh-course-branches"><i class="fas fa-location-dot" aria-hidden="true"></i> {{ $course->branches->pluck('name')->join(', ') }}</p>@endif</div>
<div class="page-actions"><a class="btn btn-outline" href="{{ route('learning.my-courses') }}">My Learning</a><a class="btn btn-outline" href="{{ route('calendar.index', ['event_type'=>'course_timetable']) }}">Calendar</a><a class="btn btn-outline" href="{{ route('certificates.mine') }}">My Certificates</a></div>
</div>

@if(!$enrolment && $course->self_enrolment_enabled)
<div class="eh-tab-section"><form method="POST" action="{{ route('learning.enrol',$course) }}">@csrf<button class="btn btn-primary">Enrol Now</button></form></div>
@elseif($enrolment)
<div class="eh-stats">
<div class="eh-stat"><span class="eh-stat-label">Participant ID</span><strong class="eh-stat-value eh-stat-value--code">{!! $enrolment->enrolment_code ? str_replace('/', '/<wbr>', e($enrolment->enrolment_code)) : '-' !!}</strong></div>
<div class="eh-stat"><span class="eh-stat-label">Course Progress</span><strong class="eh-stat-value">{{ number_format((float)$enrolment->progress_percent,0) }}%</strong></div>
<div class="eh-stat"><span class="eh-stat-label">Modules</span><strong class="eh-stat-value">{{ $course->modules->count() }}</strong></div>
</div>
@endif

@if($canViewTimetable)
<section class="eh-tab-section" aria-labelledby="course-timetable-title">
    <h2 id="course-timetable-title"><i class="fas fa-calendar-days"></i> Course Timetable</h2>
    <p>All session times are shown in their listed timezone.</p>
    <div class="admin-panel"><div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Session</th><th>Date</th><th>Time</th><th>Venue</th><th>Status</th><th class="table-actions">Actions</th></tr></thead>
        <tbody>
        @forelse($timetable as $session)
            @php($sessionState = $session['status'] === 'cancelled' ? 'Cancelled' : ($session['is_past'] ? 'Past session' : 'Scheduled'))
            <tr>
                <td><strong>{{ $session['title'] }}</strong>@if($session['notes'])<small class="admin-cell-hint" style="white-space:pre-wrap">{{ $session['notes'] }}</small>@endif</td>
                <td>{{ $session['date_label'] }}</td>
                <td>{{ $session['time_label'] }}<small class="admin-cell-hint">{{ $session['timezone'] }}</small></td>
                <td>{{ $session['venue'] ?: '-' }}</td>
                <td><span class="status-chip {{ $session['status'] === 'cancelled' ? 'inactive' : ($session['is_past'] ? 'completed' : 'active') }}">{{ $sessionState }}</span></td>
                <td class="table-actions">
                    @if($session['meeting_link'] && $session['status'] === 'scheduled')
                        <a class="btn btn-outline btn-sm" href="{{ $session['meeting_link'] }}" target="_blank" rel="noopener noreferrer"><i class="fas fa-video"></i> Online meeting</a>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="admin-empty">Your instructor has not added any time slots yet.</div></td></tr>
        @endforelse
        </tbody>
    </table>
    </div></div>
</section>
@endif

<div class="learning-module-grid">
@foreach($course->modules as $module)
@php($state=$moduleAccess->get($module->id,['accessible'=>!$enrolment,'complete'=>false]))
<section class="learning-module-card {{ $enrolment && !$state['accessible'] ? 'locked':'' }}">
<div class="learning-module-head">
<div><span class="module-number">{{ $loop->iteration }}</span><div><h2>{{ $module->title }}</h2><small>{{ $module->lessons->count() }} lesson(s)</small></div></div>
@if($enrolment)
@if($state['complete'])<span class="status-chip active"><i class="fas fa-check"></i> Completed</span>
@elseif($state['accessible'])<span class="status-chip published"><i class="fas fa-lock-open"></i> Open</span>
@else<span class="status-chip inactive"><i class="fas fa-lock"></i> Locked</span>@endif
@endif
</div>

@if($module->description)<p>{{ $module->description }}</p>@endif

<div class="learning-lesson-grid">
@foreach($module->lessons as $lesson)
@if($enrolment && $state['accessible'])
<a href="{{ route('learning.lesson.show',$lesson) }}" class="learning-lesson-card"><i class="fas fa-file-lines"></i><span>{{ $lesson->title }}</span></a>
@else
<div class="learning-lesson-card disabled"><i class="fas fa-lock"></i><span>{{ $lesson->title }}</span></div>
@endif
@endforeach
</div>

@if($enrolment && !$state['accessible'])
<div class="learning-lock-note"><i class="fas fa-circle-info"></i> Finish the previous module and/or wait for your instructor to release this module for your cohort.</div>
@endif
</section>
@endforeach
</div>

@if($enrolment && $course->assessments->count())
<div class="eh-tab-section"><h2>Assessments</h2>
<div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Assessment</th><th>Type</th><th>Attempts</th><th>Best score</th><th>Status</th><th class="table-actions">Actions</th></tr></thead><tbody>
@foreach($course->assessments as $assessment)
@php($tried = ($assessmentAttempts ?? collect())->get($assessment->id))
@php($used = (int) ($tried->used ?? 0))
@php($max = (int) $assessment->max_attempts)
@php($exhausted = $max > 0 && $used >= $max)
@php($best = $tried && $tried->best !== null ? rtrim(rtrim(number_format((float) $tried->best, 1), '0'), '.').'%' : null)
<tr @if($exhausted) aria-label="{{ $assessment->title }}, completed" @endif>
    <td><strong>{{ $assessment->title }}</strong></td>
    <td>{{ ucfirst($assessment->type) }}</td>
    @if($exhausted)
    {{-- Every attempt is used, so opening it would be refused: show the result instead of a link. --}}
    <td>All {{ $max }} {{ \Illuminate\Support\Str::plural('attempt', $max) }} used</td>
    <td>{{ $best ? 'Best score '.$best : '-' }}</td>
    <td><span class="status-chip completed">Completed</span></td>
    <td class="table-actions"></td>
    @else
    <td>@if($max > 0){{ $max - $used }} of {{ $max }} {{ \Illuminate\Support\Str::plural('attempt', $max) }} left @else Unlimited @endif</td>
    <td>{{ $best ? 'Best score '.$best : '-' }}</td>
    <td><span class="status-chip {{ $used > 0 ? 'pending' : 'active' }}">{{ $used > 0 ? 'In progress' : 'Open' }}</span></td>
    <td class="table-actions"><a class="btn btn-outline btn-sm" href="{{ route('learning.assessment.show',$assessment) }}"><i class="fas fa-arrow-right"></i> Open</a></td>
    @endif
</tr>
@endforeach
</tbody></table></div></div></div>
@endif
@endsection
