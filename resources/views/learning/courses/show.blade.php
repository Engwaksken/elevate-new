@extends('layouts.app')
@section('title', $course->title.' | ElevateHer360')
@section('meta_description', $course->briefDescription())
@section('content')
<article class="card" style="max-width:900px;margin:24px auto">
<img src="{{ $course->thumbnail_url }}" alt="{{ $course->title }}" style="width:100%;max-height:420px;object-fit:cover;border-radius:12px" loading="lazy">
<h1>{{ $course->title }}</h1><p>{{ $course->briefDescription() }}</p>
</article>
<div class="page-actions"><a class="btn btn-outline" href="{{ route('learning.index') }}">Browse Courses</a>
@if($enrolled)<a class="btn btn-primary" href="{{ route('learning.course.dashboard', $course) }}">Open in My Learning</a>
@elseif(auth()->check() && auth()->user()->isParticipant() && auth()->user()->isActive() && $course->self_enrolment_enabled)
@if(($entryAssessmentPending ?? false) && ($entryAssessment ?? null))
<a class="btn btn-primary" href="{{ route('learning.assessment.show', $entryAssessment) }}"><i class="fas fa-clipboard-check"></i> Take the entry assessment</a>
@else
<form method="POST" action="{{ route('learning.enrol', $course) }}">@csrf<button class="btn btn-primary">Enrol Now</button></form>
@endif
@elseif(auth()->check() && auth()->user()->isStaff())<a class="btn btn-primary" href="{{ route('admin.workspace.learning') }}">Open Learning workspace</a>
@else<a class="btn btn-primary" href="{{ route('login') }}">Sign in to learn</a>@endif
</div>
@if(auth()->check() && auth()->user()->isParticipant() && ($course->self_enrolment_enabled) && ($entryAssessmentPending ?? false) && ($entryAssessment ?? null))
<p class="form-hint" style="max-width:900px;margin:8px auto;color:#667085"><i class="fas fa-circle-info"></i> You will be enrolled in this course once you pass the entry assessment ({{ rtrim(rtrim(number_format((float) $course->pass_mark, 2), '0'), '.') }}% pass mark): <strong>{{ $entryAssessment->title }}</strong>.</p>
@endif
@endsection
