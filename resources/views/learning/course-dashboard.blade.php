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

<style>
.participant-course-tabs{margin:18px 0 24px}.participant-course-tabs .eh-tab-nav{display:flex;gap:8px;flex-wrap:wrap;border-bottom:1px solid #eaecf0}.participant-course-tabs .eh-tab-button{border:0;background:transparent;padding:12px 16px;font:inherit;font-weight:700;color:#667085;cursor:pointer;border-bottom:3px solid transparent}.participant-course-tabs .eh-tab-button.active{color:#800000;border-color:#800000}.participant-course-list{display:grid;gap:10px}.participant-module,.participant-lesson-group{border:1px solid #e7e4e4;border-radius:10px;background:#fff}.participant-module>summary,.participant-lesson-group>summary{display:flex;align-items:center;gap:10px;cursor:pointer;list-style:none;padding:14px 16px;font-weight:700}.participant-module>summary::-webkit-details-marker,.participant-lesson-group>summary::-webkit-details-marker{display:none}.participant-module>summary:after,.participant-lesson-group>summary:after{content:'+';margin-left:auto;font-size:1.2rem;color:#800000}.participant-module[open]>summary:after,.participant-lesson-group[open]>summary:after{content:'−'}.participant-module-content{padding:0 16px 14px}.participant-module-content>p{margin:0 0 10px;color:#667085}.participant-lesson-list{list-style:none;padding:0;margin:0;border-top:1px solid #eaecf0}.participant-lesson-list li{display:flex;gap:10px;align-items:center;padding:11px 8px;border-bottom:1px solid #f0eeee}.participant-lesson-list li:last-child{border-bottom:0}.participant-lesson-list li>i{color:#800000}.participant-lesson-list a{font-weight:700;color:#344054;text-decoration:none}.participant-lesson-list a:hover{text-decoration:underline;color:#800000}.participant-lesson-list .is-locked{color:#98a2b3}.participant-lesson-group{margin-top:8px}.participant-lesson-group>summary{padding:10px 12px;font-size:.92rem}
</style>
<div class="eh-tabs participant-course-tabs" data-eh-tabs>
<div class="eh-tab-nav" role="tablist" aria-label="Course information">
    @if($canViewTimetable)<button class="eh-tab-button active" type="button" role="tab" aria-selected="true" data-eh-tab="course-timetable"><i class="fas fa-calendar-days"></i> Course Timetable</button>@endif
    @if($enrolment && $course->assessments->count())<button class="eh-tab-button {{ $canViewTimetable ? '' : 'active' }}" type="button" role="tab" aria-selected="{{ $canViewTimetable ? 'false' : 'true' }}" data-eh-tab="course-assessments"><i class="fas fa-clipboard-check"></i> Assessments</button>@endif
</div>
<div class="eh-tab-content">
@if($canViewTimetable)
<section class="eh-tab-pane active" role="tabpanel" data-eh-pane="course-timetable" aria-labelledby="course-timetable-title">
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

@if($enrolment && $course->assessments->count())
<section class="eh-tab-pane {{ $canViewTimetable ? '' : 'active' }}" role="tabpanel" data-eh-pane="course-assessments" aria-labelledby="course-assessments-title">
<div class="eh-tab-section"><h2 id="course-assessments-title"><i class="fas fa-clipboard-check"></i> Assessments</h2>
<div class="admin-panel"><div class="admin-table-wrap"><table class="admin-table">
<thead><tr><th>Assessment</th><th>Type</th><th>Attempts</th><th>Best score</th><th>Status</th><th class="table-actions">Actions</th></tr></thead><tbody>
@foreach($course->assessments as $assessment)
@php($tried = ($assessmentAttempts ?? collect())->get($assessment->id))
@php($used = (int) ($tried->used ?? 0))
@php($max = (int) $assessment->max_attempts)
@php($exhausted = $max > 0 && $used >= $max)
@php($best = $tried && $tried->best !== null ? rtrim(rtrim(number_format((float) $tried->best, 1), '0'), '.').'%' : null)
<tr>
    <td><strong>{{ $assessment->title }}</strong></td><td>{{ ucfirst($assessment->type) }}</td>
    @if($exhausted)<td>All {{ $max }} {{ \Illuminate\Support\Str::plural('attempt', $max) }} used</td><td>{{ $best ? 'Best score '.$best : '-' }}</td><td><span class="status-chip completed">Completed</span></td><td class="table-actions"></td>
    @else<td>@if($max > 0){{ $max - $used }} of {{ $max }} {{ \Illuminate\Support\Str::plural('attempt', $max) }} left @else Unlimited @endif</td><td>{{ $best ? 'Best score '.$best : '-' }}</td><td><span class="status-chip {{ $used > 0 ? 'pending' : 'active' }}">{{ $used > 0 ? 'In progress' : 'Open' }}</span></td><td class="table-actions"><a class="btn btn-outline btn-sm" href="{{ route('learning.assessment.show',$assessment) }}"><i class="fas fa-arrow-right"></i> Open</a></td>@endif
</tr>
@endforeach
</tbody></table></div></div></div>
</section>
@endif
</div></div>

<section class="eh-tab-section" aria-labelledby="course-modules-title">
<h2 id="course-modules-title"><i class="fas fa-layer-group"></i> Course modules and lessons</h2>
<div class="participant-course-list">
@foreach($course->modules as $module)
@php($state=$moduleAccess->get($module->id,['accessible'=>!$enrolment,'complete'=>false]))
<details class="participant-module {{ $enrolment && !$state['accessible'] ? 'locked':'' }}">
<summary><span class="module-number">{{ $loop->iteration }}</span><span>{{ $module->title }} <small>· {{ $module->lessons->count() }} lesson(s)</small></span>
@if($enrolment)<span class="status-chip {{ $state['complete'] ? 'active' : ($state['accessible'] ? 'published' : 'inactive') }}">{{ $state['complete'] ? 'Completed' : ($state['accessible'] ? 'Open' : 'Locked') }}</span>@endif</summary>
<div class="participant-module-content">
@if($module->description)<p>{{ $module->description }}</p>@endif
<details class="participant-lesson-group"><summary>Lessons <small>({{ $module->lessons->count() }})</small></summary><ul class="participant-lesson-list">
@foreach($module->lessons as $lesson)
<li>@if($enrolment && $state['accessible'])<i class="fas fa-file-lines"></i><a href="{{ route('learning.lesson.show',$lesson) }}">{{ $lesson->title }}</a>@else<i class="fas fa-lock"></i><span class="is-locked">{{ $lesson->title }}</span>@endif</li>
@endforeach
</ul></details>

@if($enrolment && !$state['accessible'])
<div class="learning-lock-note"><i class="fas fa-circle-info"></i> Finish the previous module and/or wait for your instructor to release this module for your cohort.</div>
@endif
</div></details>
@endforeach
</div></section>
@endsection
