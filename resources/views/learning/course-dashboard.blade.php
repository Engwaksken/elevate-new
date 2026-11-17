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
            @php $sessionState = $session['status'] === 'cancelled' ? 'Cancelled' : ($session['is_past'] ? 'Past session' : 'Scheduled'); @endphp
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
@php
    // One row per assessment with everything the card needs, then split into
    // "To do" (still open to the participant) and "Completed" (all attempts used).
    $assessmentAttempts = $assessmentAttempts ?? collect();
    $assessmentDue = $assessmentDue ?? collect();
    $assessmentCards = $course->assessments->map(function ($assessment) use ($assessmentAttempts, $assessmentDue) {
        $tried = $assessmentAttempts->get($assessment->id);
        $used = (int) ($tried->used ?? 0);
        $max = (int) $assessment->max_attempts;
        $exhausted = $max > 0 && $used >= $max;
        $dueInfo = $assessmentDue->get($assessment->id);
        $due = $dueInfo['due'] ?? null;
        $ext = $dueInfo['extension'] ?? null;
        $overdue = ! $exhausted && $due && $due->isPast();

        return (object) [
            'assessment' => $assessment,
            'used' => $used,
            'max' => $max,
            'exhausted' => $exhausted,
            'best' => $tried && $tried->best !== null ? rtrim(rtrim(number_format((float) $tried->best, 1), '0'), '.').'%' : null,
            'due' => $due,
            'ext' => $ext,
            'overdue' => $overdue,
            'state' => $exhausted ? 'done' : ($overdue ? 'overdue' : ($used > 0 ? 'progress' : 'open')),
        ];
    });
    // Overdue first, then soonest due, then undated.
    $todoCards = $assessmentCards->where('exhausted', false)
        ->sortBy(fn ($c) => [$c->overdue ? 0 : 1, $c->due?->timestamp ?? PHP_INT_MAX])->values();
    $doneCards = $assessmentCards->where('exhausted', true)->values();
    $stateLabels = ['open' => ['Open', 'active'], 'progress' => ['In progress', 'pending'], 'overdue' => ['Overdue', 'inactive'], 'done' => ['Completed', 'completed']];
@endphp
<section class="eh-tab-pane {{ $canViewTimetable ? '' : 'active' }}" role="tabpanel" data-eh-pane="course-assessments" aria-labelledby="course-assessments-title">
<div class="eh-tab-section"><h2 id="course-assessments-title"><i class="fas fa-clipboard-check"></i> Assessments</h2>

@foreach([['todo', 'To do', 'fa-list-check', $todoCards, true], ['done', 'Completed', 'fa-circle-check', $doneCards, $todoCards->isEmpty()]] as [$groupKey, $groupLabel, $groupIcon, $cards, $openByDefault])
    @continue($cards->isEmpty())
    <details class="ag-section" @if($openByDefault) open @endif>
        <summary>
            <h3><i class="fas {{ $groupIcon }}"></i> {{ $groupLabel }}</h3>
            <span class="ag-count">{{ $cards->count() }}</span>
            @if($groupKey === 'todo' && ($overdueCount = $cards->where('overdue', true)->count()))
                <span class="status-chip inactive">{{ $overdueCount }} overdue</span>
            @endif
        </summary>
        <div class="ag-section-body">
            <div class="ag-grid">
            @foreach($cards as $card)
                @php
                    $assessment = $card->assessment;
                    [$stateLabel, $stateChip] = $stateLabels[$card->state];
                @endphp
                <article class="ag-card is-{{ $card->state }}">
                    <div class="ag-chips">
                        <span class="status-chip">{{ ucfirst($assessment->type) }}</span>
                        <span class="status-chip {{ $stateChip }}">{{ $stateLabel }}</span>
                    </div>
                    <h3 title="{{ $assessment->title }}">{{ $assessment->title }}</h3>
                    <div class="ag-meta">
                        <span><i class="fas fa-calendar-day"></i>@if($card->due)Due <strong>{{ $card->due->format('d M Y H:i') }}</strong>@else No due date @endif</span>
                        @if(! $card->exhausted && $card->ext && $card->ext->isPending())
                            <span><i class="fas fa-hourglass-half"></i>Extension requested</span>
                        @elseif(! $card->exhausted && $card->ext && $card->ext->status === 'approved')
                            <span><i class="fas fa-calendar-plus"></i>Extended</span>
                        @endif
                        <span><i class="fas fa-rotate"></i>
                            @if($card->exhausted)All {{ $card->max }} {{ \Illuminate\Support\Str::plural('attempt', $card->max) }} used
                            @elseif($card->max > 0){{ $card->max - $card->used }} of {{ $card->max }} {{ \Illuminate\Support\Str::plural('attempt', $card->max) }} left
                            @else Unlimited attempts @endif
                        </span>
                        @if($card->best)<span><i class="fas fa-star"></i>Best score {{ $card->best }}</span>@endif
                    </div>
                    @unless($card->exhausted)
                    <div class="ag-card-actions">
                        <a class="btn btn-outline btn-sm" href="{{ route('learning.assessment.show',$assessment) }}">
                            <i class="fas {{ $card->overdue ? 'fa-clock' : 'fa-arrow-right' }}"></i>
                            {{ $card->overdue ? ($card->ext && $card->ext->isPending() ? 'View request' : 'Request extension') : 'Open' }}
                        </a>
                    </div>
                    @endunless
                </article>
            @endforeach
            </div>
        </div>
    </details>
@endforeach
</div>
</section>
@endif
</div></div>

<section class="eh-tab-section" aria-labelledby="course-modules-title">
<h2 id="course-modules-title"><i class="fas fa-layer-group"></i> Course modules and lessons</h2>
<div class="participant-course-list">
@foreach($course->modules as $module)
@php $state=$moduleAccess->get($module->id,['accessible'=>!$enrolment,'complete'=>false]); @endphp
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
