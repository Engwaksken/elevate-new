@extends('layouts.app')
@section('content')
<div class="card"><h1>Instructor Dashboard</h1></div>
<div class="grid">
@foreach($courses as $course)
<div class="card">
<h3>{{ $course->title }}</h3>
<p>{{ $course->enrolments_count }} learners</p>
<a class="btn" href="{{ route('instructor.attendance.create',$course) }}">Record Attendance</a>
</div>
@endforeach
</div>
@endsection
