@extends('layouts.app')
@section('title',$course->title.' | ElevateHer360')
@section('content')
<div class="page-header">
<div><span class="eh-kicker">Learning</span><h1>{{ $course->title }}</h1><p>{{ $course->summary ?: $course->description }}</p></div>
</div>

@if(!$enrolment && $course->self_enrolment_enabled)
<div class="eh-tab-section"><form method="POST" action="{{ route('learning.enrol',$course) }}">@csrf<button class="btn btn-primary">Enrol Now</button></form></div>
@elseif($enrolment)
<div class="eh-stats">
<div class="eh-stat"><span class="eh-stat-label">Course Progress</span><strong class="eh-stat-value">{{ number_format((float)$enrolment->progress_percent,0) }}%</strong></div>
<div class="eh-stat"><span class="eh-stat-label">Modules</span><strong class="eh-stat-value">{{ $course->modules->count() }}</strong></div>
</div>
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
<div class="eh-tab-section"><h2>Assessments</h2><div class="eh-data-list">@foreach($course->assessments as $assessment)<a class="eh-data-row" href="{{ route('learning.assessment.show',$assessment) }}"><div class="eh-data-row-main"><span class="eh-data-row-icon"><i class="fas fa-clipboard-question"></i></span><div class="eh-data-row-copy"><strong>{{ $assessment->title }}</strong><span>{{ ucfirst($assessment->type) }}</span></div></div><i class="fas fa-chevron-right"></i></a>@endforeach</div></div>
@endif
@endsection
