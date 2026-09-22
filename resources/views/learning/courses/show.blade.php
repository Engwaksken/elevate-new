@extends('layouts.app')
@section('content')
<div class="card">
<h1>{{ $course->title }}</h1>
<p>{{ $course->description }}</p>
@if(!$enrolment && $course->self_enrolment_enabled)
<form method="POST" action="{{ route('learning.enrol',$course) }}">@csrf<button>Enrol Now</button></form>
@elseif($enrolment)
<p><strong>Progress:</strong> {{ $enrolment->progress_percent }}%</p>
@endif
</div>

@foreach($course->modules as $module)
<div class="card">
<h2>{{ $module->title }}</h2>
@foreach($module->lessons as $lesson)
<div style="padding:10px 0;border-top:1px solid #eee">
@if($enrolment)
<a href="{{ route('learning.lesson.show',$lesson) }}">{{ $lesson->title }}</a>
@else
{{ $lesson->title }}
@endif
</div>
@endforeach
</div>
@endforeach

@if($enrolment && $course->assessments->count())
<div class="card"><h2>Assessments</h2>
@foreach($course->assessments as $assessment)
<p><a href="{{ route('learning.assessment.show',$assessment) }}">{{ $assessment->title }}</a></p>
@endforeach
</div>
@endif
@endsection
