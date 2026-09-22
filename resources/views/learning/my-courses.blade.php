@extends('layouts.app')
@section('content')
<div class="card"><h1>My Courses</h1></div>
<div class="grid">
@foreach($enrolments as $enrolment)
<div class="card">
<h3>{{ $enrolment->course->title }}</h3>
<p>Status: {{ $enrolment->status }}</p>
<p>Progress: {{ $enrolment->progress_percent }}%</p>
<a class="btn" href="{{ route('learning.course.show',$enrolment->course) }}">Continue</a>
</div>
@endforeach
</div>
{{ $enrolments->links() }}
@endsection
